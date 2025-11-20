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

            $newTickets = TelegramChat::whereIn('telegram_bot_id', $botIds)
                ->where('status', 'open')
                ->count();

            $closedTickets = TelegramChat::whereIn('telegram_bot_id', $botIds)
                ->where('status', 'closed')
                ->count();

            $totalBots = $botIds->count();

            $totalMessages = TelegramMessage::whereHas('telegramChat', function ($q) use ($botIds) {
                $q->whereIn('telegram_bot_id', $botIds);
            })->count();

            $kpis = compact('newTickets', 'closedTickets', 'totalBots', 'totalMessages');

            $rows = TelegramMessage::selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
                ->whereHas('telegramChat', fn($q) => $q->whereIn('telegram_bot_id', $botIds))
                ->groupBy('day')->orderBy('day')->get();

            $labels = $rows->pluck('day');
            $series = [[
                'label' => 'Сообщения за день',
                'data' => $rows->pluck('cnt'),
            ]];
        } else {
            // Общая статистика (для админа)
            $newTickets = TelegramChat::where('status', 'open')->count();
            $closedTickets = TelegramChat::where('status', 'closed')->count();
            $totalBots = TelegramBot::count();
            $totalMessages = TelegramMessage::count();

            $kpis = compact('newTickets', 'closedTickets', 'totalBots', 'totalMessages');

            $rows = TelegramMessage::selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $labels = $rows->pluck('day');
            $series = [[
                'label' => 'Сообщения за день',
                'data' => $rows->pluck('cnt'),
            ]];
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
        ]);
    }
}
