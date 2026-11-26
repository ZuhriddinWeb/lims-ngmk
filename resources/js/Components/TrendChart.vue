<script setup>
/**
 * Dinamika grafigi: bir nechta qator (bitta o'q), kesishish chizig'i + tooltip, legenda jami bilan.
 * points: [{ key: '2026-11-01', received, completed, analyses }]
 * series: [{ key: 'received', label, color: 'var(--s1)' }]
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { areaPath, fmtNum, linePath, niceTicks, scaleLinear, shortDate } from '@/lib/charts';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    points: { type: Array, default: () => [] },
    series: { type: Array, required: true },
    height: { type: Number, default: 260 },
    areaKey: { type: String, default: '' },
});
const { t, locale } = useI18n();

const box = ref(null);
const width = ref(720);
let ro = null;
onMounted(() => {
    if (!box.value) return;
    width.value = box.value.clientWidth || 720;
    ro = new ResizeObserver(([e]) => { width.value = Math.max(260, Math.round(e.contentRect.width)); });
    ro.observe(box.value);
});
onBeforeUnmount(() => ro?.disconnect());

const M = { t: 12, r: 12, b: 26, l: 40 };
const innerW = computed(() => width.value - M.l - M.r);
const innerH = computed(() => props.height - M.t - M.b);

const maxVal = computed(() => Math.max(0, ...props.points.flatMap((p) => props.series.map((s) => Number(p[s.key] ?? 0)))));
const yTicks = computed(() => niceTicks(maxVal.value, 4));
const x = computed(() => scaleLinear([0, Math.max(1, props.points.length - 1)], [M.l, M.l + innerW.value]));
const y = computed(() => scaleLinear([0, yTicks.value.max], [M.t + innerH.value, M.t]));

const paths = computed(() => props.series.map((s) => {
    const pts = props.points.map((p, i) => (p[s.key] == null ? null : { x: x.value(i), y: y.value(Number(p[s.key])) }));
    return { ...s, d: linePath(pts), area: s.key === props.areaKey ? areaPath(pts, M.t + innerH.value) : '' };
}));
const totals = computed(() => Object.fromEntries(props.series.map((s) => [s.key, props.points.reduce((a, p) => a + Number(p[s.key] ?? 0), 0)])));
const hasData = computed(() => props.points.length > 1 && maxVal.value > 0);

// x o'qi yozuvlari — kengligiga qarab siyraklashtiriladi
const xLabels = computed(() => {
    const n = props.points.length;
    if (!n) return [];
    const every = Math.max(1, Math.ceil(n / Math.max(2, Math.floor(innerW.value / 64))));
    return props.points.map((p, i) => ({ i, text: shortDate(p.key) })).filter((l) => l.i % every === 0 || l.i === n - 1);
});

const hover = ref(null);
function move(ev) {
    const rect = box.value.getBoundingClientRect();
    const cx = (ev.touches?.[0]?.clientX ?? ev.clientX) - rect.left;
    const n = props.points.length;
    if (n < 2) return;
    const i = Math.round(((cx - M.l) / innerW.value) * (n - 1));
    hover.value = Math.max(0, Math.min(n - 1, i));
}
const tip = computed(() => {
    if (hover.value == null) return null;
    const p = props.points[hover.value];
    const left = x.value(hover.value);
    const top = Math.min(...props.series.map((s) => y.value(Number(p[s.key] ?? 0))));
    return { p, left: Math.min(Math.max(left, 90), width.value - 90), top: Math.max(top - 10, 70), x: left };
});
</script>

<template>
    <div ref="box" class="chart" @mouseleave="hover = null">
        <div class="legend" style="margin-bottom: 10px;">
            <span v-for="s in series" :key="s.key" class="li"><i :style="{ background: s.color }" />{{ s.label }} <b>{{ fmtNum(totals[s.key], locale) }}</b></span>
        </div>

        <div v-if="!hasData" class="empty-chart" :style="{ height: height + 'px' }">
            <Icon name="activity" :size="22" />
            <span>{{ t('chart.noData') }}</span>
        </div>

        <svg v-else :height="height" :viewBox="`0 0 ${width} ${height}`" role="img" :aria-label="series.map((s) => `${s.label}: ${totals[s.key]}`).join(', ')">
            <g>
                <line v-for="tk in yTicks.ticks" :key="tk" class="gridline" :x1="M.l" :x2="M.l + innerW" :y1="y(tk)" :y2="y(tk)" />
                <text v-for="tk in yTicks.ticks" :key="'t' + tk" class="axis-t" :x="M.l - 8" :y="y(tk) + 4" text-anchor="end">{{ fmtNum(tk, locale) }}</text>
                <text v-for="l in xLabels" :key="'x' + l.i" class="axis-t" :x="x(l.i)" :y="height - 6" text-anchor="middle">{{ l.text }}</text>
            </g>
            <path v-for="s in paths" v-show="s.area" :key="'a' + s.key" class="area" :d="s.area" :fill="s.color" />
            <path v-for="s in paths" :key="s.key" class="series" :d="s.d" :stroke="s.color" />
            <g v-if="tip">
                <line class="cross" :x1="tip.x" :x2="tip.x" :y1="M.t" :y2="M.t + innerH" />
                <circle v-for="s in series" v-show="tip.p[s.key] != null" :key="'d' + s.key" class="dot-c" r="4.5" :fill="s.color" :cx="tip.x" :cy="y(Number(tip.p[s.key] ?? 0))" />
            </g>
            <rect class="hit" :x="M.l" :y="M.t" :width="innerW" :height="innerH" @mousemove="move" @touchstart.passive="move" @touchmove.passive="move" />
        </svg>

        <div v-if="tip" class="tip" :style="{ left: tip.left + 'px', top: tip.top + 'px' }">
            <div class="t-h">{{ shortDate(tip.p.key) }}</div>
            <div v-for="s in series" :key="s.key" class="t-r"><i :style="{ background: s.color }" />{{ s.label }}<b>{{ fmtNum(tip.p[s.key], locale) }}</b></div>
        </div>
    </div>
</template>
