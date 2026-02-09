<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramApiService
{
    public function sendMessage(string $botToken, int $chatId, string $text): void
    {
        if(!$botToken) return;

        Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
        ])->throw()->json();
    }
}
