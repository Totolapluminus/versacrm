<?php

namespace App\Http\Controllers;

use App\Models\TelegramBot;
use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Для админа можно выбрать оператора
        $operatorId = $request->get('operator_id');

        if ($user->role === 'operator') {
            $targetUser = $user;
        } elseif ($user->role === 'admin' && $operatorId) {
            $targetUser = User::findOrFail($operatorId);
        } else {
            $targetUser = null;
        }

        $chatQuery = TelegramChat::query();
        $messageQuery = TelegramMessage::query();
        $botQuery = TelegramBot::query();

        if ($targetUser) {
            $botIds = $targetUser->telegramBots()->pluck('telegram_bots.id');
            $chatIds = $targetUser->telegramChats()->pluck('telegram_chats.id');

            $chatQuery->whereIn('id', $chatIds);
            $messageQuery->whereIn('telegram_chat_id', $chatIds);
            $botQuery->whereIn('id', $botIds);
        }

        // Статистика

        $newTickets = (clone $chatQuery)->where('status', 'open')->count();
        $activeTickets = (clone $chatQuery)->where('status', 'in_progress')->count();
        $closedTickets = (clone $chatQuery)->where('status', 'closed')->count();
        $totalBots = (clone $botQuery)->count();
        $totalMessages = (clone $messageQuery)->count();
        $mostLoadedBot = (clone $botQuery)->withCount('telegramChats')
            ->orderByDesc('telegram_chats_count')
            ->pluck('username')
            ->first();


        $chats = (clone $chatQuery)->get();
        $responseTimes = [];
        foreach ($chats as $chat) {

            $firstIn = (clone $messageQuery)
                ->where('telegram_chat_id', $chat->id)
                ->where('direction', 'in')
                ->orderBy('created_at')
                ->first();

            $firstOut = (clone $messageQuery)
                ->where('telegram_chat_id', $chat->id)
                ->where('direction', 'out')
                ->orderBy('created_at')
                ->first();

            if ($firstIn && $firstOut) {
                $seconds = abs($firstOut->created_at->diffInSeconds($firstIn->created_at));
                $responseTimes[] = $seconds;
            }
        }
        $avgResponseTime = count($responseTimes) ? round(array_sum($responseTimes) / count($responseTimes)) : 0;

        $closedChats = (clone $chatQuery)->where('status', 'closed')->get();
        $closeTimes = [];
        foreach ($closedChats as $chat) {
            if ($chat->created_at && $chat->updated_at) {
                $diff = abs($chat->updated_at->diffInSeconds($chat->created_at, false));
                $closeTimes[] = $diff;
            }
        }
        $avgCloseTime = count($closeTimes) ? round(array_sum($closeTimes) / count($closeTimes)) : 0;


        $kpis = compact('newTickets', 'activeTickets', 'closedTickets', 'totalBots',
            'totalMessages', 'avgResponseTime', 'mostLoadedBot', 'avgCloseTime');

        // ГРАФИК ПО ДНЯМ
        $rows = (clone $messageQuery)->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
        $labels = $rows->pluck('day');
        $series = [[
            'label' => 'Сообщения за день',
            'data' => $rows->pluck('cnt'),
        ]];


        // ГРАФИК ПО НЕДЕЛЯМ
        $weeklyRows = (clone $chatQuery)->selectRaw("DATE_TRUNC('week', created_at) as week, COUNT(*) as cnt")
            ->groupBy('week')
            ->orderBy('week')
            ->get();
        $weeklyLabels = $weeklyRows->pluck('week')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));
        $weeklySeries = $weeklyRows->pluck('cnt');


        // СЕЛЕКТ АДМИНА
        $operators = [];
        if ($user->role === 'admin') {
            $operators = User::where('role', 'operator')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('Dashboard', [
            'user' => $user,
            'operators' => $operators,
            'selectedOperatorId' => $operatorId,
            'kpis' => $kpis,
            'chart' => [
                'labels' => $labels,
                'series' => $series,
            ],
            'weeklyChart' => [
                'labels' => $weeklyLabels,
                'series' => [[
                    'label' => 'Обращения за неделю',
                    'data' => $weeklySeries,
                ]],
            ],
        ]);
    }
}
