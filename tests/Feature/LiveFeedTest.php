<?php

namespace Tests\Feature;

use App\Models\Lab;
use App\Models\LabSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Zavod LIMS'idan jonli ma'lumot: imzo, himoya, sahifalarda ko'rinishi, admin kaliti.
 * tests/fixtures/snapshot.json — zavod LIMS (Paket 44) CentralLink::snapshot() ning haqiqiy chiqishi.
 */
class LiveFeedTest extends TestCase
{
    use RefreshDatabase;

    private function lab(string $code = 'ГМЗ-3', string $status = 'live'): Lab
    {
        return Lab::create([
            'code' => $code, 'name_ru' => "Завод $code", 'name_uz' => "Zavod $code", 'name_en' => "Plant $code",
            'status' => $status, 'url' => 'http://192.168.26.8:3010', 'sort_order' => 1,
        ]);
    }

    private function payload(array $override = []): array
    {
        $p = json_decode(file_get_contents(base_path('tests/fixtures/snapshot.json')), true);
        $p['generated_at'] = now()->toIso8601String();
        $p['summary']['today']['received'] = ['value' => 42, 'prev' => 30, 'change' => 40.0];
        $p['summary']['today']['completed'] = ['value' => 35, 'prev' => 35, 'change' => 0.0];
        $p['summary']['today']['in_work'] = ['value' => 61];
        $p['summary']['month']['completed'] = ['value' => 910, 'prev' => 800, 'change' => 13.8];
        $p['summary']['month']['turnaround'] = ['count' => 900, 'median' => 18.5, 'avg' => 22.0, 'p90' => 40.1, 'prev_median' => 20.0, 'change' => -7.5];
        $p['trend']['points'][5]['completed'] = 33;

        return array_replace_recursive($p, $override);
    }

    private function send(Lab $lab, string $key, array $payload, ?int $ts = null, ?string $sig = null)
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $ts ??= time();

        return $this->call('POST', '/api/ingest', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_NGMK_VERSION' => '1',
            'HTTP_X_NGMK_LAB' => (string) $lab->id,
            'HTTP_X_NGMK_TIMESTAMP' => (string) $ts,
            'HTTP_X_NGMK_SIGNATURE' => $sig ?? hash_hmac('sha256', $ts . '.' . $body, $key),
        ], $body);
    }

    public function test_signed_snapshot_is_accepted_and_shown(): void
    {
        $lab = $this->lab();
        $key = $lab->issueKey();
        $this->assertMatchesRegularExpression('/^ngmk1\.' . $lab->id . '\.[A-Za-z0-9]{48}$/', $key);

        $this->send($lab, $key, $this->payload())->assertOk()->assertJsonPath('ok', true);

        $lab->refresh();
        $this->assertNotNull($lab->last_seen_at);
        $snap = LabSnapshot::sole();
        $this->assertSame(['summary', 'trend', 'flow', 'shifts', 'equipment', 'worksheets', 'requests', 'groups', 'customers'], $snap->payload['datasets']);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('statsVisible', true)
            ->where('labs.0.feed.online', true)
            ->where('labs.0.feed.today.received', 42)
            ->where('labs.0.feed.month.turnaround_h', 18.5)
            ->has('labs.0.feed.spark', 30)
            ->where('totals.today.received', 42)
            ->where('totals.today_change.received', 40)
            ->where('totals.labs_online', 1)
            ->has('totals.trend', 30)
            ->where('totals.compare.0.completed', 910)
            ->missing('labs.0.ingest_key')
            ->where('nav.labs.0.state', 'online')
            ->where('totals.today_prev.received', 30)
            ->has('labs.0.feed.stages.0.aging')
        );

        $this->get("/labs/{$lab->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Lab')
            ->where('lab.detail.month.completed', 910)
            ->has('lab.detail.trend', 30)
            ->has('lab.detail.flow.stages', 4)
            ->where('lab.detail.interval', 5)
        );

        // qayta yuborish — bitta qator yangilanadi
        $this->send($lab, $key, $this->payload(['summary' => ['today' => ['received' => ['value' => 43]]]]))->assertOk();
        $this->assertSame(1, LabSnapshot::count());
        $this->assertSame(43, LabSnapshot::sole()->payload['summary']['today']['received']['value']);
    }

    public function test_bad_requests_are_rejected(): void
    {
        $lab = $this->lab();
        $key = $lab->issueKey();
        $other = $this->lab('ГМЗ-5');

        $this->send($lab, $key, $this->payload(), sig: str_repeat('0', 64))->assertStatus(401);       // imzo
        $this->send($lab, 'ngmk1.' . $lab->id . '.' . str_repeat('x', 48), $this->payload())->assertStatus(401); // boshqa kalit
        $this->send($lab, $key, $this->payload(), ts: time() - 3600)->assertStatus(401);             // eski vaqt
        $this->send($other, $key, $this->payload())->assertStatus(401);                               // kalitsiz laboratoriya
        $this->send($lab, $key, ['v' => 2])->assertStatus(422);
        $this->assertSame(0, LabSnapshot::count());
        $this->assertNull($lab->fresh()->last_seen_at);

        // kalit almashtirilsa — eskisi ishlamaydi
        $new = $lab->issueKey();
        $this->send($lab, $key, $this->payload())->assertStatus(401);
        $this->send($lab, $new, $this->payload())->assertOk();
    }

    public function test_offline_and_stale_today_numbers(): void
    {
        $lab = $this->lab();
        $key = $lab->issueKey();
        $this->send($lab, $key, $this->payload())->assertOk();

        $this->travel(20)->minutes(); // oraliq 5 → 5×2+3 = 13 daqiqadan keyin «aloqa yo'q»
        $this->get('/')->assertInertia(fn (Assert $p) => $p->where('labs.0.feed.online', false)->where('labs.0.feed.today.received', 42));

        $this->travel(1)->days(); // kechagi «bugun» bugungi deb ko'rsatilmaydi
        $this->get('/')->assertInertia(fn (Assert $p) => $p
            ->where('labs.0.feed.today', null)
            ->where('labs.0.feed.month.completed', 910)
            ->where('totals.today.received', 0)
            ->where('totals.reporting', 0));
    }

    public function test_public_stats_can_be_hidden(): void
    {
        $lab = $this->lab();
        $this->send($lab, $lab->issueKey(), $this->payload())->assertOk();
        config(['portal.public_stats' => false]);

        $this->get('/')->assertInertia(fn (Assert $p) => $p->where('statsVisible', false)->where('totals', null)->missing('labs.0.feed')->where('nav.labs.0.state', 'live'));
        $this->actingAs(User::factory()->create())->get('/')
            ->assertInertia(fn (Assert $p) => $p->where('statsVisible', true)->where('labs.0.feed.today.received', 42));
    }

    public function test_admin_issues_key_once_and_can_revoke(): void
    {
        $lab = $this->lab();
        $admin = User::factory()->create();

        $this->post("/admin/labs/{$lab->id}/key")->assertRedirect('/admin/login');

        $this->actingAs($admin)->post("/admin/labs/{$lab->id}/key")->assertRedirect("/admin/labs/{$lab->id}/edit");
        $lab->refresh();
        $this->assertTrue($lab->hasKey());
        $this->assertStringNotContainsString($lab->ingest_key, (string) \DB::table('labs')->value('ingest_key')); // bazada shifrlangan

        $this->actingAs($admin)->withSession(['new_key' => $lab->ingest_key])->get("/admin/labs/{$lab->id}/edit")
            ->assertInertia(fn (Assert $p) => $p->where('newKey', $lab->ingest_key)->where('feed.connected', true)->where('feed.key_hint', $lab->keyHint()));
        $this->actingAs($admin)->get("/admin/labs/{$lab->id}/edit")->assertInertia(fn (Assert $p) => $p->where('newKey', null));
        $this->actingAs($admin)->get('/admin/labs')->assertInertia(fn (Assert $p) => $p->where('labs.0.connected', true)->missing('labs.0.ingest_key'));

        $this->actingAs($admin)->delete("/admin/labs/{$lab->id}/key")->assertRedirect();
        $this->assertFalse($lab->fresh()->hasKey());
    }
}
