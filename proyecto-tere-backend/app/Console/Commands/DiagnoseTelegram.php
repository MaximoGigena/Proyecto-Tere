<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiagnoseTelegram extends Command
{
    protected $signature = 'telegram:diagnose';
    protected $description = 'Diagnóstico completo de Telegram';

    public function handle()
    {
        $this->info('🔍 Iniciando diagnóstico de Telegram...');

        // 1. Verificar token
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) {
            $this->error('❌ TELEGRAM_BOT_TOKEN no está configurado en .env');
            return;
        }
        $this->info('✅ TELEGRAM_BOT_TOKEN configurado');

        // 2. Verificar bot
        $response = Http::get("https://api.telegram.org/bot{$token}/getMe");
        if (!$response->successful()) {
            $this->error('❌ No se puede conectar con el bot de Telegram');
            return;
        }
        $this->info('✅ Bot de Telegram activo');

        // 3. Verificar webhook
        $webhookInfo = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo")->json();
        
        if (empty($webhookInfo['result']['url'])) {
            $this->error('❌ Webhook no configurado');
            $this->info('💡 Ejecuta: php artisan telegram:set-webhook-ngrok');
            return;
        }

        $this->info("✅ Webhook configurado: {$webhookInfo['result']['url']}");
        $this->info("📊 Updates pendientes: {$webhookInfo['result']['pending_update_count']}");

        if (!empty($webhookInfo['result']['last_error_message'])) {
            $this->error("❌ Error en webhook: {$webhookInfo['result']['last_error_message']}");
        }

        // 4. Verificar rutas locales
        $this->info('🌐 Verificando rutas locales...');
        $this->call('route:list', ['--name' => 'telegram']);

        $this->info('🎯 Diagnóstico completado');
    }
}
