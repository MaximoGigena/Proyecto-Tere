<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramWebhookNgrok extends Command
{
    protected $signature = 'telegram:set-webhook-ngrok';
    protected $description = 'Configurar webhook de Telegram con ngrok';

    public function handle(TelegramBotService $telegramBotService)
    {
        $ngrokUrl = $this->getNgrokUrl();
        
        if (!$ngrokUrl) {
            $this->error('❌ No se pudo obtener la URL de ngrok.');
            return;
        }

        // ✅ CORREGIDO: Usar /bot (que es la ruta que SÍ funciona)
        $webhookUrl = $ngrokUrl . '/bot';
        
        $this->info("🎯 URL de ngrok: {$ngrokUrl}");
        $this->info("🔗 Configurando webhook: {$webhookUrl}");
        
        $result = $telegramBotService->setWebhook($webhookUrl);
        
        if ($result && $result['ok']) {
            $this->info('✅ Webhook configurado exitosamente con ngrok');
        } else {
            $this->error('❌ Error configurando webhook: ' . ($result['description'] ?? 'Unknown error'));
        }
    }

    private function getNgrokUrl()
    {
        try {
            $response = Http::get('http://localhost:4040/api/tunnels');
            $data = $response->json();
            
            foreach ($data['tunnels'] as $tunnel) {
                if ($tunnel['proto'] === 'https') {
                    return $tunnel['public_url'];
                }
            }
        } catch (\Exception $e) {
            return null;
        }
        
        return null;
    }
}