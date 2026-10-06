<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Lab extends Model
{
    public const STATUS_LIVE = 'live';
    public const STATUS_SOON = 'soon';

    protected $fillable = [
        'code',
        'name_ru',
        'name_uz',
        'name_en',
        'status',
        'url',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Есть ли у лаборатории корректный http(s)-адрес.
     */
    public function hasAddress(): bool
    {
        return (bool) $this->url && (bool) preg_match('#^https?://#i', $this->url);
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    /**
     * Представление лаборатории для фронтенда (Inertia).
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => [
                'ru' => $this->name_ru,
                'uz' => $this->name_uz,
                'en' => $this->name_en,
            ],
            'status' => $this->status,
            'url' => $this->url,
            'has_address' => $this->hasAddress(),
        ];
    }

    /**
     * Локальный скоуп: сортировка по порядку вывода.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
