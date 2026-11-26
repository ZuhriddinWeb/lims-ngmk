<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Support\LabFeed;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabPageController extends Controller
{
    /** Laboratoriyaning jonli sahifasi: zavod ulashgan barcha bloklar */
    public function show(Request $request, Lab $lab): Response
    {
        $lab->load('snapshot');
        $public = (bool) config('portal.public_stats') || $request->user();

        return Inertia::render('Lab', [
            'lab' => $public ? LabFeed::detail($lab) : $lab->toPublicArray(),
            'statsVisible' => (bool) $public,
        ]);
    }
}
