<?php

namespace App\Policies;

use App\Models\TelegramChat;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;

class TelegramChatPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TelegramChat $telegramChat): bool
    {
        Log::debug('TelegramChatPolicy@view start', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'chat_id' => $telegramChat->id,
            'chat_user_id' => $telegramChat->user_id,
        ]);

        if ($user->role === 'admin') {
            Log::debug('TelegramChatPolicy@view allow: admin');
            return true;
        }

        Log::debug('TelegramChatPolicy@view result');

        return (int) $telegramChat->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TelegramChat $telegramChat): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TelegramChat $telegramChat): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TelegramChat $telegramChat): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TelegramChat $telegramChat): bool
    {
        return false;
    }
}
