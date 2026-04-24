<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class TelegramController extends Controller
{
    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function guardarChatId(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'telegram_chat_id' => 'required|string|max:50',
            'telegram_username' => 'nullable|string|max:255',
            'telegram_first_name' => 'nullable|string|max:255',
            'telegram_last_name' => 'nullable|string|max:255'
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró un usuario con ese email'
                ], 404);
            }

            // Datos a actualizar
            $updateData = [
                'telegram_chat_id' => $request->telegram_chat_id,
                'telegram_verified_at' => now(),
            ];

            // Actualizar campos opcionales si están presentes
            if ($request->has('telegram_username')) {
                $updateData['telegram_username'] = $request->telegram_username;
            }
            if ($request->has('telegram_first_name')) {
                $updateData['telegram_first_name'] = $request->telegram_first_name;
            }
            if ($request->has('telegram_last_name')) {
                $updateData['telegram_last_name'] = $request->telegram_last_name;
            }

            $user->update($updateData);

            Log::info("✅ Datos de Telegram guardados/actualizados", [
                'user_id' => $user->id,
                'user_type' => $user->userable_type,
                'email' => $request->email,
                'chat_id' => $request->telegram_chat_id,
                'username' => $request->telegram_username ?? 'No proporcionado',
                'first_name' => $request->telegram_first_name ?? 'No proporcionado',
                'last_name' => $request->telegram_last_name ?? 'No proporcionado'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Datos de Telegram guardados exitosamente',
                'data' => [
                    'user_id' => $user->id,
                    'nombre' => $user->nombre,
                    'email' => $user->email,
                    'telegram_chat_id' => $user->telegram_chat_id,
                    'telegram_username' => $user->telegram_username,
                    'telegram_full_name' => $user->telegram_full_name,
                    'telegram_verified_at' => $user->telegram_verified_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al guardar datos de Telegram: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verificarChatIdPorEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            Log::info("🔍 Buscando datos de Telegram por email: " . $request->email);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró un usuario con ese email'
                ], 404);
            }

            if (!$user->telegram_chat_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró vinculación de Telegram para este email'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'telegram_chat_id' => $user->telegram_chat_id,
                    'telegram_username' => $user->telegram_username,
                    'telegram_first_name' => $user->telegram_first_name,
                    'telegram_last_name' => $user->telegram_last_name,
                    'telegram_full_name' => $user->telegram_full_name,
                    'telegram_verified_at' => $user->telegram_verified_at,
                    'nombre' => $user->nombre,
                    'email' => $user->email,
                    'user_id' => $user->id,
                    'user_type' => class_basename($user->userable_type)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al verificar datos de Telegram: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function obtenerChatId($userId)
    {
        try {
            Log::info("🔍 Buscando datos de Telegram para user: $userId");

            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el usuario'
                ], 404);
            }

            if (!$user->telegram_chat_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró vinculación de Telegram para este usuario'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'telegram_chat_id' => $user->telegram_chat_id,
                    'telegram_username' => $user->telegram_username,
                    'telegram_first_name' => $user->telegram_first_name,
                    'telegram_last_name' => $user->telegram_last_name,
                    'telegram_full_name' => $user->telegram_full_name,
                    'telegram_verified_at' => $user->telegram_verified_at,
                    'nombre' => $user->nombre,
                    'email' => $user->email,
                    'user_type' => class_basename($user->userable_type)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener datos de Telegram: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function obtenerTodosUsuariosTelegram()
    {
        try {
            $users = User::whereNotNull('telegram_chat_id')
                ->select('id', 'email', 'name', 'telegram_chat_id', 'telegram_username', 
                        'telegram_first_name', 'telegram_last_name', 'telegram_verified_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $users->map(function($user) {
                    return [
                        'user_id' => $user->id,
                        'nombre' => $user->nombre,
                        'email' => $user->email,
                        'telegram_chat_id' => $user->telegram_chat_id,
                        'telegram_username' => $user->telegram_username,
                        'telegram_full_name' => $user->telegram_full_name,
                        'telegram_verified_at' => $user->telegram_verified_at
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios de Telegram: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    // Métodos existentes para documentos...
    public function sendDocument(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf|max:10240',
            'chat_id' => 'required',
            'caption' => 'nullable|string|max:255'
        ]);

        try {
            $path = $request->file('document')->store('temp');
            $fullPath = Storage::path($path);

            $result = $this->telegramService->sendDocument(
                $request->chat_id,
                $fullPath,
                $request->caption
            );

            Storage::delete($path);

            if ($result['ok']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document sent successfully!'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $result['description']
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendStoredDocument($filename)
    {
        try {
            $chatId = config('telegram.chat_id');
            $result = $this->telegramService->sendDocumentFromStorage(
                $chatId,
                "documents/{$filename}",
                "Aquí tienes tu documento: {$filename}"
            );

            if ($result['ok']) {
                return response()->json(['success' => true]);
            }

            return response()->json(['success' => false], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Generar token para vincular Telegram (desde la web)
     */
    public function generarTokenTelegram(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Generar nuevo token
            $token = $user->generateTelegramToken();
            
            // Crear URL de vinculación
            $telegramBotUsername = env('TELEGRAM_BOT_USERNAME', 'Proyecto_Tere_bot');
            $telegramLink = "https://t.me/{$telegramBotUsername}?start={$token}";
            
            Log::info("🔑 Token generado para usuario", [
                'user_id' => $user->id,
                'email' => $user->email,
                'token' => $token,
                'expira_en' => $user->telegram_token_expires_at
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'telegram_link' => $telegramLink,
                    'expires_at' => $user->telegram_token_expires_at,
                    'bot_username' => $telegramBotUsername
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al generar token Telegram: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al generar token'
            ], 500);
        }
    }

    /**
     * Verificar token (para la web después de vincular)
     */
    public function verificarTokenTelegram(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        try {
            $user = User::where('telegram_token', $request->token)
                ->where('telegram_token_expires_at', '>', now())
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado'
                ], 404);
            }

            if (!$user->telegram_chat_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aún no se ha vinculado Telegram. Envía el comando /start al bot.'
                ], 404);
            }

            // Limpiar token después de verificar
            $user->clearTelegramToken();

            return response()->json([
                'success' => true,
                'message' => '✅ Telegram vinculado exitosamente',
                'data' => [
                    'telegram_chat_id' => $user->telegram_chat_id,
                    'telegram_username' => $user->telegram_username,
                    'telegram_full_name' => $user->telegram_full_name,
                    'telegram_verified_at' => $user->telegram_verified_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al verificar token: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar token'
            ], 500);
        }
    }

    // App\Http\Controllers\TelegramController.php

    public function verificarTelegramVinculado(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'vinculado' => !is_null($user->telegram_chat_id),
                    'telegram_chat_id' => $user->telegram_chat_id,
                    'telegram_username' => $user->telegram_username,
                    'telegram_full_name' => $user->telegram_full_name,
                    'telegram_verified_at' => $user->telegram_verified_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al verificar vinculación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar vinculación'
            ], 500);
        }
    }
}