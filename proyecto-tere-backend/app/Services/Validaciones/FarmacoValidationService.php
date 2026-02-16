<?php
// app/Services/Validaciones/FarmacoValidationService.php

namespace App\Services\Validaciones;

use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoFarmaco;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FarmacoValidationService
{
    /**
     * Validar si una mascota puede recibir un tipo de fármaco específico
     */
    public function validarMascotaParaTipoFarmaco(Mascota $mascota, TipoFarmaco $tipoFarmaco): array
    {
        $errors = [];

        // 1. Validar especie
        if (!$this->validarEspecie($mascota, $tipoFarmaco)) {
            $especiesTexto = $tipoFarmaco->especies_texto;
            $errors[] = "Este fármaco está indicado para: {$especiesTexto}. La mascota es {$mascota->especie}.";
        }

        // 2. Validar edad mínima si existe restricción
        $edadValidation = $this->validarRestriccionesEdad($mascota, $tipoFarmaco);
        if (!$edadValidation['valido']) {
            $errors[] = $edadValidation['mensaje'];
        }

        // 3. Validar contraindicaciones específicas
        $contraindicacionesErrors = $this->validarContraindicaciones($mascota, $tipoFarmaco);
        $errors = array_merge($errors, $contraindicacionesErrors);

        // 4. Validar interacciones con medicamentos existentes
        $interaccionesErrors = $this->validarInteracciones($mascota, $tipoFarmaco);
        $errors = array_merge($errors, $interaccionesErrors);

        return [
            'valido' => empty($errors),
            'errors' => $errors,
            'detalles' => [
                'mascota_id' => $mascota->id,
                'tipo_farmaco_id' => $tipoFarmaco->id,
                'especie_valida' => $this->validarEspecie($mascota, $tipoFarmaco),
                'edad_valida' => $edadValidation['valido'],
                'edad_actual_meses' => $this->calcularEdadEnMeses($mascota),
                'nombre_farmaco' => $tipoFarmaco->nombre_comercial,
                'especies_permitidas' => $tipoFarmaco->especies,
                'contraindicaciones' => $tipoFarmaco->contraindicaciones
            ]
        ];
    }

    /**
     * Validar especie de la mascota vs especies permitidas para el fármaco
     */
    private function validarEspecie(Mascota $mascota, TipoFarmaco $tipoFarmaco): bool
    {
        $especiesPermitidas = $tipoFarmaco->especies;
        
        // Si no hay restricciones de especie, permitir todas
        if (empty($especiesPermitidas)) {
            return true;
        }

        // Si es string, convertir a array (para compatibilidad)
        if (is_string($especiesPermitidas)) {
            $especiesPermitidas = json_decode($especiesPermitidas, true) ?? [$especiesPermitidas];
        }

        // Verificar si la especie de la mascota está permitida
        return in_array(strtolower($mascota->especie), 
                       array_map('strtolower', (array)$especiesPermitidas));
    }

    /**
     * Validar restricciones de edad desde el campo 'contraindicaciones'
     */
    private function validarRestriccionesEdad(Mascota $mascota, TipoFarmaco $tipoFarmaco): array
    {
        // Buscar patrones de edad en contraindicaciones
        $contraindicaciones = strtolower($tipoFarmaco->contraindicaciones ?? '');
        
        if (empty($contraindicaciones)) {
            return ['valido' => true, 'mensaje' => ''];
        }

        $edadMeses = $this->calcularEdadEnMeses($mascota);
        
        if ($edadMeses === null) {
            return [
                'valido' => false,
                'mensaje' => 'No se puede determinar la edad de la mascota para validar el fármaco.'
            ];
        }

        // Patrones comunes para restricciones de edad
        $patrones = [
            'mayores?\s+de?\s+(\d+)\s+(meses?|años?)' => 'mayor',
            'menores?\s+de?\s+(\d+)\s+(meses?|años?)' => 'menor',
            '(\d+)\s+años?\s+y\s+mayores?' => 'mayor_igual',
            '(\d+)\s+meses?\s+y\s+mayores?' => 'mayor_igual_meses'
        ];

        foreach ($patrones as $patron => $tipo) {
            if (preg_match("/{$patron}/", $contraindicaciones, $matches)) {
                $valor = (float)$matches[1];
                $unidad = $matches[2] ?? 'meses';
                
                $edadLimiteMeses = $this->convertirEdadAMeses($valor, $unidad);
                
                switch ($tipo) {
                    case 'mayor':
                        if ($edadMeses <= $edadLimiteMeses) {
                            $edadActual = $this->formatearEdadHumana($edadMeses);
                            $edadLimite = $this->formatearEdadHumana($edadLimiteMeses);
                            return [
                                'valido' => false,
                                'mensaje' => "Este fármaco está contraindicado en animales menores de {$edadLimite}. La mascota tiene {$edadActual}."
                            ];
                        }
                        break;
                        
                    case 'menor':
                        if ($edadMeses >= $edadLimiteMeses) {
                            $edadActual = $this->formatearEdadHumana($edadMeses);
                            $edadLimite = $this->formatearEdadHumana($edadLimiteMeses);
                            return [
                                'valido' => false,
                                'mensaje' => "Este fármaco está contraindicado en animales mayores de {$edadLimite}. La mascota tiene {$edadActual}."
                            ];
                        }
                        break;
                }
            }
        }

        // Buscar restricciones específicas por especie y edad
        $especie = strtolower($mascota->especie);
        if (preg_match("/{$especie}s?\s+menores?\s+de?\s+(\d+)\s+(meses?|años?)/", $contraindicaciones, $matches)) {
            $valor = (float)$matches[1];
            $unidad = $matches[2];
            $edadLimiteMeses = $this->convertirEdadAMeses($valor, $unidad);
            
            if ($edadMeses < $edadLimiteMeses) {
                $edadActual = $this->formatearEdadHumana($edadMeses);
                $edadLimite = $this->formatearEdadHumana($edadLimiteMeses);
                return [
                    'valido' => false,
                    'mensaje' => "Para {$mascota->especie}s, este fármaco está contraindicado en menores de {$edadLimite}. La mascota tiene {$edadActual}."
                ];
            }
        }

        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Validar otras contraindicaciones específicas
     */
    private function validarContraindicaciones(Mascota $mascota, TipoFarmaco $tipoFarmaco): array
    {
        $errors = [];
        $contraindicaciones = strtolower($tipoFarmaco->contraindicaciones ?? '');
        
        if (empty($contraindicaciones)) {
            return $errors;
        }

        // Validar contraindicaciones por estado de salud
        if ($mascota->caracteristicas) {
            // Ejemplo: si el fármaco está contraindicado en enfermos renales
            if (str_contains($contraindicaciones, 'renal') && 
                str_contains($contraindicaciones, 'enfermedad') &&
                $mascota->caracteristicas->enfermedad_renal) {
                $errors[] = "Este fármaco está contraindicado en mascotas con enfermedad renal.";
            }

            // Ejemplo: contraindicado en animales gestantes
            if (str_contains($contraindicaciones, 'gestante') && 
                $mascota->caracteristicas->gestante) {
                $errors[] = "Este fármaco está contraindicado en animales gestantes.";
            }

            // Ejemplo: contraindicado en lactancia
            if (str_contains($contraindicaciones, 'lactancia') && 
                $mascota->caracteristicas->lactando) {
                $errors[] = "Este fármaco está contraindicado durante la lactancia.";
            }
        }

        // Validar contraindicaciones por condición específica
        if (str_contains($contraindicaciones, 'no castrado') && !$mascota->castrado) {
            $errors[] = "Este fármaco está contraindicado en animales no castrados.";
        }

        return $errors;
    }

    /**
     * Validar interacciones con medicamentos actuales de la mascota
     */
    private function validarInteracciones(Mascota $mascota, TipoFarmaco $tipoFarmaco): array
    {
        $errors = [];
        $interacciones = strtolower($tipoFarmaco->interacciones_medicamentosas ?? '');
        
        if (empty($interacciones)) {
            return $errors;
        }

        // Aquí podrías obtener los fármacos actuales de la mascota
        // y verificar si hay interacciones conocidas
        // Por ahora, solo validamos contraindicaciones generales
        
        return $errors;
    }

    /**
     * Calcular edad en meses
     */
    public function calcularEdadEnMeses(Mascota $mascota): ?float
    {
        try {
            if (!$mascota->fecha_nacimiento) {
                return null;
            }

            // Usar el método ya existente en el modelo Mascota
            return $mascota->edadRelacion?->edad_meses ?? 
                   $this->calcularEdadDesdeString($mascota->fecha_nacimiento);

        } catch (\Exception $e) {
            Log::error('Error calculando edad para validación de fármaco', [
                'mascota_id' => $mascota->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calcular edad desde string si no hay relación cargada
     */
    private function calcularEdadDesdeString(string $fechaNacimiento): ?float
    {
        try {
            // Parsear formato dd/mm/yyyy
            $partes = explode('/', $fechaNacimiento);
            if (count($partes) === 3) {
                $dia = (int)$partes[0];
                $mes = (int)$partes[1];
                $anio = (int)$partes[2];
                
                $nacimiento = Carbon::create($anio, $mes, $dia);
            } else {
                $nacimiento = Carbon::parse($fechaNacimiento);
            }

            if ($nacimiento->isFuture()) {
                return 0;
            }

            $diferencia = $nacimiento->diff(Carbon::now());
            $meses = ($diferencia->y * 12) + $diferencia->m;
            $meses += $diferencia->d / 30.44;
            
            return round($meses, 2);

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convertir edad a meses según unidad
     */
    private function convertirEdadAMeses(float $edad, string $unidad): float
    {
        $unidad = strtolower($unidad);
        
        switch ($unidad) {
            case 'dias':
            case 'día':
            case 'días':
                return $edad / 30.44;
            case 'semanas':
            case 'semana':
                return $edad / 4.345;
            case 'meses':
            case 'mes':
                return $edad;
            case 'años':
            case 'año':
            case 'anos':
            case 'ano':
                return $edad * 12;
            default:
                return $edad; // Asumir meses
        }
    }

    /**
     * Formatear edad para mensajes humanos
     */
    private function formatearEdadHumana(float $meses): string
    {
        if ($meses < 1) {
            $dias = round($meses * 30.44);
            return "{$dias} día" . ($dias !== 1 ? 's' : '');
        } elseif ($meses < 12) {
            $mesesEnteros = round($meses);
            return "{$mesesEnteros} mes" . ($mesesEnteros !== 1 ? 'es' : '');
        } else {
            $años = floor($meses / 12);
            $mesesRestantes = round($meses % 12);
            
            if ($mesesRestantes < 1) {
                return "{$años} año" . ($años !== 1 ? 's' : '');
            } else {
                return "{$años} año" . ($años !== 1 ? 's' : '') . 
                       " y {$mesesRestantes} mes" . ($mesesRestantes !== 1 ? 'es' : '');
            }
        }
    }

    /**
     * Método principal para validar antes del registro
     */
    public function validarAntesDeRegistro(int $mascotaId, int $tipoFarmacoId): array
    {
        try {
            $mascota = Mascota::with(['caracteristicas', 'edadRelacion'])->findOrFail($mascotaId);
            $tipoFarmaco = TipoFarmaco::findOrFail($tipoFarmacoId);

            return $this->validarMascotaParaTipoFarmaco($mascota, $tipoFarmaco);

        } catch (\Exception $e) {
            Log::error('Error en validación de fármaco', [
                'mascota_id' => $mascotaId,
                'tipo_farmaco_id' => $tipoFarmacoId,
                'error' => $e->getMessage()
            ]);

            return [
                'valido' => false,
                'errors' => ['Error en validación: ' . $e->getMessage()]
            ];
        }
    }
}