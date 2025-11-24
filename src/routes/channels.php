<?php

use App\Models\TelegramChat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('store-telegram-message-to-chat-{chatId}', function ($user, $chatId) {
    $chat = TelegramChat::find($chatId);
    if (!$chat) return false;
    return $user->id === $chat->user_id;
});
