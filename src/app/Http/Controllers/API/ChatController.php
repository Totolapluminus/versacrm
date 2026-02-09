<?php

namespace App\Http\Controllers\API;

use App\Events\StoreTelegramChatEvent;
use App\Http\Controllers\Controller;
use App\Models\TelegramChat;
use App\Models\User;
use App\Services\TelegramApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    use AuthorizesRequests;

    public function updateStatus(Request $request, TelegramChat $chat, TelegramApiService $tgApi){

        //ДОБАВИТЬ ОТДЕЛЬНЫЙ UPDATE REQUEST

        $this->authorize('view', $chat);

        $data = $request->validate([
            'status' => 'required|in:open,in_progress,closed'
        ]);

        $oldStatus = $chat->status;
        $newStatus = $data['status'];

        $chat->update(['status' => $newStatus]);

        if ($oldStatus !== 'closed' && $newStatus === 'closed'){
            try {
                $bot = $chat->telegramBot;
                $text = "Заявка закрыта. \nЕсли нужно — создайте новую, нажав кнопку или написав /start.";
                $tgApi->sendMessage($bot?->token, (int)$chat->chat_id, $text);

                $tgChannelId = config('myapp.support_chat_id');
                $tgText = (
                    "✅ <b>Заявка закрыта ОПЕРАТОРОМ: </b>\n"
                    . "<b>Номер:</b> <b>{$chat->ticket_id}</b>\n"
                    . "<b>Категория:</b> {$chat->ticket_type}\n"
                    . "<b>Оператор:</b> {$chat->user->name}\n"
                );
                $tgApi->sendMessage($bot?->token, (int)$tgChannelId, $tgText);

            } catch (\Exception $e) {
                Log::error('Сообщение о закрытии чата не было отправлено в бот', [
                    'chat_id' => $chat->id,
                    'telegram_chat_id' => $chat->chat_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return response()->json(['status' => 'ok']);
    }

    public function updateOperator(Request $request, TelegramChat $chat, TelegramApiService $tgApi)
    {
        $this->authorize('view', $chat);

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $oldOperatorName = $chat->user->name;
        $operator = User::where('id', $request->user_id)
            ->where('role', 'operator')
            ->firstOrFail();
        $newOperatorName = $operator->name;

        $chat->update([
            'user_id' => $operator->id,
        ]);

        $bot = $chat->telegramBot;
        $tgChannelId = config('myapp.support_chat_id');
        $text = (
            "⚠️ <b>Переназначение обращения: </b>\n"
            . "<b>От:</b> <b> {$oldOperatorName}</b>\n"
            . "<b>Кому:</b> {$newOperatorName}\n"
            . "<b>Номер:</b> <b>{$chat->ticket_id}</b>\n"
            . "<b>Категория:</b> {$chat->ticket_type}\n"
            . "<b>Bot:</b> {$bot->username}\n"
        );

        $tgApi->sendMessage($bot?->token, (int)$tgChannelId, $text);

        return response()->json(['status' => 'ok']);
    }

    public function getStatus(Request $request){
        $data = $request->validate([
            'telegram_bot_id' => 'required|exists:telegram_bots,id',
            'chat_id' => 'required|exists:telegram_chats,chat_id',
        ]);

        $chat = TelegramChat::query()
            ->where('telegram_bot_id', $data['telegram_bot_id'])
            ->where('chat_id', $data['chat_id'])
            ->latest('id')
            ->first();

        return response()->json([
            'exists' => (bool)$chat,
            'chat_db_id' => $chat?->id,
            'chat_status' => $chat?->status ?? null,
        ]);
    }

    public function closeChat(Request $request, TelegramApiService $tgApi){
        $data = $request->validate([
            'telegram_bot_id' => 'required|exists:telegram_bots,id',
            'chat_id' => 'required|exists:telegram_chats,chat_id',
        ]);

        $chat = TelegramChat::query()
            ->where('telegram_bot_id', $data['telegram_bot_id'])
            ->where('chat_id', $data['chat_id'])
            ->whereIn('status', ['open', 'in_progress'])
            ->latest('id')
            ->first();

        if (!$chat) {
            return response()->json(['status' => 'ok', 'closed' => false]);
        }

        $chat->update(['status' => 'closed']);

        $bot = $chat->telegramBot;
        $tgChannelId = config('myapp.support_chat_id');
        $text = (
            "✅ <b>Заявка закрыта ПОЛЬЗОВАТЕЛЕМ: </b>\n"
            . "<b>Номер:</b> <b>{$chat->ticket_id}</b>\n"
            . "<b>Категория:</b> {$chat->ticket_type}\n"
        );

        $tgApi->sendMessage($bot?->token, (int)$tgChannelId, $text);
        event(new StoreTelegramChatEvent($chat));
        return response()->json(['status' => 'ok', 'closed' => true, 'chat_db_id' => $chat->id]);
    }

    public function destroy(TelegramChat $chat)
    {
        $this->authorize('view', $chat);

        abort_unless($chat->status === 'closed', 422, 'Можно удалить только закрытый чат.');

        DB::transaction(function () use ($chat) {
            $chat->telegramMessages()->delete();
            $chat->delete();
        });

        return response()->json(['status' => 'ok']);
    }

}
