<script setup>
/**
 * Ish oqimi — zavod LIMS'idagi «Ish oqimi» bilan bir xil: 4 bosqich, son, izoh va yosh bo'yicha taqsimot
 * (ordinal: bitta rang, och → to'q; ustun uzunligi — eng katta bosqichga nisbatan).
 */
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Icon from '@/Components/Icon.vue';
import { fmtNum } from '@/lib/charts';

const props = defineProps({ stages: { type: Array, default: () => [] } });
const { t, locale } = useI18n();
const AGE = ['d1', 'd3', 'd7', 'over'];
const COLORS = { d1: 'var(--age-1)', d3: 'var(--age-2)', d7: 'var(--age-3)', over: 'var(--age-4)' };
const max = computed(() => Math.max(1, ...props.stages.map((s) => s.count || 0)));
const parts = (s) => AGE.map((a) => ({ a, v: s.aging?.[a] ?? 0 })).filter((p) => p.v > 0);
</script>

<template>
    <div class="flow">
        <div v-for="s in stages" :key="s.key" class="stage">
            <Icon class="chev" name="chevron-right" :size="16" />
            <div class="st-top">
                <span class="st-l">{{ t(`flow.${s.key}`) }}</span>
                <span class="st-n">{{ fmtNum(s.count, locale) }}</span>
            </div>
            <div class="st-h">{{ t(`flowHint.${s.key}`) }}</div>
            <div class="agebar">
                <div style="display:flex;gap:2px;height:100%" :style="{ width: (100 * (s.count || 0)) / max + '%' }">
                    <span v-for="p in parts(s)" :key="p.a" :style="{ flexGrow: p.v, flexBasis: 0, minWidth: '3px', background: COLORS[p.a] }" :title="`${t('age.' + p.a)}: ${p.v}`" />
                </div>
            </div>
            <div v-if="s.aging?.over" class="over"><Icon name="alert" :size="13" /> {{ t('age.over') }}: {{ fmtNum(s.aging.over, locale) }}</div>
        </div>
    </div>
    <div class="agelegend">
        <span>{{ t('age.label') }}:</span>
        <span v-for="a in AGE" :key="a"><i :style="{ background: COLORS[a] }" />{{ t('age.' + a) }}</span>
    </div>
</template>
