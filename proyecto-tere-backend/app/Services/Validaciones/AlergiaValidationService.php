<?php
// app/Services/Validaciones/AlergiaValidationService.php

namespace App\Services\Validaciones;

use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoAlergia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AlergiaValidationService
{
    /**
     * Validar si una mascota puede tener registrada una alergia específica
     */
    public function validarMascotaParaTipoAlergia(Mascota $mascota, TipoAlergia $tipoAlergia): array
    {
        $errors = [];

        // 1. Validar especie
        if (!$this->validarEspecie($mascota, $tipoAlergia)) {
            $errors[] = "Esta alergia no es aplicable para la especie '{$mascota->especie}'.";
        }

        // 2. Validar edad mínima (si existe restricción)
        $edadValidation = $this->validarRestriccionesEdad($mascota, $tipoAlergia);
        if (!$edadValidation['valido']) {
            $errors[] = $edadValidation['mensaje'];
        }

        // 3. Validar condiciones especiales desde observaciones_adicionales
        $condicionesErrors = $this->validarCondicionesEspeciales($mascota, $tipoAlergia);
        $errors = array_merge($errors, $condicionesErrors);

        // 4. Validar nivel de riesgo vs especie
        $riesgoErrors = $this->validarNivelRiesgo($mascota, $tipoAlergia);
        $errors = array_merge($errors, $riesgoErrors);

        // 5. Validar si ya existe alergia similar activa
        $duplicadoErrors = $this->validarDuplicados($mascota, $tipoAlergia);
        $errors = array_merge($errors, $duplicadoErrors);

        return [
            'valido' => empty($errors),
            'errors' => $errors,
            'detalles' => [
                'mascota_id' => $mascota->id,
                'tipo_alergia_id' => $tipoAlergia->id,
                'especie_valida' => $this->validarEspecie($mascota, $tipoAlergia),
                'edad_valida' => $edadValidation['valido'],
                'especie_mascota' => $mascota->especie,
                'especies_permitidas' => $tipoAlergia->especies,
                'nivel_riesgo' => $tipoAlergia->nivel_riesgo,
                'observaciones_adicionales' => $tipoAlergia->observaciones_adicionales
            ]
        ];
    }

    /**
     * Validar especie de la mascota vs especies permitidas para la alergia
     */
    private function validarEspecie(Mascota $mascota, TipoAlergia $tipoAlergia): bool
    {
        // Si el campo especies es null o vacío, acepta todas las especies
        if (empty($tipoAlergia->especies)) {
            return true;
        }

        $especiesPermitidas = $tipoAlergia->especies;
        
        // Si es string (por si acaso), convertir a array
        if (is_string($especiesPermitidas)) {
            $especiesPermitidas = json_decode($especiesPermitidas, true) ?? [$especiesPermitidas];
        }

        // Si no es array después de intentar convertir, asumir que acepta todas
        if (!is_array($especiesPermitidas)) {
            return true;
        }

        // Verificar si la especie de la mascota está en las permitidas
        // Convertir a minúsculas para comparación insensible
        $especieMascota = strtolower(trim($mascota->especie));
        $especiesPermitidasLower = array_map(function($especie) {
            return strtolower(trim($especie));
        }, $especiesPermitidas);

        return in_array($especieMascota, $especiesPermitidasLower);
    }

    /**
     * Validar restricciones de edad desde observaciones_adicionales
     */
    private function validarRestriccionesEdad(Mascota $mascota, TipoAlergia $tipoAlergia): array
    {
        // Buscar patrones de edad en observaciones_adicionales
        $observaciones = $tipoAlergia->observaciones_adicionales ?? '';
        
        if (empty($observaciones)) {
            return ['valido' => true, 'mensaje' => ''];
        }

        // Patrones comunes para restricciones de edad
        $patrones = [
            '/mayor(es)? a (\d+)\s*(mes|meses|año|años)/i',
            '/edad mínima.*?(\d+)\s*(mes|meses|año|años)/i',
            '/a partir de (\d+)\s*(mes|meses|año|años)/i',
            '/no aplicar.*?menor(es)? de (\d+)\s*(mes|meses|año|años)/i',
            '/(\d+)\s*(mes|meses|año|años).*?mínimo/i',
            '/mayores? de (\d+)\s*(mes|meses|año|años)/i'
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $observaciones, $matches)) {
                $edadMinima = (int) $matches[2];
                $unidad = strtolower($matches[3]);
                
                $edadActualMeses = $this->calcularEdadEnMeses($mascota);
                
                if ($edadActualMeses === null) {
                    return [
                        'valido' => false,
                        'mensaje' => 'No se puede determinar la edad de la mascota para validar restricciones.'
                    ];
                }

                $edadMinimaMeses = $this->convertirEdadAMeses($edadMinima, $unidad);
                
                if ($edadActualMeses < $edadMinimaMeses) {
                    $edadActualFormateada = $this->formatearMesesAHumano($edadActualMeses);
                    $edadMinimaFormateada = $this->formatearMesesAHumano($edadMinimaMeses);
                    
                    return [
                        'valido' => false,
                        'mensaje' => "La mascota tiene {$edadActualFormateada}, pero esta alergia requiere mínimo {$edadMinimaFormateada} (según observaciones)."
                    ];
                }
            }
        }

        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Validar condiciones especiales desde observaciones_adicionales
     */
    private function validarCondicionesEspeciales(Mascota $mascota, TipoAlergia $tipoAlergia): array
    {
        $errors = [];
        $observaciones = strtolower($tipoAlergia->observaciones_adicionales ?? '');
        
        if (empty($observaciones)) {
            return $errors;
        }

        // Validar especie específica en observaciones
        $especieMascota = strtolower($mascota->especie);
        
        // Patrones de exclusión por especie
        $exclusionPatterns = [
            "/no aplicar.*?{$especieMascota}/i",
            "/no.*?{$especieMascota}/i",
            "/excluir.*?{$especieMascota}/i",
            "/no.*?recomendado.*?para.*?{$especieMascota}/i"
        ];

        foreach ($exclusionPatterns as $pattern) {
            if (preg_match($pattern, $observaciones)) {
                $errors[] = "Esta alergia no se recomienda para la especie {$mascota->especie} (según observaciones).";
                break;
            }
        }

        // Validar castración
        if (str_contains($observaciones, 'castrad') && !$mascota->castrado) {
            $errors[] = "Esta alergia requiere que la mascota esté castrada (según observaciones).";
        }

        // Validar estado de salud
        if (str_contains($observaciones, 'inmunodeprimid') || 
            str_contains($observaciones, 'inmunocomprometid')) {
            // Aquí podrías agregar lógica para verificar historial médico
            // Por ahora solo muestra advertencia
            $errors[] = "Esta alergia requiere evaluación especial para mascotas con condiciones inmunológicas.";
        }

        // Validar embarazo/lactancia
        if (str_contains($observaciones, 'embarazad') || 
            str_contains($observaciones, 'lactanci') ||
            str_contains($observaciones, 'gestante')) {
            // Podrías agregar lógica para verificar si la hembra está en estas condiciones
            $errors[] = "Esta alergia requiere evaluación especial para hembras embarazadas o en lactancia.";
        }

        return $errors;
    }

    /**
     * Validar nivel de riesgo vs especie
     */
    private function validarNivelRiesgo(Mascota $mascota, TipoAlergia $tipoAlergia): array
    {
        $errors = [];
        
        // Definir niveles de riesgo críticos por especie
        $riesgosCriticosPorEspecie = [
            'grave' => [
                'especies_sensibles' => ['ave', 'ave exótica', 'conejo', 'hámster', 'cobaya'],
                'mensaje' => 'Nivel de riesgo GRAVE - Requiere evaluación veterinaria especial para esta especie.'
            ],
            'muy_grave' => [
                'especies_sensibles' => ['ave', 'ave exótica', 'conejo', 'hámster', 'cobaya', 'hurón'],
                'mensaje' => 'Nivel de riesgo MUY GRAVE - Evaluación veterinaria obligatoria antes de registrar.'
            ]
        ];

        $nivelRiesgo = strtolower($tipoAlergia->nivel_riesgo ?? '');
        $especieMascota = strtolower($mascota->especie);

        if (isset($riesgosCriticosPorEspecie[$nivelRiesgo])) {
            $config = $riesgosCriticosPorEspecie[$nivelRiesgo];
            
            if (in_array($especieMascota, $config['especies_sensibles'])) {
                $errors[] = $config['mensaje'];
            }
        }

        return $errors;
    }

    /**
     * Validar duplicados de alergia (evitar registrar la misma alergia activa)
     */
    private function validarDuplicados(Mascota $mascota, TipoAlergia $tipoAlergia): array
    {
        $errors = [];
        
        try {
            // Verificar si ya existe alergia del mismo tipo activa
            $existeAlergiaActiva = $mascota->procesosMedicos()
                ->whereHas('procesable', function($query) use ($tipoAlergia) {
                    $query->where('tipo_alergia_id', $tipoAlergia->id)
                          ->where('estado', 'activa');
                })
                ->exists();

            if ($existeAlergiaActiva) {
                $errors[] = "La mascota ya tiene registrada esta alergia como ACTIVA.";
            }

            // Verificar si hay alergia similar (mismo desencadenante) en los últimos 6 meses
            $alergiasSimilares = $mascota->procesosMedicos()
                ->whereHas('procesable', function($query) use ($tipoAlergia) {
                    $query->where('desencadenante', 'like', "%{$tipoAlergia->desencadenante}%")
                          ->where('created_at', '>=', now()->subMonths(6));
                })
                ->count();

            if ($alergiasSimilares > 0) {
                $errors[] = "Se ha registrado una alergia similar recientemente. Verificar si es el mismo caso.";
            }

        } catch (\Exception $e) {
            Log::warning('Error validando duplicados de alergia', [
                'mascota_id' => $mascota->id,
                'tipo_alergia_id' => $tipoAlergia->id,
                'error' => $e->getMessage()
            ]);
        }

        return $errors;
    }

    /**
     * Calcular edad en meses desde fecha_nacimiento string
     */
    public function calcularEdadEnMeses(Mascota $mascota): ?float
    {
        try {
            if (!$mascota->fecha_nacimiento) {
                return null;
            }

            // Usar el método existente en el modelo Mascota
            if (method_exists($mascota, 'calcularEdadDirectamente')) {
                $edadString = $mascota->calcularEdadDirectamente($mascota->fecha_nacimiento);
                
                // Convertir string de edad a meses
                return $this->convertirEdadStringAMeses($edadString);
            }

            // Parsear fecha desde string dd/mm/yyyy
            $partes = explode('/', $mascota->fecha_nacimiento);
            if (count($partes) === 3) {
                $dia = (int)$partes[0];
                $mes = (int)$partes[1];
                $anio = (int)$partes[2];
                
                $fechaNacimiento = Carbon::create($anio, $mes, $dia);
            } else {
                // Intentar otros formatos
                $fechaNacimiento = Carbon::parse($mascota->fecha_nacimiento);
            }

            if ($fechaNacimiento->isFuture()) {
                return 0;
            }

            $diferencia = $fechaNacimiento->diff(Carbon::now());
            
            // Calcular meses con precisión decimal
            $meses = ($diferencia->y * 12) + $diferencia->m;
            $meses += $diferencia->d / 30.44;
            
            return round($meses, 2);

        } catch (\Exception $e) {
            Log::error('Error calculando edad en meses', [
                'mascota_id' => $mascota->id,
                'fecha_nacimiento' => $mascota->fecha_nacimiento,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Convertir string de edad (ej: "2 años y 3 meses") a meses
     */
    private function convertirEdadStringAMeses(string $edadString): ?float
    {
        $meses = 0;
        
        // Buscar años
        if (preg_match('/(\d+)\s*año/', $edadString, $matches)) {
            $meses += (int)$matches[1] * 12;
        }
        
        // Buscar meses
        if (preg_match('/(\d+)\s*mes/', $edadString, $matches)) {
            $meses += (int)$matches[1];
        }
        
        // Buscar días
        if (preg_match('/(\d+)\s*día/', $edadString, $matches)) {
            $meses += (int)$matches[1] / 30.44;
        }
        
        return $meses > 0 ? $meses : null;
    }

    /**
     * Convertir edad a meses según unidad
     */
    private function convertirEdadAMeses(float $edad, string $unidad): float
    {
        switch (strtolower($unidad)) {
            case 'dias':
            case 'día':
            case 'días':
                return $edad / 30.44;
            case 'semanas':
                return $edad / 4.345;
            case 'meses':
            case 'mes':
                return $edad;
            case 'años':
            case 'año':
                return $edad * 12;
            default:
                return $edad; // Asumir meses por defecto
        }
    }

    /**
     * Formatear meses a texto humano
     */
    private function formatearMesesAHumano(float $meses): string
    {
        if ($meses < 1) {
            $dias = round($meses * 30.44);
            return "{$dias} día" . ($dias !== 1 ? 's' : '');
        } elseif ($meses < 12) {
            return round($meses) . " mes" . (round($meses) !== 1 ? 'es' : '');
        } else {
            $años = floor($meses / 12);
            $mesesRestantes = $meses % 12;
            
            if ($mesesRestantes < 1) {
                return "{$años} año" . ($años !== 1 ? 's' : '');
            } else {
                return "{$años} año" . ($años !== 1 ? 's' : '') . 
                       " y " . round($mesesRestantes) . " mes" . (round($mesesRestantes) !== 1 ? 'es' : '');
            }
        }
    }

    /**
     * Método para validar antes del registro
     */
    public function validarAntesDeRegistro(int $mascotaId, int $tipoAlergiaId): array
    {
        try {
            $mascota = Mascota::findOrFail($mascotaId);
            $tipoAlergia = TipoAlergia::findOrFail($tipoAlergiaId);

            return $this->validarMascotaParaTipoAlergia($mascota, $tipoAlergia);
            
        } catch (\Exception $e) {
            Log::error('Error en validación de alergia', [
                'mascota_id' => $mascotaId,
                'tipo_alergia_id' => $tipoAlergiaId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'valido' => false,
                'errors' => ['Error en validación: ' . $e->getMessage()],
                'detalles' => []
            ];
        }
    }

    /**
     * Validación rápida desde cualquier lugar
     */
    public function validarRapida(Mascota $mascota, TipoAlergia $tipoAlergia): bool
    {
        $validacion = $this->validarMascotaParaTipoAlergia($mascota, $tipoAlergia);
        return $validacion['valido'];
    }
}