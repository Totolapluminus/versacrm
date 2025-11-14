<?php

namespace App\Http\Controllers\API;

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
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramMessageController extends Controller
{
    use AuthorizesRequests;
    public function storeIn(StoreInRequest $request){

        $data = $request->validated();

        $user = TelegramUser::firstOrCreate([
            'username' => $data['user_username'] ?? null,
            'telegram_id' => $data['user_id'],
        ]);

        $chat = TelegramChat::firstOrCreate([
            'telegram_bot_id' => $data['bot_db_id'],
            'telegram_user_id' => $user->id,
            'chat_id' => $data['chat_id'],
            'type' => $data['chat_type'],
            'status' => 'open',
        ]);

        $telegramMessage = TelegramMessage::Create([
             'telegram_user_id' => $user->id,
            'telegram_chat_id' => $chat->id,
            'text' => $data['text'],
            'direction' => $data['direction'],
        ]);

        Log::info($telegramMessage);

        if($chat->wasRecentlyCreated){
            event(new StoreTelegramChatEvent($chat));
        }

        event(new StoreTelegramMessageEvent($telegramMessage, $chat->id));

        return response()->json(['status' => 'ok'], 200);
    }


    public function storeOut(StoreOutRequest $request, TelegramChat $chat){

        $this->authorize('view', $chat);

        $data = $request->validated();

        $chat = TelegramChat::query()
            ->select('id','telegram_bot_id','chat_id')
            ->with(['telegramBot:id,token'])
            ->findOrFail($data['telegram_chat_db_id']);

        $bot = $chat->telegramBot;

        $token = $bot->token;

        $res = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chat['chat_id'],
            'text' => $data['text'],
        ])->throw()->json();

        ////

        $message = TelegramMessage::Create([
            'telegram_bot_id' => $bot['id'],
            'telegram_chat_id' => $chat['id'],
            'text' => $data['text'],
            'direction' => $data['direction'],
        ]);

        return response()->json($message, 201);
    }
}
