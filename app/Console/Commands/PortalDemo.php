<?php

namespace App\Console\Commands;

use App\Models\Lab;
use App\Models\LabSnapshot;
use Illuminate\Console\Command;

/**
 * Namoyish uchun namunaviy ma'lumot — haqiqiy ma'lumoti yo'q laboratoriyalarga.
 * Har bir shunday karta «DEMO» belgisi bilan ko'rinadi; haqiqiy snapshot hech qachon ustidan yozilmaydi.
 *
 *   php artisan portal:demo           — ma'lumoti yo'q laboratoriyalarni to'ldirish (yoki demo'ni yangilash)
 *   php artisan portal:demo --clear   — barcha demo ma'lumotni o'chirish
 */
class PortalDemo extends Command
{
    protected $signature = 'portal:demo {--clear : Demo ma\'lumotni o\'chirish}';

    protected $description = "Namoyish uchun namunaviy ma'lumot (DEMO belgisi bilan; haqiqiy ma'lumotga tegmaydi)";

    public function handle(): int
    {
        if ($this->option('clear')) {
            $n = 0;
            foreach (LabSnapshot::with('lab')->get() as $s) {
                if (! empty($s->payload['demo'])) {
                    $s->delete();
                    if ($s->lab && ! $s->lab->hasKey()) {
                        $s->lab->forceFill(['last_seen_at' => null, 'last_ip' => null])->save();
                    }
                    $n++;
                }
            }
            $this->info("Demo ma'lumot o'chirildi: $n ta laboratoriya");

            return self::SUCCESS;
        }

        $done = [];
        foreach (Lab::ordered()->with('snapshot')->get() as $i => $lab) {
            if ($lab->snapshot && empty($lab->snapshot->payload['demo'])) {
                continue; // haqiqiy ma'lumot — tegilmaydi
            }
            mt_srand(crc32($lab->code) + (int) now()->format('z'));
            $payload = $this->fake($i);
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
            LabSnapshot::updateOrCreate(['lab_id' => $lab->id], [
                'payload' => $payload, 'datasets' => implode(',', $payload['datasets']),
                'bytes' => strlen($body), 'generated_at' => now(), 'received_at' => now(),
            ]);
            $lab->forceFill(['last_seen_at' => now()])->save();
            $done[] = $lab->code;
        }
        $this->info($done ? 'DEMO: ' . implode(', ', $done) : "Hamma laboratoriyada haqiqiy ma'lumot bor — demo kerak emas");

        return self::SUCCESS;
    }

    private function fake(int $i): array
    {
        $scale = [1.0, 0.8, 1.3, 0.6, 1.1, 0.7, 0.9, 1.6][$i % 8];
        $base = (int) round(38 * $scale);
        $points = [];
        $sumRec = $sumCom = $sumAn = 0;
        for ($d = 29; $d >= 0; $d--) {
            $date = now()->subDays($d);
            $wk = in_array($date->dayOfWeekIso, [6, 7], true) ? .55 : 1.0;
            $rec = (int) max(0, round($base * $wk * (0.8 + mt_rand(0, 40) / 100)));
            $com = (int) max(0, round($rec * (0.86 + mt_rand(0, 20) / 100)));
            $an = (int) round($com * (2.6 + mt_rand(0, 12) / 10));
            if ($d === 0) { // bugun — hali kun tugamagan
                $part = max(.15, min(1, now()->hour / 20));
                [$rec, $com, $an] = [(int) round($rec * $part), (int) round($com * $part), (int) round($an * $part)];
            }
            $points[] = ['key' => $date->toDateString(), 'received' => $rec, 'completed' => $com, 'analyses' => $an];
            $sumRec += $rec;
            $sumCom += $com;
            $sumAn += $an;
        }
        $today = end($points);
        // kecha — xuddi shu soatgacha (zavod hisobidagi kabi: bugungi qisman kun ↔ kechaning shu qismi)
        $part = max(.15, min(1, now()->hour / 20));
        $y = $points[count($points) - 2];
        $yday = ['received' => (int) round($y['received'] * $part), 'completed' => (int) round($y['completed'] * $part), 'analyses' => (int) round($y['analyses'] * $part)];
        $delta = fn ($v, $p) => ['value' => $v, 'prev' => $p, 'change' => $p ? round(($v - $p) / $p * 100, 1) : null];
        $inWork = (int) round($base * 1.6 + mt_rand(0, 12));
        $overdue = mt_rand(0, 100) < 35 ? mt_rand(1, 6) : 0;
        $med = round(14 + mt_rand(0, 140) / 10, 1);
        $kpi = fn ($r, $c, $a, $rp, $cp, $ap) => [
            'received' => $delta($r, $rp), 'completed' => $delta($c, $cp), 'analyses' => $delta($a, $ap),
            'turnaround' => ['count' => $c, 'median' => $med, 'avg' => round($med * 1.2, 1), 'p90' => round($med * 2.3, 1), 'prev_median' => $med + 1, 'change' => -5.0],
            'in_work' => ['value' => $inWork], 'overdue' => ['value' => $overdue, 'days' => 7], 'worksheets' => null,
        ];
        $stage = fn ($key, $n) => ['key' => $key, 'count' => $n, 'aging' => ['d1' => (int) round($n * .6), 'd3' => (int) round($n * .25), 'd7' => (int) round($n * .1), 'over' => max(0, $n - (int) round($n * .6) - (int) round($n * .25) - (int) round($n * .1))]];
        $eqTotal = mt_rand(18, 42);
        $eqOver = mt_rand(0, 2);
        $eqBlocked = mt_rand(0, 2);

        return [
            'v' => 1, 'demo' => true, 'generated_at' => now()->toIso8601String(), 'timezone' => config('app.timezone'),
            'app' => ['name' => 'LIMS', 'package' => 44], 'interval' => 5,
            'datasets' => ['summary', 'trend', 'flow', 'shifts', 'equipment', 'worksheets', 'requests', 'groups', 'customers'],
            'summary' => [
                'today' => $kpi($today['received'], $today['completed'], $today['analyses'], $yday['received'], $yday['completed'], $yday['analyses']),
                'month' => $kpi($sumRec, $sumCom, $sumAn, (int) round($sumRec * .93), (int) round($sumCom * .95), (int) round($sumAn * .9)),
                'month_from' => now()->subDays(29)->toDateString(), 'month_to' => now()->toDateString(),
            ],
            'trend' => ['bucket' => 'day', 'points' => $points],
            'flow' => ['stages' => [$stage('registered', (int) round($inWork * .18)), $stage('assigned', (int) round($inWork * .22)), $stage('analysis', (int) round($inWork * .42)), $stage('review', (int) round($inWork * .18))], 'open' => $inWork, 'overdue_count' => $overdue],
            'shifts' => ['configured' => true, 'live' => ['id' => 1, 'name' => now()->hour >= 8 && now()->hour < 20 ? '1-smena' : '2-smena', 'start_time' => now()->hour >= 8 && now()->hour < 20 ? '08:00' : '20:00', 'end_time' => now()->hour >= 8 && now()->hour < 20 ? '20:00' : '08:00', 'received' => (int) round($today['received'] * .7), 'completed' => (int) round($today['completed'] * .7), 'analyses' => (int) round($today['analyses'] * .7), 'worksheets' => mt_rand(3, 12)], 'rows' => []],
            'equipment' => ['total' => $eqTotal, 'working' => $eqTotal - $eqBlocked - $eqOver, 'blocked' => $eqBlocked, 'waiting' => 0, 'overdue' => $eqOver, 'due_soon' => mt_rand(0, 3), 'readiness' => (int) round(($eqTotal - $eqBlocked - $eqOver) / $eqTotal * 100), 'items' => $eqOver ? [['id' => 1, 'name' => 'AAS spektrometr (demo)', 'serial_number' => 'DEMO-1', 'next_calibration_at' => now()->subDays(3)->toDateString(), 'is_blocked' => true]] : []],
            'worksheets' => ['available' => true, 'created' => ['main' => mt_rand(80, 200), 'control' => mt_rand(5, 20)], 'open' => ['draft' => mt_rand(0, 4), 'printed' => mt_rand(1, 6), 'in_progress' => mt_rand(4, 14), 'completed' => mt_rand(1, 5)], 'approved' => mt_rand(60, 180), 'with_warnings' => mt_rand(0, 3), 'awaiting' => ['count' => mt_rand(1, 6), 'old' => 0], 'extra_parallels' => mt_rand(0, 5), 'retests' => mt_rand(0, 4)],
            'requests' => ['waiting' => mt_rand(2, 14), 'expired' => mt_rand(0, 2), 'submitted_today' => (int) round($today['received'] / 4), 'received_today' => (int) round($today['received'] / 4.5), 'month' => ['submitted' => (int) round($sumRec / 4), 'received' => (int) round($sumRec / 4.3), 'rejected' => mt_rand(0, 4), 'cancelled' => mt_rand(0, 6), 'schedule' => mt_rand(20, 60)]],
            'groups' => ['rows' => [
                ['id' => 1, 'code' => 'NTG', 'name_ru' => 'Группа анализа проб', 'name_uz' => 'Namuna tahlil guruhi', 'name_en' => 'Sample analysis', 'in_work' => (int) round($inWork * .55), 'overdue' => $overdue, 'received' => (int) round($sumRec * .6), 'completed' => (int) round($sumCom * .6), 'analyses' => (int) round($sumAn * .6), 'median_hours' => $med],
                ['id' => 2, 'code' => 'FNG', 'name_ru' => 'Группа фазового контроля', 'name_uz' => 'Fazali nazorat guruhi', 'name_en' => 'Phase control', 'in_work' => (int) round($inWork * .45), 'overdue' => 0, 'received' => (int) round($sumRec * .4), 'completed' => (int) round($sumCom * .4), 'analyses' => (int) round($sumAn * .4), 'median_hours' => round($med * 1.3, 1)],
            ]],
            'customers' => ['available' => true, 'rows' => [
                ['id' => 1, 'code' => 'OTK', 'name' => 'OTK', 'received' => (int) round($sumRec * .45), 'completed' => (int) round($sumCom * .45), 'in_work' => (int) round($inWork * .4), 'overdue' => 0, 'median_hours' => $med],
                ['id' => 2, 'code' => 'TTG', 'name' => 'Texnologik tadqiqotlar guruhi', 'received' => (int) round($sumRec * .3), 'completed' => (int) round($sumCom * .3), 'in_work' => (int) round($inWork * .35), 'overdue' => $overdue, 'median_hours' => round($med * 1.4, 1)],
                ['id' => 3, 'code' => 'SEX', 'name' => 'Sorbsiya sexi', 'received' => (int) round($sumRec * .25), 'completed' => (int) round($sumCom * .25), 'in_work' => (int) round($inWork * .25), 'overdue' => 0, 'median_hours' => round($med * .9, 1)],
            ], 'other' => null],
        ];
    }
}
