<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

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
        'ingest_key' => \App\Casts\SafeEncrypted::class, // ochilmasa — null (sahifa yiqilmaydi)
        'key_created_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /** Ulanish kaliti hech qachon JSON / Inertia props'ga tushmaydi */
    protected $hidden = ['ingest_key'];

    public function snapshot(): HasOne
    {
        return $this->hasOne(LabSnapshot::class);
    }

    /**
     * Yangi ulanish kaliti: ngmk1.<id>.<48 belgi>. Zavod LIMS'iga bir marta kiritiladi;
     * bu yerda shifrlangan holda saqlanadi va qayta ko'rsatilmaydi.
     */
    public function issueKey(): string
    {
        $key = 'ngmk1.' . $this->id . '.' . Str::random(48);
        $this->forceFill(['ingest_key' => $key, 'key_created_at' => now()])->save();

        return $key;
    }

    public function revokeKey(): void
    {
        $this->forceFill(['ingest_key' => null, 'key_created_at' => null])->save();
    }

    public function hasKey(): bool
    {
        return ! empty($this->ingest_key);
    }

    /** Bazada kalit bor, lekin bu serverning APP_KEY'i bilan ochilmaydi — qayta yaratish kerak */
    public function keyBroken(): bool
    {
        return ! empty($this->getRawOriginal('ingest_key')) && ! $this->hasKey();
    }

    public function keyHint(): ?string
    {
        if (! $this->hasKey()) {
            return null;
        }
        $parts = explode('.', $this->ingest_key);

        return ($parts[0] ?? '') . '.' . ($parts[1] ?? '') . '.' . substr($parts[2] ?? '', 0, 4) . '…' . substr($this->ingest_key, -4);
    }

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
