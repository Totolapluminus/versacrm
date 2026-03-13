<?php

namespace App\Http\Controllers;

use App\Models\TelegramBot;
use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\User;
use App\Services\DashboardDataService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardDataService $dashboardDataService)
    {
        $user = Auth::user();
        $operatorId = $request->get('operator_id');  //ID который приходит при выборе конкретного оператора в выпадающем списке

        if ($user->role === 'operator') {
            $targetUser = $user;
        } elseif ($user->role === 'admin' && $operatorId) {
            $targetUser = User::findOrFail($operatorId);
        } else {
            $targetUser = null;
        }

        // Селект админа
        $operators = [];
        if ($user->role === 'admin') {
            $operators = User::where('role', 'operator')->select('id', 'name')->orderBy('name')->get();
        }

        $newTickets = TelegramChat::visibleToUser($targetUser)->where('status', 'open')->count();
        $activeTickets = TelegramChat::visibleToUser($targetUser)->where('status', 'in_progress')->count();
        $closedTickets = TelegramChat::visibleToUser($targetUser)->where('status', 'closed')->count();
        $totalBots = TelegramBot::visibleToUser($targetUser)->count();
        $totalMessages = TelegramMessage::whereHas('telegramChat', fn($query) => $query->visibleToUser($targetUser))->count();
        $mostLoadedBot = TelegramBot::visibleToUser($targetUser)
            ->withCount(['telegramChats as visible_telegram_chats_count' => fn($query) => $query->visibleToUser($targetUser)])
            ->orderBy('visible_telegram_chats_count', 'desc')
            ->value('username');

        $chats = TelegramChat::visibleToUser($targetUser)->with(['firstMessageIn', 'firstMessageOut'])->get();
        $avgResponseTime = $dashboardDataService->ticketAvgResponseTime($chats);

        $closedChats = TelegramChat::visibleToUser($targetUser)->where('status', 'closed')->get();
        $avgCloseTime = $dashboardDataService->ticketAvgCloseTime($closedChats);

        $rows = TelegramMessage::whereHas('telegramChat', fn($query) => $query->visibleToUser($targetUser))->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')->groupBy('day')->orderBy('day')->get();
        $chartByDayData = $dashboardDataService->chartByDayData($rows);

        $weeklyRows = TelegramChat::visibleToUser($targetUser)->selectRaw("DATE_TRUNC('week', created_at) as week, COUNT(*) as cnt")->groupBy('week')->orderBy('week')->get();
        $chartByWeekData = $dashboardDataService->chartByWeekData($weeklyRows);

        $kpis = compact('newTickets', 'activeTickets', 'closedTickets', 'totalBots',
            'totalMessages', 'avgResponseTime', 'mostLoadedBot', 'avgCloseTime');

        return Inertia::render('Dashboard', [
            'user' => $user,
            'operators' => $operators,
            'selectedOperatorId' => $operatorId,
            'kpis' => $kpis,
            'chart' => $chartByDayData,
            'weeklyChart' => $chartByWeekData,
        ]);
    }
}
