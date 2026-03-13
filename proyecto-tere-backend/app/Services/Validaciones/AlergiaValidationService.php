<?php
// app/Services/Validaciones/AlergiaValidationService.php

namespace App\Services\Validaciones;

use App\Models\Mascota;
use App\Models\ProcedimientosMedicos\Alergia;
use App\Models\TiposProcedimientos\TipoAlergia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AlergiaValidationService
{
    /**
     * Validar antes de registrar una nueva alergia
     */
    public function validarAntesDeRegistro(int $mascotaId, int $tipoAlergiaId): array
    {
        try {
            $mascota = Mascota::findOrFail($mascotaId);
            $tipoAlergia = TipoAlergia::findOrFail($tipoAlergiaId);

            return $this->validarMascotaParaTipoAlergia($mascota, $tipoAlergia);
            
        } catch (\Exception $e) {
            Log::error('Error en validación de registro de alergia', [
                'mascota_id' => $mascotaId,
                'tipo_alergia_id' => $tipoAlergiaId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'valido' => false,
                'errors' => ['Error en validación: ' . $e->getMessage()],
                'detalles' => ['exception' => $e->getMessage()]
            ];
        }
    }

    /**
     * Validar antes de actualizar una alergia
     */
    public function validarAntesDeActualizacion(int $mascotaId, int $alergiaId, int $nuevoTipoAlergiaId): array
    {
        $errors = [];
        $detalles = [];

        try {
            // Obtener la mascota
            $mascota = Mascota::find($mascotaId);
            
            if (!$mascota) {
                return [
                    'valido' => false,
                    'errors' => ['La mascota especificada no existe.'],
                    'detalles' => ['mascota_no_encontrada' => true]
                ];
            }

            $detalles['mascota'] = [
                'id' => $mascota->id,
                'nombre' => $mascota->nombre,
                'especie' => $mascota->especie,
                'edad_meses' => $this->calcularEdadEnMeses($mascota)
            ];

            // Obtener la alergia actual
            $alergiaActual = Alergia::with(['tipoAlergia', 'procesoMedico'])->find($alergiaId);
            
            if (!$alergiaActual) {
                return [
                    'valido' => false,
                    'errors' => ['La alergia a actualizar no existe.'],
                    'detalles' => ['alergia_no_encontrada' => true]
                ];
            }

            // Verificar que la alergia pertenezca a la mascota
            if ($alergiaActual->procesoMedico->mascota_id != $mascotaId) {
                return [
                    'valido' => false,
                    'errors' => ['La alergia no pertenece a la mascota especificada.'],
                    'detalles' => ['pertenencia_incorrecta' => true]
                ];
            }

            $detalles['alergia_actual'] = [
                'id' => $alergiaActual->id,
                'tipo_alergia_id' => $alergiaActual->tipo_alergia_id,
                'tipo_alergia_nombre' => $alergiaActual->tipoAlergia->nombre ?? 'Desconocido',
                'estado' => $alergiaActual->estado
            ];

            // Obtener el nuevo tipo de alergia
            $nuevoTipoAlergia = TipoAlergia::find($nuevoTipoAlergiaId);
            
            if (!$nuevoTipoAlergia) {
                return [
                    'valido' => false,
                    'errors' => ['El nuevo tipo de alergia especificado no existe.'],
                    'detalles' => $detalles
                ];
            }

            $detalles['nuevo_tipo_alergia'] = [
                'id' => $nuevoTipoAlergia->id,
                'nombre' => $nuevoTipoAlergia->nombre,
                'especie_id' => $nuevoTipoAlergia->especie_id,
                'especies_permitidas' => $nuevoTipoAlergia->especies,
                'nivel_riesgo' => $nuevoTipoAlergia->nivel_riesgo
            ];

            // Si no hay cambio de tipo, la validación es exitosa
            if ($alergiaActual->tipo_alergia_id == $nuevoTipoAlergiaId) {
                return [
                    'valido' => true,
                    'errors' => [],
                    'detalles' => $detalles
                ];
            }

            // VALIDACIONES PARA CAMBIO DE TIPO DE ALERGIA

            // 1. Validar especie
            if (!$this->validarEspecie($mascota, $nuevoTipoAlergia)) {
                $errors[] = "El nuevo tipo de alergia '{$nuevoTipoAlergia->nombre}' no es compatible con la especie de la mascota ({$mascota->especie}).";
                $detalles['error_compatibilidad_especie'] = true;
            }

            // 2. Validar restricciones de edad desde observaciones_adicionales
            $edadValidation = $this->validarRestriccionesEdad($mascota, $nuevoTipoAlergia);
            if (!$edadValidation['valido']) {
                $errors[] = $edadValidation['mensaje'];
            }

            // 3. Validar condiciones especiales desde observaciones_adicionales
            $condicionesErrors = $this->validarCondicionesEspeciales($mascota, $nuevoTipoAlergia);
            $errors = array_merge($errors, $condicionesErrors);

            // 4. Validar nivel de riesgo vs especie
            $riesgoErrors = $this->validarNivelRiesgo($mascota, $nuevoTipoAlergia);
            $errors = array_merge($errors, $riesgoErrors);

            // 5. Validar que no exista otra alergia activa del mismo tipo
            $alergiaDuplicada = Alergia::where('tipo_alergia_id', $nuevoTipoAlergiaId)
                ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                    $query->where('mascota_id', $mascotaId);
                })
                ->where('estado', 'activa')
                ->where('id', '!=', $alergiaId) // Excluir la alergia actual
                ->whereNull('deleted_at')
                ->first();

            if ($alergiaDuplicada) {
                $errors[] = "La mascota ya tiene registrada una alergia activa del tipo '{$nuevoTipoAlergia->nombre}'.";
                $detalles['alergia_duplicada'] = [
                    'id' => $alergiaDuplicada->id,
                    'estado' => $alergiaDuplicada->estado
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error en validación de actualización de alergia', [
                'mascota_id' => $mascotaId,
                'alergia_id' => $alergiaId,
                'nuevo_tipo_alergia_id' => $nuevoTipoAlergiaId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'valido' => false,
                'errors' => ['Error interno durante la validación de actualización.'],
                'detalles' => ['exception' => $e->getMessage()]
            ];
        }

        return [
            'valido' => empty($errors),
            'errors' => $errors,
            'detalles' => $detalles
        ];
    }

    /**
     * Validar si una mascota puede tener registrada una alergia específica
     */
    private function validarMascotaParaTipoAlergia(Mascota $mascota, TipoAlergia $tipoAlergia): array
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
        
        // Si es string, verificar si es JSON o texto plano
        if (is_string($especiesPermitidas)) {
            // Intentar decodificar JSON
            $decodificado = json_decode($especiesPermitidas, true);
            if (is_array($decodificado)) {
                $especiesPermitidas = $decodificado;
            } else {
                // Si no es JSON, dividir por comas o espacios
                if (str_contains($especiesPermitidas, ',')) {
                    $especiesPermitidas = array_map('trim', explode(',', $especiesPermitidas));
                } else {
                    $especiesPermitidas = [$especiesPermitidas];
                }
            }
        }

        // Si no es array después de procesar, asumir que acepta todas
        if (!is_array($especiesPermitidas)) {
            return true;
        }

        // Obtener especie de la mascota (es un string)
        $especieMascota = strtolower(trim($mascota->especie));
        
        // Convertir especies permitidas a minúsculas para comparación
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
                
                // Si encontramos una restricción y se cumple, salimos
                break;
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

        // Obtener especie de la mascota (string)
        $especieMascota = strtolower($mascota->especie);

        // Patrones de exclusión por especie
        $exclusionPatterns = [
            "/no aplicar.*?{$especieMascota}/i",
            "/no.*?{$especieMascota}/i",
            "/excluir.*?{$especieMascota}/i",
            "/no.*?recomendado.*?para.*?{$especieMascota}/i",
            "/contraindicado.*?{$especieMascota}/i"
        ];

        foreach ($exclusionPatterns as $pattern) {
            if (preg_match($pattern, $observaciones)) {
                $errors[] = "Esta alergia no se recomienda para la especie {$mascota->especie} (según observaciones).";
                break;
            }
        }

        // Validar castración
        if (preg_match('/requiere.*?castrad[oa]|solo.*?castrad[oa]|necesita.*?castrad[oa]/i', $observaciones)) {
            if (!$mascota->castrado) {
                $errors[] = "Esta alergia requiere que la mascota esté castrada (según observaciones).";
            }
        }

        // Validar estado de salud
        if (preg_match('/inmunodeprimid|inmunocomprometid|sistema inmune/i', $observaciones)) {
            // Aquí podrías agregar lógica para verificar historial médico
            // Por ahora solo muestra advertencia
            $errors[] = "Esta alergia requiere evaluación especial para mascotas con condiciones inmunológicas (revisar observaciones).";
        }

        // Validar embarazo/lactancia
        if (preg_match('/embarazad|gestant|lactanci|preñez/i', $observaciones)) {
            // Verificar si es hembra (asumiendo 'H' para hembra)
            if ($mascota->sexo === 'H' || $mascota->sexo === 'F' || strtolower($mascota->sexo) === 'hembra') {
                $errors[] = "Esta alergia requiere evaluación especial para hembras embarazadas o en lactancia (según observaciones).";
            }
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
            
            foreach ($config['especies_sensibles'] as $especieSensible) {
                if (str_contains($especieMascota, $especieSensible)) {
                    $errors[] = $config['mensaje'];
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Validar duplicados de alergia (evitar registrar la misma alergia activa)
     */
    private function validarDuplicados(Mascota $mascota, TipoAlergia $tipoAlergia, ?int $excluirAlergiaId = null): array
    {
        $errors = [];
        
        try {
            // Verificar si ya existe alergia del mismo tipo activa
            $query = Alergia::where('tipo_alergia_id', $tipoAlergia->id)
                ->whereHas('procesoMedico', function($query) use ($mascota) {
                    $query->where('mascota_id', $mascota->id);
                })
                ->where('estado', 'activa')
                ->whereNull('deleted_at');

            // Excluir una alergia específica (útil para actualizaciones)
            if ($excluirAlergiaId) {
                $query->where('id', '!=', $excluirAlergiaId);
            }

            $existeAlergiaActiva = $query->exists();

            if ($existeAlergiaActiva) {
                $errors[] = "La mascota ya tiene registrada esta alergia como ACTIVA.";
            }

            // Verificar si hay alergia similar (mismo desencadenante) en los últimos 6 meses
            if ($tipoAlergia->desencadenante) {
                $alergiasSimilares = Alergia::where('desencadenante', 'like', "%{$tipoAlergia->desencadenante}%")
                    ->whereHas('procesoMedico', function($query) use ($mascota) {
                        $query->where('mascota_id', $mascota->id);
                    })
                    ->where('created_at', '>=', now()->subMonths(6))
                    ->whereNull('deleted_at');

                if ($excluirAlergiaId) {
                    $alergiasSimilares->where('id', '!=', $excluirAlergiaId);
                }

                $count = $alergiasSimilares->count();

                if ($count > 0) {
                    $errors[] = "Se ha registrado una alergia similar en los últimos 6 meses. Verificar si es el mismo caso.";
                }
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
     * Calcular edad en meses desde fecha_nacimiento string (dd/mm/yyyy)
     */
    public function calcularEdadEnMeses(Mascota $mascota): ?float
    {
        try {
            if (!$mascota->fecha_nacimiento) {
                return null;
            }

            // Usar el método calcularEdadDirectamente del modelo Mascota
            $edadString = $mascota->calcularEdadDirectamente($mascota->fecha_nacimiento);
            
            // Convertir string de edad a meses
            $meses = $this->convertirEdadStringAMeses($edadString);
            
            return $meses;

        } catch (\Exception $e) {
            Log::error('Error calculando edad en meses', [
                'mascota_id' => $mascota->id,
                'fecha_nacimiento' => $mascota->fecha_nacimiento,
                'error' => $e->getMessage()
            ]);
            
            // Fallback: intentar calcular desde la fecha
            return $this->calcularEdadDesdeFecha($mascota->fecha_nacimiento);
        }
    }

    /**
     * Calcular edad en meses directamente desde fecha string dd/mm/yyyy
     */
    private function calcularEdadDesdeFecha(string $fechaNacimiento): ?float
    {
        try {
            // Parsear fecha en formato dd/mm/yyyy
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fechaNacimiento, $partes)) {
                $dia = (int) $partes[1];
                $mes = (int) $partes[2];
                $anio = (int) $partes[3];
                
                if (!checkdate($mes, $dia, $anio)) {
                    return null;
                }
                
                $nacimiento = Carbon::createFromDate($anio, $mes, $dia);
                $hoy = Carbon::now();
                
                if ($nacimiento->isFuture()) {
                    return 0;
                }

                $diferencia = $nacimiento->diff($hoy);
                
                // Calcular meses con precisión decimal
                $meses = ($diferencia->y * 12) + $diferencia->m;
                $meses += $diferencia->d / 30.44;
                
                return round($meses, 2);
            }
            
            return null;
            
        } catch (\Exception $e) {
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
        
        return $meses > 0 ? round($meses, 2) : null;
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
            case 'semana':
            case 'semanas':
                return $edad / 4.345;
            case 'mes':
            case 'meses':
                return $edad;
            case 'año':
            case 'años':
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
            return "{$dias} día" . ($dias != 1 ? 's' : '');
        } elseif ($meses < 12) {
            $mesesRedondeados = round($meses);
            return "{$mesesRedondeados} mes" . ($mesesRedondeados != 1 ? 'es' : '');
        } else {
            $años = floor($meses / 12);
            $mesesRestantes = round($meses % 12);
            
            if ($mesesRestantes < 1) {
                return "{$años} año" . ($años != 1 ? 's' : '');
            } else {
                return "{$años} año" . ($años != 1 ? 's' : '') . 
                       " y {$mesesRestantes} mes" . ($mesesRestantes != 1 ? 'es' : '');
            }
        }
    }

    /**
     * Validar si se puede eliminar/dar de baja una alergia
     */
    public function validarAntesDeBaja(int $mascotaId, int $alergiaId): array
    {
        $errors = [];
        $detalles = [];

        try {
            $alergia = Alergia::with(['procesoMedico', 'tipoAlergia'])->find($alergiaId);
            
            if (!$alergia) {
                return [
                    'valido' => false,
                    'errors' => ['La alergia no existe.'],
                    'detalles' => ['alergia_no_encontrada' => true]
                ];
            }

            // Verificar que la alergia pertenezca a la mascota
            if ($alergia->procesoMedico->mascota_id != $mascotaId) {
                return [
                    'valido' => false,
                    'errors' => ['La alergia no pertenece a la mascota especificada.'],
                    'detalles' => ['pertenencia_incorrecta' => true]
                ];
            }

            // Verificar si ya está eliminada
            if ($alergia->isEliminada()) {
                return [
                    'valido' => false,
                    'errors' => ['La alergia ya fue dada de baja anteriormente.'],
                    'detalles' => ['ya_eliminada' => true]
                ];
            }

            $detalles = [
                'alergia_id' => $alergia->id,
                'estado' => $alergia->estado,
                'tipo_alergia' => $alergia->tipoAlergia->nombre ?? null
            ];

        } catch (\Exception $e) {
            Log::error('Error en validación de baja de alergia', [
                'mascota_id' => $mascotaId,
                'alergia_id' => $alergiaId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'valido' => false,
                'errors' => ['Error interno durante la validación de baja.'],
                'detalles' => ['exception' => $e->getMessage()]
            ];
        }

        return [
            'valido' => empty($errors),
            'errors' => $errors,
            'detalles' => $detalles
        ];
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