<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramUser extends Model
{
    protected $guarded = [];

    public function telegramMessages() : HasMany {
        return $this->hasMany(TelegramMessage::class);
    }

    public function telegramChats() : BelongsToMany {
        return $this->belongsToMany(TelegramChat::class, 'telegram_chat_user');
    }
}
