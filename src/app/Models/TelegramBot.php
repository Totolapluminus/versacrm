<?php

namespace App\Models;

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
}
