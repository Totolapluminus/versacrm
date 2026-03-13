<?php

namespace App\Http\Controllers;

use App\Events\StoreTelegramChatEvent;
use App\Models\TelegramBot;
use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $bots = TelegramBot::query()
            ->select('id', 'username')
            ->visibleToUser($user)
            ->with([
                'telegramChats' => function ($q) use ($user) {
                    $q->visibleToUser($user)
                        ->orderByLastMessage()
                        ->with([
                            'telegramUser:id,username,first_name',
                            'lastMessage.attachments'
                        ]);
                },
            ])->get();

        return Inertia::render('Chat/Index', [
            'bots' => $bots,
            'user' => $user,
        ]);
    }

    public function show(TelegramChat $chat)
    {
        $operators = User::where('role', 'operator')->select('id', 'name')->get();
        $user = Auth::user();
        if ($chat->has_new && $user->role !== 'admin') {
            $chat->update(['has_new' => false]);
        }

        $bots = TelegramBot::query()
            ->select('id', 'username')
            ->visibleToUser($user)
            ->with([
                'telegramChats' => function ($q) use ($user) {
                    $q->visibleToUser($user)
                        ->orderByLastMessage()
                        ->with([
                            'telegramUser:id,username,first_name',
                            'lastMessage.attachments'
                        ]);
                },
            ])->get();

        $chat->load([
            'telegramMessages.attachments',
            'telegramUser:id,username,first_name',
        ]);


        return Inertia::render('Chat/Show', [
            'operators' => $operators,
            'bots' => $bots,
            'current_chat' => $chat,
            'user' => $user,
        ]);
    }
}
