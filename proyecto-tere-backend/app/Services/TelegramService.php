<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TelegramService
{
    protected $token;
    protected $baseUrl;

    public function __construct()
    {
        $this->token = config('telegram.bot_token');
        $this->baseUrl = "https://api.telegram.org/bot{$this->token}";
    }

    public function sendMessage($chatId, $message)
    {
        $response = Http::post("{$this->baseUrl}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);

        return $response->json();
    }

    public function sendDocument($chatId, $documentPath, $caption = null)
    {
        $response = Http::attach(
            'document',
            file_get_contents($documentPath),
            basename($documentPath)
        )->post("{$this->baseUrl}/sendDocument", [
            'chat_id' => $chatId,
            'caption' => $caption,
        ]);

        return $response->json();
    }

    public function sendDocumentFromStorage($chatId, $storagePath, $caption = null)
    {
        $fullPath = Storage::path($storagePath);
        
        return $this->sendDocument($chatId, $fullPath, $caption);
    }
}