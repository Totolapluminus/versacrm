<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $guarded = false;

    public function telegramMessage() : BelongsTo {
        return $this->belongsTo(TelegramMessage::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Attachment $attachment) {
            if ($attachment->path) {
                Storage::disk('public')->delete($attachment->path);
            }
        });
    }
}
