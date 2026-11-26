<?php

namespace App\Http\Middleware;

use App\Models\Lab;
use App\Support\LabFeed;
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

            // Yon panel: laboratoriyalar ro'yxati va aloqa holati (sonlar yashirilgan bo'lsa — faqat status)
            'nav' => fn () => [
                'labs' => Lab::ordered()->with('snapshot')->get()->map(fn (Lab $l) => LabFeed::navItem($l, (bool) config('portal.public_stats') || $request->user()))->values(),
            ],

            // Flash-сообщения для тостов/уведомлений в админке.
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
