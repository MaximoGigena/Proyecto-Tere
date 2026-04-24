<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsAppService;

class TestTwilioWhatsApp extends Command
{
    protected $signature = 'twilio:test {number}';
    protected $description = 'Test Twilio WhatsApp sending';

    public function handle(WhatsAppService $whatsapp)
    {
        $number = $this->argument('number');
        $this->info("Enviando mensaje de prueba a {$number}...");
        
        $result = $whatsapp->sendTextMessage($number, "¡Hola! Este es un mensaje de prueba desde TERE 🐾");
        
        if ($result['success']) {
            $this->info("✅ Mensaje enviado: " . $result['message_id']);
        } else {
            $this->error("❌ Error: " . $result['message']);
        }
    }
}