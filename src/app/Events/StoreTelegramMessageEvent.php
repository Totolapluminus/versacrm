<?php

namespace App\Events;

use App\Http\Resources\TelegramMessageResource;
use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StoreTelegramMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private TelegramMessage $telegramMessage;
    private TelegramChat $telegramChat;

    /**
     * Create a new event instance.
     */
    public function __construct(TelegramMessage $telegramMessage, TelegramChat $telegramChat)
    {
        $this->telegramMessage = $telegramMessage;
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
            new PrivateChannel('store-telegram-message-to-chat-' . $this->telegramChat->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'store-telegram-message-to-chat';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->telegramMessage->load('attachments');

        return [
            'telegramMessage' => TelegramMessageResource::make($this->telegramMessage)->resolve()
        ];
    }
}
