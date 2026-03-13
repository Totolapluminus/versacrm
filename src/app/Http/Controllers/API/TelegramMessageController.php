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
use App\Services\OperatorPickerService;
use App\Services\TelegramApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramMessageController extends Controller
{
    use AuthorizesRequests;

    public function storeIn(StoreInRequest $request, TelegramApiService $tgApi, OperatorPickerService $pickerService)
    {
        $data = $request->validated();
        $path = null;

        try {
            $user = $pickerService->pickLeastOccupied($data['bot_db_id']);

            $telegramUser = TelegramUser::firstOrCreate(
                [
                    'telegram_id' => $data['user_id'],
                ],
                [
                    'username' => $data['user_username'] ?? null,
                    'first_name' => $data['user_first_name'] ?? null,
                    'last_name' => $data['user_last_name'] ?? null,
                ]
            );

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
                $tgApi->sendChannelMessageNewTicketNotification($chat, $telegramMessage, $telegramUser);
            }

            if ($chat->user->telegram_notifications_enabled) {
                $tgApi->sendOperatorMessageNewMessageNotification($chat, $telegramMessage, $telegramUser);
            }

            $chat->update(['has_new' => true]);
        }
        catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            report($e);
            return response()->json([
                'message' => 'Server store message error.',
            ], 500);
        }

        event(new StoreTelegramChatEvent($chat));
        event(new StoreTelegramMessageEvent($telegramMessage, $chat));
        event(new NewMessageNotificationEvent($chat));

        return response()->json(['status' => 'ok'], 200);
    }



    public function storeOut(StoreOutRequest $request, TelegramChat $chat, TelegramApiService $tgApi)
    {
        $this->authorize('view', $chat);

        $data = $request->validated();
        $chat->load('telegramBot:id,token');
        $token = $chat->telegramBot->token;
        $paths = [];

        try {
                $telegramMessage = TelegramMessage::Create([
                    'telegram_bot_id' => $chat->telegramBot->id,
                    'telegram_chat_id' => $chat->id,
                    'text' => $data['text'] ?? null,
                    'direction' => $data['direction'],
                ]);

                if ($request->hasFile('attachments')) {
                    $files = $request->file('attachments');
                    $isFirst = true;

                    foreach ($files as $file) {
                        $path = $file->store('attachments', 'public');
                        $paths[] = $path;

                        Attachment::Create([
                            'telegram_message_id' => $telegramMessage->id,
                            'path' => $path,
                        ]);
                        $filePath = storage_path('app/public/' . $path);

                        $tgApi->sendChatMessageAttachment($filePath, $token, $chat->chat_id, $isFirst, $telegramMessage->text ?? null);
                        $isFirst = false;

                    }
                } else {
                    $tgApi->sendChatMessageText($token, $chat->chat_id, $telegramMessage->text ?? null);
                }
            $telegramMessage = $telegramMessage->fresh()->load('attachments');
            if ($chat->status === 'open') {
                $chat->update(['status' => 'in_progress']);
            }
            return response()->json($telegramMessage, 201);

        }
        catch (\Throwable $e) {
            if (!empty($paths)) {
                foreach ($paths as $path) {
                    Storage::disk('public')->delete($path);
                }
            }
            report($e);
            return response()->json([
                'message' => 'Server store message error.',
            ], 500);
        }

    }
}
