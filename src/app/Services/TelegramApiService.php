<?php

namespace App\Services;

use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramApiService
{
    public function sendChatMessageText(string $botToken, int $chatId, string $text = null) : array
    {
        return Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ])->throw()->json();

    }

    public function sendChatMessageAttachment(string $filePath, string $botToken, int $chatId, bool $isFirst = true, string $caption = null)
    {
        $stream = fopen($filePath, 'r');
        try {
            return Http::attach('photo', $stream, basename($filePath))
                ->post("https://api.telegram.org/bot{$botToken}/sendPhoto", [
                    'chat_id' => $chatId,
                    'caption' => $isFirst ? $caption : null,
                ])->throw()->json();
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

    }

    public function sendOperatorMessageNewMessageNotification(TelegramChat $chat, TelegramMessage $telegramMessage, TelegramUser $telegramUser) : void{
        $attachmentsText = $telegramMessage->attachments()->exists() ? '*Изображение*' : '';
        $username = $telegramUser->username ?? $telegramUser->first_name;
        $token = $chat->telegramBot->token;
        $text = (
            "<b>{$username}:</b>\n"
            . "{$attachmentsText}"
            . "{$telegramMessage->text}"
        );
        $this->sendChatMessageText($token, (int)$chat->user->telegram_id, $text);
    }

    public function sendChannelMessageNewTicketNotification(TelegramChat $chat, TelegramMessage $telegramMessage, TelegramUser $telegramUser) : void
    {
        $firstName = $telegramUser->first_name ?? '';
        $lastName = $telegramUser->last_name ?? '';
        $username = $telegramUser->username ?? '';
        $replyMarkup = null;
        $chatUrl = route('chat.show', ['chat' => $chat->id]);

        if (config('app.env') === 'product') {
            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Открыть чат',
                            'url' => $chatUrl
                        ],
                        [
                            'text' => 'Взять в работу',
                            'callback_data' => 'take:' . $chat->id,
                        ]
                    ]
                ]
            ];
        }

        $token = $chat->telegramBot->token;
        $text = (
            "🆘 <b>Новое обращение в техподдержку</b>\n"
            . "<b>Номер:</b> <b>{$chat->ticket_id}</b>\n"
            . "<b>Сайт:</b> {$chat->ticket_domain}\n"
            . "<b>Категория:</b> {$chat->ticket_type}\n"
            . "<b>От:</b> {$firstName} {$lastName} <code>@{$username}</code>\n"
            . "<b>User ID:</b> <code>{$telegramUser->telegram_id}</code>\n"
            . "<b>Оператор:</b> <b>{$chat->user->name}</b>\n"
            . "<b>Bot:</b> <code>" . ($chat->telegramBot->username) . "</code> (db_id=<code>{$chat->telegramBot->id}</code>)\n\n"
            . "<b>Описание:</b>\n{$telegramMessage->text}\n"
        );
        $tgChannelId = config('myapp.support_chat_id');

        $this->sendChannelMessage($token, (int)$tgChannelId, $text, $replyMarkup ?? null);

    }

    public function sendChannelMessageReassignedTicketNotification(TelegramChat $chat, string $oldOperatorName, string $newOperatorName) : void{
        $bot = $chat->telegramBot;
        $chatUrl = route('chat.show', ['chat' => $chat->id]);
        $replyMarkup = null;

        if(config('app.env') === 'product') {
            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Открыть чат',
                            'url' => $chatUrl
                        ]
                    ]
                ]
            ];
        }

        $tgChannelId = config('myapp.support_chat_id');
        $text = (
            "⚠️ <b>Переназначение обращения: </b>\n"
            . "<b>От:</b> <b> {$oldOperatorName}</b>\n"
            . "<b>Кому:</b> {$newOperatorName}\n"
            . "<b>Номер:</b> <b>{$chat->ticket_id}</b>\n"
            . "<b>Категория:</b> {$chat->ticket_type}\n"
            . "<b>Bot:</b> {$bot->username}\n"
        );

        $this->sendChannelMessage($bot?->token, (int)$tgChannelId, $text, $replyMarkup ?? null);
    }

    public function sendChannelMessageClosedTicketByOperatorNotification(TelegramChat $chat): void
    {
        $bot = $chat->telegramBot;
        $tgChannelId = config('myapp.support_chat_id');
        $text = (
            "✅ <b>Заявка закрыта ОПЕРАТОРОМ: </b>\n"
            . "<b>Номер:</b> <b>{$chat->ticket_id}</b>\n"
            . "<b>Категория:</b> {$chat->ticket_type}\n"
            . "<b>Оператор:</b> {$chat->user->name}\n"
        );
        $this->sendChannelMessage($bot?->token, (int)$tgChannelId, $text);
    }

    public function sendChannelMessageClosedTicketByTelegramUserNotification(TelegramChat $chat) : void{
        $bot = $chat->telegramBot;
        $tgChannelId = config('myapp.support_chat_id');
        $text = (
            "✅ <b>Заявка закрыта ПОЛЬЗОВАТЕЛЕМ: </b>\n"
            . "<b>Номер:</b> <b>{$chat->ticket_id}</b>\n"
            . "<b>Категория:</b> {$chat->ticket_type}\n"
        );

        $this->sendChannelMessage($bot?->token, (int)$tgChannelId, $text);
    }


    public function sendChannelMessage(string $botToken, int $chatId, string $text, array $replyMarkup = null) : void
    {
        if (!$botToken) return;

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
