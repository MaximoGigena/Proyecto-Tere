<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook';
    protected $description = 'Configurar webhook de Telegram';

    public function handle(TelegramBotService $telegramBotService)
    {
        // La URL es la MISMA, pero ahora está en web.php
        $webhookUrl = 'https://margarete-proanarchic-enid.ngrok-free.dev';
        
        $this->info("Configurando webhook: {$webhookUrl}");
        
        $result = $telegramBotService->setWebhook($webhookUrl);
        
        if ($result && $result['ok']) {
            $this->info('✅ Webhook configurado exitosamente');
        } else {
            $this->error('❌ Error configurando webhook: ' . ($result['description'] ?? 'Unknown error'));
        }
    }
}