<?php

namespace App\Policies;

use App\Models\TelegramChat;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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
        if ($user->role === 'admin') {
            return true;
        }

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
