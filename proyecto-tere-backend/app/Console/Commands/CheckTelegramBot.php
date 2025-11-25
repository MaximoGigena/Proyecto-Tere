<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class CheckTelegramBot extends Command
{
    protected $signature = 'telegram:check';
    protected $description = 'Verificar estado del bot de Telegram';

    public function handle(TelegramBotService $telegramBotService)
    {
        $this->info("🔍 Verificando estado del bot de Telegram...");

        // Verificar información del bot
        $botInfo = $telegramBotService->getBotInfo();
        
        if ($botInfo && $botInfo['ok']) {
            $bot = $botInfo['result'];
            $this->info("✅ Bot activo: @{$bot['username']} - {$bot['first_name']}");
        } else {
            $this->error("❌ No se pudo obtener información del bot");
            return;
        }

        // Verificar webhook
        $webhookInfo = $telegramBotService->getWebhookInfo();
        
        if ($webhookInfo && $webhookInfo['ok']) {
            $webhook = $webhookInfo['result'];
            
            if (!empty($webhook['url'])) {
                $this->info("✅ Webhook configurado: {$webhook['url']}");
                $this->info("   Último error: " . ($webhook['last_error_message'] ?? 'Ninguno'));
                $this->info("   Updates pendientes: " . $webhook['pending_update_count']);
            } else {
                $this->warn("⚠️ Webhook no configurado");
            }
        } else {
            $this->error("❌ Error al verificar webhook");
        }
    }
}