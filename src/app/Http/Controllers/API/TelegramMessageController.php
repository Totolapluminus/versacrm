<?php

namespace App\Http\Controllers\API;

use App\Events\NewMessageNotificationEvent;
use App\Events\StoreTelegramChatEvent;
use App\Events\StoreTelegramMessageEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\TelegramMessage\StoreInRequest;
use App\Http\Requests\TelegramMessage\StoreOutRequest;
use App\Http\Resources\TelegramMessageResource;
use App\Models\Attachment;
use App\Models\TelegramBot;
use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use App\Models\User;
use App\Services\TelegramApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramMessageController extends Controller
{
    use AuthorizesRequests;
    public function storeIn(StoreInRequest $request, TelegramApiService $tgApi){

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
                'ticket_domain' => $data['ticket_domain'],
            ]
        );

        $telegramMessage = TelegramMessage::Create([
            'telegram_user_id' => $telegramUser->id,
            'telegram_chat_id' => $chat->id,
            'text' => $data['text'] ?? '',
            'direction' => $data['direction'],
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('attachments', 'public');
            Attachment::Create([
                'telegram_message_id' => $telegramMessage->id,
                'path' => $path,
            ]);
        }

        if ($chat->wasRecentlyCreated) {
            $firstName = $telegramUser->first_name ?? '';
            $lastName  = $telegramUser->last_name ?? '';
            $username  = $telegramUser->username ?? '';

            $chatUrl = route('chat.show', ['chat' => $chat->id]);

            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Открыть чат',
                            'url' => $chatUrl
                        ],
                        [
                            'text' => 'Взять в работу',
                            'callback_data' => 'take:' . $chat->id,
                        ]
                    ]
                ]
            ];

            $token = $chat->telegramBot->token;

            $text = (
                "🆘 <b>Новое обращение в техподдержку</b>\n"
                . "<b>Номер:</b> <b>{$data['ticket_id']}</b>\n"
                . "<b>Сайт:</b> {$data['ticket_domain']}\n"
                . "<b>Категория:</b> {$data['ticket_type']}\n"
                . "<b>От:</b> {$firstName} {$lastName} <code>@{$username}</code>\n"
                . "<b>User ID:</b> <code>{$telegramUser->telegram_id}</code>\n"
                . "<b>Оператор:</b> <b>{$chat->user->name}</b>\n"
                . "<b>Bot:</b> <code>" . ($chat->telegramBot->username) . "</code> (db_id=<code>{$chat->telegramBot->id}</code>)\n\n"
                . "<b>Описание:</b>\n{$telegramMessage->text}\n"
            );
            $tgChannelId = config('myapp.support_chat_id');
            $tgApi->sendMessage($token, (int)$tgChannelId, $text, $replyMarkup);
        }


        $chat->update(['has_new' => true]);

        $chat = TelegramChat::query()
            ->select('id','telegram_bot_id','telegram_user_id', 'user_id', 'status', 'has_new', 'chat_id', 'ticket_id', 'ticket_type', 'ticket_domain',)
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
