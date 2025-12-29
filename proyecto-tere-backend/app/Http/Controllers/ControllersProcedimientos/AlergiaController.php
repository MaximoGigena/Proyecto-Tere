<?php

namespace App\Http\Controllers\ControllersProcedimientos;

use App\Models\ProcedimientosMedicos\Alergia;
use App\Models\ProcesoMedico;
use App\Models\Mascota;
use App\Models\TiposProcedimientos\TipoAlergia;
use App\Models\CentroVeterinario;
use App\Models\ContactoUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\EnvioDocumentosService;

class AlergiaController extends Controller
{
    protected $envioDocumentosService;

    public function __construct(EnvioDocumentosService $envioDocumentosService)
    {
        $this->envioDocumentosService = $envioDocumentosService;
    }

    /**
     * Almacenar nueva alergia
     */
    public function store(Request $request, $mascotaId)
    {
        // Validación según los campos del formulario
        $validated = $request->validate([
            'tipo_alergia_id' => 'required|exists:tipos_alergia,id',
            'fecha_deteccion' => 'required|date',
            'gravedad' => 'required|in:leve,moderada,grave',
            'reaccion_comun' => 'required|string|max:255',
            'estado' => 'required|in:activa,superada,seguimiento',
            'desencadenante' => 'nullable|string|max:255',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'conducta_recomendada' => 'nullable|string',
            'recomendaciones_tutor' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'medio_envio' => 'required|in:email,telegram,whatsapp',
        ]);

        try {
            $alergiaCreada = null;
            $mascotaData = null;

            DB::transaction(function () use ($validated, $mascotaId, &$alergiaCreada, &$mascotaData) {
                // 1. Crear el registro específico de Alergia
                $alergia = Alergia::create([
                    'tipo_alergia_id' => $validated['tipo_alergia_id'],
                    'fecha_deteccion' => $validated['fecha_deteccion'],
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
                    'fecha_aplicacion' => $validated['fecha_deteccion'],
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
                    'fecha_deteccion' => $alergia->fecha_deteccion ? $alergia->fecha_deteccion->format('d/m/Y') : null,
                    'fecha_deteccion_raw' => $alergia->fecha_deteccion ? $alergia->fecha_deteccion->format('Y-m-d') : null,
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
                    'fecha_registro' => $alergia->created_at->format('d/m/Y H:i'),
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

            // Formatear respuesta
            $alergiaData = [
                'id' => $alergia->id,
                'tipo_alergia' => $alergia->tipoAlergia ? [
                    'id' => $alergia->tipoAlergia->id,
                    'nombre' => $alergia->tipoAlergia->nombre
                ] : null,
                'fecha_deteccion' => $alergia->fecha_deteccion ? $alergia->fecha_deteccion->format('Y-m-d') : null,
                'gravedad' => $alergia->gravedad,
                'gravedad_label' => $this->getGravedadLabel($alergia->gravedad),
                'reaccion_comun' => $alergia->reaccion_comun,
                'estado' => $alergia->estado,
                'estado_label' => $this->getEstadoLabel($alergia->estado),
                'desencadenante' => $alergia->desencadenante,
                'conducta_recomendada' => $alergia->conducta_recomendada,
                'recomendaciones_tutor' => $alergia->recomendaciones_tutor,
                'observaciones' => $alergia->observaciones,
                'proceso_medico' => [
                    'centro_veterinario' => $alergia->procesoMedico->centroVeterinario ?? null,
                    'veterinario' => $alergia->procesoMedico->veterinario ?? null,
                    'fecha_aplicacion' => $alergia->procesoMedico->fecha_aplicacion ? 
                        $alergia->procesoMedico->fecha_aplicacion->format('d/m/Y') : null,
                    'observaciones' => $alergia->procesoMedico->observaciones,
                ],
                'created_at' => $alergia->created_at->format('d/m/Y H:i'),
                'updated_at' => $alergia->updated_at->format('d/m/Y H:i'),
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
    public function update(Request $request, $mascotaId, $alergiaId): JsonResponse
    {
        // Validación
        $validated = $request->validate([
            'tipo_alergia_id' => 'sometimes|exists:tipos_alergia,id',
            'fecha_deteccion' => 'sometimes|date',
            'gravedad' => 'sometimes|in:leve,moderada,grave',
            'reaccion_comun' => 'sometimes|string|max:255',
            'estado' => 'sometimes|in:activa,superada,seguimiento',
            'desencadenante' => 'nullable|string|max:255',
            'centro_veterinario_id' => 'nullable|exists:centros_veterinarios,id',
            'conducta_recomendada' => 'nullable|string',
            'recomendaciones_tutor' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

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
                    'fecha_deteccion' => $validated['fecha_deteccion'] ?? $alergia->fecha_deteccion,
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
                        $updateData['fecha_aplicacion'] = $validated['fecha_deteccion'];
                    }
                    
                    $procesoMedico->update($updateData);
                }

                // 3. Cargar relaciones actualizadas
                $alergiaActualizada = $alergia->load([
                    'tipoAlergia',
                    'procesoMedico.centroVeterinario',
                    'procesoMedico.veterinario'
                ]);
            });

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

            DB::transaction(function () use ($alergia) {
                // Eliminar el proceso médico (esto eliminará la alergia por cascade)
                $alergia->procesoMedico->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Alergia eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar alergia:', [
                'mascota_id' => $mascotaId,
                'alergia_id' => $alergiaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la alergia: ' . $e->getMessage()
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