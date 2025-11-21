<?php

use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\TelegramBotController;
use App\Http\Controllers\API\TelegramMessageController;
use App\Models\TelegramChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/telegram-bots', [TelegramBotController::class, 'index'])->middleware('auth:sanctum');

Route::get('/dashboard/getKpi', [DashboardController::class, 'getKpi']);
Route::get('/dashboard/getKpiByBot', [DashboardController::class, 'getKpiByBot']);

Route::post('/messages', [TelegramMessageController::class, 'storeIn'])->name('messages.storeIn');
Route::post('/chat/{chat}', [TelegramMessageController::class, 'storeOut'])->name('messages.storeOut')->middleware('auth:sanctum');

Route::put('/chat/{chat}/status', [\App\Http\Controllers\API\ChatController::class, 'updateStatus'])->name('chat.updateStatus')->middleware('auth:sanctum');
Route::put('/chat/{chat}/operator', [\App\Http\Controllers\API\ChatController::class, 'updateOperator'])->name('chat.updateOperator')->middleware('auth:sanctum');


//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');
