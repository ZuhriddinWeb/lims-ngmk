<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use App\Models\LabSnapshot;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Zavod LIMS'idan jonli ma'lumot qabul qilish: POST /api/ingest
 *
 * Sarlavhalar: X-Ngmk-Version: 1, X-Ngmk-Lab: <lab id>, X-Ngmk-Timestamp: <unix>, X-Ngmk-Signature: hmac_sha256(ts + "." + body, kalit)
 * Kalit faqat shu portal admin panelida yaratiladi; imzo mos kelmasa, eski bo'lsa yoki hajm katta bo'lsa — rad etiladi.
 */
class IngestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $body = $request->getContent();
        if (strlen($body) > (int) config('portal.ingest_max_bytes', 1048576)) {
            return $this->deny(413, 'Hajm juda katta');
        }
        if ($request->header('X-Ngmk-Version') !== '1') {
            return $this->deny(400, 'Protokol versiyasi mos emas');
        }

        $lab = Lab::find((int) $request->header('X-Ngmk-Lab'));
        if (! $lab || ! $lab->hasKey()) {
            return $this->deny(401, 'Laboratoriya topilmadi yoki kalit berilmagan');
        }

        $ts = (string) $request->header('X-Ngmk-Timestamp');
        if (! ctype_digit($ts) || abs(time() - (int) $ts) > (int) config('portal.ingest_max_skew', 600)) {
            return $this->deny(401, 'Vaqt belgisi eskirgan — zavod serveri soatini tekshiring');
        }

        $expected = hash_hmac('sha256', $ts . '.' . $body, (string) $lab->ingest_key);
        if (! hash_equals($expected, (string) $request->header('X-Ngmk-Signature'))) {
            return $this->deny(401, "Imzo noto'g'ri — zavoddagi ulanish kalitini tekshiring");
        }

        $data = json_decode($body, true);
        if (! is_array($data) || (int) ($data['v'] ?? 0) !== 1) {
            return $this->deny(422, "Ma'lumot formati noto'g'ri");
        }

        $datasets = array_values(array_filter((array) ($data['datasets'] ?? []), 'is_string'));
        try {
            $generated = isset($data['generated_at']) ? Carbon::parse($data['generated_at']) : now();
        } catch (\Throwable) {
            $generated = now();
        }

        LabSnapshot::updateOrCreate(['lab_id' => $lab->id], [
            'payload' => $data,
            'datasets' => mb_substr(implode(',', $datasets), 0, 255),
            'bytes' => strlen($body),
            'generated_at' => $generated,
            'received_at' => now(),
        ]);
        $lab->forceFill(['last_seen_at' => now(), 'last_ip' => mb_substr((string) $request->ip(), 0, 64)])->save();

        return response()->json(['ok' => true, 'received_at' => now()->toIso8601String()]);
    }

    private function deny(int $status, string $message): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => $message], $status);
    }
}
