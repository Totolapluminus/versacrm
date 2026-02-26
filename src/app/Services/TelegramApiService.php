<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramApiService
{
    public function sendMessage(string $botToken, int $chatId, string $text, array $replyMarkup = null): void
    {
        if(!$botToken) return;

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE); //Параметр для кириллицы в логах, телеграм и так понимает
        }

        Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", $payload)->throw()->json();
    }
}
