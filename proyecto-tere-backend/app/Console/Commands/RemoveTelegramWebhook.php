<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class RemoveTelegramWebhook extends Command
{
    protected $signature = 'telegram:remove-webhook';
    protected $description = 'Eliminar webhook de Telegram';

    public function handle(TelegramBotService $telegramBotService)
    {
        $this->info("Eliminando webhook de Telegram...");
        
        $result = $telegramBotService->deleteWebhook();
        
        if ($result && $result['ok']) {
            $this->info('✅ Webhook eliminado exitosamente');
        } else {
            $this->error('❌ Error eliminando webhook: ' . ($result['description'] ?? 'Unknown error'));
        }
    }
}