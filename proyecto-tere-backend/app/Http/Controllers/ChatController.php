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
            
            $validator = validator($request->all(), [
                'user_id' => 'required|exists:users,id',
                'solicitud_id' => 'nullable|exists:solicitudes_adopcion,idSolicitud'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // No permitir chat consigo mismo
            if ($user->id == $request->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes crear un chat contigo mismo'
                ], 400);
            }

            // Verificar permisos para la solicitud
            if ($request->solicitud_id) {
                $solicitud = SolicitudAdopcion::find($request->solicitud_id);
                
                if (!$solicitud) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Solicitud no encontrada'
                    ], 404);
                }

                // Verificar que ambos usuarios están relacionados con la solicitud
                $usuariosRelacionados = [
                    $solicitud->idUsuarioSolicitante,
                    $solicitud->mascota->usuario_id ?? null
                ];

                if (!in_array($user->id, $usuariosRelacionados) || 
                    !in_array($request->user_id, $usuariosRelacionados)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No autorizado para crear chat para esta solicitud'
                    ], 403);
                }
            }

            // Obtener o crear chat
            $chat = Chat::obtenerOCrearChat($user->id, $request->user_id, $request->solicitud_id);

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
            Log::error('Error al crear/obtener chat:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Log::error('EXCEPCIÓN en crearObtenerChat:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
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
}