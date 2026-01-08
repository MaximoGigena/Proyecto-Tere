<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Mensaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MensajeController extends Controller
{
    /**
     * Obtener mensajes de un chat
     */
    public function index(Request $request, $chatId)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 50);
            $page = $request->get('page', 1);
            
            Log::info('Obteniendo mensajes para chat: ' . $chatId . ', usuario: ' . $user->id);
            
            // Verificar que el chat existe y el usuario es participante
            $chat = Chat::findOrFail($chatId);
            
            if (!$chat->esParticipante($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para ver este chat'
                ], 403);
            }

            // Obtener mensajes
            $mensajes = Mensaje::delChat($chatId)
                ->paginate($perPage, ['*'], 'page', $page);

            Log::info('Total mensajes encontrados: ' . $mensajes->count());

            // Formatear respuesta
            $mensajesFormateados = $mensajes->map(function($mensaje) use ($user) {
                return $mensaje->formatearParaFrontend();
            });

            // Marcar como leídos si es la última página
            if ($page == 1) {
                $chat->marcarComoLeido($user->id);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'mensajes' => $mensajesFormateados,
                    'total' => $mensajes->total(),
                    'per_page' => $mensajes->perPage(),
                    'current_page' => $mensajes->currentPage(),
                    'last_page' => $mensajes->lastPage(),
                    'chat_id' => $chatId,
                    'usuario_id' => $user->id
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener mensajes:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mensajes',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Enviar un mensaje
     */
    public function store(Request $request, $chatId)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            Log::info('📤 Intentando enviar mensaje', [
                'chat_id' => $chatId,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'contenido' => $request->contenido,
                'tipo' => $request->tipo,
                'ip' => $request->ip()
            ]);

            $validator = validator($request->all(), [
                'contenido' => 'required|string|max:2000',
                'tipo' => 'nullable|in:texto,imagen,documento,audio',
                'url_adjunto' => 'nullable|url|max:500'
            ]);

            if ($validator->fails()) {
                Log::warning('❌ Validación fallida', [
                    'errors' => $validator->errors()->toArray()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que el chat existe y el usuario es participante
            $chat = Chat::find($chatId);
            
            if (!$chat) {
                Log::warning('❌ Chat no encontrado', ['chat_id' => $chatId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Chat no encontrado'
                ], 404);
            }
            
            Log::info('✅ Chat encontrado', [
                'chat_id' => $chat->chat_id,
                'user1_id' => $chat->user1_id,
                'user2_id' => $chat->user2_id
            ]);

            if (!$chat->esParticipante($user->id)) {
                Log::warning('❌ Usuario no es participante', [
                    'user_id' => $user->id,
                    'chat_users' => [$chat->user1_id, $chat->user2_id]
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para enviar mensajes en este chat'
                ], 403);
            }

            // Crear el mensaje
            $mensaje = Mensaje::create([
                'chat_id' => $chatId,
                'user_id' => $user->id,
                'contenido' => $request->contenido,
                'tipo' => $request->tipo ?? 'texto',
                'canal' => 'interno', // Agregar este campo
                'url_adjunto' => $request->url_adjunto,
                'leido' => false
            ]);

            Log::info('✅ Mensaje creado', [
                'mensaje_id' => $mensaje->mensaje_id,
                'contenido' => $mensaje->contenido
            ]);

            // Actualizar último mensaje en el chat
            $chat->update([
                'ultimo_mensaje' => $request->contenido,
                'ultimo_mensaje_en' => now()
            ]);

            DB::commit();

            // Formatear respuesta
            $mensajeFormateado = $mensaje->formatearParaFrontend();

            Log::info('✅ Mensaje enviado exitosamente', [
                'mensaje_id' => $mensaje->mensaje_id,
                'formateado' => $mensajeFormateado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado exitosamente',
                'data' => [
                    'mensaje' => $mensajeFormateado
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ Error al enviar mensaje:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar mensaje',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Eliminar un mensaje (soft delete)
     */
    public function destroy($chatId, $mensajeId)
    {
        try {
            $user = Auth::user();
            
            $mensaje = Mensaje::findOrFail($mensajeId);

            // Verificar que el mensaje pertenece al chat
            if ($mensaje->chat_id != $chatId) {
                return response()->json([
                    'success' => false,
                    'message' => 'El mensaje no pertenece a este chat'
                ], 400);
            }

            // Verificar que el usuario es el propietario del mensaje
            if ($mensaje->user_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para eliminar este mensaje'
                ], 403);
            }

            $mensaje->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mensaje eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar mensaje:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar mensaje',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Marcar mensajes como leídos
     */
    public function marcarLeidos(Request $request, $chatId)
    {
        try {
            $user = Auth::user();
            
            $validator = validator($request->all(), [
                'mensaje_ids' => 'required|array',
                'mensaje_ids.*' => 'exists:mensajes,mensaje_id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que los mensajes pertenecen al chat y son del otro usuario
            $mensajes = Mensaje::whereIn('mensaje_id', $request->mensaje_ids)
                ->where('chat_id', $chatId)
                ->where('user_id', '!=', $user->id)
                ->get();

            foreach ($mensajes as $mensaje) {
                $mensaje->marcarComoLeido();
            }

            return response()->json([
                'success' => true,
                'message' => 'Mensajes marcados como leídos',
                'data' => [
                    'marcados' => $mensajes->count()
                ]
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
     * Buscar en mensajes
     */
    public function buscar(Request $request, $chatId)
    {
        try {
            $user = Auth::user();
            
            $validator = validator($request->all(), [
                'query' => 'required|string|min:2|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que el chat existe y el usuario es participante
            $chat = Chat::findOrFail($chatId);
            
            if (!$chat->esParticipante($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            // Buscar mensajes
            $mensajes = Mensaje::where('chat_id', $chatId)
                ->where('contenido', 'like', '%' . $request->query . '%')
                ->with('usuario')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            $mensajesFormateados = $mensajes->map(function($mensaje) use ($user) {
                return $mensaje->formatearParaFrontend();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'mensajes' => $mensajesFormateados,
                    'total' => $mensajes->count(),
                    'query' => $request->query
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al buscar mensajes:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar mensajes',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }
}