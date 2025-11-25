<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ClearTelegramUpdates extends Command
{
    protected $signature = 'telegram:clear-updates';
    protected $description = 'Limpiar updates pendientes de Telegram';

    public function handle()
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        
        // Eliminar webhook para limpiar updates
        $response = Http::post("https://api.telegram.org/bot{$token}/deleteWebhook");
        
        if ($response->json()['ok']) {
            $this->info('✅ Webhook eliminado, updates limpiados');
            
            // Esperar 2 segundos
            sleep(2);
            
            // Reconfigurar webhook
            $this->call('telegram:set-webhook-ngrok');
        } else {
            $this->error('❌ Error eliminando webhook');
        }
    }
}