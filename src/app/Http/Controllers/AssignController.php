<?php

namespace App\Http\Controllers;

use App\Http\Requests\Assign\StoreRequest;
use App\Models\TelegramBot;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssignController extends Controller
{
    public function index(){

        $users = User::with('telegramBots')->where('role', 'operator')->get();

        $bots = TelegramBot::select('id', 'username')->get();
        return Inertia::render('Assign/Index', [
            'users' => $users,
            'bots' => $bots,
        ]);
    }

    public function store(StoreRequest $request){

        $data = $request->validated();

        $user = User::where('role', 'operator')->findOrFail($data['user_id']);
        $user->telegramBots()->sync($data['bot_ids'] ?? []);

        return redirect()->route('assign.index')->with('success', 'Связи обновлены');
    }
}
