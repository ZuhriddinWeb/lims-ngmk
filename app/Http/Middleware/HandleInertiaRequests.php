<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Корневой Blade-шаблон, который оборачивает первоначальную загрузку страницы.
     */
    protected $rootView = 'app';

    /**
     * Определяет текущую версию ассетов.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Данные, доступные всем страницам (shared props).
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'appName' => config('app.name'),

            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                    ]
                    : null,
            ],

            // Flash-сообщения для тостов/уведомлений в админке.
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
