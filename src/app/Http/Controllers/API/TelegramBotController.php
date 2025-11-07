<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TelegramBot;
use Illuminate\Http\Request;

class TelegramBotController extends Controller
{
    public function index(){

        $bots = TelegramBot::all(['id', 'token']);

        return response()->json($bots);
    }
}
