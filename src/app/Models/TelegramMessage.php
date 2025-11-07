<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    protected $guarded = [];

    public function telegramUser() : BelongsTo {
        return $this->belongsTo(TelegramUser::class);
    }
    public function telegramBot() : BelongsTo {
        return $this->belongsTo(TelegramBot::class);
    }

    public function telegramChat() : BelongsTo {
        return $this->belongsTo(TelegramChat::class);
    }
}
