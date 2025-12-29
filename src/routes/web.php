<?php

use App\Http\Controllers\API\TelegramMessageController;
use App\Http\Controllers\AssignController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::get('/chat/{chat}', [ChatController::class, 'show'])->name('chat.show')->middleware(['auth', 'can:view,chat']);

Route::get('/assign', [AssignController::class, 'index'])->name('assign.index')->middleware(['auth', 'can:admin']);
Route::post('/assign', [AssignController::class, 'store'])->name('assign.store')->middleware(['auth', 'can:admin']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
