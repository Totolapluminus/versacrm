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
            $targetUser = User::find($operatorId);
        } else {
            $targetUser = null;
        }

        if ($targetUser) {
            $botIds = $targetUser->telegramBots()->pluck('telegram_bots.id');
            $chatIds = $targetUser->telegramChats()->pluck('telegram_chats.id');


            $newTickets = TelegramChat::whereIn('id', $chatIds)
                ->where('status', 'open')
                ->count();
            $activeTickets = TelegramChat::whereIn('id', $chatIds)
                ->where('status', 'in_progress')
                ->count();
            $closedTickets = TelegramChat::whereIn('id', $chatIds)
                ->where('status', 'closed')
                ->count();
            $totalBots = $botIds->count();
            $totalMessages = TelegramMessage::whereHas('telegramChat', function ($q) use ($botIds) {
                $q->whereIn('telegram_bot_id', $botIds);
            })->count();
            $mostLoadedBot = TelegramBot::withCount('telegramChats')
                ->orderByDesc('telegram_chats_count')
                ->pluck('username')
                ->first();

            $chats = TelegramChat::whereIn('id', $chatIds)->get();
            $responseTimes = [];
            foreach ($chats as $chat) {
                $firstIn = TelegramMessage::where('telegram_chat_id', $chat->id)
                    ->where('direction', 'in')
                    ->orderBy('created_at')
                    ->first();

                $firstOut = TelegramMessage::where('telegram_chat_id', $chat->id)
                    ->where('direction', 'out')
                    ->orderBy('created_at')
                    ->first();

                if ($firstIn && $firstOut) {
                    $seconds = abs($firstOut->created_at->diffInSeconds($firstIn->created_at));
                    $responseTimes[] = $seconds;
                }
            }
            $avgResponseTime = count($responseTimes) ? round(array_sum($responseTimes) / count($responseTimes)) : 0;

            $closedChats = TelegramChat::whereIn('id', $chatIds)
                ->where('status', 'closed')
                ->get();
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


            $rows = TelegramMessage::selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
                ->whereHas('telegramChat', fn($q) => $q->whereIn('telegram_bot_id', $botIds))
                ->groupBy('day')->orderBy('day')->get();
            $labels = $rows->pluck('day');
            $series = [[
                'label' => 'Сообщения за день',
                'data' => $rows->pluck('cnt'),
            ]];


            // --- График обращений по неделям ---
            $weeklyRows = TelegramChat::whereIn('id', $chatIds)
                ->selectRaw("DATE_TRUNC('week', created_at) as week, COUNT(*) as cnt")
                ->groupBy('week')
                ->orderBy('week')
                ->get();
            $weeklyLabels = $weeklyRows->pluck('week')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));
            $weeklySeries = $weeklyRows->pluck('cnt');


        } else {
            // Общая статистика (для админа)
            $newTickets = TelegramChat::where('status', 'open')->count();
            $activeTickets = TelegramChat::where('status', 'in_progress')->count();
            $closedTickets = TelegramChat::where('status', 'closed')->count();
            $totalBots = TelegramBot::count();
            $totalMessages = TelegramMessage::count();
            $mostLoadedBot = TelegramBot::withCount('telegramChats')
                ->orderByDesc('telegram_chats_count')
                ->pluck('username')
                ->first();

            $chats = TelegramChat::all();
            $responseTimes = [];
            foreach ($chats as $chat) {

                $firstIn = TelegramMessage::where('telegram_chat_id', $chat->id)
                    ->where('direction', 'in')
                    ->orderBy('created_at')
                    ->first();

                $firstOut = TelegramMessage::where('telegram_chat_id', $chat->id)
                    ->where('direction', 'out')
                    ->orderBy('created_at')
                    ->first();

                if ($firstIn && $firstOut) {
                    $seconds = abs($firstOut->created_at->diffInSeconds($firstIn->created_at));
                    $responseTimes[] = $seconds;
                }
            }
            $avgResponseTime = count($responseTimes) ? round(array_sum($responseTimes) / count($responseTimes)) : 0;

            $closedChats = TelegramChat::where('status', 'closed')->get();
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


            $rows = TelegramMessage::selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
                ->groupBy('day')
                ->orderBy('day')
                ->get();
            $labels = $rows->pluck('day');
            $series = [[
                'label' => 'Сообщения за день',
                'data' => $rows->pluck('cnt'),
            ]];


            // --- График обращений по неделям ---
            $weeklyRows = TelegramChat::selectRaw("DATE_TRUNC('week', created_at) as week, COUNT(*) as cnt")
                ->groupBy('week')
                ->orderBy('week')
                ->get();
            $weeklyLabels = $weeklyRows->pluck('week')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));
            $weeklySeries = $weeklyRows->pluck('cnt');


        }

        // Для селекта админа — список всех операторов
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
                'series' => $weeklySeries,
            ],
        ]);
    }
}
