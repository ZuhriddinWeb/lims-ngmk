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
            'labs' => Lab::ordered()->get()->map(fn (Lab $lab) => [
                'id' => $lab->id,
                'code' => $lab->code,
                'name_ru' => $lab->name_ru,
                'status' => $lab->status,
                'url' => $lab->url,
                'sort_order' => $lab->sort_order,
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
        ]);
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
