<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TelegramMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'telegram_bot_id' => $this->telegram_bot_id,
            'telegram_user_id' => $this->telegram_user_id,
            'telegram_chat_id' => $this->telegram_chat_id,
            'direction' => $this->direction,
            'text' => $this->text,
            'raw' => $this->raw,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'time_human' => $this->time_human,
        ];
    }
}
