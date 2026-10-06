<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Главная «обложка» портала ЛИМС: список лабораторий + статистика.
     */
    public function index(): Response
    {
        $labs = Lab::ordered()->get();

        $live = $labs->where('status', Lab::STATUS_LIVE)->count();

        return Inertia::render('Home', [
            'labs' => $labs->map->toPublicArray()->values(),
            'stats' => [
                'total' => $labs->count(),
                'live' => $live,
                'soon' => $labs->count() - $live,
            ],
        ]);
    }
}
