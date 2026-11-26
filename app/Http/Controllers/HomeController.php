<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Support\LabFeed;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Bosh sahifa: kombinat bo'yicha jonli ko'rsatkichlar + har laboratoriya kartasi.
     * Sonlar zavod LIMS'laridan keladi (faqat zavod admini ruxsat bergan bloklar).
     */
    public function index(Request $request): Response
    {
        $labs = Lab::ordered()->with('snapshot')->get();
        $live = $labs->where('status', Lab::STATUS_LIVE)->count();
        $public = (bool) config('portal.public_stats') || $request->user();

        return Inertia::render('Home', [
            'labs' => $labs->map(fn (Lab $l) => $public ? LabFeed::card($l) : $l->toPublicArray())->values(),
            'stats' => [
                'total' => $labs->count(),
                'live' => $live,
                'soon' => $labs->count() - $live,
            ],
            'totals' => $public ? LabFeed::totals($labs) : null,
            'statsVisible' => (bool) $public,
        ]);
    }
}
