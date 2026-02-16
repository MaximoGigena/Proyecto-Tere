<?php
// app/Services/Validaciones/DesparasitacionValidationService.php

namespace App\Services\Validaciones;

use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoDesparasitacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DesparasitacionValidationService
{
    /**
     * Validar si una mascota puede recibir un tipo de desparasitación específico
     */
    public function validarMascotaParaTipoDesparasitacion(Mascota $mascota, TipoDesparasitacion $tipoDesparasitacion): array
    {
        $errors = [];

        // 1. Validar especie
        if (!$this->validarEspecie($mascota, $tipoDesparasitacion)) {
            $errors[] = "Esta desparasitación no es aplicable para la especie '{$mascota->especie}'.";
        }

        // 2. Validar edad mínima
        $edadValidation = $this->validarEdadMinima($mascota, $tipoDesparasitacion);
        if (!$edadValidation['valido']) {
            $errors[] = $edadValidation['mensaje'];
        }

        // 3. Validar contraindicaciones (si existen)
        $contraindicacionesErrors = $this->validarContraindicaciones($mascota, $tipoDesparasitacion);
        $errors = array_merge($errors, $contraindicacionesErrors);

        return [
            'valido' => empty($errors),
            'errors' => $errors,
            'detalles' => [
                'mascota_id' => $mascota->id,
                'tipo_desparasitacion_id' => $tipoDesparasitacion->id,
                'especie_valida' => $this->validarEspecie($mascota, $tipoDesparasitacion),
                'edad_valida' => $edadValidation['valido'],
                'edad_actual_meses' => $this->calcularEdadEnSemanas($mascota),
                'edad_minima_requerida' => $tipoDesparasitacion->edad_minima,
                'unidad_edad' => $tipoDesparasitacion->edad_unidad,
                'especies_permitidas' => $tipoDesparasitacion->especies,
                'mascota_especie' => $mascota->especie
            ]
        ];
    }

    /**
     * Validar especie de la mascota vs especies permitidas
     */
    private function validarEspecie(Mascota $mascota, TipoDesparasitacion $tipoDesparasitacion): bool
    {
        $especiesPermitidas = $tipoDesparasitacion->especies;
        
        // Si no hay restricción de especie, todas son válidas
        if (empty($especiesPermitidas)) {
            return true;
        }

        // Si es string, convertir a array
        if (is_string($especiesPermitidas)) {
            $especiesPermitidas = json_decode($especiesPermitidas, true) ?? [$especiesPermitidas];
        }

        // Verificar si la especie de la mascota está en las permitidas
        $especieMascota = strtolower(trim($mascota->especie));
        $especiesPermitidas = array_map('strtolower', array_map('trim', $especiesPermitidas));

        return in_array($especieMascota, $especiesPermitidas);
    }

    /**
     * Validar edad mínima requerida
     */
    private function validarEdadMinima(Mascota $mascota, TipoDesparasitacion $tipoDesparasitacion): array
    {
        // Si no hay restricción de edad, es válido
        if (empty($tipoDesparasitacion->edad_minima)) {
            return ['valido' => true, 'mensaje' => ''];
        }

        $edadActualSemanas = $this->calcularEdadEnSemanas($mascota);
        
        if ($edadActualSemanas === null) {
            return [
                'valido' => false,
                'mensaje' => 'No se puede determinar la edad de la mascota.'
            ];
        }

        // Usar el método del modelo TipoDesparasitacion para validar edad
        if (!$tipoDesparasitacion->aplicaParaEdad($edadActualSemanas)) {
            $edadActualFormateada = $this->formatearSemanasAHumano($edadActualSemanas);
            $edadMinimaFormateada = "{$tipoDesparasitacion->edad_minima} {$tipoDesparasitacion->edad_unidad}";
            
            return [
                'valido' => false,
                'mensaje' => "La mascota tiene {$edadActualFormateada}, pero esta desparasitación requiere mínimo {$edadMinimaFormateada}."
            ];
        }

        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Calcular edad en semanas desde fecha_nacimiento string
     */
    private function calcularEdadEnSemanas(Mascota $mascota): ?float
    {
        try {
            if (!$mascota->fecha_nacimiento) {
                return null;
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

            // Calcular diferencia en semanas con precisión decimal
            $dias = $fechaNacimiento->diffInDays(Carbon::now());
            $semanas = $dias / 7;
            
            return round($semanas, 2);

        } catch (\Exception $e) {
            Log::error('Error calculando edad en semanas', [
                'mascota_id' => $mascota->id,
                'fecha_nacimiento' => $mascota->fecha_nacimiento,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Formatear semanas a texto humano
     */
    private function formatearSemanasAHumano(float $semanas): string
    {
        if ($semanas < 4) {
            $dias = round($semanas * 7);
            return "{$dias} día" . ($dias !== 1 ? 's' : '');
        } elseif ($semanas < 52) {
            $meses = round($semanas / 4.345);
            return "{$meses} mes" . ($meses !== 1 ? 'es' : '');
        } else {
            $años = floor($semanas / 52);
            $semanasRestantes = $semanas % 52;
            
            if ($semanasRestantes < 4) {
                return "{$años} año" . ($años !== 1 ? 's' : '');
            } else {
                $mesesRestantes = round($semanasRestantes / 4.345);
                return "{$años} año" . ($años !== 1 ? 's' : '') . 
                       " y {$mesesRestantes} mes" . ($mesesRestantes !== 1 ? 'es' : '');
            }
        }
    }

    /**
     * Validar contraindicaciones y condiciones especiales
     */
    private function validarContraindicaciones(Mascota $mascota, TipoDesparasitacion $tipoDesparasitacion): array
    {
        $errors = [];
        
        // Aquí puedes agregar validaciones específicas según riesgos o recomendaciones
        if ($tipoDesparasitacion->riesgos) {
            // Ejemplo: Validar si la mascota tiene alguna condición que coincida con riesgos
            // Esto depende de cómo tengas estructurado el campo 'riesgos'
        }
        
        // Validar estado de salud si está disponible
        if ($mascota->caracteristicas && $mascota->caracteristicas->condiciones_especiales) {
            // Aquí podrías verificar si hay condiciones que contraindiquen esta desparasitación
        }

        return $errors;
    }

    /**
     * Método principal para validar antes del registro
     */
    public function validarAntesDeRegistro(int $mascotaId, int $tipoDesparasitacionId): array
    {
        try {
            $mascota = Mascota::with('caracteristicas')->find($mascotaId);
            
            if (!$mascota) {
                return [
                    'valido' => false,
                    'errors' => ['Mascota no encontrada.']
                ];
            }

            $tipoDesparasitacion = TipoDesparasitacion::find($tipoDesparasitacionId);
            
            if (!$tipoDesparasitacion) {
                return [
                    'valido' => false,
                    'errors' => ['Tipo de desparasitación no encontrado.']
                ];
            }

            // Verificar si el tipo está activo
            if (!$tipoDesparasitacion->activo) {
                return [
                    'valido' => false,
                    'errors' => ['Este tipo de desparasitación no está disponible actualmente.']
                ];
            }

            return $this->validarMascotaParaTipoDesparasitacion($mascota, $tipoDesparasitacion);

        } catch (\Exception $e) {
            Log::error('Error en validación de desparasitación', [
                'mascota_id' => $mascotaId,
                'tipo_desparasitacion_id' => $tipoDesparasitacionId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'valido' => false,
                'errors' => ['Error en la validación: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Validar frecuencia (opcional - para validaciones adicionales)
     */
    public function validarFrecuenciaRecomendada(int $tipoDesparasitacionId, int $frecuenciaValor, string $frecuenciaUnidad): array
    {
        $tipoDesparasitacion = TipoDesparasitacion::find($tipoDesparasitacionId);
        
        if (!$tipoDesparasitacion) {
            return ['valido' => false, 'errors' => ['Tipo no encontrado']];
        }

        // Aquí puedes agregar validaciones de frecuencia recomendada
        // Por ejemplo: comparar con frecuencia recomendada en el tipo
        
        return ['valido' => true, 'errors' => []];
    }
}