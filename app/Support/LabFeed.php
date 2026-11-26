<?php

namespace App\Support;

use App\Models\Lab;
use App\Models\LabSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Zavod LIMS'idan kelgan snapshot'ni sahifalar uchun tayyorlaydi.
 *
 * Qoidalar:
 *  - «onlayn» — oxirgi qabul zavod oralig'i × 2 + zaxira daqiqa ichida bo'lsa;
 *  - «bugun» sonlari faqat bugun tuzilgan snapshot'dan olinadi (kechagi son bugungi deb ko'rsatilmaydi);
 *  - zavod ulashmagan blok umuman ko'rsatilmaydi (null).
 */
class LabFeed
{
    public const KPI = ['received', 'completed', 'analyses'];

    /** Bosh sahifa kartasi */
    public static function card(Lab $lab): array
    {
        $snap = $lab->relationLoaded('snapshot') ? $lab->snapshot : $lab->snapshot()->first();
        $p = $snap?->payload ?? [];
        $sum = $p['summary'] ?? null;
        $fresh = self::isToday($snap);

        return $lab->toPublicArray() + [
            'feed' => [
                'connected' => $lab->hasKey(),
                'online' => self::online($lab, $p),
                'demo' => (bool) ($p['demo'] ?? false),
                'last_seen' => $lab->last_seen_at?->toIso8601String(),
                'generated_at' => $snap?->generated_at?->toIso8601String(),
                'datasets' => $p['datasets'] ?? [],
                'today' => $sum && $fresh ? self::kpi($sum['today'] ?? null) : null,
                'month' => $sum ? self::kpi($sum['month'] ?? null) : null,
                'spark' => isset($p['trend']['points'])
                    ? array_map(fn ($pt) => $pt['completed'] ?? null, $p['trend']['points'])
                    : null,
                'readiness' => $p['equipment']['readiness'] ?? null,
                'stages' => isset($p['flow']['stages']) ? array_map(fn ($s) => ['key' => $s['key'], 'count' => (int) ($s['count'] ?? 0), 'aging' => array_map('intval', (array) ($s['aging'] ?? []))], $p['flow']['stages']) : null,
            ],
        ];
    }

    /** Yon panel uchun qisqa yozuv: holat (online | offline | unlinked | live | soon) */
    public static function navItem(Lab $lab, bool $public): array
    {
        $state = $lab->status === Lab::STATUS_LIVE ? 'live' : 'soon';
        if ($public) {
            $p = $lab->snapshot?->payload ?? [];
            $state = self::online($lab, $p) ? 'online' : ($lab->hasKey() ? 'offline' : ($lab->status === Lab::STATUS_LIVE ? 'unlinked' : 'soon'));
        }

        return [
            'id' => $lab->id,
            'code' => $lab->code,
            'name' => ['ru' => $lab->name_ru, 'uz' => $lab->name_uz, 'en' => $lab->name_en],
            'state' => $state,
            'demo' => $public && (bool) ($lab->snapshot?->payload['demo'] ?? false),
        ];
    }

    /** Laboratoriya sahifasi — zavod ulashgan hamma narsa */
    public static function detail(Lab $lab): array
    {
        $snap = $lab->snapshot;
        $p = $snap?->payload ?? [];
        $sum = $p['summary'] ?? null;
        $fresh = self::isToday($snap);

        return self::card($lab) + [
            'detail' => [
                'today' => $sum && $fresh ? self::kpi($sum['today'] ?? null) : null,
                'month' => $sum ? self::kpi($sum['month'] ?? null) : null,
                'month_from' => $sum['month_from'] ?? null,
                'month_to' => $sum['month_to'] ?? null,
                'trend' => $p['trend']['points'] ?? null,
                'flow' => $p['flow'] ?? null,
                'shifts' => $fresh ? ($p['shifts'] ?? null) : null,
                'equipment' => $p['equipment'] ?? null,
                'worksheets' => $p['worksheets'] ?? null,
                'requests' => $fresh ? ($p['requests'] ?? null) : null,
                'groups' => $p['groups']['rows'] ?? null,
                'customers' => $p['customers']['rows'] ?? null,
                'interval' => (int) ($p['interval'] ?? 5),
                'bytes' => $snap?->bytes,
            ],
        ];
    }

    /**
     * Kombinat bo'yicha yig'indi: bugungi sonlar (faqat bugungi snapshot'lardan), 30 kunlik dinamika (sanalar bo'yicha qo'shiladi),
     * laboratoriyalar solishtiruvi.
     *
     * @param  Collection<int, Lab>  $labs
     */
    public static function totals(Collection $labs): array
    {
        $today = array_fill_keys(['received', 'completed', 'analyses', 'in_work', 'overdue'], 0);
        $prev = array_fill_keys(self::KPI, 0);
        $reporting = 0;
        $trend = [];
        $compare = [];
        $readiness = ['working' => 0, 'total' => 0];

        foreach ($labs as $lab) {
            $snap = $lab->snapshot;
            if (! $snap) {
                continue;
            }
            $p = $snap->payload ?? [];
            $sum = $p['summary'] ?? null;
            if ($sum && self::isToday($snap) && ($t = self::kpi($sum['today'] ?? null))) {
                $reporting++;
                foreach (array_keys($today) as $k) {
                    $today[$k] += (int) ($t[$k] ?? 0);
                }
                foreach (self::KPI as $k) {
                    $prev[$k] += (int) ($t[$k . '_prev'] ?? 0);
                }
            }
            foreach ($p['trend']['points'] ?? [] as $pt) {
                $key = (string) ($pt['key'] ?? '');
                if ($key === '') {
                    continue;
                }
                $trend[$key] ??= ['key' => $key, 'received' => 0, 'completed' => 0, 'analyses' => 0, 'labs' => 0];
                $any = false;
                foreach (self::KPI as $k) {
                    if (isset($pt[$k])) {
                        $trend[$key][$k] += (int) $pt[$k];
                        $any = true;
                    }
                }
                $trend[$key]['labs'] += $any ? 1 : 0;
            }
            if ($sum && ($m = self::kpi($sum['month'] ?? null))) {
                $compare[] = [
                    'id' => $lab->id, 'code' => $lab->code,
                    'completed' => (int) $m['completed'], 'received' => (int) $m['received'],
                    'turnaround_h' => $m['turnaround_h'], 'overdue' => (int) $m['overdue'],
                ];
            }
            if (isset($p['equipment']['total'])) {
                $readiness['working'] += (int) ($p['equipment']['working'] ?? 0);
                $readiness['total'] += (int) $p['equipment']['total'];
            }
        }
        ksort($trend);
        usort($compare, fn ($a, $b) => $b['completed'] <=> $a['completed']);

        $online = $labs->filter(fn (Lab $l) => self::online($l, $l->snapshot?->payload ?? []))->count();

        return [
            'labs_total' => $labs->count(),
            'labs_live' => $labs->where('status', Lab::STATUS_LIVE)->count(),
            'labs_connected' => $labs->filter(fn (Lab $l) => $l->hasKey())->count(),
            'labs_online' => $online,
            'reporting' => $reporting,
            'today' => $today,
            'today_prev' => $prev,
            'today_change' => array_combine(self::KPI, array_map(fn ($k) => self::change($today[$k], $prev[$k]), self::KPI)),
            'trend' => array_values(array_slice($trend, -30, 30, true)),
            'compare' => $compare,
            'readiness' => $readiness['total'] > 0 ? (int) round($readiness['working'] / $readiness['total'] * 100) : null,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    // ------------------------------------------------------------------

    public static function online(Lab $lab, array $payload = []): bool
    {
        if (! $lab->last_seen_at) {
            return false;
        }
        $interval = max(1, (int) ($payload['interval'] ?? 5));
        $limit = $interval * 2 + (int) config('portal.online_grace_minutes', 3);

        return $lab->last_seen_at->greaterThan(now()->subMinutes($limit));
    }

    private static function isToday(?LabSnapshot $snap): bool
    {
        if (! $snap?->generated_at) {
            return false;
        }

        return $snap->generated_at->copy()->setTimezone(config('app.timezone'))->isSameDay(now());
    }

    /** ExecutiveInsights «kpi» blokini tekis ko'rinishga keltiradi */
    private static function kpi(?array $k): ?array
    {
        if (! $k) {
            return null;
        }
        $val = fn ($x) => is_array($x) ? (int) ($x['value'] ?? 0) : (int) $x;
        $out = [];
        foreach (self::KPI as $key) {
            $out[$key] = $val($k[$key] ?? 0);
            $out[$key . '_prev'] = is_array($k[$key] ?? null) ? (int) ($k[$key]['prev'] ?? 0) : null;
            $out[$key . '_change'] = is_array($k[$key] ?? null) ? ($k[$key]['change'] ?? null) : null;
        }
        $out['in_work'] = $val($k['in_work'] ?? 0);
        $out['overdue'] = $val($k['overdue'] ?? 0);
        $med = $k['turnaround']['median'] ?? null; // soat (zavodda daqiqadan hisoblangan)
        $out['turnaround_h'] = $med !== null ? round((float) $med, 1) : null;
        $out['turnaround_count'] = (int) ($k['turnaround']['count'] ?? 0);

        return $out;
    }

    private static function change(int $cur, int $prev): ?float
    {
        if ($prev === 0) {
            return null;
        }

        return round(($cur - $prev) / $prev * 100, 1);
    }

    public static function parseTime(?string $iso): ?Carbon
    {
        try {
            return $iso ? Carbon::parse($iso) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
