<?php

namespace App\Services;

use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class DashboardDataService
{
    public function ticketAvgResponseTime(Collection $chats) {
        $responseTimes = [];
        foreach ($chats as $chat) {
            $firstIn = $chat->firstMessageIn;
            $firstOut = $chat->firstMessageOut;

            if ($firstIn && $firstOut) {
                $seconds = abs($firstOut->created_at->diffInSeconds($firstIn->created_at));
                $responseTimes[] = $seconds;
            }
        }
        return count($responseTimes) ? round(array_sum($responseTimes) / count($responseTimes)) : 0;

    }

    public function ticketAvgCloseTime(Collection $closedChats) {
        $closeTimes = [];
        foreach ($closedChats as $chat) {
            if ($chat->created_at && $chat->updated_at) {
                $diff = abs($chat->updated_at->diffInSeconds($chat->created_at, false));
                $closeTimes[] = $diff;
            }
        }
        return count($closeTimes) ? round(array_sum($closeTimes) / count($closeTimes)) : 0;
    }

    public function chartByDayData(Collection $rows){
        $labels = $rows->pluck('day');
        $series = [[
            'label' => 'Сообщения за день',
            'data' => $rows->pluck('cnt'),
        ]];
        return [
            'labels' => $labels,
            'series' => $series
        ];
    }

    public function chartByWeekData(Collection $weeklyRows){
        $weeklyLabels = $weeklyRows->pluck('week')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));
        $weeklySeries = $weeklyRows->pluck('cnt');
        return [
            'labels' => $weeklyLabels,
            'series' => [
                [
                    'label' => 'Обращения за неделю',
                    'data' => $weeklySeries,
                ],
            ]
        ];
    }
}
