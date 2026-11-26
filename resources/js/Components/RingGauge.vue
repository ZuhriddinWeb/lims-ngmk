<script setup>
/** Halqa ko'rsatkich (0–100%). Rang — holat bo'yicha: ≥90 yaxshi, ≥70 ogohlantirish, aks holda xavfli. */
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: null },
    size: { type: Number, default: 92 },
    label: { type: String, default: '' },
});
const R = 40;
const C = 2 * Math.PI * R;
const color = computed(() => (props.value == null ? 'var(--idle)' : props.value >= 90 ? 'var(--good)' : props.value >= 70 ? 'var(--warn)' : 'var(--crit)'));
const offset = computed(() => C * (1 - Math.max(0, Math.min(100, props.value ?? 0)) / 100));
</script>

<template>
    <div class="ring" :style="{ width: size + 'px', height: size + 'px' }" role="img" :aria-label="`${label}: ${value ?? '—'}%`">
        <svg :width="size" :height="size" viewBox="0 0 100 100">
            <circle class="rb" cx="50" cy="50" :r="R" fill="none" stroke-width="9" />
            <circle class="rv" cx="50" cy="50" :r="R" fill="none" stroke-width="9" :stroke="color" :stroke-dasharray="C" :stroke-dashoffset="offset" />
        </svg>
        <div class="rt"><b>{{ value ?? '—' }}<small v-if="value != null" style="font-size:12px">%</small></b><span>{{ label }}</span></div>
    </div>
</template>
