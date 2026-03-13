<?php

namespace App\Services;

use App\Models\User;

class OperatorPickerService
{
    public function pickLeastOccupied(int $botId): User {
        return User::query()
            ->where('role', 'operator')
            ->whereHas('telegramBots', function ($query) use ($botId) {
                $query->where('telegram_bots.id', $botId);
            })
            ->withCount(['telegramChats as active_chats_count' => function ($query) use ($botId) {
                $query->whereIn('status', ['open', 'in_progress']);
            }])
            ->orderBy('active_chats_count', 'asc')
            ->orderBy('id', 'asc')
            ->firstOrFail();
    }
}
