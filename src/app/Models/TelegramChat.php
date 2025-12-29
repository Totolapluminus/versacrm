<?php

namespace App\Models;

use App\Traits\HasHumanTime;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramChat extends Model
{
    use HasHumanTime;

    protected $appends = ['last_message_in_human'];

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

    public function user() : BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function getLastMessageInHumanAttribute(): ?string
    {
        if (!$this->last_message_in_at) {
            return null;
        }

        $date = $this->last_message_in_at instanceof Carbon
            ? $this->last_message_in_at
            : Carbon::parse($this->last_message_in_at);

        return $this->humanizeDate($date);
    }

}
