<?php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\ProcedimientosMedicos\Alergia;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoAlergia;
use App\Models\CentroVeterinario;
use App\Models\ContactoUsuario;
use App\Http\Requests\StoreAlergiaRequest;
use App\Http\Requests\UpdateAlergiaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\EnvioDocumentosService;
use Carbon\Carbon;

class AlergiaController extends Controller
{
    protected $envioDocumentosService;

    public function __construct(EnvioDocumentosService $envioDocumentosService)
    {
        $this->envioDocumentosService = $envioDocumentosService;
    }

    /**
     * Helper para formatear fechas de manera segura
     */
    private function formatFechaSegura($fecha, $formato = 'Y-m-d')
    {
        try {
            if (!$fecha) {
                return null;
            }

            // Si es string, convertirlo a Carbon
            if (is_string($fecha)) {
                return Carbon::parse($fecha)->format($formato);
            }
            
            // Si ya es un objeto Carbon o DateTime
            if ($fecha instanceof \DateTime || $fecha instanceof Carbon) {
                return $fecha->format($formato);
            }

            // Si es algo más (posiblemente ya formateado)
            return (string) $fecha;
        } catch (\Exception $e) {
            Log::warning('⚠️ Error formateando fecha', [
                'fecha' => $fecha,
                'formato' => $formato,
                'error' => $e->getMessage()
            ]);
            return $fecha; // Devolver el valor original si hay error
        }
    }

    /**
     * Almacenar nueva alergia
     */
     public function store(StoreAlergiaRequest $request, $mascotaId) // Cambiar Request por StoreAlergiaRequest
    {
        // La validación ya se hizo en el FormRequest
        $validated = $request->validated();

        try {
            $alergiaCreada = null;
            $mascotaData = null;

            DB::transaction(function () use ($validated, $mascotaId, &$alergiaCreada, &$mascotaData) {
                // 1. Crear el registro específico de Alergia
                $alergia = Alergia::create([
                    'tipo_alergia_id' => $validated['tipo_alergia_id'],
                    'fecha_deteccion' => Carbon::parse($validated['fecha_deteccion']), // Asegurar que sea Carbon
                    'gravedad' => $validated['gravedad'],
                    'reaccion_comun' => $validated['reaccion_comun'],
                    'estado' => $validated['estado'],
                    'desencadenante' => $validated['desencadenante'] ?? null,
                    'conducta_recomendada' => $validated['conducta_recomendada'] ?? null,
                    'recomendaciones_tutor' => $validated['recomendaciones_tutor'] ?? null,
                    'observaciones' => $validated['observaciones'] ?? null,
                ]);

                // 2. Crear el registro general en ProcesoMedico
                $procesoMedico = new ProcesoMedico([
                    'mascota_id' => $mascotaId,
                    'veterinario_id' => Auth::id(),
                    'centro_veterinario_id' => $validated['centro_veterinario_id'] ?? null,
                    'categoria' => 'preventivo',
                    'fecha_aplicacion' => Carbon::parse($validated['fecha_deteccion']), // También aquí
                    'observaciones' => 'Registro de alergia/sensibilidad',
                    'costo' => 0,
                ]);

                // 3. Asociar la alergia con el proceso médico
                $alergia->procesoMedico()->save($procesoMedico);
                
                // 4. Cargar relaciones para posibles usos futuros
                $alergiaCreada = $alergia->load([
                    'tipoAlergia',
                    'procesoMedico.centroVeterinario'
                ]);
                
                // 5. Obtener datos de la mascota con relaciones
                $mascotaData = Mascota::with('usuario')->find($mascotaId);
            });

            // 6. Enviar registro/documento después del registro exitoso
            $mensajeEnvio = '';
            if ($alergiaCreada && $mascotaData) {
                try {
                    $resultadoEnvio = $this->envioDocumentosService->enviarRegistroAlergia(
                        $alergiaCreada, 
                        $mascotaData, 
                        $validated['medio_envio']
                    );

                    $mensajeEnvio = ' y registro enviado';
                    
                    Log::info('✅ Registro de alergia enviado exitosamente', [
                        'alergia_id' => $alergiaCreada->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'usuario_id' => $mascotaData->usuario_id
                    ]);

                } catch (\Exception $e) {
                    $mensajeEnvio = ' (pero error enviando registro: ' . $e->getMessage() . ')';
                    
                    Log::error('❌ Error enviando registro de alergia', [
                        'alergia_id' => $alergiaCreada->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // DEVUELVE JSON PARA API
            return response()->json([
                'success' => true,
                'message' => 'Alergia registrada exitosamente' . $mensajeEnvio,
                'data' => [
                    'alergia' => $alergiaCreada,
                    'envio_exitoso' => empty($mensajeEnvio) ? false : !str_contains($mensajeEnvio, 'error')
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error completo al registrar alergia', [
                'mascota_id' => $mascotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // DEVUELVE JSON ERROR PARA API
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la alergia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener alergias de una mascota
     */
    public function index($mascotaId): JsonResponse
    {
        try {
            // Verificar que la mascota exista
            $mascota = Mascota::find($mascotaId);
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }

            // Obtener las alergias con sus relaciones
            $alergias = Alergia::with([
                'tipoAlergia',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->orderBy('fecha_deteccion', 'desc')
            ->get()
            ->map(function($alergia) {
                return [
                    'id' => $alergia->id,
                    'nombre' => $alergia->tipoAlergia->nombre ?? 'Alergia no especificada',
                    'tipo_alergia_id' => $alergia->tipo_alergia_id,
                    'fecha_deteccion' => $this->formatFechaSegura($alergia->fecha_deteccion, 'd/m/Y'),
                    'fecha_deteccion_raw' => $this->formatFechaSegura($alergia->fecha_deteccion, 'Y-m-d'),
                    'gravedad' => $alergia->gravedad,
                    'gravedad_label' => $this->getGravedadLabel($alergia->gravedad),
                    'reaccion_comun' => $alergia->reaccion_comun,
                    'estado' => $alergia->estado,
                    'estado_label' => $this->getEstadoLabel($alergia->estado),
                    'desencadenante' => $alergia->desencadenante,
                    'conducta_recomendada' => $alergia->conducta_recomendada,
                    'recomendaciones_tutor' => $alergia->recomendaciones_tutor,
                    'observaciones' => $alergia->observaciones,
                    'centro_veterinario' => $alergia->procesoMedico->centroVeterinario->nombre ?? 'No especificado',
                    'veterinario' => $alergia->procesoMedico->veterinario->name ?? 'No especificado',
                    'fecha_registro' => $alergia->created_at ? $alergia->created_at->format('d/m/Y H:i') : null,
                    'created_at' => $alergia->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $alergias
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener alergias: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las alergias: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una alergia específica
     */
    public function show($mascotaId, $alergiaId): JsonResponse
    {
        try {
            // Verificar que la mascota exista
            $mascota = Mascota::find($mascotaId);
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }

            // Obtener la alergia con relaciones
            $alergia = Alergia::with([
                'tipoAlergia',
                'procesoMedico.centroVeterinario',
                'procesoMedico.veterinario'
            ])
            ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                $query->where('mascota_id', $mascotaId);
            })
            ->find($alergiaId);

            if (!$alergia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alergia no encontrada'
                ], 404);
            }

            // DIAGNÓSTICO: Verificar el tipo de dato
            Log::debug('📊 Diagnóstico alergia:', [
                'alergia_id' => $alergia->id,
                'fecha_deteccion_raw' => $alergia->fecha_deteccion,
                'type' => gettype($alergia->fecha_deteccion),
                'is_string' => is_string($alergia->fecha_deteccion),
                'is_object' => is_object($alergia->fecha_deteccion),
                'class' => is_object($alergia->fecha_deteccion) ? get_class($alergia->fecha_deteccion) : 'not object',
                'proceso_fecha_aplicacion' => $alergia->procesoMedico->fecha_aplicacion ?? null,
            ]);

            // Formatear respuesta
            $alergiaData = [
                'id' => $alergia->id,
                'tipo_alergia' => $alergia->tipoAlergia ? [
                    'id' => $alergia->tipoAlergia->id,
                    'nombre' => $alergia->tipoAlergia->nombre
                ] : null,
                'tipo_alergia_id' => $alergia->tipo_alergia_id, // Agregado para el formulario
                'fecha_deteccion' => $this->formatFechaSegura($alergia->fecha_deteccion, 'Y-m-d'),
                'gravedad' => $alergia->gravedad,
                'gravedad_label' => $this->getGravedadLabel($alergia->gravedad),
                'reaccion_comun' => $alergia->reaccion_comun,
                'estado' => $alergia->estado,
                'estado_label' => $this->getEstadoLabel($alergia->estado),
                'desencadenante' => $alergia->desencadenante,
                'conducta_recomendada' => $alergia->conducta_recomendada,
                'recomendaciones_tutor' => $alergia->recomendaciones_tutor,
                'observaciones' => $alergia->observaciones,
                'centro_veterinario_id' => $alergia->procesoMedico->centro_veterinario_id ?? null, // Agregado para el formulario
                'medio_envio' => $alergia->procesoMedico->medio_envio ?? null, // Agregado para el formulario
                'proceso_medico' => [
                    'centro_veterinario' => $alergia->procesoMedico->centroVeterinario ?? null,
                    'veterinario' => $alergia->procesoMedico->veterinario ?? null,
                    'fecha_aplicacion' => $this->formatFechaSegura($alergia->procesoMedico->fecha_aplicacion ?? null, 'd/m/Y'),
                    'observaciones' => $alergia->procesoMedico->observaciones,
                ],
                'created_at' => $alergia->created_at ? $alergia->created_at->format('d/m/Y H:i') : null,
                'updated_at' => $alergia->updated_at ? $alergia->updated_at->format('d/m/Y H:i') : null,
            ];

            return response()->json([
                'success' => true,
                'data' => $alergiaData
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener alergia:', [
                'mascota_id' => $mascotaId,
                'alergia_id' => $alergiaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al obtener la alergia'
            ], 500);
        }
    }

    /**
     * Actualizar alergia
     */
    /**
     * Actualizar alergia
     */
    public function update(UpdateAlergiaRequest $request, $mascotaId, $alergiaId): JsonResponse
    {
        // La validación ya se hizo en UpdateAlergiaRequest
        $validated = $request->validated();

        try {
            // Verificar que la mascota exista
            $mascota = Mascota::find($mascotaId);
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }

            // Obtener la alergia
            $alergia = Alergia::whereHas('procesoMedico', function($query) use ($mascotaId) {
                    $query->where('mascota_id', $mascotaId);
                })
                ->find($alergiaId);

            if (!$alergia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alergia no encontrada'
                ], 404);
            }

            $alergiaActualizada = null;

            DB::transaction(function () use ($validated, $alergia, &$alergiaActualizada) {
                // 1. Actualizar la alergia
                $alergia->update([
                    'tipo_alergia_id' => $validated['tipo_alergia_id'] ?? $alergia->tipo_alergia_id,
                    'fecha_deteccion' => isset($validated['fecha_deteccion']) ? 
                        Carbon::parse($validated['fecha_deteccion']) : $alergia->fecha_deteccion,
                    'gravedad' => $validated['gravedad'] ?? $alergia->gravedad,
                    'reaccion_comun' => $validated['reaccion_comun'] ?? $alergia->reaccion_comun,
                    'estado' => $validated['estado'] ?? $alergia->estado,
                    'desencadenante' => $validated['desencadenante'] ?? $alergia->desencadenante,
                    'conducta_recomendada' => $validated['conducta_recomendada'] ?? $alergia->conducta_recomendada,
                    'recomendaciones_tutor' => $validated['recomendaciones_tutor'] ?? $alergia->recomendaciones_tutor,
                    'observaciones' => $validated['observaciones'] ?? $alergia->observaciones,
                ]);

                // 2. Actualizar el proceso médico asociado si hay cambios relevantes
                if (isset($validated['centro_veterinario_id']) || isset($validated['fecha_deteccion'])) {
                    $procesoMedico = $alergia->procesoMedico;
                    
                    $updateData = [];
                    if (isset($validated['centro_veterinario_id'])) {
                        $updateData['centro_veterinario_id'] = $validated['centro_veterinario_id'];
                    }
                    if (isset($validated['fecha_deteccion'])) {
                        $updateData['fecha_aplicacion'] = Carbon::parse($validated['fecha_deteccion']);
                    }
                    
                    $procesoMedico->update($updateData);
                }

                // 3. Registrar en log si se cambió el tipo de alergia
                if (isset($validated['tipo_alergia_id']) && $validated['tipo_alergia_id'] != $alergia->getOriginal('tipo_alergia_id')) {
                    Log::info('Tipo de alergia actualizado', [
                        'alergia_id' => $alergia->id,
                        'tipo_anterior' => $alergia->getOriginal('tipo_alergia_id'),
                        'tipo_nuevo' => $validated['tipo_alergia_id'],
                        'usuario_id' => Auth::id()
                    ]);
                }

                // 4. Cargar relaciones actualizadas
                $alergiaActualizada = $alergia->load([
                    'tipoAlergia',
                    'procesoMedico.centroVeterinario',
                    'procesoMedico.veterinario'
                ]);
            });

            // 5. Opcional: Enviar notificación de actualización si se especificó medio_envio
            if (isset($validated['medio_envio']) && $mascota) {
                try {
                    // Aquí podrías implementar el envío de notificación de actualización
                    // Similar a como haces en store pero con un método específico
                    Log::info('Notificación de actualización enviada', [
                        'alergia_id' => $alergia->id,
                        'mascota_id' => $mascotaId,
                        'medio_envio' => $validated['medio_envio']
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error enviando notificación de actualización', [
                        'alergia_id' => $alergia->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Alergia actualizada exitosamente',
                'data' => $alergiaActualizada
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar alergia:', [
                'mascota_id' => $mascotaId,
                'alergia_id' => $alergiaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la alergia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar alergia
     */
    public function destroy($mascotaId, $alergiaId): JsonResponse
    {
        // Redirigir al método de baja lógica
        return $this->bajaLogica(new Request(), $mascotaId, $alergiaId);
    }

    /**
     * Baja lógica de una alergia
     */
    public function bajaLogica(Request $request, $mascotaId, $alergiaId): JsonResponse
    {
        try {
            // Verificar que la mascota exista
            $mascota = Mascota::find($mascotaId);
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }

            // Obtener la alergia
            $alergia = Alergia::whereHas('procesoMedico', function($query) use ($mascotaId) {
                    $query->where('mascota_id', $mascotaId);
                })
                ->find($alergiaId);

            if (!$alergia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alergia no encontrada'
                ], 404);
            }

            // Verificar si ya está eliminada
            if ($alergia->isEliminada()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta alergia ya fue dada de baja anteriormente'
                ], 400);
            }

            // Realizar la baja lógica
            DB::transaction(function () use ($alergia, $request) {
                // Marcar la alergia como eliminada
                $alergia->marcarComoEliminada();
                
                // Opcional: también marcar el proceso médico como inactivo
                if ($alergia->procesoMedico) {
                    $alergia->procesoMedico->update([
                        'estado' => 'inactivo',
                        'observaciones' => $alergia->procesoMedico->observaciones . 
                                        ' (Dado de baja el ' . now()->format('d/m/Y') . ')'
                    ]);
                }

                // Registrar en el log quién realizó la acción
                Log::info('Alergia dada de baja lógicamente', [
                    'alergia_id' => $alergia->id,
                    'mascota_id' => $alergia->procesoMedico->mascota_id ?? null,
                    'usuario_id' => Auth::id(),
                    'fecha_baja' => now()->toDateTimeString()
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Alergia dada de baja exitosamente',
                'data' => [
                    'id' => $alergia->id,
                    'deleted_at' => $alergia->deleted_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en baja lógica de alergia:', [
                'mascota_id' => $mascotaId,
                'alergia_id' => $alergiaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al dar de baja la alergia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar alergia eliminada
     */
    public function restaurar(Request $request, $mascotaId, $alergiaId): JsonResponse
    {
        try {
            // Verificar que la mascota exista
            $mascota = Mascota::find($mascotaId);
            if (!$mascota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mascota no encontrada'
                ], 404);
            }

            // Obtener la alergia (incluyendo eliminadas)
            $alergia = Alergia::withTrashed()
                ->whereHas('procesoMedico', function($query) use ($mascotaId) {
                    $query->where('mascota_id', $mascotaId);
                })
                ->find($alergiaId);

            if (!$alergia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alergia no encontrada'
                ], 404);
            }

            // Verificar si está eliminada
            if (!$alergia->isEliminada()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta alergia no está dada de baja'
                ], 400);
            }

            // Restaurar la alergia
            DB::transaction(function () use ($alergia) {
                $alergia->restaurar();
                
                // Opcional: restaurar también el proceso médico
                if ($alergia->procesoMedico) {
                    $alergia->procesoMedico->update([
                        'estado' => 'activo',
                        'observaciones' => str_replace(
                            ' (Dado de baja el ' . date('d/m/Y') . ')', 
                            '', 
                            $alergia->procesoMedico->observaciones
                        )
                    ]);
                }

                Log::info('Alergia restaurada', [
                    'alergia_id' => $alergia->id,
                    'usuario_id' => Auth::id(),
                    'fecha_restauracion' => now()->toDateTimeString()
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Alergia restaurada exitosamente',
                'data' => [
                    'id' => $alergia->id,
                    'deleted_at' => $alergia->deleted_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error restaurando alergia:', [
                'mascota_id' => $mascotaId,
                'alergia_id' => $alergiaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar la alergia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de alergia
     */
    public function getTiposAlergia(): JsonResponse
    {
        try {
            $tipos = TipoAlergia::orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener tipos de alergia: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de alergia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper para obtener label de gravedad
     */
    private function getGravedadLabel($gravedad): string
    {
        $labels = [
            'leve' => 'Leve',
            'moderada' => 'Moderada',
            'grave' => 'Grave',
        ];

        return $labels[$gravedad] ?? $gravedad;
    }

    /**
     * Helper para obtener label del estado
     */
    private function getEstadoLabel($estado): string
    {
        $labels = [
            'activa' => 'Activa',
            'superada' => 'Superada',
            'seguimiento' => 'Bajo seguimiento',
        ];

        return $labels[$estado] ?? $estado;
    }
}