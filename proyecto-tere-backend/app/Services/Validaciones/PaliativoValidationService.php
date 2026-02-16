<?php
// app/Services/Validaciones/PaliativoValidationService.php

namespace App\Services\Validaciones;

use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoPaliativo;
use App\Models\ProcedimientosMedicos\CuidadoPaliativo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaliativoValidationService
{
    /**
     * Validar si una mascota puede recibir un tipo de procedimiento paliativo específico
     */
    public function validarMascotaParaTipoPaliativo(Mascota $mascota, TipoPaliativo $tipoPaliativo): array
    {
        $errors = [];
        $advertencias = [];

        // 1. Validar especie
        $validacionEspecie = $this->validarEspecie($mascota, $tipoPaliativo);
        if (!$validacionEspecie['valido']) {
            $errors[] = $validacionEspecie['mensaje'];
        }

        // 2. Validar edad mínima (si aplica)
        if ($this->tieneRestriccionEdad($tipoPaliativo)) {
            $edadValidation = $this->validarEdadMinima($mascota, $tipoPaliativo);
            if (!$edadValidation['valido']) {
                $errors[] = $edadValidation['mensaje'];
            }
        }

        // 3. Validar sexo (si aplica)
        $validacionSexo = $this->validarSexo($mascota, $tipoPaliativo);
        if (!$validacionSexo['valido']) {
            $advertencias[] = $validacionSexo['mensaje'];
        }

        // 4. Validar contraindicaciones específicas
        $contraindicacionesValidation = $this->validarContraindicaciones($mascota, $tipoPaliativo);
        if (!$contraindicacionesValidation['valido']) {
            $errors[] = $contraindicacionesValidation['mensaje'];
        }

        // 5. Validar compatibilidad con procedimientos existentes
        if ($mascota->procesosMedicos()->exists()) {
            $compatibilidadValidation = $this->validarCompatibilidadProcedimientos($mascota, $tipoPaliativo);
            if (!$compatibilidadValidation['valido']) {
                $advertencias = array_merge($advertencias, $compatibilidadValidation['advertencias']);
            }
        }

        // 6. Validar estado de salud (si hay información disponible)
        if ($mascota->procesosClinicos()->exists()) {
            $saludValidation = $this->validarEstadoSalud($mascota, $tipoPaliativo);
            if (!$saludValidation['valido']) {
                $errors[] = $saludValidation['mensaje'];
            }
        }

        return [
            'valido' => empty($errors),
            'errors' => $errors,
            'advertencias' => $advertencias,
            'detalles' => [
                'mascota_id' => $mascota->id,
                'tipo_paliativo_id' => $tipoPaliativo->id,
                'especie_valida' => $validacionEspecie['valido'],
                'tiene_restriccion_edad' => $this->tieneRestriccionEdad($tipoPaliativo),
                'edad_valida' => isset($edadValidation) ? $edadValidation['valido'] : true,
                'edad_actual_meses' => $this->calcularEdadEnMeses($mascota),
                'sexo_compatible' => $validacionSexo['valido'],
                'contraindicaciones_valido' => $contraindicacionesValidation['valido'],
                'especies_permitidas' => $tipoPaliativo->especies,
                'contraindicaciones_texto' => $tipoPaliativo->contraindicaciones,
            ]
        ];
    }

    /**
     * Validar especie de la mascota vs especies permitidas para el paliativo
     */
    private function validarEspecie(Mascota $mascota, TipoPaliativo $tipoPaliativo): array
    {
        $especiesPermitidas = $tipoPaliativo->especies;
        
        // Si no hay restricción de especie, es válido
        if (empty($especiesPermitidas)) {
            return [
                'valido' => true,
                'mensaje' => ''
            ];
        }

        // Si es string, intentar convertir a array
        if (is_string($especiesPermitidas)) {
            $especiesPermitidas = json_decode($especiesPermitidas, true) ?? [$especiesPermitidas];
        }

        // Verificar si la especie de la mascota está en las permitidas
        $especieMascota = strtolower($mascota->especie);
        $especiesPermitidas = array_map('strtolower', (array)$especiesPermitidas);

        if (!in_array($especieMascota, $especiesPermitidas)) {
            $especiesTexto = implode(', ', array_map(function($esp) {
                return match($esp) {
                    'canino' => 'caninos',
                    'felino' => 'felinos',
                    'equino' => 'equinos',
                    'bovino' => 'bovinos',
                    'ave' => 'aves',
                    'pez' => 'peces',
                    default => $esp
                };
            }, $especiesPermitidas));

            return [
                'valido' => false,
                'mensaje' => "Este procedimiento paliativo está diseñado para {$especiesTexto}. La mascota es un {$mascota->especie}."
            ];
        }

        return [
            'valido' => true,
            'mensaje' => ''
        ];
    }

    /**
     * Verificar si el tipo de paliativo tiene restricción de edad
     */
    private function tieneRestriccionEdad(TipoPaliativo $tipoPaliativo): bool
    {
        // Analizar las contraindicaciones para buscar restricciones de edad
        if (empty($tipoPaliativo->contraindicaciones)) {
            return false;
        }

        $contraindicaciones = strtolower($tipoPaliativo->contraindicaciones);
        
        // Buscar patrones de edad en las contraindicaciones
        $patronesEdad = [
            '/menor(es)? de\s+(\d+)\s+(mes|meses|año|años)/i',
            '/mayor(es)? de\s+(\d+)\s+(mes|meses|año|años)/i',
            '/edad mínima\s*:?\s*(\d+)\s*(mes|meses|año|años)/i',
            '/edad máxima\s*:?\s*(\d+)\s*(mes|meses|año|años)/i',
            '/no aplicar en\s+(cachorro|gatito|potro|ternero)/i',
            '/solo para\s+(adulto|senior)/i',
        ];

        foreach ($patronesEdad as $patron) {
            if (preg_match($patron, $contraindicaciones)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validar edad mínima/máxima basada en las contraindicaciones
     */
    private function validarEdadMinima(Mascota $mascota, TipoPaliativo $tipoPaliativo): array
    {
        $contraindicaciones = strtolower($tipoPaliativo->contraindicaciones);
        $edadMeses = $this->calcularEdadEnMeses($mascota);

        if ($edadMeses === null) {
            return [
                'valido' => false,
                'mensaje' => 'No se puede determinar la edad de la mascota para validar restricciones de edad.'
            ];
        }

        // Extraer restricciones de edad del texto
        $restricciones = $this->extraerRestriccionesEdad($contraindicaciones);

        // Validar edad mínima
        if ($restricciones['edad_minima_meses'] !== null && 
            $edadMeses < $restricciones['edad_minima_meses']) {
            
            $edadActualFormateada = $this->formatearMesesAHumano($edadMeses);
            $edadMinimaFormateada = $this->formatearMesesAHumano($restricciones['edad_minima_meses']);
            
            return [
                'valido' => false,
                'mensaje' => "La mascota tiene {$edadActualFormateada}. Este procedimiento requiere mínimo {$edadMinimaFormateada}."
            ];
        }

        // Validar edad máxima
        if ($restricciones['edad_maxima_meses'] !== null && 
            $edadMeses > $restricciones['edad_maxima_meses']) {
            
            $edadActualFormateada = $this->formatearMesesAHumano($edadMeses);
            $edadMaximaFormateada = $this->formatearMesesAHumano($restricciones['edad_maxima_meses']);
            
            return [
                'valido' => false,
                'mensaje' => "La mascota tiene {$edadActualFormateada}. Este procedimiento es solo para mascotas menores de {$edadMaximaFormateada}."
            ];
        }

        return [
            'valido' => true,
            'mensaje' => ''
        ];
    }

    /**
     * Extraer restricciones de edad del texto de contraindicaciones
     */
    private function extraerRestriccionesEdad(string $contraindicaciones): array
    {
        $resultado = [
            'edad_minima_meses' => null,
            'edad_maxima_meses' => null,
            'excluir_crias' => false
        ];

        // Patrones para edad mínima
        if (preg_match('/edad mínima\s*:?\s*(\d+)\s*(mes|meses|año|años)/i', $contraindicaciones, $matches)) {
            $valor = (int)$matches[1];
            $unidad = strtolower($matches[2]);
            $resultado['edad_minima_meses'] = $this->convertirAMeses($valor, $unidad);
        }
        elseif (preg_match('/mayor(es)? de\s+(\d+)\s+(mes|meses|año|años)/i', $contraindicaciones, $matches)) {
            $valor = (int)$matches[2];
            $unidad = strtolower($matches[3]);
            $resultado['edad_minima_meses'] = $this->convertirAMeses($valor, $unidad);
        }

        // Patrones para edad máxima
        if (preg_match('/edad máxima\s*:?\s*(\d+)\s*(mes|meses|año|años)/i', $contraindicaciones, $matches)) {
            $valor = (int)$matches[1];
            $unidad = strtolower($matches[2]);
            $resultado['edad_maxima_meses'] = $this->convertirAMeses($valor, $unidad);
        }
        elseif (preg_match('/menor(es)? de\s+(\d+)\s+(mes|meses|año|años)/i', $contraindicaciones, $matches)) {
            $valor = (int)$matches[2];
            $unidad = strtolower($matches[3]);
            $resultado['edad_maxima_meses'] = $this->convertirAMeses($valor, $unidad);
        }

        // Patrones para excluir crías
        if (preg_match('/no aplicar en\s+(cachorro|gatito|potro|ternero)/i', $contraindicaciones) ||
            preg_match('/solo para\s+(adulto|senior)/i', $contraindicaciones)) {
            $resultado['excluir_crias'] = true;
        }

        return $resultado;
    }

    /**
     * Validar sexo (algunos paliativos pueden ser específicos por sexo)
     */
    private function validarSexo(Mascota $mascota, TipoPaliativo $tipoPaliativo): array
    {
        // Buscar indicaciones específicas por sexo en las indicaciones clínicas
        $indicaciones = strtolower($tipoPaliativo->indicaciones_clinicas ?? '');
        
        if (str_contains($indicaciones, 'macho') && $mascota->sexo !== 'macho') {
            return [
                'valido' => false,
                'mensaje' => 'Este procedimiento está específicamente indicado para machos.'
            ];
        }
        
        if (str_contains($indicaciones, 'hembra') && $mascota->sexo !== 'hembra') {
            return [
                'valido' => false,
                'mensaje' => 'Este procedimiento está específicamente indicado para hembras.'
            ];
        }

        return [
            'valido' => true,
            'mensaje' => ''
        ];
    }

    /**
     * Validar contraindicaciones específicas
     */
    private function validarContraindicaciones(Mascota $mascota, TipoPaliativo $tipoPaliativo): array
    {
        if (empty($tipoPaliativo->contraindicaciones)) {
            return [
                'valido' => true,
                'mensaje' => ''
            ];
        }

        // Aquí puedes agregar lógica específica basada en las características de la mascota
        // Por ejemplo, verificar si está gestante, si tiene condiciones preexistentes, etc.
        
        // Por ahora, validamos solo las más comunes:
        $contraindicaciones = strtolower($tipoPaliativo->contraindicaciones);
        
        if (str_contains($contraindicaciones, 'gestante') && $this->esMascotaGestante($mascota)) {
            return [
                'valido' => false,
                'mensaje' => 'Este procedimiento está contraindicado para mascotas gestantes.'
            ];
        }

        return [
            'valido' => true,
            'mensaje' => ''
        ];
    }

    /**
     * Validar compatibilidad con otros procedimientos médicos
     */
    private function validarCompatibilidadProcedimientos(Mascota $mascota, TipoPaliativo $tipoPaliativo): array
    {
        $advertencias = [];
        
        // Obtener procedimientos activos recientes
        $procedimientosRecientes = $mascota->procesosMedicos()
            ->where('fecha_aplicacion', '>=', now()->subDays(30))
            ->with('procesable')
            ->get();

        foreach ($procedimientosRecientes as $procedimiento) {
            // Verificar si el procesable es un cuidado paliativo
            if ($procedimiento->procesable_type === 'App\Models\ProcedimientosMedicos\CuidadoPaliativo') {
                $paliativoExistente = $procedimiento->procesable;
                
                // Advertencia si ya hay un paliativo similar activo
                if ($paliativoExistente->tipo_paliativo_id === $tipoPaliativo->id) {
                    $advertencias[] = "Ya existe un procedimiento paliativo similar registrado el {$paliativoExistente->fecha_inicio->format('d/m/Y')}.";
                }
            }
        }

        return [
            'valido' => true, // Esto no bloquea, solo advierte
            'advertencias' => $advertencias
        ];
    }

    /**
     * Validar estado de salud de la mascota
     */
    private function validarEstadoSalud(Mascota $mascota, TipoPaliativo $tipoPaliativo): array
    {
        // Solo verificar si hay restricción por estado crítico
        if (!str_contains(strtolower($tipoPaliativo->contraindicaciones ?? ''), 'estado crítico')) {
            return [
                'valido' => true,
                'mensaje' => ''
            ];
        }
        
        // Consulta directa para CuidadoPaliativo
        $paliativosCriticos = CuidadoPaliativo::whereHas('procesoMedico', function($query) use ($mascota) {
                $query->where('mascota_id', $mascota->id)
                    ->where('categoria', 'clinico')
                    ->where('fecha_aplicacion', '>=', now()->subDays(7));
            })
            ->where(function($query) {
                $query->where('estado_mascota', 'critico')
                    ->orWhere('resultado', 'empeoramiento');
            })
            ->exists();

        if ($paliativosCriticos) {
            return [
                'valido' => false,
                'mensaje' => 'La mascota presenta cuidados paliativos críticos recientes que contraindican este procedimiento.'
            ];
        }

        return [
            'valido' => true,
            'mensaje' => ''
        ];
    }
    /**
     * Métodos auxiliares (puedes reutilizar los de VacunaValidationService)
     */

    /**
     * Calcular edad en meses
     */
    public function calcularEdadEnMeses(Mascota $mascota): ?float
    {
        try {
            if (!$mascota->fecha_nacimiento) {
                return null;
            }

            $partes = explode('/', $mascota->fecha_nacimiento);
            if (count($partes) === 3) {
                $dia = (int)$partes[0];
                $mes = (int)$partes[1];
                $anio = (int)$partes[2];
                
                $fechaNacimiento = Carbon::create($anio, $mes, $dia);
            } else {
                $fechaNacimiento = Carbon::parse($mascota->fecha_nacimiento);
            }

            if ($fechaNacimiento->isFuture()) {
                return 0;
            }

            $diferencia = $fechaNacimiento->diff(Carbon::now());
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
     * Convertir a meses según unidad
     */
    private function convertirAMeses(float $valor, string $unidad): float
    {
        return match(strtolower($unidad)) {
            'día', 'dias' => $valor / 30.44,
            'semana', 'semanas' => $valor / 4.345,
            'mes', 'meses' => $valor,
            'año', 'años' => $valor * 12,
            default => $valor
        };
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
     * Verificar si la mascota podría estar gestante (simplificado)
     */
    private function esMascotaGestante(Mascota $mascota): bool
    {
        // Esta es una implementación simplificada
        // En un sistema real, esto vendría de un historial reproductivo o diagnóstico
        
        if ($mascota->sexo !== 'hembra') {
            return false;
        }

        // Aquí podrías agregar lógica para verificar si está gestante
        // Por ejemplo, consultando un historial de cruzas recientes
        
        return false; // Por defecto, asumimos que no está gestante
    }

    /**
     * Método principal para validar antes del registro
     */
    public function validarAntesDeRegistro(int $mascotaId, int $tipoPaliativoId): array
    {
        try {
            $mascota = Mascota::with(['procesosMedicos.procesable'])->findOrFail($mascotaId);
            $tipoPaliativo = TipoPaliativo::findOrFail($tipoPaliativoId);

            return $this->validarMascotaParaTipoPaliativo($mascota, $tipoPaliativo);
            
        } catch (\Exception $e) {
            Log::error('Error en validación de paliativo', [
                'mascota_id' => $mascotaId,
                'tipo_paliativo_id' => $tipoPaliativoId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'valido' => false,
                'errors' => ['Error en validación: ' . $e->getMessage()],
                'advertencias' => [],
                'detalles' => []
            ];
        }
    }
}