<?php
// app/Services/Validaciones/RevisionValidationService.php

namespace App\Services\Validaciones;

use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoRevision;
use App\Models\ProcedimientosMedicos\Revision;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RevisionValidationService
{
    /**
     * Validar si una mascota puede recibir un tipo de revisión específico
     */
    public function validarMascotaParaTipoRevision(Mascota $mascota, TipoRevision $tipoRevision): array
    {
        $errors = [];

        // 1. Validar especie
        if (!$this->validarEspecie($mascota, $tipoRevision)) {
            $errors[] = "Esta revisión no es aplicable para la especie '{$mascota->especie}'.";
        }

        // 2. Validar edad mínima (si está configurada)
        $edadValidation = $this->validarEdad($mascota, $tipoRevision);
        if (!$edadValidation['valido']) {
            $errors[] = $edadValidation['mensaje'];
        }

        // 3. Validar condiciones específicas por tipo de revisión
        if (!empty($tipoRevision->riesgos_clinicos)) {
            $condicionesErrors = $this->validarCondicionesEspecificas($mascota, $tipoRevision);
            $errors = array_merge($errors, $condicionesErrors);
        }

        return [
            'valido' => empty($errors),
            'errors' => $errors,
            'detalles' => [
                'mascota_id' => $mascota->id,
                'tipo_revision_id' => $tipoRevision->id,
                'especie_valida' => $this->validarEspecie($mascota, $tipoRevision),
                'edad_valida' => $edadValidation['valido'],
                'edad_actual_semanas' => $this->calcularEdadEnSemanas($mascota),
                'edad_sugerida' => $tipoRevision->edad_sugerida,
                'edad_unidad' => $tipoRevision->edad_unidad,
                'especies_permitidas' => $tipoRevision->especies
            ]
        ];
    }

    /**
     * Validar especie de la mascota vs especies permitidas para la revisión
     */
    private function validarEspecie(Mascota $mascota, TipoRevision $tipoRevision): bool
    {
        $especiesPermitidas = $tipoRevision->especies;
        
        // Si es null, vacío o contiene 'todos', acepta todas las especies
        if (empty($especiesPermitidas) || 
            (is_array($especiesPermitidas) && in_array('todos', $especiesPermitidas))) {
            return true;
        }

        // Si es string, convertir a array
        if (is_string($especiesPermitidas)) {
            $especiesPermitidas = json_decode($especiesPermitidas, true) ?? [$especiesPermitidas];
        }

        // Verificar si la especie de la mascota está en las permitidas
        return in_array(strtolower($mascota->especie), 
                       array_map('strtolower', $especiesPermitidas));
    }

    /**
     * Validar edad según restricciones del tipo de revisión
     */
    private function validarEdad(Mascota $mascota, TipoRevision $tipoRevision): array
    {
        // Si no hay restricción de edad, es válido
        if (empty($tipoRevision->edad_sugerida)) {
            return ['valido' => true, 'mensaje' => ''];
        }

        $edadActualSemanas = $this->calcularEdadEnSemanas($mascota);
        
        if ($edadActualSemanas === null) {
            return [
                'valido' => false,
                'mensaje' => 'No se puede determinar la edad de la mascota.'
            ];
        }

        // Usar el método del modelo para obtener edad en semanas
        $edadMinimaSemanas = $tipoRevision->edad_en_semanas;

        if ($edadActualSemanas < $edadMinimaSemanas) {
            $edadActualFormateada = $this->formatearSemanasAHumano($edadActualSemanas);
            $edadMinimaFormateada = $this->formatearSemanasAHumano($edadMinimaSemanas);
            
            return [
                'valido' => false,
                'mensaje' => "La mascota tiene {$edadActualFormateada}, pero esta revisión requiere mínimo {$edadMinimaFormateada}."
            ];
        }

        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Calcular edad en semanas desde fecha_nacimiento
     */
    public function calcularEdadEnSemanas(Mascota $mascota): ?float
    {
        try {
            // Si no hay fecha de nacimiento, retornar null
            if (!$mascota->fecha_nacimiento) {
                Log::warning('Mascota sin fecha de nacimiento', ['mascota_id' => $mascota->id]);
                return null;
            }

            // Intentar múltiples formatos de fecha
            $fechaNac = $this->parsearFecha($mascota->fecha_nacimiento);
            
            if (!$fechaNac) {
                Log::error('No se pudo parsear fecha de nacimiento', [
                    'mascota_id' => $mascota->id,
                    'fecha_nacimiento' => $mascota->fecha_nacimiento
                ]);
                return null;
            }

            $hoy = Carbon::now();

            // Validar que la fecha no sea futura
            if ($fechaNac->isFuture()) {
                Log::warning('Fecha de nacimiento futura', [
                    'mascota_id' => $mascota->id,
                    'fecha_nacimiento' => $mascota->fecha_nacimiento
                ]);
                return 0;
            }

            // Calcular semanas con precisión
            $semanas = $fechaNac->diffInDays($hoy) / 7;
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
     * Parsear fecha en múltiples formatos
     */
    private function parsearFecha(string $fechaString): ?Carbon
    {
        try {
            // Intentar formato dd/mm/yyyy
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fechaString, $matches)) {
                return Carbon::createFromDate($matches[3], $matches[2], $matches[1]);
            }
            
            // Intentar formato yyyy-mm-dd (formato de base de datos)
            if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $fechaString, $matches)) {
                return Carbon::createFromDate($matches[1], $matches[2], $matches[3]);
            }
            
            // Intentar parsear con Carbon directamente
            try {
                return Carbon::parse($fechaString);
            } catch (\Exception $e) {
                return null;
            }
            
        } catch (\Exception $e) {
            Log::error('Error parseando fecha', [
                'fecha_string' => $fechaString,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calcular semanas directamente desde string
     */
    private function calcularSemanasDirectamente(string $fechaNacimiento): float
    {
        try {
            // Parsear fecha dd/mm/yyyy
            $partes = explode('/', $fechaNacimiento);
            if (count($partes) !== 3) {
                throw new \Exception('Formato de fecha inválido');
            }

            $dia = (int)$partes[0];
            $mes = (int)$partes[1];
            $anio = (int)$partes[2];
            
            $fechaNac = Carbon::create($anio, $mes, $dia);
            $hoy = Carbon::now();

            if ($fechaNac->isFuture()) {
                return 0;
            }

            // Calcular semanas con precisión
            $semanas = $fechaNac->diffInDays($hoy) / 7;
            return round($semanas, 2);

        } catch (\Exception $e) {
            Log::error('Error en calcularSemanasDirectamente', [
                'fecha_nacimiento' => $fechaNacimiento,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Formatear semanas a texto humano
     */
    private function formatearSemanasAHumano(float $semanas): string
    {
        if ($semanas < 1) {
            $dias = round($semanas * 7);
            return "{$dias} día" . ($dias !== 1 ? 's' : '');
        } elseif ($semanas < 4) {
            return round($semanas, 1) . " semana" . (round($semanas, 1) !== 1 ? 's' : '');
        } elseif ($semanas < 52) {
            $meses = round($semanas / 4.345, 1);
            return "{$meses} mes" . ($meses !== 1 ? 'es' : '');
        } else {
            $años = floor($semanas / 52);
            $semanasRestantes = $semanas % 52;
            
            if ($semanasRestantes < 1) {
                return "{$años} año" . ($años !== 1 ? 's' : '');
            } else {
                $mesesRestantes = round($semanasRestantes / 4.345);
                return "{$años} año" . ($años !== 1 ? 's' : '') . 
                       " y {$mesesRestantes} mes" . ($mesesRestantes !== 1 ? 'es' : '');
            }
        }
    }

    /**
     * Validar condiciones específicas según riesgos_clinicos
     */
    private function validarCondicionesEspecificas(Mascota $mascota, TipoRevision $tipoRevision): array
    {
        $errors = [];
        
        // Aquí puedes agregar validaciones basadas en riesgos_clinicos
        // Por ejemplo: 
        // - Verificar si la mascota tiene ciertas condiciones preexistentes
        // - Validar estado de salud actual
        // - Verificar si ha tenido complicaciones previas
        
        if ($this->tieneCondicionesContraindicadas($mascota, $tipoRevision)) {
            $errors[] = "La mascota presenta condiciones que pueden contraindicar esta revisión.";
        }
        
        return $errors;
    }

    /**
     * Validar si la mascota tiene condiciones contraindicadas
     */
    private function tieneCondicionesContraindicadas(Mascota $mascota, TipoRevision $tipoRevision): bool
    {
        // Implementar lógica según tus necesidades
        // Podrías verificar en ProcesoMedico si hay diagnósticos previos
        // o en CaracteristicasMascota si hay condiciones especiales
        
        return false; // Por defecto, no hay contraindicaciones
    }

    /**
     * Método para validar antes del registro
     */
    public function validarAntesDeRegistro(int $mascotaId, int $tipoRevisionId): array
    {
        $mascota = Mascota::with(['edadRelacion'])->findOrFail($mascotaId);
        $tipoRevision = TipoRevision::findOrFail($tipoRevisionId);

        return $this->validarMascotaParaTipoRevision($mascota, $tipoRevision);
    }

    /**
     * Validar frecuencia de revisiones (evitar revisiones muy seguidas del mismo tipo)
     */
    public function validarFrecuenciaRevision(int $mascotaId, int $tipoRevisionId, string $fechaRevision): array
    {
        $errors = [];
        
        try {
            $tipoRevision = TipoRevision::findOrFail($tipoRevisionId);
            $fechaRevisionCarbon = Carbon::parse($fechaRevision);
            
            // Buscar revisiones recientes del mismo tipo
            $revisionesRecientes = Revision::where('tipo_revision_id', $tipoRevisionId)
                ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                    $query->where('mascota_id', $mascotaId);
                })
                ->whereDate('fecha_revision', '>=', $fechaRevisionCarbon->subDays(7))
                ->count();
            
            if ($revisionesRecientes > 0) {
                $errors[] = "Esta mascota ya tiene una revisión de este tipo programada recientemente.";
            }
            
            // Validar según frecuencia recomendada
            if ($tipoRevision->frecuencia_recomendada) {
                $ultimaRevision = Revision::where('tipo_revision_id', $tipoRevisionId)
                    ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                        $query->where('mascota_id', $mascotaId);
                    })
                    ->orderBy('fecha_revision', 'desc')
                    ->first();
                
                if ($ultimaRevision) {
                    $diasDesdeUltima = $fechaRevisionCarbon->diffInDays($ultimaRevision->fecha_revision);
                    
                    $diasMinimosRecomendados = $this->obtenerDiasMinimosPorFrecuencia(
                        $tipoRevision->frecuencia_recomendada
                    );
                    
                    if ($diasDesdeUltima < $diasMinimosRecomendados) {
                        $errors[] = "La frecuencia mínima recomendada para esta revisión es cada " . 
                                   $diasMinimosRecomendados . " días.";
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error validando frecuencia de revisión', [
                'mascota_id' => $mascotaId,
                'tipo_revision_id' => $tipoRevisionId,
                'error' => $e->getMessage()
            ]);
        }
        
        return [
            'valido' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Obtener días mínimos según frecuencia
     */
    private function obtenerDiasMinimosPorFrecuencia(string $frecuencia): int
    {
        return match(strtolower($frecuencia)) {
            'diaria' => 1,
            'semanal' => 7,
            'quincenal' => 15,
            'mensual' => 30,
            'bimestral' => 60,
            'trimestral' => 90,
            'semestral' => 180,
            'anual' => 365,
            'bienal' => 730,
            default => 7 // Por defecto, una semana
        };
    }
}