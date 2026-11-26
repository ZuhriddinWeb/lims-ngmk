<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LabController extends Controller
{
    /**
     * Список лабораторий в админке.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Labs/Index', [
            'labs' => Lab::ordered()->with('snapshot')->get()->map(fn (Lab $lab) => [
                'id' => $lab->id,
                'code' => $lab->code,
                'name_ru' => $lab->name_ru,
                'status' => $lab->status,
                'url' => $lab->url,
                'sort_order' => $lab->sort_order,
                'connected' => $lab->hasKey(),
                'online' => \App\Support\LabFeed::online($lab, $lab->snapshot?->payload ?? []),
                'last_seen' => $lab->last_seen_at?->toIso8601String(),
                'datasets' => $lab->snapshot?->payload['datasets'] ?? [],
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Labs/Create', [
            'nextSortOrder' => (int) (Lab::max('sort_order') ?? 0) + 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Lab::create($this->validated($request));

        return redirect()
            ->route('admin.labs.index')
            ->with('success', 'Лаборатория добавлена.');
    }

    public function edit(Lab $lab): Response
    {
        return Inertia::render('Admin/Labs/Edit', [
            'lab' => [
                'id' => $lab->id,
                'code' => $lab->code,
                'name_ru' => $lab->name_ru,
                'name_uz' => $lab->name_uz,
                'name_en' => $lab->name_en,
                'status' => $lab->status,
                'url' => $lab->url,
                'sort_order' => $lab->sort_order,
            ],
            'feed' => [
                'connected' => $lab->hasKey(),
                'key_hint' => $lab->keyHint(),
                'key_created_at' => $lab->key_created_at?->toIso8601String(),
                'last_seen' => $lab->last_seen_at?->toIso8601String(),
                'last_ip' => $lab->last_ip,
                'online' => \App\Support\LabFeed::online($lab, $lab->snapshot?->payload ?? []),
                'datasets' => $lab->snapshot?->payload['datasets'] ?? [],
                'bytes' => $lab->snapshot?->bytes,
                'interval' => $lab->snapshot?->payload['interval'] ?? null,
                'ingest_url' => url('/'),
            ],
            // yangi kalit faqat yaratilgan zahoti bir marta ko'rsatiladi
            'newKey' => session('new_key'),
        ]);
    }

    /** Ulanish kalitini yaratish yoki almashtirish (eski kalit darhol ishlamay qoladi) */
    public function issueKey(Lab $lab): RedirectResponse
    {
        $key = $lab->issueKey();

        return redirect()->route('admin.labs.edit', $lab)
            ->with('new_key', $key)
            ->with('success', 'Ulanish kaliti yaratildi — uni hozir nusxalab, zavod LIMS\'iga kiriting.');
    }

    /** Kalitni bekor qilish — zavod endi ma'lumot yubora olmaydi */
    public function revokeKey(Lab $lab): RedirectResponse
    {
        $lab->revokeKey();

        return redirect()->route('admin.labs.edit', $lab)->with('success', 'Ulanish kaliti bekor qilindi.');
    }

    public function update(Request $request, Lab $lab): RedirectResponse
    {
        $lab->update($this->validated($request, $lab->id));

        return redirect()
            ->route('admin.labs.index')
            ->with('success', 'Изменения сохранены.');
    }

    public function destroy(Lab $lab): RedirectResponse
    {
        $lab->snapshot()->delete();
        $lab->delete();

        return redirect()
            ->route('admin.labs.index')
            ->with('success', 'Лаборатория удалена.');
    }

    /**
     * Общие правила валидации для создания/обновления.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('labs', 'code')->ignore($ignoreId)],
            'name_ru' => ['required', 'string', 'max:255'],
            'name_uz' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in([Lab::STATUS_LIVE, Lab::STATUS_SOON])],
            'url' => ['nullable', 'string', 'max:255', 'url'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);
    }
}
