<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramChat extends Model
{
    protected $guarded = [];

    public function telegramMessages() : HasMany {
        return $this->hasMany(TelegramMessage::class);
    }

    public function telegramUser() : BelongsTo{
        return $this->belongsTo(TelegramUser::class);
    }

    public function telegramBot() : BelongsTo{
        return $this->belongsTo(TelegramBot::class);
    }
}
