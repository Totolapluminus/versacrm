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

class StoreTelegramChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $telegramChat;

    /**
     * Create a new event instance.
     */
    public function __construct(TelegramChat $telegramChat)
    {
        $this->telegramChat = $telegramChat;
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
            'telegramChat' => [
                'id' => $this->telegramChat->id,
                'telegram_user' => [
                    'id' => $this->telegramChat->telegramUser?->id,
                    'username' => $this->telegramChat->telegramUser?->username,
                    'first_name' => $this->telegramChat->telegramUser?->first_name,
                ],
                'telegram_bot_id' => $this->telegramChat->telegram_bot_id,
                'user_id' => $this->telegramChat->user_id
            ]
        ];
    }
}
