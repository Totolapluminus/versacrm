<?php

use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\TelegramBotController;
use App\Http\Controllers\API\TelegramMessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/telegram-bots', [TelegramBotController::class, 'index'])->middleware('auth:sanctum');

Route::get('/dashboard/getKpi', [DashboardController::class, 'getKpi']);
Route::get('/dashboard/getKpiByBot', [DashboardController::class, 'getKpiByBot']);

Route::post('/messages', [TelegramMessageController::class, 'storeIn']);
Route::post('/chat', [TelegramMessageController::class, 'storeOut']);

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');
