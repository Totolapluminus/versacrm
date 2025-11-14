<?php

namespace App\Http\Controllers;

use App\Models\TelegramBot;
use App\Models\TelegramChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index(){
        $bots = TelegramBot::query()
            ->select('id')
            ->whereHas('users', fn($q) => $q->whereKey(auth()->id()))
            ->with([
                'telegramChats' => function ($q) {
                    $q->select('id','telegram_bot_id','telegram_user_id')
                        ->with(['telegramUser:id,username']);
                },
            ])
            ->get();
        return Inertia::render('Chat/Index', [
            'bots' => $bots,
        ]);
    }

    public function show(TelegramChat $chat){
        $bots = TelegramBot::query()
            ->select('id')
            ->whereHas('users', fn($q) => $q->whereKey(auth()->id()))
            ->with([
                'telegramChats' => function ($q) {
                    $q->select('id','telegram_bot_id','telegram_user_id')
                        ->with(['telegramUser:id,username']);
                },
            ])
            ->get();
        $chat->load([
            'telegramMessages'
        ]);
        return Inertia::render('Chat/Show', [
            'bots' => $bots,
            'current_chat' => $chat
        ]);
    }
}
