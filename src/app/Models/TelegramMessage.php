<?php

namespace App\Models;

use App\Traits\HasHumanTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    use HasHumanTime;

    protected $guarded = [];

    protected $appends = ['time_human'];

    public function telegramUser() : BelongsTo {
        return $this->belongsTo(TelegramUser::class);
    }
    public function telegramBot() : BelongsTo {
        return $this->belongsTo(TelegramBot::class);
    }

    public function telegramChat() : BelongsTo {
        return $this->belongsTo(TelegramChat::class);
    }

    public function getTimeHumanAttribute(): string
    {
        return $this->humanizeDate($this->created_at);
    }
}
