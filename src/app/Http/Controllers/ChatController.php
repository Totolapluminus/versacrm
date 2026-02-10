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
    public function index(){
        $user = Auth::user();
        $botsQuery = TelegramBot::query()->select('id', 'username');
        if ($user->role !== 'admin') {
            $botsQuery->whereHas('users', fn($q) => $q->whereKey($user->id));
        }
        $bots = $botsQuery->with([
                'telegramChats' => function ($q) use ($user) {
                    $q->select('id','telegram_bot_id','telegram_user_id', 'user_id', 'status', 'has_new', 'ticket_id', 'ticket_type', 'ticket_domain')
                        ->addSelect([
                            'last_message_in_text' => TelegramMessage::select('text')
                                ->whereColumn('telegram_messages.telegram_chat_id', 'telegram_chats.id')
                                ->orderByDesc('id')
                                ->limit(1),

                            // если нужен ещё и час/дата
                            'last_message_in_at' => TelegramMessage::select('created_at')
                                ->whereColumn('telegram_messages.telegram_chat_id', 'telegram_chats.id')
                                ->orderByDesc('id')
                                ->limit(1),
                        ])
                        ->with(['telegramUser:id,username,first_name']);

                    if ($user->role !== 'admin') {
                        $q->where('user_id', $user->id);
                    }
                },
            ])
            ->get();

        $bots->each(function ($bot) {
            $bot->setRelation('closedChats', $bot->telegramChats->where('status', 'closed')->values());
            $bot->setRelation('telegramChats', $bot->telegramChats->where('status', '!=', 'closed')->values());
        });

        $bots->each(function ($bot) {
            foreach (['telegramChats', 'closedChats'] as $rel) {
                $bot->$rel->each(function ($chat) {
                    $chat->last_message_in_time = $chat->last_message_in_at
                        ? Carbon::parse($chat->last_message_in_at)
                            ->timezone('Europe/Moscow')
                            ->format('H:i')
                        : null;
                });
            }
        });

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

        if ($chat->has_new && $user->role !== 'admin') {
            $chat->update(['has_new' => false]);
        }

        $botsQuery = TelegramBot::query()->select('id', 'username');

        if ($user->role !== 'admin') {
            $botsQuery->whereHas('users', fn($q) => $q->whereKey($user->id));
        }

        $bots = $botsQuery->with([
            'telegramChats' => function ($q) use ($user) {
                $q->select('id','telegram_bot_id','telegram_user_id', 'user_id', 'status', 'has_new', 'ticket_id', 'ticket_type', 'ticket_domain')
                    ->addSelect([
                        'last_message_in_text' => TelegramMessage::select('text')
                            ->whereColumn('telegram_messages.telegram_chat_id', 'telegram_chats.id')
                            ->orderByDesc('id')
                            ->limit(1),

                        'last_message_in_at' => TelegramMessage::select('created_at')
                            ->whereColumn('telegram_messages.telegram_chat_id', 'telegram_chats.id')
                            ->orderByDesc('id')
                            ->limit(1),
                    ])
                    ->with(['telegramUser:id,username,first_name']);

                if ($user->role !== 'admin') {
                    $q->where('user_id', $user->id);
                }
            },
        ])->get();

        $bots->each(function ($bot) {
            $bot->setRelation('closedChats', $bot->telegramChats->where('status', 'closed')->values());
            $bot->setRelation('telegramChats', $bot->telegramChats->where('status', '!=', 'closed')->values());
        });

        $bots->each(function ($bot) {
            foreach (['telegramChats', 'closedChats'] as $rel) {
                $bot->$rel->each(function ($chat) {
                    $chat->last_message_in_time = $chat->last_message_in_at
                        ? Carbon::parse($chat->last_message_in_at)
                            ->timezone('Europe/Moscow')
                            ->format('H:i')
                        : null;
                });
            }
        });

        $chat->load([
            'telegramMessages',
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
