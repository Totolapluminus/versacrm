<?php

namespace App\Models;

use App\Traits\HasHumanTime;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TelegramChat extends Model
{
    use HasHumanTime;

    protected $appends = [
        'last_message_in_human',
        'last_message_in_time',
        'created_at_formatted',
    ];

    protected $guarded = [];

    public function telegramMessages() : HasMany {
        return $this->hasMany(TelegramMessage::class);
    }

    public function lastMessage() : HasOne {
        return $this->hasOne(TelegramMessage::class)->orderBy('created_at', 'desc');    // ->latestOfMany()
    }

    public function firstMessageIn() : HasOne {
        return $this->hasOne(TelegramMessage::class)->where('direction', 'in')->orderBy('created_at'); // ->oldestOfMany()
    }

    public function firstMessageOut() : HasOne {
        return $this->hasOne(TelegramMessage::class)->where('direction', 'out')->orderBy('created_at'); // ->oldestOfMany()
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

    public function getLastMessageInTimeAttribute() {
        $lastMessage = $this->resolveLastMessage();

        if (!$lastMessage->created_at) {
            return null;
        }
        return Carbon::parse($lastMessage->created_at)->timezone('Europe/Moscow')->format('H:i');

    }

    public function getCreatedAtFormattedAttribute() {
        return $this->created_at?->timezone('Europe/Moscow')->format('d.m.Y H:i');

    }

    public function getLastMessageInHumanAttribute(): ?string {
        $lastMessage = $this->resolveLastMessage();

        if (!$lastMessage->created_at) {
            return null;
        }
        $date = $lastMessage->created_at instanceof Carbon
            ? $lastMessage->created_at
            : Carbon::parse($lastMessage->created_at);

        return $this->humanizeDate($date);

    }

    public function scopeVisibleToUser(Builder $query, User $user = null) : Builder {
        if (!$user) return $query;
        if ($user->role === 'admin') return $query;

        return $query->where('user_id', $user->id);

    }

    public function scopeOrderByLastMessage(Builder $query) : Builder {
        return $query->addSelect([
            'last_message_created_at' => TelegramMessage::select('created_at')
                ->whereColumn('telegram_messages.telegram_chat_id', 'telegram_chats.id')
                ->orderByDesc('created_at')
                ->limit(1),
        ])->orderByDesc('last_message_created_at');

    }

    public function resolveLastMessage(): ?TelegramMessage {
        if ($this->relationLoaded('telegramMessages')) {
            return $this->telegramMessages
                ->sortByDesc('created_at')
                ->first();
        }
        if ($this->relationLoaded('lastMessage')) {
            return $this->lastMessage;
        }
        return $this->lastMessage;

    }
}
