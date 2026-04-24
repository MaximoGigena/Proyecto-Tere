<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppService;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Log de TODO lo que llega (incluso el raw)
        Log::info('📨 Webhook RAW', [
            'all_input' => $request->all(),
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type')
        ]);
        
        // Twilio envía los datos como form-data
        $from = $request->input('From');
        $to = $request->input('To');
        $body = $request->input('Body');
        $messageType = $request->input('MessageType');
        $mediaUrl = $request->input('MediaUrl0');
        
        Log::info("📨 Webhook recibido de Twilio", [
            'from' => $from,
            'to' => $to,
            'body' => $body,
            'messageType' => $messageType,
            'mediaUrl' => $mediaUrl
        ]);
        
        // Solo procesar si hay un mensaje
        if ($body && $from) {
            // Extraer número limpio del remitente
            $cleanNumber = str_replace('whatsapp:', '', $from);
            $text = trim($body);
            
            Log::info("📱 Mensaje de {$cleanNumber}: {$text}");
            
            // Responder a comandos
            if (strtolower($text) === 'hola') {
                $whatsapp = app(WhatsAppService::class);
                $whatsapp->sendTextMessage($cleanNumber, "¡Hola! Soy el asistente veterinario TERE. 🐾 ¿En qué puedo ayudarte?");
            }
        }
        
        // Respuesta TwiML válida (sin usar método xml)
        return response()->make(
            '<?xml version="1.0" encoding="UTF-8"?><Response></Response>',
            200,
            ['Content-Type' => 'text/xml']
        );
    }
    
    public function verify(Request $request)
    {
        Log::info("Verificación de webhook");
        return response('OK', 200);
    }
}