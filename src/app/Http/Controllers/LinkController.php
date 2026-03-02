<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LinkController extends Controller
{
    public function create(){
        return Inertia::render('Link/Create');
    }

    public function store(Request $request){
        $data = $request->validate([
            'telegram_id' => 'required|integer',
        ]);
        $user = $request->user();

        $user->telegram_id = $data['telegram_id'];
        $user->save();

        return redirect()->back()->with('success', 'Telegram ID привязан');
    }
}
