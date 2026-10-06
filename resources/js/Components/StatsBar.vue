<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
});

const { t } = useI18n();

const items = computed(() => [
    { n: props.stats.total, l: t('stats.total') },
    { n: props.stats.live, l: t('stats.live') },
    { n: props.stats.soon, l: t('stats.soon') },
]);
</script>

<template>
    <section class="stats">
        <div v-for="(item, i) in items" :key="i" class="stat">
            <div class="num">{{ item.n }}</div>
            <div class="lbl">{{ item.l }}</div>
        </div>
    </section>
</template>
