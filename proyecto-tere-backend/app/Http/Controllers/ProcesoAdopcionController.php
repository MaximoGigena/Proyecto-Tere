<?php

namespace App\Http\Controllers;

use App\Models\ProcesoAdopcion;
use App\Models\SolicitudAdopcion;
use App\Models\OfertaAdopcion;
use App\Models\Usuario;
use App\Models\AdopcionCompletada;
use App\Models\HistorialTransferenciaMascota;
use App\Models\Mascota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcesoAdopcionController extends Controller
{
    /**
     * Crear proceso de adopción al aprobar una solicitud
     */
    public function crearDesdeSolicitudAprobada($solicitudId)
    {
        DB::beginTransaction();
        
        try {
            // 1. Buscar la solicitud aprobada
            $solicitud = SolicitudAdopcion::with(['mascota', 'usuario'])
                ->where('idSolicitud', $solicitudId)
                ->where('estadoSolicitud', 'aprobada')
                ->first();
            
            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud aprobada no encontrada'
                ], 404);
            }
            
            // 2. Buscar la transferencia reciente
            $transferenciaReciente = HistorialTransferenciaMascota::where('solicitud_adopcion_id', $solicitudId)
                ->latest()
                ->first();
            
            if (!$transferenciaReciente) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el historial de transferencia para esta solicitud'
                ], 404);
            }
            
            // 3. Buscar la oferta relacionada
            $oferta = OfertaAdopcion::where('id_mascota', $solicitud->idMascota)
                ->where('estado_oferta', 'cerrada')
                ->first();
            
            if (!$oferta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la oferta de adopción relacionada'
                ], 404);
            }
            
            // 4. Verificar que no existe ya un proceso activo
            $procesoExistente = ProcesoAdopcion::where('id_solicitud', $solicitudId)
                ->whereNotIn('estado_proceso', ['finalizado', 'rechazado', 'cancelado'])
                ->exists();
            
            if ($procesoExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un proceso de adopción activo para esta solicitud'
                ], 409);
            }
            
            // 5. Crear el proceso de adopción (SIN notas_tutor si la columna no existe)
            $procesoData = [
                'id_oferta' => $oferta->id_oferta,
                'id_solicitud' => $solicitud->idSolicitud,
                'id_usuario_tutor' => $transferenciaReciente->tutor_anterior_id,
                'id_usuario_adoptante' => $solicitud->idUsuarioSolicitante,
                'estado_proceso' => 'iniciado',
                'fecha_inicio' => now()
            ];
            
            // Agregar notas_tutor solo si existe la columna
            // Puedes verificar si la columna existe o simplemente omitirla
            $proceso = ProcesoAdopcion::create($procesoData);

            // ✅ ACTUALIZAR LA TRANSFERENCIA CON EL ID DEL PROCESO
            $transferenciaReciente->update(['proceso_adopcion_id' => $proceso->id_proceso]);
            
            // 6. Registrar evento inicial
            $proceso->seguimientos()->create([
                'id_usuario' => $transferenciaReciente->tutor_anterior_id,
                'estado_anterior' => null,
                'estado_nuevo' => 'iniciado',
                'observaciones' => 'Proceso de adopción iniciado formalmente.',
                'tipo_evento' => 'inicio_proceso'
            ]);
            
            DB::commit();
            
            Log::info('Proceso de adopción creado', [
                'proceso_id' => $proceso->id_proceso,
                'solicitud_id' => $solicitudId,
                'tutor_id' => $transferenciaReciente->tutor_anterior_id,
                'adoptante_id' => $solicitud->idUsuarioSolicitante
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Proceso de adopción creado exitosamente',
                'data' => [
                    'proceso' => $proceso,
                    'solicitud' => $solicitud,
                    'oferta' => $oferta,
                    'mascota' => $solicitud->mascota->only(['id', 'nombre', 'especie'])
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al crear proceso de adopción', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el proceso de adopción',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }
    
    /**
     * Obtener procesos del usuario (tutor o adoptante)
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            $query = ProcesoAdopcion::with([
                'oferta.mascota',
                'solicitud',
                'tutor',
                'adoptante'
            ]);
            
            // Filtrar por rol
            if ($request->has('rol')) {
                if ($request->rol === 'tutor') {
                    $query->where('id_usuario_tutor', $user->id);
                } elseif ($request->rol === 'adoptante') {
                    $query->where('id_usuario_adoptante', $user->id);
                }
            } else {
                // Mostrar todos los procesos donde el usuario participa
                $query->where(function($q) use ($user) {
                    $q->where('id_usuario_tutor', $user->id)
                      ->orWhere('id_usuario_adoptante', $user->id);
                });
            }
            
            // Filtrar por estado
            if ($request->has('estado')) {
                $query->where('estado_proceso', $request->estado);
            }
            
            // Ordenar por fecha más reciente
            $procesos = $query->orderBy('created_at', 'desc')->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'procesos' => $procesos,
                    'estadisticas' => $this->obtenerEstadisticas($user->id)
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener procesos de adopción', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener procesos de adopción'
            ], 500);
        }
    }
    
    /**
     * Obtener un proceso específico
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            
            $proceso = ProcesoAdopcion::with([
                'oferta.mascota.fotos',
                'solicitud',
                'tutor',
                'adoptante',
                'seguimientos.usuario'
            ])->findOrFail($id);
            
            // Verificar que el usuario tenga acceso
            if ($proceso->id_usuario_tutor !== $user->id && 
                $proceso->id_usuario_adoptante !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para ver este proceso'
                ], 403);
            }
            
            return response()->json([
                'success' => true,
                'data' => $proceso
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Proceso no encontrado'
            ], 404);
        }
    }
    
    /**
     * Actualizar estado del proceso
     */
    public function actualizarEstado(Request $request, $id)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'estado_proceso' => 'required|in:entrevista,evaluacion,aprobado,rechazado,cancelado,finalizado',
                'observaciones' => 'nullable|string|max:1000',
                'motivo_rechazo' => 'required_if:estado_proceso,rechazado,cancelado|string|max:500'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $proceso = ProcesoAdopcion::findOrFail($id);
            
            // Verificar permisos (solo tutor puede cambiar estados)
            if ($proceso->id_usuario_tutor !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo el tutor puede cambiar el estado del proceso'
                ], 403);
            }
            
            // Validar flujo de estados
            if (!$proceso->puedeAvanzar($request->estado_proceso)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transición de estado no permitida',
                    'estado_actual' => $proceso->estado_proceso,
                    'estado_solicitado' => $request->estado_proceso
                ], 400);
            }
            
            $estadoAnterior = $proceso->estado_proceso;
            
            // Actualizar estado
            $proceso->estado_proceso = $request->estado_proceso;
            
            // Guardar motivo si es rechazo o cancelación
            if (in_array($request->estado_proceso, ['rechazado', 'cancelado'])) {
                $proceso->motivo_rechazo = $request->motivo_rechazo;
                
                // Si se rechaza o cancela DESPUÉS de haber aprobado, revertir transferencia
               // if ($estadoAnterior === 'aprobado') {
                 //   $this->revertirTransferencia($proceso);
                //}
            }
            
            // ✅ REMOVER esta línea - La mascota ya fue transferida al aprobar la solicitud
            // if ($request->estado_proceso === 'aprobado') {
            //     $this->transferirMascota($proceso);
            // }
            
            $proceso->save();
            
            // Registrar seguimiento
            $proceso->seguimientos()->create([
                'id_usuario' => $user->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $request->estado_proceso,
                'observaciones' => $request->observaciones,
                'tipo_evento' => 'cambio_estado',
                'datos_adicionales' => [
                    'motivo' => $request->motivo_rechazo ?? null,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Estado del proceso actualizado exitosamente',
                'data' => $proceso
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al actualizar estado del proceso', [
                'error' => $e->getMessage(),
                'proceso_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado'
            ], 500);
        }
    }
        
    /**
     * Confirmar entrega/recepción
     */
    public function confirmarEntrega(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            $proceso = ProcesoAdopcion::findOrFail($id);
            
            // Verificar que el proceso está en estado "aprobado"
            if ($proceso->estado_proceso !== 'aprobado') {
                return response()->json([
                    'success' => false,
                    'message' => 'El proceso debe estar en estado "aprobado" para confirmar entrega'
                ], 400);
            }
            
            // Determinar qué confirmación actualizar
            if ($proceso->id_usuario_tutor === $user->id) {
                $proceso->confirmacion_tutor = true;
                $tipoConfirmacion = 'tutor';
            } elseif ($proceso->id_usuario_adoptante === $user->id) {
                $proceso->confirmacion_adoptante = true;
                $tipoConfirmacion = 'adoptante';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para confirmar'
                ], 403);
            }
            
            $proceso->save();
            
            // Registrar evento
            $proceso->seguimientos()->create([
                'id_usuario' => $user->id,
                'estado_anterior' => $proceso->estado_proceso,
                'estado_nuevo' => $proceso->estado_proceso,
                'observaciones' => "Confirmación de entrega realizada por el {$tipoConfirmacion}",
                'tipo_evento' => 'confirmacion_entrega',
                'datos_adicionales' => [
                    'tipo_confirmacion' => $tipoConfirmacion,
                    'fecha_confirmacion' => now()
                ]
            ]);
            
            // Intentar finalizar si ambas partes confirmaron
            if ($proceso->intentarFinalizar()) {
                // Registrar finalización automática
                $proceso->seguimientos()->create([
                    'id_usuario' => null, // Sistema
                    'estado_anterior' => 'aprobado',
                    'estado_nuevo' => 'finalizado',
                    'observaciones' => 'Proceso finalizado automáticamente tras confirmaciones de ambas partes',
                    'tipo_evento' => 'finalizacion_automatica'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Confirmación registrada exitosamente',
                'data' => [
                    'proceso' => $proceso,
                    'fue_finalizado' => $proceso->estado_proceso === 'finalizado'
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al confirmar entrega', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la confirmación'
            ], 500);
        }
    }
    
    /**
     * Agregar seguimiento al proceso
     */
    public function agregarSeguimiento(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'observaciones' => 'required|string|max:1000',
                'tipo_evento' => 'required|in:comunicacion,visita,documentacion,acuerdo,incidencia,otro'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $proceso = ProcesoAdopcion::findOrFail($id);
            
            // Verificar que el usuario participa en el proceso
            if ($proceso->id_usuario_tutor !== $user->id && 
                $proceso->id_usuario_adoptante !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para agregar seguimiento'
                ], 403);
            }
            
            $seguimiento = $proceso->seguimientos()->create([
                'id_usuario' => $user->id,
                'estado_anterior' => $proceso->estado_proceso,
                'estado_nuevo' => $proceso->estado_proceso,
                'observaciones' => $request->observaciones,
                'tipo_evento' => $request->tipo_evento,
                'datos_adicionales' => [
                    'fecha_evento' => $request->fecha_evento ?? now(),
                    'ubicacion' => $request->ubicacion ?? null,
                    'participantes' => $request->participantes ?? null
                ]
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Seguimiento agregado exitosamente',
                'data' => $seguimiento
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al agregar seguimiento', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar seguimiento'
            ], 500);
        }
    }
    
    /**
     * Finalizar proceso con evaluación
     */
    public function finalizarConEvaluacion(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'puntuacion_experiencia' => 'required|integer|min:1|max:5',
                'comentario_final' => 'nullable|string|max:2000'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $proceso = ProcesoAdopcion::findOrFail($id);
            
            // Solo puede finalizar si es participante y está aprobado
            if (($proceso->id_usuario_tutor !== $user->id && 
                 $proceso->id_usuario_adoptante !== $user->id) ||
                $proceso->estado_proceso !== 'aprobado') {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado o estado inválido para finalizar'
                ], 403);
            }
            
            // Actualizar evaluación
            $proceso->puntuacion_experiencia = $request->puntuacion_experiencia;
            $proceso->comentario_final = $request->comentario_final;
            
            // Si ya confirmó su parte, marcar como finalizado
            if ($proceso->intentarFinalizar()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Proceso finalizado exitosamente',
                    'data' => $proceso
                ]);
            }
            
            $proceso->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Evaluación registrada. Esperando confirmación de la otra parte.',
                'data' => $proceso
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al finalizar proceso', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar el proceso'
            ], 500);
        }
    }
    
    /**
     * Métodos privados auxiliares
     */
    private function transferirMascota(ProcesoAdopcion $proceso)
    {
        DB::beginTransaction();
        
        try {
            $mascota = Mascota::find($proceso->oferta->id_mascota);
            
            if (!$mascota) {
                throw new \Exception('Mascota no encontrada para transferir');
            }
            
            // Verificar que el tutor actual es quien dice ser
            if ($mascota->usuario_id !== $proceso->id_usuario_tutor) {
                throw new \Exception('El usuario no es el tutor actual de la mascota');
            }
            
            // Verificar que el adoptante existe
            $adoptante = Usuario::find($proceso->id_usuario_adoptante);
            if (!$adoptante) {
                throw new \Exception('Usuario adoptante no encontrado');
            }
            
            // Guardar historial detallado
            $historial = [
                'tutor_anterior' => $mascota->usuario_id,
                'tutor_anterior_nombre' => $proceso->tutor->nombre ?? 'Desconocido',
                'tutor_nuevo' => $proceso->id_usuario_adoptante,
                'tutor_nuevo_nombre' => $adoptante->nombre ?? 'Desconocido',
                'fecha_transferencia' => now(),
                'proceso_adopcion_id' => $proceso->id_proceso,
                'solicitud_adopcion_id' => $proceso->id_solicitud,
                'oferta_adopcion_id' => $proceso->id_oferta,
                'motivo' => 'Transferencia por proceso de adopción aprobado'
            ];
            
            // ✅ TRANSFERIR PROPIEDAD DE LA MASCOTA
            $mascota->usuario_id = $proceso->id_usuario_adoptante;
            
            // Guardar historial en JSON
            $historialTransiciones = json_decode($mascota->historial_transiciones ?? '[]', true);
            $historialTransiciones[] = $historial;
            $mascota->historial_transiciones = json_encode($historialTransiciones);
            
            // Cambiar estado de la mascota
            $mascota->estado_adopcion = 'transferida';
            $mascota->fecha_adopcion = now();
            
            // ✅ GUARDAR CAMBIOS
            $mascota->save();
            
            // Opcional: crear un registro de adopción completada
            AdopcionCompletada::create([
                'mascota_id' => $mascota->id,
                'tutor_anterior_id' => $proceso->id_usuario_tutor,
                'tutor_nuevo_id' => $proceso->id_usuario_adoptante,
                'proceso_adopcion_id' => $proceso->id_proceso,
                'fecha_adopcion' => now(),
                'estado' => 'completada'
            ]);
            
            DB::commit();
            
            Log::info('Mascota transferida exitosamente en proceso de adopción', [
                'mascota_id' => $mascota->id,
                'nombre_mascota' => $mascota->nombre,
                'tutor_anterior' => $historial['tutor_anterior'],
                'tutor_nuevo' => $mascota->usuario_id,
                'proceso_id' => $proceso->id_proceso
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al transferir mascota', [
                'error' => $e->getMessage(),
                'proceso_id' => $proceso->id_proceso,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Re-lanzar la excepción para manejarla arriba
        }
    }
    
    private function obtenerEstadisticas($userId)
    {
        return [
            'total' => ProcesoAdopcion::where('id_usuario_tutor', $userId)
                ->orWhere('id_usuario_adoptante', $userId)
                ->count(),
            'activos' => ProcesoAdopcion::whereIn('estado_proceso', ['iniciado', 'entrevista', 'evaluacion', 'aprobado'])
                ->where(function($q) use ($userId) {
                    $q->where('id_usuario_tutor', $userId)
                      ->orWhere('id_usuario_adoptante', $userId);
                })
                ->count(),
            'finalizados' => ProcesoAdopcion::where('estado_proceso', 'finalizado')
                ->where(function($q) use ($userId) {
                    $q->where('id_usuario_tutor', $userId)
                      ->orWhere('id_usuario_adoptante', $userId);
                })
                ->count(),
            'como_tutor' => ProcesoAdopcion::where('id_usuario_tutor', $userId)->count(),
            'como_adoptante' => ProcesoAdopcion::where('id_usuario_adoptante', $userId)->count()
        ];
    }
}