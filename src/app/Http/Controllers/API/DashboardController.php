<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TelegramBot;
use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getKpi(){
        $newTickets = TelegramChat::where('status', 'open')->count();
        $closedTickets = TelegramChat::where('status', 'closed')->count();
        $totalBots = TelegramBot::count();
        $totalMessages = TelegramMessage::count();

        return [
            'newTickets' => $newTickets,
            'closedTickets' => $closedTickets,
            'totalBots' => $totalBots,
            'totalMessages' => $totalMessages,
        ];
    }

    public function getKpiByBot(Request $request){
        // 1) границы периода по данным
        $bounds = DB::table('telegram_chats')
            ->selectRaw('MIN(DATE(created_at)) as dmin, MAX(DATE(created_at)) as dmax')
            ->first();

        if (!$bounds || !$bounds->dmin || !$bounds->dmax) {
            return ['labels' => [], 'series' => []];
        }

        $from = Carbon::parse($bounds->dmin);
        $to   = Carbon::parse($bounds->dmax);

        // 2) все даты (не пропуская дни)
        $labels = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $labels[] = $d->toDateString();
        }

        // 3) агрегаты: чаты/день/бот
        $rows = DB::table('telegram_chats')
            ->selectRaw('telegram_bot_id, DATE(created_at) as day, COUNT(*) as cnt')
            ->groupBy('telegram_bot_id', 'day')
            ->orderBy('day')
            ->get();

        if ($rows->isEmpty()) {
            return ['labels' => $labels, 'series' => []];
        }

        // 4) метки ботов
        $botIds = $rows->pluck('telegram_bot_id')->unique()->values();
        $bots = DB::table('telegram_bots')
            ->whereIn('id', $botIds)
            ->select('id')
            ->get()
            ->mapWithKeys(fn($b) => [$b->id => "bot#{$b->id}"]);

        // 5) быстрый лук-ап [bot_id][day] => cnt
        $map = [];
        foreach ($rows as $r) {
            $map[$r->telegram_bot_id][$r->day] = (int)$r->cnt;
        }

        // 6) собираем серии под Chart.js
        $series = [];
        foreach ($botIds as $botId) {
            $label = $bots[$botId]->label ?? ("bot#".$botId);
            $data = [];
            foreach ($labels as $day) {
                $data[] = $map[$botId][$day] ?? 0;
            }
            $series[] = ['label' => $label, 'data' => $data];
        }

        return [
            'labels' => $labels, // дни
            'series' => $series, // по боту: [{label, data: [..counts per day..]}]
        ];
    }
}
