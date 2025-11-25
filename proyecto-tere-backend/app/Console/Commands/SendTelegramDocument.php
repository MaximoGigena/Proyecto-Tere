<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;

class SendTelegramDocument extends Command
{
    protected $signature = 'telegram:send-document 
                           {file : Path to the PDF file}
                           {--chat_id= : Chat ID}
                           {--caption= : Document caption}';
    
    protected $description = 'Send PDF document via Telegram';

    public function handle()
    {
        $telegramService = new TelegramService();
        $file = $this->argument('file');
        $chatId = $this->option('chat_id') ?? config('telegram.chat_id');
        $caption = $this->option('caption');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $result = $telegramService->sendDocument($chatId, $file, $caption);
        
        if ($result['ok']) {
            $this->info('Document sent successfully!');
        } else {
            $this->error('Error sending document: ' . $result['description']);
        }

        return 0;
    }
}