<script setup>
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Hero from '@/Components/Hero.vue';
import StatsBar from '@/Components/StatsBar.vue';
import LabCard from '@/Components/LabCard.vue';
import FutureBlock from '@/Components/FutureBlock.vue';

defineProps({
    labs: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({ total: 0, live: 0, soon: 0 }),
    },
});

const { t } = useI18n();
</script>

<template>
    <Head>
        <title>{{ t('brand.sub') }}</title>
    </Head>

    <PublicLayout>
        <Hero />

        <StatsBar :stats="stats" />

        <div class="sec-head">
            <h2>{{ t('sections.labs') }}</h2>
            <div class="line"></div>
        </div>

        <section class="grid">
            <LabCard v-for="lab in labs" :key="lab.id" :lab="lab" />
        </section>

        <div class="sec-head">
            <h2>{{ t('sections.next') }}</h2>
            <div class="line"></div>
        </div>

        <FutureBlock />
    </PublicLayout>
</template>
