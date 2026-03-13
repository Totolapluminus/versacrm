<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramBot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'token' => 'encrypted',
    ];

    public function telegramChats() : HasMany {
        return $this->hasMany(TelegramChat::class);
    }

    public function users() : BelongsToMany {
        return $this->belongsToMany(User::class);
    }

    public function scopeVisibleToUser(Builder $query, User $user = null) : Builder {
        if (!$user) return $query;
        if ($user->role === 'admin') return $query;

        return $query->whereHas('users', function ($q) use ($user) {
            $q->whereKey($user->id);  // ТО ЖЕ САМОЕ ЧТО И  $q->where('id', $user->id)     (whereHas потому-что BELONGS TO MANY)
        });

    }
}
