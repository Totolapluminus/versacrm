<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TelegramMessage\StoreInRequest;
use App\Http\Requests\TelegramMessage\StoreOutRequest;
use App\Models\TelegramBot;
use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramMessageController extends Controller
{

    public function storeIn(StoreInRequest $request){

        $data = $request->validated();

        Log::info('Incoming', $data);

        //ОПТИМИЗИРОВАТЬ (запомнить в переменную)?

        $user = TelegramUser::firstOrCreate([
            'username' => $data['user_username'] ?? null,
            'telegram_id' => $data['user_id'],
        ]);

        //ОПТИМИЗИРОВАТЬ (запомнить в переменную)?

        $chat = TelegramChat::firstOrCreate([
            'telegram_bot_id' => $data['bot_db_id'],
            'telegram_user_id' => $user->id,
            'chat_id' => $data['chat_id'],
            'type' => $data['chat_type'],
            'status' => 'open',
        ]);

        TelegramMessage::firstOrCreate([
            'telegram_user_id' => $user->id,
            'telegram_chat_id' => $chat->id,
            'text' => $data['text'],
            'direction' => $data['direction'],
        ]);

        return response()->json(['status' => 'ok'], 200);
    }


    public function storeOut(StoreOutRequest $request){

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

        $message = TelegramMessage::firstOrCreate([
            'telegram_bot_id' => $bot['id'],
            'telegram_chat_id' => $chat['id'],
            'text' => $data['text'],
            'direction' => $data['direction'],
        ]);

        return response()->json($message, 201);
    }
}
