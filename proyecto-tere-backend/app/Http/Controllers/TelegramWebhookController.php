<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class TelegramWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('🔔 ===== WEBHOOK INICIADO =====');
        
        try {
            $update = $request->all();
            
            Log::info('📱 Webhook recibido:', $update);

            if (!isset($update['message'])) {
                Log::info('📱 No hay mensaje en el webhook');
                return response()->json(['status' => 'success', 'message' => 'No message']);
            }

            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            $from = $message['from'] ?? [];

            Log::info("📱 Procesando mensaje", [
                'chat_id' => $chatId,
                'text' => $text,
                'from_username' => $from['username'] ?? 'No username',
                'from_first_name' => $from['first_name'] ?? 'No first name',
                'from_last_name' => $from['last_name'] ?? 'No last name'
            ]);

            if (strpos($text, '/start') === 0) {
                $this->handleStartCommand($chatId, $text, $from);
            } else {
                $this->sendMessage($chatId, "¡Hola! 👋 Usa /start para vincular tu cuenta.");
            }

            Log::info('🔔 ===== WEBHOOK FINALIZADO =====');
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('❌ Error en webhook: ' . $e->getMessage());
            Log::error('❌ Stack trace: ' . $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 200);
        }
    }

    private function handleStartCommand($chatId, $text, $from)
    {
        try {
            Log::info("🎯 Procesando /start", [
                'chat_id' => $chatId,
                'text_completo' => $text
            ]);

            $params = explode(' ', $text);
            $token = $params[1] ?? null;

            if (!$token) {
                $this->sendMessage($chatId, 
                    "¡Hola! 👋\n\nPara vincular tu cuenta, usa el enlace desde nuestra aplicación web."
                );
                return;
            }

            // ✅ BUSCAR POR TOKEN EN LUGAR DE EMAIL
            $user = User::where('telegram_token', $token)
                ->where('telegram_token_expires_at', '>', now())
                ->first();

            if (!$user) {
                Log::warning("❌ Token inválido o expirado: " . $token);
                
                $this->sendMessage($chatId, 
                    "❌ El enlace de vinculación ha expirado o es inválido.\n\n" .
                    "Por favor, genera un nuevo enlace desde nuestra aplicación web."
                );
                return;
            }

            // Guardar datos de Telegram
            $telegramData = [
                'telegram_chat_id' => $chatId,
                'telegram_username' => $from['username'] ?? null,
                'telegram_first_name' => $from['first_name'] ?? null,
                'telegram_last_name' => $from['last_name'] ?? null,
                'telegram_verified_at' => now(),
            ];

            $user->update($telegramData);
            
            // No limpiar el token aún, lo limpiaremos cuando la web verifique
            // $user->clearTelegramToken(); // ❌ No limpiar aquí
            
            Log::info("💾 Datos de Telegram guardados con token", [
                'user_id' => $user->id,
                'user_type' => $user->userable_type,
                'chat_id' => $chatId,
                'token' => $token,
                'username' => $from['username'] ?? 'No username',
                'first_name' => $from['first_name'] ?? 'No first name',
                'last_name' => $from['last_name'] ?? 'No last name'
            ]);

            // Mensaje de confirmación
            $telegramName = $user->telegram_full_name ?: ($from['username'] ?? 'Usuario');
            $userType = class_basename($user->userable_type);
            
            $message = "🎉 ¡Cuenta vinculada exitosamente, {$telegramName}!\n\n" .
                       "✅ Email: {$user->email}\n" .
                       "✅ Chat ID: {$chatId}\n" .
                       "✅ Tipo de usuario: {$userType}\n" .
                       "✅ Usuario de Telegram: @" . ($from['username'] ?? 'No username') . "\n\n" .
                       "¡Ahora recibirás notificaciones importantes! 📱\n\n" .
                       "⚠️ Por favor, regresa a la aplicación web y haz clic en 'Verificar' para completar el proceso.";

            $this->sendMessage($chatId, $message);

            Log::info("✅ Proceso /start COMPLETADO con token", [
                'user_id' => $user->id,
                'email' => $user->email,
                'chat_id' => $chatId,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en handleStartCommand: ' . $e->getMessage());
            Log::error('❌ Stack trace: ' . $e->getTraceAsString());
            $this->sendMessage($chatId, 
                "❌ Ocurrió un error al vincular tu cuenta.\n\n" .
                "Por favor, intenta nuevamente o contacta a soporte."
            );
        }
    }

    private function sendMessage($chatId, $text)
    {
        try {
            $token = env('TELEGRAM_BOT_TOKEN');
            
            if (!$token) {
                Log::error('❌ TELEGRAM_BOT_TOKEN no está configurado');
                return null;
            }

            $response = Http::timeout(10)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML'
                ]);

            $result = $response->json();
            
            if (!$result['ok']) {
                Log::error('❌ Error enviando mensaje:', $result);
            } else {
                Log::info("✅ Mensaje enviado correctamente a chat $chatId");
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('❌ Error enviando mensaje: ' . $e->getMessage());
            return null;
        }
    }

    // Métodos para configuración
    public function setWebhook()
    {
        try {
            $webhookUrl = url('/api/telegram/webhook');
            $token = env('TELEGRAM_BOT_TOKEN');
            
            $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $webhookUrl
            ]);

            $result = $response->json();

            return response()->json([
                'success' => true,
                'message' => 'Webhook configurado',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeWebhook()
    {
        try {
            $token = env('TELEGRAM_BOT_TOKEN');
            $response = Http::post("https://api.telegram.org/bot{$token}/deleteWebhook");
            $result = $response->json();

            return response()->json([
                'success' => true,
                'message' => 'Webhook eliminado',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getWebhookInfo()
    {
        try {
            $token = env('TELEGRAM_BOT_TOKEN');
            $response = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");
            $result = $response->json();

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}