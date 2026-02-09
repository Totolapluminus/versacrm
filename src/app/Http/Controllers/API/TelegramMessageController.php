<?php

namespace App\Http\Controllers\API;

use App\Events\NewMessageNotificationEvent;
use App\Events\StoreTelegramChatEvent;
use App\Events\StoreTelegramMessageEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\TelegramMessage\StoreInRequest;
use App\Http\Requests\TelegramMessage\StoreOutRequest;
use App\Http\Resources\TelegramMessageResource;
use App\Models\TelegramBot;
use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramMessageController extends Controller
{
    use AuthorizesRequests;
    public function storeIn(StoreInRequest $request){

        $data = $request->validated();

        $user = User::where('role', 'operator')
        ->whereHas('telegramBots', function ($q) use ($data) {
            $q->where('telegram_bots.id', $data['bot_db_id']);
        })
            ->withCount(['telegramChats as active_chats_count' => function($q) {
                $q->whereIn('status', ['open', 'in_progress']);
            }])
            ->orderBy('active_chats_count', 'asc')
            ->orderBy('id', 'asc')
            ->first();

        $telegramUser = TelegramUser::firstOrCreate([
            'username' => $data['user_username'] ?? null,
            'first_name' => $data['user_first_name'] ?? null,
            'last_name' => $data['user_last_name'] ?? null,
            'telegram_id' => $data['user_id'],
        ]);

        $chat = TelegramChat::firstOrCreate(
            [
                'telegram_bot_id' => $data['bot_db_id'],
                'telegram_user_id' => $telegramUser->id,
                'chat_id' => $data['chat_id'],
                'ticket_id' => $data['ticket_id'],
            ],
            [
                'type' => $data['chat_type'],
                'status' => 'open',
                'user_id' => $user?->id,
                'ticket_type' => $data['ticket_type'],
            ]
        );

        $telegramMessage = TelegramMessage::Create([
            'telegram_user_id' => $telegramUser->id,
            'telegram_chat_id' => $chat->id,
            'text' => $data['text'],
            'direction' => $data['direction'],
        ]);


        if ($chat->wasRecentlyCreated) {
            $firstName = $telegramUser->first_name ?? '';
            $lastName  = $telegramUser->last_name ?? '';
            $username  = $telegramUser->username ?? '';

            $token = $chat->telegramBot->token;
            $text = (
                "🆘 <b>Новое обращение в техподдержку</b>\n"
                . "<b>Номер:</b> <b>{$data['ticket_id']}</b>\n"
                . "<b>Категория:</b> {$data['ticket_type']}\n"
                . "<b>От:</b> {$firstName} {$lastName} <code>@{$username}</code>\n"
                . "<b>User ID:</b> <code>{$telegramUser->telegram_id}</code>\n"
                . "<b>Bot:</b> <code>" . ($chat->telegramBot->username) . "</code> (db_id=<code>{$chat->telegramBot->id}</code>)\n\n"
                . "<b>Описание:</b>\n{$telegramMessage->text}\n"
            );

            $res = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => '-1003777308302',
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);

            if (!$res->successful()) {
                logger()->error('Telegram sendMessage failed', [
                    'status' => $res->status(),
                    'body' => $res->body(),
                ]);
            }
        }


        $chat->update(['has_new' => true]);

        $chat = TelegramChat::query()
            ->select('id','telegram_bot_id','telegram_user_id', 'user_id', 'status', 'has_new', 'chat_id', 'ticket_id', 'ticket_type')
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
            ->with(['telegramUser:id,username,first_name'])
            ->findOrFail($chat->id);

//        Log::info("LOGGED CHAT",$chat->toArray());

        event(new StoreTelegramChatEvent($chat));
        event(new StoreTelegramMessageEvent($telegramMessage, $chat));
        event(new NewMessageNotificationEvent($chat));

        return response()->json(['status' => 'ok'], 200);
    }


    public function storeOut(StoreOutRequest $request, TelegramChat $chat){

        $this->authorize('view', $chat);

        $data = $request->validated();

        $chat->load('telegramBot:id,token');

        $token = $chat->telegramBot->token;

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chat['chat_id'],
            'text' => $data['text'],
        ])->throw()->json();


        $message = TelegramMessage::Create([
            'telegram_bot_id' => $chat->telegramBot->id,
            'telegram_chat_id' => $chat['id'],
            'text' => $data['text'],
            'direction' => $data['direction'],
        ]);

        if($chat->status === 'open'){
            $chat->update(['status'=>'in_progress']);
        }

        return response()->json($message, 201);
    }
}
