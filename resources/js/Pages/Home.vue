<script setup>
/**
 * Umumiy ko'rinish — kombinat laboratoriyalarining jonli ko'rsatkichlari.
 * Sonlar zavod LIMS'laridan (har biri o'zi yuboradi; faqat zavod admini ruxsat bergan bloklar). 60 soniyada jim yangilanadi.
 */
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import KpiTile from '@/Components/KpiTile.vue';
import TrendChart from '@/Components/TrendChart.vue';
import CompareBars from '@/Components/CompareBars.vue';
import FlowStages from '@/Components/FlowStages.vue';
import LabTable from '@/Components/LabTable.vue';
import LabCard, { labState } from '@/Components/LabCard.vue';
import RingGauge from '@/Components/RingGauge.vue';
import Icon from '@/Components/Icon.vue';
import { useAutoRefresh } from '@/composables/useAutoRefresh';
import { useNow } from '@/composables/useNow';
import { clockTime, fmtNum } from '@/lib/charts';

const props = defineProps({
    labs: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ total: 0, live: 0, soon: 0 }) },
    totals: { type: Object, default: null },
    statsVisible: { type: Boolean, default: true },
});
const { t, locale } = useI18n();
const now = useNow(15000);
const { refreshedAt, busy, refresh } = useAutoRefresh(['labs', 'totals', 'stats', 'nav'], 60000);

const T = computed(() => props.totals);
const series = computed(() => [
    { key: 'received', label: t('kpi.received'), color: 'var(--s1)' },
    { key: 'completed', label: t('kpi.completed'), color: 'var(--s2)' },
]); // tahlillar boshqa miqyosda (≈3×) — bitta o'qqa qo'yilmaydi, KPI kartasida
const spark = (k) => (T.value?.trend?.length ? T.value.trend.map((p) => p[k]) : null);
const compareRows = computed(() => (T.value?.compare ?? []).map((r) => ({
    id: r.id, code: r.code, value: r.completed,
    sub: r.turnaround_h != null ? `${fmtNum(r.turnaround_h, locale.value)} ${t('kpi.hShort')}` : '—',
})));
const states = computed(() => props.labs.map((l) => labState(l)));
const anyData = computed(() => (T.value?.trend?.length ?? 0) > 0 || (T.value?.reporting ?? 0) > 0);

/** Barcha laboratoriyalar bo'yicha ish oqimi (bosqich va yosh bo'yicha yig'indi) */
const flow = computed(() => {
    const acc = {};
    let n = 0;
    for (const l of props.labs) {
        if (!l.feed?.stages) continue;
        n++;
        for (const s of l.feed.stages) {
            acc[s.key] ??= { key: s.key, count: 0, aging: { d1: 0, d3: 0, d7: 0, over: 0 } };
            acc[s.key].count += s.count || 0;
            for (const a of ['d1', 'd3', 'd7', 'over']) acc[s.key].aging[a] += s.aging?.[a] ?? 0;
        }
    }
    const order = ['registered', 'assigned', 'analysis', 'review'];
    return n ? { labs: n, stages: order.filter((k) => acc[k]).map((k) => acc[k]) } : null;
});
const flowOpen = computed(() => (flow.value?.stages ?? []).reduce((a, s) => a + s.count, 0));
</script>

<template>
    <Head><title>{{ t('shell.overview') }}</title></Head>

    <PublicLayout :crumbs="[{ label: t('shell.app') }, { label: t('shell.overview') }]" :live="(T?.labs_online ?? 0) > 0">
        <div class="page-head">
            <div>
                <h1>{{ t('home.title') }}</h1>
                <p class="lead">{{ t('home.lead') }}</p>
            </div>
            <div v-if="statsVisible" class="actions">
                <span class="upd tnum">{{ t('home.refreshed', { t: clockTime(refreshedAt, false) }) }}</span>
                <button class="btn btn-outline btn-sm" type="button" :disabled="busy" @click="refresh"><Icon name="refresh" :size="14" :class="{ spin: busy }" /> {{ t('home.refresh') }}</button>
            </div>
        </div>

        <template v-if="statsVisible && T">
            <div class="kpis">
                <KpiTile icon="test-tube" color="var(--s1)" :label="t('kpi.todayReceived')" :value="T.today.received" :change="T.today_change.received" :prev="T.today_prev?.received ?? null" :sub="t('kpi.vsYesterday')" :spark="spark('received')" />
                <KpiTile icon="check-circle" color="var(--s2)" :label="t('kpi.todayCompleted')" :value="T.today.completed" :change="T.today_change.completed" :prev="T.today_prev?.completed ?? null" :sub="t('kpi.vsYesterday')" :spark="spark('completed')" />
                <KpiTile icon="flask" color="var(--s3)" :label="t('kpi.todayAnalyses')" :value="T.today.analyses" :change="T.today_change.analyses" :prev="T.today_prev?.analyses ?? null" :sub="t('kpi.vsYesterday')" :spark="spark('analyses')" />
                <KpiTile icon="layers" :label="t('kpi.inWork')" :value="T.today.in_work" :compare="false" :sub="t('kpi.labsReporting', { n: T.reporting })" />
                <KpiTile icon="alert" alert-when-positive :label="t('kpi.overdue')" :value="T.today.overdue" :compare="false" :sub="t('kpi.overdueHint')" />
                <KpiTile icon="radio" tone="ok" :label="t('home.online')" :value="T.labs_online" :unit="`/ ${T.labs_total}`" :compare="false" :sub="t('home.connectedN', { n: T.labs_connected })">
                    <div class="k-dots" :title="t('home.netHint')"><i v-for="(s, i) in states" :key="i" :class="{ on: s === 'online', stale: s === 'offline' }" /></div>
                </KpiTile>
            </div>

            <div class="row-3">
                <section class="panel">
                    <div class="panel-h"><Icon name="activity" :size="16" /><h2>{{ t('home.trend') }}</h2><span class="sub">{{ t('home.trendSub') }}</span></div>
                    <div class="panel-b"><TrendChart :points="T.trend" :series="series" area-key="completed" :height="340" /></div>
                </section>
                <section class="panel">
                    <div class="panel-h"><Icon name="layers" :size="16" /><h2>{{ t('home.compare') }}</h2><span class="sub">{{ t('home.compareSub') }}</span></div>
                    <div class="panel-b">
                        <CompareBars v-if="compareRows.length" :rows="compareRows" :head="[t('compare.lab'), '', t('compare.completed'), t('compare.median')]" />
                        <div v-else class="empty-chart" style="height:200px"><Icon name="layers" :size="22" />{{ t('chart.noData') }}</div>
                        <div v-if="T.readiness != null" class="readiness" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--line-soft)">
                            <RingGauge :value="T.readiness" :size="72" :label="t('home.readyShort')" />
                            <div>
                                <div class="r-t">{{ t('home.readiness') }}</div>
                                <div class="r-s">{{ t('home.readinessHint') }}</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <section v-if="flow" class="panel mt">
                <div class="panel-h">
                    <Icon name="layers" :size="16" /><h2>{{ t('home.flow') }}</h2>
                    <span class="sub">{{ t('home.flowSub', { n: flow.labs }) }}</span>
                    <span class="right sub tnum">{{ t('home.flowOpen', { n: fmtNum(flowOpen, locale) }) }}</span>
                </div>
                <div class="panel-b"><FlowStages :stages="flow.stages" /></div>
            </section>

            <p v-if="!anyData" class="panel hidden-block mt"><Icon name="radio" /> {{ t('home.waiting') }}</p>
        </template>

        <section class="panel mt">
            <div class="panel-h">
                <Icon name="network" :size="16" /><h2>{{ t('sections.labs') }}</h2>
                <span class="sub">{{ t('home.labsSub', { live: stats.live, total: stats.total }) }}</span>
            </div>
            <div class="panel-b labtbl-box" style="padding:12px 0 4px"><LabTable :labs="labs" :now="now" /></div>
            <div class="panel-b labs-grid"><LabCard v-for="lab in labs" :key="lab.id" :lab="lab" :now="now" /></div>
        </section>
    </PublicLayout>
</template>
