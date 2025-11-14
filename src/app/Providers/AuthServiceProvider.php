<?php

namespace App\Providers;

use App\Models\TelegramChat;
use App\Policies\TelegramChatPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{

    protected $policies = [
        TelegramChat::class => TelegramChatPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::define('admin', fn($user) => $user->role === 'admin');
    }
}
