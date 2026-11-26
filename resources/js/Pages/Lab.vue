<script setup>
/**
 * Laboratoriya sahifasi — zavod LIMS'idagi «Rahbariyat paneli» tuzilishida: KPI, ish oqimi, dinamika + smenalar,
 * chapda guruhlar va bo'limlar, o'ngda uskunalar, buyurtmalar, ishchi varaqlar. Ulashilmagan blok — «ulashilmagan».
 */
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import KpiTile from '@/Components/KpiTile.vue';
import TrendChart from '@/Components/TrendChart.vue';
import FlowStages from '@/Components/FlowStages.vue';
import RingGauge from '@/Components/RingGauge.vue';
import StatusPill from '@/Components/StatusPill.vue';
import Icon from '@/Components/Icon.vue';
import { labState } from '@/Components/LabCard.vue';
import { useAutoRefresh } from '@/composables/useAutoRefresh';
import { useNow } from '@/composables/useNow';
import { useAgo } from '@/composables/useAgo';
import { fmtNum, shortDate } from '@/lib/charts';

const props = defineProps({
    lab: { type: Object, required: true },
    statsVisible: { type: Boolean, default: true },
});
const { t, locale } = useI18n();
const ago = useAgo();
const now = useNow(15000);
const { busy, refresh } = useAutoRefresh(['lab', 'nav'], 60000);

const d = computed(() => props.lab.detail ?? null);
const feed = computed(() => props.lab.feed ?? null);
const state = computed(() => labState(props.lab));
const name = computed(() => props.lab.name?.[locale.value] || props.lab.name?.ru);
const period = ref('today');
const k = computed(() => (period.value === 'today' ? d.value?.today : d.value?.month) ?? null);
const series = computed(() => [
    { key: 'received', label: t('kpi.received'), color: 'var(--s1)' },
    { key: 'completed', label: t('kpi.completed'), color: 'var(--s2)' },
]); // tahlillar boshqa miqyosda — bitta o'qqa qo'yilmaydi (KPI kartasida)
const gname = (g) => g?.[`name_${locale.value}`] || g?.name_ru || g?.name_uz || g?.code || '—';
const eq = computed(() => d.value?.equipment ?? null);
const rq = computed(() => d.value?.requests ?? null);
const ws = computed(() => d.value?.worksheets ?? null);
const shifts = computed(() => d.value?.shifts ?? null);
const n = (v) => fmtNum(v ?? null, locale.value);
const cmpSub = computed(() => (period.value === 'today' ? t('kpi.vsYesterday') : t('kpi.vsPrev30')));
const empty = computed(() => !props.statsVisible || !d.value || (!d.value.today && !d.value.month && !d.value.trend));
</script>

<template>
    <Head><title>{{ lab.code }}</title></Head>

    <PublicLayout :crumbs="[{ label: t('shell.app'), href: '/' }, { label: t('sections.labs'), href: '/' }, { label: lab.code }]" :active-lab="lab.id" :live="state === 'online'">
        <div class="page-head">
            <div style="min-width:0">
                <h1>{{ lab.code }} <StatusPill :state="state" /> <span v-if="feed?.demo" class="demo-tag">DEMO</span></h1>
                <p class="lead">{{ name }}</p>
                <div v-if="feed?.last_seen || d?.interval" class="meta">
                    <span v-if="feed?.last_seen"><Icon name="clock" :size="13" /> {{ t('lab.lastSeen', { t: ago(feed.last_seen, now) }) }}</span>
                    <span v-if="d?.interval">{{ t('lab.interval', { n: d.interval }) }}</span>
                    <span v-if="period === 'month' && d?.month_from" class="tnum">{{ shortDate(d.month_from) }} — {{ shortDate(d.month_to) }}</span>
                </div>
            </div>
            <div class="actions">
                <div v-if="!empty" class="seg" role="tablist">
                    <button :class="{ on: period === 'today' }" role="tab" @click="period = 'today'">{{ t('lab.today') }}</button>
                    <button :class="{ on: period === 'month' }" role="tab" @click="period = 'month'">{{ t('lab.month') }}</button>
                </div>
                <button v-if="statsVisible" class="btn btn-outline btn-sm" type="button" :disabled="busy" @click="refresh"><Icon name="refresh" :size="14" :class="{ spin: busy }" /> {{ t('home.refresh') }}</button>
                <a v-if="lab.has_address" :href="lab.url" target="_blank" rel="noopener" class="btn btn-gold btn-sm">{{ t('card.open') }} <Icon name="external" :size="14" /></a>
            </div>
        </div>

        <div v-if="empty" class="panel hidden-block">
            <Icon :name="statsVisible ? 'wifi-off' : 'lock'" /> {{ statsVisible ? t('lab.noData') : t('lab.hidden') }}
        </div>

        <template v-else>
            <p v-if="period === 'today' && !d.today" class="panel hidden-block" style="margin-bottom:16px"><Icon name="clock" /> {{ t('lab.noToday') }}</p>

            <div class="kpis">
                <KpiTile icon="test-tube" color="var(--s1)" :label="t('kpi.received')" :value="k?.received ?? null" :change="k?.received_change ?? null" :prev="k?.received_prev ?? null" :sub="cmpSub" />
                <KpiTile icon="check-circle" color="var(--s2)" :label="t('kpi.completed')" :value="k?.completed ?? null" :change="k?.completed_change ?? null" :prev="k?.completed_prev ?? null" :sub="cmpSub" />
                <KpiTile icon="flask" color="var(--s3)" :label="t('kpi.analyses')" :value="k?.analyses ?? null" :change="k?.analyses_change ?? null" :prev="k?.analyses_prev ?? null" :sub="cmpSub" />
                <KpiTile icon="timer" :label="t('kpi.turnaround')" :value="k?.turnaround_h ?? null" :unit="t('kpi.hShort')" :digits="1" :compare="false" :sub="k?.turnaround_count ? t('kpi.turnaroundN', { n: n(k.turnaround_count) }) : ''" />
                <KpiTile icon="layers" :label="t('kpi.inWork')" :value="k?.in_work ?? null" :compare="false" :sub="t('kpi.inWorkHint')" />
                <KpiTile icon="alert" alert-when-positive :label="t('kpi.overdue')" :value="k?.overdue ?? null" :compare="false" :sub="t('kpi.overdueHint')" />
            </div>

            <section class="panel">
                <div class="panel-h">
                    <Icon name="layers" :size="16" /><h2>{{ t('lab.flow') }}</h2>
                    <span v-if="d.flow" class="right sub tnum">{{ t('lab.flowSub', { n: n(d.flow.open), o: n(d.flow.overdue_count) }) }}</span>
                </div>
                <div class="panel-b">
                    <FlowStages v-if="d.flow" :stages="d.flow.stages" />
                    <div v-else class="hidden-block" style="padding:0"><Icon name="eye-off" /> {{ t('lab.notShared') }}</div>
                </div>
            </section>

            <div class="row-3 mt">
                <section class="panel">
                    <div class="panel-h"><Icon name="activity" :size="16" /><h2>{{ t('home.trend') }}</h2><span class="sub">{{ t('lab.trendSub') }}</span></div>
                    <div class="panel-b">
                        <TrendChart v-if="d.trend" :points="d.trend" :series="series" area-key="completed" :height="280" />
                        <div v-else class="hidden-block"><Icon name="eye-off" /> {{ t('lab.notShared') }}</div>
                    </div>
                </section>
                <section class="panel">
                    <div class="panel-h"><Icon name="clock" :size="16" /><h2>{{ t('lab.shifts') }}</h2><span v-if="shifts?.live" class="sub">{{ t('lab.nowShift', { s: shifts.live.name }) }}</span></div>
                    <div v-if="shifts?.live || shifts?.rows?.length" class="panel-b">
                        <div v-if="shifts.live" class="kv">
                            <div class="kv-i"><b>{{ n(shifts.live.received) }}</b><span>{{ t('kpi.received') }}</span></div>
                            <div class="kv-i"><b>{{ n(shifts.live.completed) }}</b><span>{{ t('kpi.completed') }}</span></div>
                            <div class="kv-i"><b>{{ n(shifts.live.analyses) }}</b><span>{{ t('kpi.analyses') }}</span></div>
                            <div class="kv-i"><b>{{ n(shifts.live.worksheets) }}</b><span>{{ t('lab.worksheets') }}</span></div>
                        </div>
                        <table v-if="shifts.rows?.length" class="tbl" style="margin-top:12px">
                            <thead><tr><th>{{ t('lab.shift') }}</th><th class="num">{{ t('table.received') }}</th><th class="num">{{ t('table.completed') }}</th><th class="num">{{ t('table.analyses') }}</th></tr></thead>
                            <tbody>
                                <tr v-for="r in shifts.rows" :key="r.id">
                                    <td><div style="font-weight:500">{{ r.name }}</div><div class="muted" style="font-size:11.5px">{{ r.start_time }}–{{ r.end_time }}</div></td>
                                    <td class="num">{{ n(r.received) }}</td><td class="num">{{ n(r.completed) }}</td><td class="num">{{ n(r.analyses) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="hidden-block"><Icon name="eye-off" /> {{ shifts ? t('lab.noShift') : t('lab.notShared') }}</div>
                </section>
            </div>

            <div class="row-3 top mt">
                <div class="stack">
                    <section class="panel">
                        <div class="panel-h"><Icon name="users" :size="16" /><h2>{{ t('lab.groups') }}</h2></div>
                        <div v-if="d.groups" class="panel-b tbl-scroll">
                            <table class="tbl">
                                <thead><tr><th>{{ t('lab.group') }}</th><th class="num">{{ t('kpi.inWork') }}</th><th class="num">{{ t('kpi.overdueShort') }}</th><th class="num">{{ t('kpi.completed') }}</th><th class="num">{{ t('kpi.medianH') }}</th></tr></thead>
                                <tbody>
                                    <tr v-for="(g, i) in d.groups" :key="i"><td :class="{ muted: g.unassigned }" style="font-weight:500">{{ g.unassigned ? t('lab.unassigned') : gname(g) }}</td><td class="num">{{ n(g.in_work) }}</td><td class="num" :class="{ alert: g.overdue }">{{ n(g.overdue) }}</td><td class="num">{{ n(g.completed) }}</td><td class="num">{{ n(g.median_hours) }}</td></tr>
                                    <tr v-if="!d.groups.length"><td colspan="5" class="muted">—</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="hidden-block"><Icon name="eye-off" /> {{ t('lab.notShared') }}</div>
                    </section>
                    <section class="panel">
                        <div class="panel-h"><Icon name="building" :size="16" /><h2>{{ t('lab.customers') }}</h2></div>
                        <div v-if="d.customers" class="panel-b tbl-scroll">
                            <table class="tbl">
                                <thead><tr><th>{{ t('lab.customer') }}</th><th class="num">{{ t('kpi.received') }}</th><th class="num">{{ t('kpi.completed') }}</th><th class="num">{{ t('kpi.inWork') }}</th><th class="num">{{ t('kpi.overdueShort') }}</th></tr></thead>
                                <tbody>
                                    <tr v-for="(c, i) in d.customers" :key="i"><td :class="{ muted: !c.name }" style="font-weight:500">{{ c.name || t('lab.noCustomer') }}</td><td class="num">{{ n(c.received) }}</td><td class="num">{{ n(c.completed) }}</td><td class="num">{{ n(c.in_work) }}</td><td class="num" :class="{ alert: c.overdue }">{{ n(c.overdue) }}</td></tr>
                                    <tr v-if="!d.customers.length"><td colspan="5" class="muted">—</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="hidden-block"><Icon name="eye-off" /> {{ t('lab.notShared') }}</div>
                    </section>
                </div>

                <div class="stack">
                    <section class="panel">
                        <div class="panel-h"><Icon name="wrench" :size="16" /><h2>{{ t('lab.equipment') }}</h2></div>
                        <div v-if="eq" class="panel-b">
                            <div class="readiness">
                                <RingGauge :value="eq.readiness" :size="84" :label="t('home.readyShort')" />
                                <div class="kv" style="flex:1">
                                    <div class="kv-i ok"><b>{{ n(eq.working) }}</b><span>{{ t('eq.working') }}</span></div>
                                    <div class="kv-i" :class="{ alert: eq.blocked }"><b>{{ n(eq.blocked) }}</b><span>{{ t('eq.blocked') }}</span></div>
                                    <div class="kv-i" :class="{ alert: eq.overdue }"><b>{{ n(eq.overdue) }}</b><span>{{ t('eq.overdue') }}</span></div>
                                    <div class="kv-i"><b>{{ n(eq.due_soon) }}</b><span>{{ t('eq.dueSoon') }}</span></div>
                                </div>
                            </div>
                            <table v-if="eq.items?.length" class="tbl" style="margin-top:12px">
                                <thead><tr><th>{{ t('eq.name') }}</th><th class="num">{{ t('eq.calib') }}</th></tr></thead>
                                <tbody><tr v-for="e in eq.items" :key="e.id"><td>{{ e.name }} <span v-if="e.is_blocked" class="status offline" style="margin-left:6px"><span class="sd" />{{ t('eq.blockedOne') }}</span></td><td class="num alert">{{ shortDate(e.next_calibration_at) }}</td></tr></tbody>
                            </table>
                        </div>
                        <div v-else class="hidden-block"><Icon name="eye-off" /> {{ t('lab.notShared') }}</div>
                    </section>
                    <section class="panel">
                        <div class="panel-h"><Icon name="inbox" :size="16" /><h2>{{ t('lab.requests') }}</h2></div>
                        <div v-if="rq" class="panel-b kv">
                            <div class="kv-i"><b>{{ n(rq.waiting) }}</b><span>{{ t('rq.waiting') }}</span></div>
                            <div class="kv-i" :class="{ alert: rq.expired }"><b>{{ n(rq.expired) }}</b><span>{{ t('rq.expired') }}</span></div>
                            <div class="kv-i"><b>{{ n(rq.submitted_today) }}</b><span>{{ t('rq.submittedToday') }}</span></div>
                            <div class="kv-i ok"><b>{{ n(rq.received_today) }}</b><span>{{ t('rq.receivedToday') }}</span></div>
                        </div>
                        <div v-else class="hidden-block"><Icon name="eye-off" /> {{ t('lab.notShared') }}</div>
                    </section>
                    <section class="panel">
                        <div class="panel-h"><Icon name="sheet" :size="16" /><h2>{{ t('lab.worksheets') }}</h2></div>
                        <div v-if="ws" class="panel-b kv">
                            <div class="kv-i"><b>{{ n((ws.open?.in_progress ?? 0) + (ws.open?.printed ?? 0) + (ws.open?.draft ?? 0)) }}</b><span>{{ t('ws.open') }}</span></div>
                            <div class="kv-i"><b>{{ n(ws.awaiting?.count ?? 0) }}</b><span>{{ t('ws.awaiting') }}</span></div>
                            <div class="kv-i ok"><b>{{ n(ws.approved ?? 0) }}</b><span>{{ t('ws.approved') }}</span></div>
                            <div class="kv-i"><b>{{ n(ws.retests ?? 0) }}</b><span>{{ t('ws.retests') }}</span></div>
                        </div>
                        <div v-else class="hidden-block"><Icon name="eye-off" /> {{ t('lab.notShared') }}</div>
                    </section>
                </div>
            </div>
        </template>
    </PublicLayout>
</template>
