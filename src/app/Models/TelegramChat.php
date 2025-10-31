<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramChat extends Model
{
    protected $guarded = [];

    public function telegramUsers() : BelongsToMany{
        return $this->belongsToMany(TelegramUser::class, 'telegram_chat_user');
    }

    public function telegramMessages() : HasMany {
        return $this->hasMany(TelegramMessage::class);
    }
}
