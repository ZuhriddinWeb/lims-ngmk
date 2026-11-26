<script setup>
/**
 * KPI kartasi — zavod LIMS'idagi kabi: ikonka, o'zgarish (yaxshi/yomon rangda), katta son, nom, izoh.
 * O'zgarish faqat ma'noli bo'lsa: asos kichik bo'lsa ko'rsatilmaydi, 2 baravardan ko'p — «×N».
 */
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Sparkline from '@/Components/Sparkline.vue';
import Icon from '@/Components/Icon.vue';
import { useCountUp } from '@/composables/useCountUp';
import { deltaInfo, fmtNum } from '@/lib/charts';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: Number, default: null },
    unit: { type: String, default: '' },
    change: { type: Number, default: null },
    prev: { type: Number, default: null },
    goodUp: { type: Boolean, default: true },
    compare: { type: Boolean, default: true },
    sub: { type: String, default: '' },
    icon: { type: String, default: 'activity' },
    tone: { type: String, default: 'neutral' },   // neutral | crit | ok | series
    color: { type: String, default: '' },          // series rangi (grafikdagi bilan bir xil)
    spark: { type: Array, default: null },
    digits: { type: Number, default: 0 },
    alertWhenPositive: { type: Boolean, default: false },
});
const { t, locale } = useI18n();
const shown = useCountUp(() => props.value);
const text = computed(() => {
    if (props.value == null) return '—';
    const v = typeof shown.value === 'number' ? shown.value : props.value;
    return fmtNum(props.digits ? Math.round(v * 10 ** props.digits) / 10 ** props.digits : Math.round(v), locale.value);
});
const d = computed(() => (props.compare ? deltaInfo(props.change, props.prev, props.value) : null));
const dCls = computed(() => {
    if (!d.value || d.value.kind === 'small') return 'none';
    if (!d.value.dir) return 'flat';
    return (d.value.dir > 0) === props.goodUp ? 'up' : 'down';
});
const iconTone = computed(() => {
    if (props.alertWhenPositive) return props.value > 0 ? 'crit' : 'ok';
    return props.color ? 's' : props.tone === 'neutral' ? '' : props.tone;
});
</script>

<template>
    <div class="panel kpi">
        <div class="k-head">
            <span class="k-icon" :class="iconTone" :style="color ? { '--c': color } : null"><Icon :name="icon" :size="16" /></span>
            <span v-if="d && d.kind !== 'small'" class="delta" :class="dCls" :title="prev != null ? t('kpi.prevWas', { n: fmtNum(prev, locale) }) : ''">
                <Icon v-if="d.dir" :name="d.dir > 0 ? 'arrow-up-right' : 'arrow-down-right'" :size="13" />{{ d.text }}
            </span>
            <span v-else-if="compare && (change != null || d?.kind === 'small')" class="delta none" :title="prev != null ? t('kpi.prevWas', { n: fmtNum(prev, locale) }) : ''">{{ t('kpi.smallBase') }}</span>
        </div>
        <div class="k-val" :class="{ crit: alertWhenPositive && value > 0 }">{{ text }}<span v-if="unit && value != null" class="k-unit">{{ unit }}</span></div>
        <div class="k-lbl">{{ label }}</div>
        <div v-if="sub" class="k-sub">{{ sub }}</div>
        <slot />
        <div v-if="spark" class="k-spark"><Sparkline :values="spark" :color="color || 'var(--s2)'" :height="40" /></div>
    </div>
</template>
