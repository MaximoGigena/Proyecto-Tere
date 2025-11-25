<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ContactoUsuario;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class TelegramWebhookController extends Controller
{
    /**
     * Webhook público - SIN dependencias inyectadas
     */
    /**
     * Webhook público - SIN dependencias inyectadas
     */
    public function handleWebhook(Request $request)
    {
        Log::info('🔔 ===== WEBHOOK INICIADO - SIN DEPENDENCIAS =====');
        
        try {
            $update = $request->all();
            
            Log::info('📱 Webhook recibido (sin dependencias):', $update);

            // Verificar que es un mensaje válido
            if (!isset($update['message'])) {
                Log::info('📱 No hay mensaje en el webhook');
                return response()->json(['status' => 'success', 'message' => 'No message']);
            }

            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            $from = $message['from'] ?? [];

            Log::info("📱 Procesando mensaje (sin dependencias)", [
                'chat_id' => $chatId,
                'text' => $text,
                'from_username' => $from['username'] ?? 'No username'
            ]);

            // Procesar comando /start
            if (strpos($text, '/start') === 0) {
                $this->handleStartCommand($chatId, $text, $from);
            } else {
                // Responder a otros mensajes
                $this->sendMessage($chatId, "¡Hola! 👋 Usa /start para vincular tu cuenta.");
            }

            Log::info('🔔 ===== WEBHOOK FINALIZADO =====');

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('❌ Error en webhook (sin dependencias): ' . $e->getMessage());
            Log::error('❌ Stack trace: ' . $e->getTraceAsString());
            
            // SIEMPRE devolver 200 aunque haya error
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 200);
        }
    }

    private function handleStartCommand($chatId, $text, $from)
    {
        try {
            Log::info("🎯 Procesando /start - ANÁLISIS COMPLETO", [
                'chat_id' => $chatId,
                'text_completo' => $text,
                'longitud_texto' => strlen($text),
                'from_username' => $from['username'] ?? 'No username',
                'from_id' => $from['id'] ?? 'No id'
            ]);

            // Extraer parámetros
            $params = explode(' ', $text);

            Log::info("📋 ANÁLISIS DE PARÁMETROS:", [
                'total_parametros' => count($params),
                'parametro_0' => $params[0] ?? 'N/A',
                'parametro_1' => $params[1] ?? 'N/A', 
                'parametro_2' => $params[2] ?? 'N/A',
                'todos_parametros' => $params
            ]);
            
            $email = $params[1] ?? null;

            Log::info("📧 Email recibido en /start:", ['email' => $email]);

            if (!$email) {
                Log::info("📝 /start sin email");
                $this->sendMessage($chatId, 
                    "¡Hola! 👋\n\nPara vincular tu cuenta, usa el enlace desde nuestra aplicación web."
                );
                return;
            }

            Log::info("📧 RESULTADO FINAL - Email:", [
                'email_crudo' => $email,
                'email_decodificado' => $email ? urldecode($email) : 'N/A',
                'tiene_arroba' => $email && strpos($email, '@') !== false
            ]);

            // Decodificar email (por si tiene encoding)
            $email = urldecode($email);
            
            // 🔥 NUEVO: Buscar en AMBAS tablas
            Log::info("🔍 Buscando usuario por email decodificado: " . $email);
            
            $usuario = null;
            $contacto = null;
            $usuarioId = null;

            // Primero buscar en ContactoUsuario
            $contacto = ContactoUsuario::where('email', $email)->first();
            
            if ($contacto) {
                Log::info("✅ Contacto encontrado en tabla ContactoUsuario", [
                    'usuario_id' => $contacto->usuario_id,
                    'nombre_completo' => $contacto->nombre_completo,
                    'email' => $contacto->email
                ]);
                $usuarioId = $contacto->usuario_id;
            } else {
                // Si no está en ContactoUsuario, buscar en User
                Log::info("🔍 Contacto no encontrado, buscando en tabla User...");
                $usuario = User::where('email', $email)->first();
                
                if ($usuario) {
                    Log::info("✅ Usuario encontrado en tabla User", [
                        'usuario_id' => $usuario->id,
                        'nombre' => $usuario->nombre,
                        'email' => $usuario->email
                    ]);
                    $usuarioId = $usuario->id;
                    
                    // Crear registro en ContactoUsuario si no existe
                    $contacto = ContactoUsuario::create([
                        'usuario_id' => $usuarioId,
                        'email' => $email,
                        'nombre_completo' => $usuario->nombre,
                        'telegram_chat_id' => $chatId
                    ]);
                    
                    Log::info("✅ Registro creado en ContactoUsuario", [
                        'usuario_id' => $usuarioId,
                        'email' => $email
                    ]);
                }
            }

            if (!$usuarioId) {
                Log::warning("❌ No se encontró usuario para email: " . $email);
                
                // Log para debug
                $allUserEmails = User::pluck('email')->toArray();
                $allContactEmails = ContactoUsuario::pluck('email')->toArray();
                
                Log::info("📋 Todos los emails en User:", $allUserEmails);
                Log::info("📋 Todos los emails en ContactoUsuario:", $allContactEmails);
                
                $this->sendMessage($chatId, 
                    "❌ No se encontró una cuenta con el email: $email\n\n" .
                    "Verifica que:\n" .
                    "• El email sea correcto\n" .
                    "• Ya tengas una cuenta registrada\n" .
                    "• Hayas completado el registro en la web"
                );
                return;
            }

            // Si ya existe el contacto, actualizar el chat ID
            if ($contacto && !$contacto->wasRecentlyCreated) {
                $contacto->update(['telegram_chat_id' => $chatId]);
                $contacto->refresh();
                
                Log::info("💾 Chat ID actualizado en contacto existente", [
                    'nuevo_chat_id' => $contacto->telegram_chat_id,
                    'esperado' => $chatId,
                    'coincide' => $contacto->telegram_chat_id == $chatId
                ]);
            }

            // Enviar mensaje de confirmación
            $this->sendMessage($chatId, 
                "🎉 ¡Cuenta vinculada exitosamente!\n\n" .
                "✅ Email: $email\n" .
                "✅ Chat ID: $chatId\n" .
                "✅ Usuario ID: {$usuarioId}\n\n" .
                "¡Ahora recibirás notificaciones importantes!"
            );

            Log::info("✅ Proceso /start COMPLETADO", [
                'usuario_id' => $usuarioId,
                'email' => $email,
                'chat_id' => $chatId,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en handleStartCommand: ' . $e->getMessage());
            Log::error('❌ Stack trace: ' . $e->getTraceAsString());
            $this->sendMessage($chatId, 
                "❌ Ocurrió un error al vincular tu cuenta.\n\n" .
                "Por favor, intenta nuevamente."
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

            $response = \Illuminate\Support\Facades\Http::timeout(10)
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

    /**
     * Métodos para configuración (pueden mantener dependencias)
     */
    public function setWebhook()
    {
        try {
            $webhookUrl = url('/api/telegram/webhook');
            
            $token = env('TELEGRAM_BOT_TOKEN');
            $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
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
            $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/deleteWebhook");
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
}