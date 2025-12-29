<?php

namespace App\Http\Middleware;

use App\Models\TelegramChat;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'api_token' => fn () => $request->session()->pull('api_token'),
                'success'   => fn () => $request->session()->pull('success'),
                'error'     => fn () => $request->session()->pull('error'),
            ],
            'unreadChatIds' => fn () => $request->user()
                ? TelegramChat::query()
                    ->where('has_new', true)
                    ->when(
                        $request->user()->role !== 'admin',
                        fn ($q) => $q->where('user_id', $request->user()->id)
                    )
                    ->pluck('id')
                : [],
        ]);
    }
}
