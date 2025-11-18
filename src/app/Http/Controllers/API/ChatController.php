<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TelegramChat;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    use AuthorizesRequests;

    public function updateStatus(Request $request, TelegramChat $chat){

        //ДОБАВИТЬ ОТДЕЛЬНЫЙ UPDATE REQUEST

        $this->authorize('view', $chat);

        $request->validate(['status' => 'required|in:open,in_progress,closed']);

        $chat->update(['status' => $request->status]);

        return response()->json(['status' => 'ok']);
    }
}
