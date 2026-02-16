<?php
namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Mensaje;
use App\Models\SolicitudAdopcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Obtener todos los chats del usuario
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 20);
            
            Log::info('Obteniendo chats para usuario: ' . $user->id);
            
            // Obtener chats con información del otro usuario
            $chats = Chat::chatsUsuario($user->id)
                ->paginate($perPage);

            Log::info('Total chats encontrados: ' . $chats->count());

            // Formatear respuesta
            $chatsFormateados = $chats->map(function($chat) use ($user) {
                $otroUsuario = $chat->otroUsuario($user->id);
                $userable = $otroUsuario->userable ?? null;
                $ultimoMensaje = $chat->ultimoMensaje;
                $mensajesNoLeidos = $chat->mensajesNoLeidos($user->id);

                // Obtener información del usuario
                $nombre = 'Usuario';
                $fotoUrl = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png';
                
                if ($userable instanceof \App\Models\Usuario) {
                    $nombre = $userable->nombre ?? $otroUsuario->name ?? 'Usuario';
                    
                    if ($userable->fotos && $userable->fotos->isNotEmpty()) {
                        $foto = $userable->fotos->first();
                        $fotoUrl = asset('storage/' . $foto->ruta_foto);
                    } elseif ($userable->foto_perfil) {
                        $fotoUrl = asset('storage/' . $userable->foto_perfil);
                    }
                } else {
                    $nombre = $otroUsuario->name ?? 'Usuario';
                }

                // Obtener información de la solicitud si existe
                $mascotaNombre = null;
                if ($chat->solicitud_id && $chat->solicitud) {
                    $mascotaNombre = $chat->solicitud->mascota->nombre ?? null;
                }

                return [
                    'chat_id' => $chat->chat_id,
                    'usuario_id' => $otroUsuario->id,
                    'nombre' => $nombre,
                    'img' => $fotoUrl,
                    'ultimo_mensaje' => $ultimoMensaje->contenido ?? null,
                    'ultimo_mensaje_en' => $ultimoMensaje->created_at ?? null,
                    'mensajes_no_leidos' => $mensajesNoLeidos,
                    'solicitud_id' => $chat->solicitud_id,
                    'mascota_nombre' => $mascotaNombre,
                    'online' => false, // Aquí implementarías lógica de presencia
                    'favorito' => false // Implementar si es necesario
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'chats' => $chatsFormateados,
                    'total' => $chats->total(),
                    'per_page' => $chats->perPage(),
                    'current_page' => $chats->currentPage(),
                    'last_page' => $chats->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener chats:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener chats',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Crear o obtener un chat entre usuarios
     */
    public function crearObtenerChat(Request $request)
    {
        try {
            $user = Auth::user();
            
            Log::info('INICIANDO CREACIÓN DE CHAT', [
                'usuario_autenticado_id' => $user->id,
                'usuario_destino_id' => $request->user_id,
                'solicitud_id' => $request->solicitud_id
            ]);
            
            $validator = validator($request->all(), [
                'user_id' => 'required|exists:users,id',
                'solicitud_id' => 'nullable|exists:solicitudes_adopcion,idSolicitud'
            ]);

            if ($validator->fails()) {
                Log::error('Error de validación', ['errors' => $validator->errors()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // No permitir chat consigo mismo
            if ($user->id == $request->user_id) {
                Log::warning('Intento de chat consigo mismo', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes crear un chat contigo mismo'
                ], 400);
            }

            // Verificar permisos para la solicitud
            if ($request->solicitud_id) {
                // CARGAR RELACIONES CORRECTAMENTE
                $solicitud = SolicitudAdopcion::with([
                    'mascota.usuario.user',
                    'mascota.user'
                ])->find($request->solicitud_id);
                
                if (!$solicitud) {
                    Log::error('Solicitud no encontrada', ['solicitud_id' => $request->solicitud_id]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Solicitud no encontrada'
                    ], 404);
                }

                Log::info('Solicitud encontrada', [
                    'solicitud' => [
                        'id' => $solicitud->idSolicitud,
                        'idUsuarioSolicitante' => $solicitud->idUsuarioSolicitante,
                        'idMascota' => $solicitud->idMascota,
                        'estado' => $solicitud->estadoSolicitud
                    ]
                ]);

                // Obtener el User ID del dueño de la mascota
                $usuarioDuenoMascota = null;
                
                if ($solicitud->mascota) {
                    Log::info('DEBUG MASCOTA', [
                        'mascota_id' => $solicitud->mascota->id,
                        'mascota_usuario_id' => $solicitud->mascota->usuario_id,
                        'tiene_relacion_usuario' => !empty($solicitud->mascota->usuario),
                        'tiene_relacion_user' => !empty($solicitud->mascota->user)
                    ]);

                    // Cambia a esto (verificando correctamente):
                    if ($solicitud->mascota->usuario && $solicitud->mascota->usuario->user) {
                        // Aquí obtenemos el User ID del dueño
                        $usuarioDuenoMascota = $solicitud->mascota->usuario->user->id;
                        Log::info('✅ Dueño desde usuario->user', ['user_id' => $usuarioDuenoMascota]);
                    } elseif ($solicitud->mascota->user) {
                        // Si hay relación directa con user
                        $usuarioDuenoMascota = $solicitud->mascota->user->id;
                        Log::info('✅ Dueño desde relación user directa', ['user_id' => $usuarioDuenoMascota]);
                    } else {
                        // Si no hay relaciones, intentamos convertir usuario_id a user_id
                        // Esto depende de cómo estén relacionados tus modelos
                        Log::warning('⚠️ Sin relaciones claras, intentando inferir dueño');
                        
                        // Opción 1: Si usuario_id es el user_id
                        $usuarioDuenoMascota = $solicitud->mascota->usuario_id;
                        
                        // O Opción 2: Buscar el User correspondiente al Usuario
                        $usuarioDueno = \App\Models\Usuario::find($solicitud->mascota->usuario_id);
                        if ($usuarioDueno && $usuarioDueno->user) {
                            $usuarioDuenoMascota = $usuarioDueno->user->id;
                        }
                    }
                    
                    Log::info('RESUMEN DUEÑO', [
                        'dueño_user_id' => $usuarioDuenoMascota,
                        'usuario_autenticado_id' => $user->id,
                        'es_el_dueño' => $usuarioDuenoMascota == $user->id
                    ]);
                } else {
                    Log::warning('Solicitud no tiene mascota cargada', ['idMascota' => $solicitud->idMascota]);
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo encontrar la mascota asociada'
                    ], 404);
                }

                // En el método crearObtenerChat(), después de cargar la solicitud:

                Log::info('🔍 DIAGNÓSTICO COMPLETO', [
                    'usuario_autenticado' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                        'userable_type' => $user->userable_type,
                        'userable_id' => $user->userable_id
                    ],
                    'solicitud' => [
                        'id' => $solicitud->idSolicitud,
                        'idMascota' => $solicitud->idMascota,
                        'idUsuarioSolicitante' => $solicitud->idUsuarioSolicitante,
                        'solicitante_user_email' => \App\Models\User::find($solicitud->idUsuarioSolicitante)->email ?? 'No encontrado'
                    ],
                    'mascota' => [
                        'id' => $solicitud->mascota->id ?? null,
                        'nombre' => $solicitud->mascota->nombre ?? null,
                        'usuario_id' => $solicitud->mascota->usuario_id ?? null,
                    ],
                    'relaciones' => [
                        'mascota_usuario' => $solicitud->mascota->usuario ? [
                            'id' => $solicitud->mascota->usuario->id,
                            'nombre' => $solicitud->mascota->usuario->nombre,
                            'user_id' => $solicitud->mascota->usuario->user_id ?? 'No tiene'
                        ] : null,
                        'mascota_user' => $solicitud->mascota->user ? [
                            'id' => $solicitud->mascota->user->id,
                            'email' => $solicitud->mascota->user->email,
                            'name' => $solicitud->mascota->user->name
                        ] : null
                    ],
                    'comparacion' => [
                        'autenticado_vs_dueño_user' => $user->id . ' == ' . ($solicitud->mascota->user->id ?? 'null'),
                        'son_iguales' => $user->id == ($solicitud->mascota->user->id ?? null),
                        'autenticado_vs_usuario_id' => $user->id . ' == ' . $solicitud->mascota->usuario_id,
                        'son_iguales_2' => $user->id == $solicitud->mascota->usuario_id
                    ]
                ]);

                // VERIFICACIÓN PRINCIPAL: El usuario autenticado debe ser el dueño
                if ($usuarioDuenoMascota != $user->id) {
                    Log::error('PERMISO DENEGADO: Usuario no es el dueño', [
                        'usuario_autenticado' => $user->id,
                        'dueño_mascota' => $usuarioDuenoMascota,
                        'comparacion' => 'DIFERENTES'
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Solo el dueño de la mascota puede iniciar el chat',
                        'debug' => [
                            'usuario_actual' => $user->id,
                            'dueño_mascota' => $usuarioDuenoMascota,
                            'solicitante' => $solicitud->idUsuarioSolicitante
                        ]
                    ], 403);
                }

                // VERIFICACIÓN: El usuario destino debe ser el solicitante
                if ($request->user_id != $solicitud->idUsuarioSolicitante) {
                    Log::error('PERMISO DENEGADO: Chat solo con solicitante', [
                        'usuario_destino' => $request->user_id,
                        'solicitante' => $solicitud->idUsuarioSolicitante,
                        'comparacion' => 'DIFERENTES'
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'El chat solo puede ser iniciado con el solicitante de la adopción',
                        'debug' => [
                            'usuario_destino' => $request->user_id,
                            'solicitante' => $solicitud->idUsuarioSolicitante
                        ]
                    ], 403);
                }

                Log::info('✅ PERMISOS VALIDADOS EXITOSAMENTE', [
                    'dueño' => $user->id,
                    'solicitante' => $request->user_id
                ]);
            }

            // Obtener o crear chat
            $chat = Chat::obtenerOCrearChat($user->id, $request->user_id, $request->solicitud_id);
            
            Log::info('Chat creado/obtenido', [
                'chat_id' => $chat->chat_id,
                'user1_id' => $chat->user1_id,
                'user2_id' => $chat->user2_id,
                'solicitud_id' => $chat->solicitud_id
            ]);

            // Obtener información del otro usuario
            $otroUsuario = $chat->otroUsuario($user->id);
            $userable = $otroUsuario->userable ?? null;
            
            $nombre = 'Usuario';
            $fotoUrl = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png';
            
            if ($userable instanceof \App\Models\Usuario) {
                $nombre = $userable->nombre ?? $otroUsuario->name ?? 'Usuario';
                
                if ($userable->fotos && $userable->fotos->isNotEmpty()) {
                    $foto = $userable->fotos->first();
                    $fotoUrl = asset('storage/' . $foto->ruta_foto);
                } elseif ($userable->foto_perfil) {
                    $fotoUrl = asset('storage/' . $userable->foto_perfil);
                }
            } else {
                $nombre = $otroUsuario->name ?? 'Usuario';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'chat' => [
                        'chat_id' => $chat->chat_id,
                        'usuario_id' => $otroUsuario->id,
                        'nombre' => $nombre,
                        'img' => $fotoUrl,
                        'solicitud_id' => $chat->solicitud_id,
                        'created_at' => $chat->created_at
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('EXCEPCIÓN en crearObtenerChat:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'user' => $user ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear chat',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Obtener un chat específico
     */
    public function show($chatId)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::with(['user1.userable', 'user2.userable'])
                ->findOrFail($chatId);

            // Verificar que el usuario sea participante
            if (!$chat->esParticipante($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para ver este chat'
                ], 403);
            }

            // Obtener información del otro usuario
            $otroUsuario = $chat->otroUsuario($user->id);
            $userable = $otroUsuario->userable ?? null;
            
            $nombre = 'Usuario';
            $fotoUrl = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png';
            
            if ($userable instanceof \App\Models\Usuario) {
                $nombre = $userable->nombre ?? $otroUsuario->name ?? 'Usuario';
                
                if ($userable->fotos && $userable->fotos->isNotEmpty()) {
                    $foto = $userable->fotos->first();
                    $fotoUrl = asset('storage/' . $foto->ruta_foto);
                } elseif ($userable->foto_perfil) {
                    $fotoUrl = asset('storage/' . $userable->foto_perfil);
                }
            } else {
                $nombre = $otroUsuario->name ?? 'Usuario';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'chat' => [
                        'chat_id' => $chat->chat_id,
                        'usuario_id' => $otroUsuario->id,
                        'nombre' => $nombre,
                        'img' => $fotoUrl,
                        'solicitud_id' => $chat->solicitud_id,
                        'mascota_nombre' => $chat->solicitud->mascota->nombre ?? null,
                        'created_at' => $chat->created_at,
                        'mensajes_no_leidos' => $chat->mensajesNoLeidos($user->id)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener chat:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener chat',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Eliminar chat (soft delete para el usuario)
     */
    public function destroy($chatId)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::findOrFail($chatId);

            if (!$chat->esParticipante($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para eliminar este chat'
                ], 403);
            }

            // Marcar como eliminado para este usuario
            if ($chat->user1_id == $user->id) {
                $chat->user1_deleted = true;
            } else {
                $chat->user2_deleted = true;
            }
            
            $chat->save();

            return response()->json([
                'success' => true,
                'message' => 'Chat eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar chat:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar chat',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Marcar todos los mensajes como leídos
     */
    public function marcarLeido($chatId)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::findOrFail($chatId);

            if (!$chat->esParticipante($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $chat->marcarComoLeido($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Mensajes marcados como leídos'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al marcar mensajes como leídos:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar mensajes como leídos',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }
    /**
     * Obtener estadísticas de interacciones de un chat
     */
    public function obtenerInteracciones($chatId)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::findOrFail($chatId);

            // Verificar que el usuario sea participante
            if (!$chat->esParticipante($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para ver este chat'
                ], 403);
            }

            // Obtener estadísticas
            $estadisticas = $chat->obtenerEstadisticasInteracciones();
            $resumen = $chat->obtenerResumenParaAdopcion();

            return response()->json([
                'success' => true,
                'data' => [
                    'estadisticas' => $estadisticas,
                    'resumen_adopcion' => $resumen,
                    'puede_aprobar' => $chat->estaListoParaAdopcion(),
                    'progreso' => [
                        'actual' => min($estadisticas['total_interacciones_pingpong'] / 2, 5),
                        'requerido' => 5,
                        'porcentaje' => $estadisticas['progreso'],
                        'mensaje' => $estadisticas['faltan_mensajes'] > 0 
                            ? "Faltan {$estadisticas['faltan_mensajes']} mensajes para habilitar aprobación"
                            : "✅ Chat listo para aprobación"
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener interacciones:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener interacciones',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Verificar si un chat asociado a una solicitud está listo para adopción
     */
    public function verificarChatParaAdopcion($solicitudId)
    {
        try {
            $user = Auth::user();
            
            // Buscar chat asociado a la solicitud
            $chat = Chat::where('solicitud_id', $solicitudId)
                ->where(function($query) use ($user) {
                    $query->where('user1_id', $user->id)
                          ->orWhere('user2_id', $user->id);
                })
                ->first();

            if (!$chat) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'chat_existe' => false,
                        'puede_aprobar' => false,
                        'mensaje' => 'No hay chat creado para esta solicitud'
                    ]
                ]);
            }

            $estaListo = $chat->estaListoParaAdopcion();
            $estadisticas = $chat->obtenerEstadisticasInteracciones();

            return response()->json([
                'success' => true,
                'data' => [
                    'chat_existe' => true,
                    'chat_id' => $chat->chat_id,
                    'puede_aprobar' => $estaListo,
                    'estadisticas' => $estadisticas,
                    'recomendacion' => $estaListo 
                        ? '✅ El chat cumple con el mínimo de interacciones. Puedes proceder con la aprobación de la adopción.'
                        : '⚠️ Se requieren más interacciones. Faltan ' . $estadisticas['faltan_mensajes'] . ' mensajes para habilitar la aprobación.'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al verificar chat para adopción:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar chat',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Obtener chats con estado de adopción
     */
    public function chatsConEstadoAdopcion(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 20);
            
            // Obtener chats con solicitud de adopción
            $chats = Chat::chatsUsuario($user->id)
                ->whereNotNull('solicitud_id')
                ->paginate($perPage);

            // Formatear respuesta con estado de adopción
            $chatsFormateados = $chats->map(function($chat) use ($user) {
                $otroUsuario = $chat->otroUsuario($user->id);
                $userable = $otroUsuario->userable ?? null;
                $ultimoMensaje = $chat->ultimoMensaje;
                
                // Información del usuario
                $nombre = 'Usuario';
                $fotoUrl = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png';
                
                if ($userable instanceof \App\Models\Usuario) {
                    $nombre = $userable->nombre ?? $otroUsuario->name ?? 'Usuario';
                    
                    if ($userable->fotos && $userable->fotos->isNotEmpty()) {
                        $foto = $userable->fotos->first();
                        $fotoUrl = asset('storage/' . $foto->ruta_foto);
                    } elseif ($userable->foto_perfil) {
                        $fotoUrl = asset('storage/' . $userable->foto_perfil);
                    }
                } else {
                    $nombre = $otroUsuario->name ?? 'Usuario';
                }

                // Obtener información de la solicitud
                $mascotaNombre = null;
                $solicitudInfo = null;
                if ($chat->solicitud_id && $chat->solicitud) {
                    $mascotaNombre = $chat->solicitud->mascota->nombre ?? null;
                    $solicitudInfo = [
                        'id' => $chat->solicitud_id,
                        'estado' => $chat->solicitud->estadoSolicitud,
                        'mascota_id' => $chat->solicitud->idMascota
                    ];
                }

                // Estado de adopción
                $estadoAdopcion = $chat->obtenerResumenParaAdopcion();

                return [
                    'chat_id' => $chat->chat_id,
                    'usuario_id' => $otroUsuario->id,
                    'nombre' => $nombre,
                    'img' => $fotoUrl,
                    'ultimo_mensaje' => $ultimoMensaje->contenido ?? null,
                    'ultimo_mensaje_en' => $ultimoMensaje->created_at ?? null,
                    'mensajes_no_leidos' => $chat->mensajesNoLeidos($user->id),
                    'solicitud_id' => $chat->solicitud_id,
                    'mascota_nombre' => $mascotaNombre,
                    'solicitud_info' => $solicitudInfo,
                    'estado_adopcion' => [
                        'listo' => $chat->listo_para_adopcion,
                        'interacciones_usuario' => $chat->interaccionesDeUsuario($user->id),
                        'interacciones_otro' => $chat->interaccionesOtroUsuario($user->id),
                        'faltan_mensajes' => $estadoAdopcion['detalle_usuarios']['usuario1']['cumple_minimo'] 
                            && $estadoAdopcion['detalle_usuarios']['usuario2']['cumple_minimo']
                            ? 0 
                            : 5 - min($chat->interacciones_usuario1, $chat->interacciones_usuario2),
                        'puede_aprobar' => $chat->estaListoParaAdopcion(),
                        'progreso' => min(100, (min($chat->interacciones_usuario1, $chat->interacciones_usuario2) / 5) * 100)
                    ],
                    'online' => false
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'chats' => $chatsFormateados,
                    'total' => $chats->total(),
                    'per_page' => $chats->perPage(),
                    'current_page' => $chats->currentPage(),
                    'last_page' => $chats->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener chats con estado adopción:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener chats',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de interacción para un chat
     */
    public function obtenerEstadisticasInteraccion($chatId)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::findOrFail($chatId);

            if (!$chat->esParticipante($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para ver este chat'
                ], 403);
            }

            $estadisticas = [
                'interacciones_usuario' => $chat->interaccionesDeUsuario($user->id),
                'interacciones_otro_usuario' => $chat->interaccionesOtroUsuario($user->id),
                'total_interacciones' => $chat->total_interacciones,
                'listo_para_adopcion' => $chat->listo_para_adopcion,
                'faltan_mensajes' => max(0, 5 - min($chat->interacciones_usuario1, $chat->interacciones_usuario2)),
                'alcanzo_interaccion_minima' => $chat->estaListoParaAdopcion(),
                'mensajes_usuario_cedente' => $chat->interacciones_usuario1,
                'mensajes_usuario_solicitante' => $chat->interacciones_usuario2,
                'detalle' => [
                    'usuario_actual' => [
                        'id' => $user->id,
                        'mensajes' => $chat->interaccionesDeUsuario($user->id),
                        'es_user1' => $chat->user1_id == $user->id
                    ],
                    'otro_usuario' => [
                        'id' => $chat->otroUsuario($user->id)->id,
                        'mensajes' => $chat->interaccionesOtroUsuario($user->id)
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'estadisticas' => $estadisticas,
                    'chat' => [
                        'chat_id' => $chat->chat_id,
                        'solicitud_id' => $chat->solicitud_id,
                        'fecha_habilitado_adopcion' => $chat->fecha_habilitado_adopcion
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de interacción:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }
}