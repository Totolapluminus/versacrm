<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function checkOperator(Request $request) {
        $data = $request->validate([
            'bot_db_id' => ['required', 'integer'],
            'telegram_id' => ['required', 'integer'],
        ]);

        $isOperator = User::telegramOperatorForBot($data['telegram_id'], $data['bot_db_id'])->exists();

        return response()->json([
            'is_operator' => $isOperator,
            'bot_db_id' => $data['bot_db_id'],
            'telegram_id' => $data['telegram_id'],
        ]);
    }

    public function toggleNotificationMode(Request $request) {
        $data = $request->validate([
            'bot_db_id' => ['required', 'integer'],
            'telegram_id' => ['required', 'integer'],
            'enabled' => ['required', 'boolean'],
        ]);

        $operator = User::telegramOperatorForBot($data['telegram_id'], $data['bot_db_id'])->first();

        if($operator){
            $operator->telegram_notifications_enabled = $data['enabled'];
            $operator->save();

            return response()->json([
                'status' => 'ok',
                'is_operator' => (bool) $operator,
                'enabled' => (bool) $operator->telegram_notifications_enabled,
            ]);

        }

        return response()->json([
            'status' => 'error',
            'message' => 'Operator not found',
        ], 404);
    }
}
