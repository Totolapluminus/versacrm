<?php

use App\Http\Controllers\TelegramMessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/messages', [TelegramMessageController::class, 'storeIn']);
Route::post('/chat', [TelegramMessageController::class, 'storeOut']);

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');
