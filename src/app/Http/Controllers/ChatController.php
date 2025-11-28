<?php

namespace App\Http\Controllers;

use App\Events\StoreTelegramChatEvent;
use App\Models\TelegramBot;
use App\Models\TelegramChat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index(){
        $user = Auth::user();
        $bots = TelegramBot::query()
            ->select('id', 'username')
            ->whereHas('users', fn($q) => $q->whereKey(auth()->id()))
            ->with([
                'telegramChats' => function ($q) {
                    $q->select('id','telegram_bot_id','telegram_user_id', 'user_id', 'status', 'has_new')
                        ->with(['telegramUser:id,username,first_name']);
                },
            ])
            ->get();
        return Inertia::render('Chat/Index', [
            'bots' => $bots,
            'user' => $user,
        ]);
    }

    public function show(TelegramChat $chat){
        $operators = User::where('role', 'operator')
            ->select('id', 'name')
            ->get();
        $user = Auth::user();
        if($chat->has_new) {
            $chat->update(['has_new' => false]);
        }
        $bots = TelegramBot::query()
            ->select('id', 'username')
            ->whereHas('users', fn($q) => $q->whereKey(auth()->id()))
            ->with([
                'telegramChats' => function ($q) {
                    $q->select('id','telegram_bot_id','telegram_user_id', 'user_id', 'status', 'has_new')
                        ->with(['telegramUser:id,username,first_name']);
                },
            ])
            ->get();
        $chat->load([
            'telegramMessages'
        ]);
        return Inertia::render('Chat/Show', [
            'operators' => $operators,
            'bots' => $bots,
            'current_chat' => $chat,
            'user' => $user,
        ]);
    }
}
