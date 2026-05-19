<?php

namespace App\Http\Controllers;

use App\Models\ProcesoAdopcion;
use App\Models\SolicitudAdopcion;
use App\Models\OfertaAdopcion;
use App\Models\User;
use App\Models\Usuario;
use App\Models\Notificacion;
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
    public function crearDesdeSolicitudAprobada($solicitudId, $tutorOriginalId = null)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            $usuarioAutenticadoId = $user->id ?? null; // User ID
            
            if (!$usuarioAutenticadoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado correctamente'
                ], 403);
            }

            // 1. Buscar la solicitud aprobada
            $solicitud = SolicitudAdopcion::with(['mascota', 'usuarioSolicitante'])
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

            // ✅ CORREGIDO: Obtener el USER ID del adoptante directamente
            // usuarioSolicitante ya es un User, no necesitas ->user
            $adoptanteUserId = $solicitud->usuarioSolicitante->id ?? null;
            
            if (!$adoptanteUserId) {
                Log::error('No se pudo obtener el User ID del adoptante', [
                    'solicitud_id' => $solicitudId,
                    'usuario_solicitante_id' => $solicitud->idUsuarioSolicitante ?? null
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener el ID de usuario del adoptante'
                ], 404);
            }

            // ✅ CORREGIDO: Obtener el User ID del tutor
            // Si se pasa tutorOriginalId, buscar el User ID correspondiente
            $tutorUserId = null;
            if ($tutorOriginalId) {
                // tutorOriginalId es el ID del Usuario (userable)
                $tutorUser = User::where('userable_id', $tutorOriginalId)
                    ->where('userable_type', 'App\\Models\\Usuario')
                    ->first();
                
                if ($tutorUser) {
                    $tutorUserId = $tutorUser->id;
                }
            }
            
            // Si no se encuentra, usar el ID del usuario autenticado
            if (!$tutorUserId) {
                $tutorUserId = $usuarioAutenticadoId;
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
            
            // 5. Crear el proceso de adopción
            $procesoData = [
                'id_oferta' => $oferta->id_oferta,
                'id_solicitud' => $solicitud->idSolicitud,
                'id_usuario_tutor' => $tutorUserId, // ✅ User ID del tutor
                'id_usuario_adoptante' => $adoptanteUserId, // ✅ User ID del adoptante
                'estado_proceso' => 'iniciado',
                'fecha_inicio' => now()
            ];
            
            $proceso = ProcesoAdopcion::create($procesoData);

            // Actualizar la transferencia con el ID del proceso
            $transferenciaReciente->update(['proceso_adopcion_id' => $proceso->id_proceso]);

            // ✅ Enviar notificación de proceso iniciado
            $this->enviarNotificacionProcesoIniciado($proceso);
            
            // 6. Registrar evento inicial
            $proceso->seguimientos()->create([
                'id_usuario' => $tutorUserId, // User ID
                'estado_anterior' => null,
                'estado_nuevo' => 'iniciado',
                'observaciones' => 'Proceso de adopción iniciado formalmente.',
                'tipo_evento' => 'inicio_proceso'
            ]);
            
            DB::commit();
            
            Log::info('✅ Proceso de adopción creado exitosamente', [
                'proceso_id' => $proceso->id_proceso,
                'tutor_user_id' => $tutorUserId,
                'adoptante_user_id' => $adoptanteUserId
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Proceso de adopción creado exitosamente',
                'data' => $proceso->load(['tutor', 'adoptante', 'oferta.mascota'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ Error al crear proceso de adopción', [
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
            $userId = $user->id; // ✅ User ID
            
            $query = ProcesoAdopcion::with([
                'oferta.mascota',
                'solicitud',
                'tutor',
                'adoptante'
            ]);
            
            // Filtrar por rol
            if ($request->has('rol')) {
                if ($request->rol === 'tutor') {
                    $query->where('id_usuario_tutor', $userId);
                } elseif ($request->rol === 'adoptante') {
                    $query->where('id_usuario_adoptante', $userId);
                }
            } else {
                // Mostrar todos los procesos donde el usuario participa
                $query->where(function($q) use ($userId) {
                    $q->where('id_usuario_tutor', $userId)
                      ->orWhere('id_usuario_adoptante', $userId);
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
                    'estadisticas' => $this->obtenerEstadisticas($userId)
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
            $userId = $user->id; // ✅ User ID
            
            $proceso = ProcesoAdopcion::with([
                'oferta.mascota.fotos',
                'solicitud',
                'tutor',
                'adoptante',
                'seguimientos.usuario'
            ])->findOrFail($id);
            
            // Verificar que el usuario tenga acceso
            if ($proceso->id_usuario_tutor !== $userId && 
                $proceso->id_usuario_adoptante !== $userId) {
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
            $userId = $user->id; // ✅ User ID
            
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
            if ($proceso->id_usuario_tutor !== $userId) {
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
            }
            
            $proceso->save();
            
            // Registrar seguimiento
            $proceso->seguimientos()->create([
                'id_usuario' => $userId, // ✅ User ID
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
            $userId = $user->id; // ✅ User ID
            
            $proceso = ProcesoAdopcion::findOrFail($id);
            
            // Verificar que el proceso está en estado "aprobado"
            if ($proceso->estado_proceso !== 'aprobado') {
                return response()->json([
                    'success' => false,
                    'message' => 'El proceso debe estar en estado "aprobado" para confirmar entrega'
                ], 400);
            }
            
            // Determinar qué confirmación actualizar
            if ($proceso->id_usuario_tutor === $userId) {
                $proceso->confirmacion_tutor = true;
                $tipoConfirmacion = 'tutor';
            } elseif ($proceso->id_usuario_adoptante === $userId) {
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
                'id_usuario' => $userId, // ✅ User ID
                'estado_anterior' => $proceso->estado_proceso,
                'estado_nuevo' => $proceso->estado_proceso,
                'observaciones' => "Confirmación de entrega realizada por el {$tipoConfirmacion}",
                'tipo_evento' => 'confirmacion_entrega',
                'datos_adicionales' => [
                    'tipo_confirmacion' => $tipoConfirmacion,
                    'fecha_confirmacion' => now()
                ]
            ]);
            
            // Verificar si ambas partes confirmaron
            $ambasConfirmaciones = $proceso->confirmacion_tutor && $proceso->confirmacion_adoptante;
            
            if ($ambasConfirmaciones && $proceso->estado_proceso === 'aprobado') {
                // Cambiar estado manualmente
                $proceso->estado_proceso = 'finalizado';
                $proceso->save();
                
                // Recargar relaciones antes de enviar notificaciones
                $proceso->load(['tutor', 'adoptante', 'oferta.mascota']);
                
                // Enviar notificaciones
                $this->enviarNotificacionAdopcionCompletada($proceso);
                
                // Registrar finalización
                $proceso->seguimientos()->create([
                    'id_usuario' => null,
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
                    'proceso' => $proceso->fresh(),
                    'fue_finalizado' => $proceso->estado_proceso === 'finalizado'
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al confirmar entrega', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la confirmación: ' . $e->getMessage()
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
            $userId = $user->id; // ✅ User ID
            
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
            if ($proceso->id_usuario_tutor !== $userId && 
                $proceso->id_usuario_adoptante !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para agregar seguimiento'
                ], 403);
            }
            
            $seguimiento = $proceso->seguimientos()->create([
                'id_usuario' => $userId, // ✅ User ID
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
            $userId = $user->id; // ✅ User ID
            
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
            if (($proceso->id_usuario_tutor !== $userId && 
                 $proceso->id_usuario_adoptante !== $userId) ||
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
                $this->enviarNotificacionAdopcionCompletada($proceso);
                
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

    /**
     * Enviar notificaciones de adopción completada a ambas partes
     */
    private function enviarNotificacionAdopcionCompletada(ProcesoAdopcion $proceso)
    {
        Log::info('🔔 Creando notificaciones de adopción completada', [
            'proceso_id' => $proceso->id_proceso,
            'tutor_user_id' => $proceso->id_usuario_tutor,
            'adoptante_user_id' => $proceso->id_usuario_adoptante
        ]);
        
        // Cargar relaciones necesarias
        $proceso->loadMissing(['tutor', 'adoptante', 'oferta.mascota']);
        
        // Verificar que existen los usuarios
        if (!$proceso->tutor) {
            Log::error('❌ Tutor no encontrado', ['tutor_user_id' => $proceso->id_usuario_tutor]);
            return false;
        }
        
        if (!$proceso->adoptante) {
            Log::error('❌ Adoptante no encontrado', ['adoptante_user_id' => $proceso->id_usuario_adoptante]);
            return false;
        }
        
        $mascota = $proceso->oferta->mascota ?? null;
        $nombreMascota = $mascota ? $mascota->nombre : 'la mascota';
        
        $titulo = "🎉 ¡Proceso de adopción completado!";
        
        DB::beginTransaction();
        
        try {
            // ✅ Notificación para el TUTOR (user_id es el ID del User)
            $notificacionTutor = Notificacion::create([
                'user_id' => $proceso->id_usuario_tutor,
                'tipo' => 'ADOPCION',
                'titulo' => $titulo,
                'contenido' => "Hola {$proceso->tutor->nombre},\n\n¡Felicitaciones! El proceso de adopción de **{$nombreMascota}** ha sido completado exitosamente.\n\nGracias por confiar en nuestra plataforma.",
                'origen' => 'SISTEMA',
                'referencia_tipo' => 'proceso_adopcion',
                'referencia_id' => $proceso->id_proceso,
                'leida' => false,
                'activa' => true
            ]);
            
            Log::info('✅ Notificación TUTOR creada', [
                'notificacion_id' => $notificacionTutor->id,
                'user_id' => $proceso->id_usuario_tutor
            ]);
            
            // ✅ Notificación para el ADOPTANTE (user_id es el ID del User)
            $notificacionAdoptante = Notificacion::create([
                'user_id' => $proceso->id_usuario_adoptante,
                'tipo' => 'ADOPCION',
                'titulo' => $titulo,
                'contenido' => "Hola {$proceso->adoptante->nombre},\n\n¡Felicitaciones! El proceso de adopción de **{$nombreMascota}** ha sido completado exitosamente.\n\n**{$nombreMascota}** ahora es oficialmente parte de tu familia.",
                'origen' => 'SISTEMA',
                'referencia_tipo' => 'proceso_adopcion',
                'referencia_id' => $proceso->id_proceso,
                'leida' => false,
                'activa' => true
            ]);
            
            Log::info('✅ Notificación ADOPTANTE creada', [
                'notificacion_id' => $notificacionAdoptante->id,
                'user_id' => $proceso->id_usuario_adoptante
            ]);
            
            DB::commit();
            
            Log::info('🎉 Ambas notificaciones creadas exitosamente', [
                'proceso_id' => $proceso->id_proceso
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ Error al crear notificaciones', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'proceso_id' => $proceso->id_proceso
            ]);
            
            return false;
        }
    }

    /**
     * Enviar notificaciones cuando se inicia el proceso de adopción
     */
    private function enviarNotificacionProcesoIniciado(ProcesoAdopcion $proceso)
    {
        Log::info('🔔 Enviando notificación de proceso iniciado', [
            'proceso_id' => $proceso->id_proceso,
            'tutor_user_id' => $proceso->id_usuario_tutor,
            'adoptante_user_id' => $proceso->id_usuario_adoptante
        ]);
        
        // Cargar relaciones necesarias
        $proceso->loadMissing(['tutor', 'adoptante', 'oferta.mascota']);
        
        // Verificar que existen los usuarios
        if (!$proceso->tutor) {
            Log::error('❌ Tutor no encontrado', ['tutor_user_id' => $proceso->id_usuario_tutor]);
            return false;
        }
        
        if (!$proceso->adoptante) {
            Log::error('❌ Adoptante no encontrado', ['adoptante_user_id' => $proceso->id_usuario_adoptante]);
            return false;
        }
        
        $mascota = $proceso->oferta->mascota ?? null;
        $nombreMascota = $mascota ? $mascota->nombre : 'la mascota';
        
        DB::beginTransaction();
        
        try {
            // ✅ Notificación para el TUTOR
            $notificacionTutor = Notificacion::create([
                'user_id' => $proceso->id_usuario_tutor,
                'tipo' => 'ADOPCION',
                'titulo' => '📋 Proceso de adopción iniciado',
                'contenido' => "Hola {$proceso->tutor->nombre},\n\nEl proceso de adopción de **{$nombreMascota}** ha sido iniciado. Ahora puedes coordinar los siguientes pasos con el adoptante a través del chat.",
                'origen' => 'SISTEMA',
                'referencia_tipo' => 'proceso_adopcion',
                'referencia_id' => $proceso->id_proceso,
                'leida' => false,
                'activa' => true
            ]);
            
            Log::info('✅ Notificación TUTOR creada (inicio proceso)', [
                'notificacion_id' => $notificacionTutor->id
            ]);
            
            // ✅ Notificación para el ADOPTANTE
            $notificacionAdoptante = Notificacion::create([
                'user_id' => $proceso->id_usuario_adoptante,
                'tipo' => 'ADOPCION',
                'titulo' => '📋 ¡Tu solicitud fue aprobada!',
                'contenido' => "Hola {$proceso->adoptante->nombre},\n\n¡Felicitaciones! Tu solicitud para adoptar a **{$nombreMascota}** ha sido aprobada. Se ha iniciado el proceso de adopción. El tutor se comunicará contigo para coordinar los siguientes pasos.",
                'origen' => 'SISTEMA',
                'referencia_tipo' => 'proceso_adopcion',
                'referencia_id' => $proceso->id_proceso,
                'leida' => false,
                'activa' => true
            ]);
            
            Log::info('✅ Notificación ADOPTANTE creada (inicio proceso)', [
                'notificacion_id' => $notificacionAdoptante->id
            ]);
            
            DB::commit();
            
            Log::info('🎉 Notificaciones de inicio de proceso creadas exitosamente', [
                'proceso_id' => $proceso->id_proceso
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ Error al crear notificaciones de inicio', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'proceso_id' => $proceso->id_proceso
            ]);
            
            return false;
        }
    }
}