<script setup>
/** Laboratoriyalar solishtiruvi — bitta rang, gorizontal ustunlar (eng kattasi 100%) */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { fmtNum } from '@/lib/charts';

const props = defineProps({
    rows: { type: Array, default: () => [] }, // [{ id, code, value, sub }]
    head: { type: Array, default: null },     // [kod, (ustun), qiymat, qo'shimcha]
});
const { locale } = useI18n();
const max = computed(() => Math.max(1, ...props.rows.map((r) => r.value || 0)));
</script>

<template>
    <div class="bars">
        <div v-if="head" class="bars-h"><span v-for="(h, i) in head" :key="i">{{ h }}</span></div>
        <component :is="r.id ? Link : 'div'" v-for="r in rows" :key="r.code" :href="r.id ? `/labs/${r.id}` : undefined" class="bar-row" >
            <span class="bl">{{ r.code }}</span>
            <span class="bt"><span class="bf" :style="{ width: (100 * (r.value || 0)) / max + '%' }" /></span>
            <span class="bv">{{ fmtNum(r.value, locale) }}</span>
            <span class="bs">{{ r.sub ?? '' }}</span>
        </component>
    </div>
</template>
