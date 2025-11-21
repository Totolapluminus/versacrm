<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TelegramChat;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    use AuthorizesRequests;

    public function updateStatus(Request $request, TelegramChat $chat){

        //ДОБАВИТЬ ОТДЕЛЬНЫЙ UPDATE REQUEST

        $this->authorize('view', $chat);

        $request->validate([
            'status' => 'required|in:open,in_progress,closed'
        ]);

        $chat->update(['status' => $request->status]);
        return response()->json(['status' => 'ok']);
    }

    public function updateOperator(Request $request, TelegramChat $chat)
    {
        $this->authorize('view', $chat);

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $operator = User::where('id', $request->user_id)
            ->where('role', 'operator')
            ->firstOrFail();

        $chat->update([
            'user_id' => $operator->id,
        ]);

        return response()->json(['status' => 'ok']);
    }

}
