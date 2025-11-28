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
                $q->whereIn('status', ['open', 'active']);
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
            ],
            [
                'type' => $data['chat_type'],
                'status' => 'open',
                'user_id' => $user?->id,
            ]
        );

        $telegramMessage = TelegramMessage::Create([
            'telegram_user_id' => $telegramUser->id,
            'telegram_chat_id' => $chat->id,
            'text' => $data['text'],
            'direction' => $data['direction'],
        ]);

        $chat->update(['has_new' => true]);

        Log::info($chat);

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
