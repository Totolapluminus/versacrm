<?php

namespace App\Events;

use App\Models\TelegramChat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StoreTelegramChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $chatPayload;

    /**
     * Create a new event instance.
     */
    public function __construct(TelegramChat $telegramChat)
    {
        $this->chatPayload = [
            'id' => $telegramChat->id,
            'telegram_user' => [
                'id' => $telegramChat->telegramUser?->id,
                'username' => $telegramChat->telegramUser?->username,
                'first_name' => $telegramChat->telegramUser?->first_name,
            ],
            'telegram_bot_id' => $telegramChat->telegram_bot_id,
            'user_id' => $telegramChat->user_id,
            'has_new' => $telegramChat->has_new,
            'chat_id' => $telegramChat->chat_id,
            'status' => $telegramChat->status,
            'ticket_id' => $telegramChat->ticket_id,
            'ticket_type' => $telegramChat->ticket_type,
            'last_message_in_text' => $telegramChat->last_message_in_text,
            'last_message_in_at' => $telegramChat->last_message_in_at,
            'last_message_in_human' => $telegramChat->last_message_in_human,
        ];

        Log::info('LOGGED CHAT', $telegramChat->toArray());
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('store-telegram-chat'),
        ];
    }
    public function broadcastAs(): string
    {
        return 'store-telegram-chat';
    }
    public function broadcastWith(): array
    {
        return [
            'telegramChat' => $this->chatPayload,
        ];
    }
}
