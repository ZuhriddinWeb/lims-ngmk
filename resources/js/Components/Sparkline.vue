<script setup>
/** Kichik grafik: bitta qator, ostida yengil maydon. Bo'sh qiymatlar — uzilish. */
import { computed } from 'vue';
import { areaPath, linePath, sparkPoints } from '@/lib/charts';

const props = defineProps({
    values: { type: Array, default: () => [] },
    color: { type: String, default: 'var(--s2)' },
    height: { type: Number, default: 40 },
});
const W = 240;
let seq = 0;
const gid = `sg${Date.now().toString(36)}${(seq += 1)}${Math.random().toString(36).slice(2, 7)}`;
const pts = computed(() => sparkPoints(props.values, W, props.height, 3));
const line = computed(() => linePath(pts.value));
const area = computed(() => areaPath(pts.value, props.height));
const last = computed(() => [...pts.value].reverse().find((p) => p));
const has = computed(() => pts.value.filter(Boolean).length > 1);
</script>

<template>
    <svg v-if="has" :viewBox="`0 0 ${W} ${height}`" preserveAspectRatio="none" :height="height" width="100%" aria-hidden="true" style="display:block;overflow:visible">
        <defs>
            <linearGradient :id="gid" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" :stop-color="color" stop-opacity=".28" />
                <stop offset="1" :stop-color="color" stop-opacity="0" />
            </linearGradient>
        </defs>
        <path :d="area" :fill="`url(#${gid})`" />
        <path :d="line" fill="none" :stroke="color" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round" />
        <circle v-if="last" :cx="last.x" :cy="last.y" r="3" :fill="color" vector-effect="non-scaling-stroke" />
    </svg>
</template>
