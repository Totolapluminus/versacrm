<?php

namespace App\Models;

use App\Traits\HasHumanTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TelegramMessage extends Model
{
    use HasHumanTime;

    protected $guarded = [];

    protected $appends = ['time_human', 'attachments_urls'];

    public function telegramUser() : BelongsTo {
        return $this->belongsTo(TelegramUser::class);
    }
    public function telegramBot() : BelongsTo {
        return $this->belongsTo(TelegramBot::class);
    }

    public function telegramChat() : BelongsTo {
        return $this->belongsTo(TelegramChat::class);
    }

    public function attachments() : HasMany {
        return $this->hasMany(Attachment::class);
    }

    public function getTimeHumanAttribute(): string
    {
        return $this->humanizeDate($this->created_at);
    }

    public function getAttachmentsUrlsAttribute() {
        $attachmentsUrls = [];
        foreach ($this->attachments as $attachment) {
            $attachmentsUrls[] = Storage::url($attachment->path);
        }
        return $attachmentsUrls;
    }
}
