<?php

namespace App\Traits;

use Carbon\Carbon;

trait HasHumanTime
{
    protected function humanizeDate(?Carbon $date): ?string
    {
        if (!$date) return null;

        $dt  = $date->copy()->timezone('Europe/Moscow');
        $now = now('Europe/Moscow');

        if ($dt->isToday()) {
            return $dt->format('H:i');
        }

        if ($dt->isYesterday()) {
            return 'вчера';
        }

        $days = $dt->startOfDay()->diffInDays($now->startOfDay());

        return $days.' '.$this->pluralizeDays($days).' назад';
    }

    protected function pluralizeDays(int $n): string
    {
        $nMod10  = $n % 10;
        $nMod100 = $n % 100;

        if ($nMod100 >= 11 && $nMod100 <= 14) {
            return 'дней';
        }

        return match ($nMod10) {
            1 => 'день',
            2, 3, 4 => 'дня',
            default => 'дней',
        };
    }
}
