<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ContactoUsuario;

class TelegramBotService
{
    protected $token;

    public function __construct()
    {
        $this->token = env('TELEGRAM_BOT_TOKEN');
    }

    public function processWebhook($update)
    {
        try {
            Log::info('📱 Webhook de Telegram recibido:', $update);

            $message = $update['message'] ?? null;
            
            if (!$message) {
                Log::info('No hay mensaje en el webhook');
                return;
            }

            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            $from = $message['from'] ?? [];

            Log::info("Procesando mensaje", [
                'chat_id' => $chatId,
                'text' => $text,
                'from' => $from
            ]);

            // Procesar comando /start
            if (strpos($text, '/start') === 0) {
                $this->handleStartCommand($chatId, $text, $from);
            } else {
                // Responder a otros mensajes
                $this->sendMessage($chatId, "¡Hola! 👋 Usa el comando /start para vincular tu cuenta con nuestra aplicación.");
            }

        } catch (\Exception $e) {
            Log::error('Error procesando webhook de Telegram: ' . $e->getMessage());
        }
    }

    private function handleStartCommand($chatId, $text, $from)
    {
        try {
            Log::info("Procesando comando /start", [
                'chat_id' => $chatId,
                'text' => $text
            ]);

            // Extraer parámetros del comando /start
            $params = explode(' ', $text);
            $email = $params[1] ?? null;

            if (!$email) {
                $this->sendMessage($chatId, 
                    "¡Hola! 👋\n\n" .
                    "Para vincular tu cuenta, por favor:\n" .
                    "1. Ve a la aplicación web\n" .
                    "2. Ve a tu perfil\n" . 
                    "3. Haz click en 'Configurar Telegram'\n" .
                    "4. Sigue las instrucciones allí\n\n" .
                    "¡Allí obtendrás un enlace personalizado!"
                );
                return;
            }

            Log::info("Buscando contacto por email", ['email' => $email]);

            // Buscar usuario por email
            $contacto = ContactoUsuario::where('email', $email)->first();

            if (!$contacto) {
                Log::warning("No se encontró contacto", ['email' => $email]);
                $this->sendMessage($chatId, 
                    "❌ No se encontró una cuenta con el email: $email\n\n" .
                    "Verifica que:\n" .
                    "• El email sea correcto\n" .
                    "• Ya tengas una cuenta en nuestra plataforma\n" .
                    "• Hayas completado el registro"
                );
                return;
            }

            // Verificar si ya tiene Telegram configurado
            if ($contacto->telegram_chat_id) {
                if ($contacto->telegram_chat_id == $chatId) {
                    $this->sendMessage($chatId, 
                        "✅ ¡Ya tienes tu cuenta vinculada con este chat!\n\n" .
                        "Estás listo para recibir notificaciones importantes sobre tus mascotas. 🐾"
                    );
                } else {
                    $this->sendMessage($chatId, 
                        "⚠️ Este email ya está vinculado con otro chat de Telegram.\n\n" .
                        "Si quieres cambiar la vinculación, contacta con soporte."
                    );
                }
                return;
            }

            // Guardar el chat ID
            $contacto->update([
                'telegram_chat_id' => $chatId
            ]);

            // Enviar mensaje de confirmación
            $this->sendMessage($chatId, 
                "🎉 ¡Cuenta vinculada exitosamente!\n\n" .
                "✅ Email: $email\n" .
                "✅ Chat ID: $chatId\n\n" .
                "Ahora recibirás notificaciones importantes sobre:\n" .
                "• Tus mascotas 🐾\n" .
                "• Recordatorios de vacunas 💉\n" .
                "• Citas con veterinarios 🏥\n" .
                "• Alertas importantes ⚠️\n\n" .
                "¡Gracias por usar nuestro servicio!"
            );

            Log::info("✅ Chat ID de Telegram guardado exitosamente", [
                'usuario_id' => $contacto->usuario_id,
                'email' => $email,
                'chat_id' => $chatId,
                'nombre_completo' => $contacto->nombre_completo
            ]);

        } catch (\Exception $e) {
            Log::error('Error en handleStartCommand: ' . $e->getMessage());
            $this->sendMessage($chatId, 
                "❌ Ocurrió un error al vincular tu cuenta.\n\n" .
                "Por favor, intenta nuevamente o contacta con soporte si el problema persiste."
            );
        }
    }

    public function sendMessage($chatId, $text)
    {
        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ]);

            $result = $response->json();
            
            if (!$result['ok']) {
                Log::error('Error enviando mensaje de Telegram:', $result);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Error enviando mensaje de Telegram: ' . $e->getMessage());
            return null;
        }
    }

    public function getBotInfo()
    {
        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$this->token}/getMe");
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error obteniendo info del bot: ' . $e->getMessage());
            return null;
        }
    }

    public function setWebhook($url)
    {
        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->token}/setWebhook", [
                'url' => $url
            ]);

            $result = $response->json();
            Log::info('Webhook configurado:', $result);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error configurando webhook: ' . $e->getMessage());
            return null;
        }
    }

    public function getWebhookInfo()
    {
        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$this->token}/getWebhookInfo");
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error obteniendo info del webhook: ' . $e->getMessage());
            return null;
        }
    }

    public function deleteWebhook()
    {
        try {
            $response = Http::post("https://api.telegram.org/bot{$this->token}/deleteWebhook");
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error eliminando webhook: ' . $e->getMessage());
            return null;
        }
    }
}