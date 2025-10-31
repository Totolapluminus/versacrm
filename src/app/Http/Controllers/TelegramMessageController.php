<?php

namespace App\Http\Controllers;

use App\Http\Requests\TelegramMessage\StoreInRequest;
use App\Http\Requests\TelegramMessage\StoreOutRequest;
use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramMessageController extends Controller
{

    public function storeIn(StoreInRequest $request){

        $data = $request->validated();

        $chat = TelegramChat::firstOrCreate([
            'chat_id' => $data['chat_id'],
            'type' => $data['chat_type']
        ]);

        $user = TelegramUser::firstOrCreate([
            'username' => $data['user_username'] ?? null,
            'telegram_id' => $data['user_id'],
        ]);

        if(isset($chat) && isset($user)){
            $chat->telegramUsers()->syncWithoutDetaching([$user->id]);
        }

        TelegramMessage::firstOrCreate([
            'telegram_user_id' => TelegramUser::where('telegram_id', $data['user_id'])->first()->id,
            'telegram_chat_id' => TelegramChat::where('chat_id', $data['chat_id'])->first()->id,
            'text' => $data['text'],
            'direction' => $data['direction'],
        ]);

        return response()->json(['status' => 'ok'], 200);
    }


    public function storeOut(StoreOutRequest $request){

        $data = $request->validated();

        $token = env('TELEGRAM_BOT_TOKEN');

        $telegram_bot_id = Http::get('https://api.telegram.org/bot' . $token . '/getMe')->json('result.id');

        $telegram_chat_id = TelegramChat::where('chat_id', $data['telegram_chat_raw_id'])->first()->id;

        $res = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $data['telegram_chat_raw_id'],
            'text' => $data['text'],
        ])->throw()->json();

        $user = TelegramUser::firstOrCreate([
            'telegram_id' => $telegram_bot_id,
            'is_bot' => true
        ]);

        $user->telegramChats()->syncWithoutDetaching([$telegram_chat_id]);

        $message = TelegramMessage::firstOrCreate([
            'telegram_user_id' => TelegramUser::where('telegram_id', $telegram_bot_id)->first()->id,
            'telegram_chat_id' => $telegram_chat_id,
            'text' => $data['text'],
            'direction' => $data['direction'],
        ]);

        return response()->json($message, 201);
    }
}
