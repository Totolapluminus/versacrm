<?php

namespace App\Http\Controllers;

use App\Models\TelegramChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index(){
        $chats = TelegramChat::with(['telegramUsers', 'telegramMessages'])->get();
        return Inertia::render('Chat/Index', [
            'chats' => $chats,
        ]);
    }

    public function show(TelegramChat $chat){
        $chat->load([
            'telegramUsers',
            'telegramMessages'
        ]);
        $chats = TelegramChat::with(['telegramUsers'])->get();
        return Inertia::render('Chat/Show', [
            'chats' => $chats,
            'current_chat' => $chat
        ]);
    }
}
