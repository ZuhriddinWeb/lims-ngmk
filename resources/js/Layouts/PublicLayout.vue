<script setup>
import TopBar from '@/Components/TopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
</script>

<template>
    <TopBar />
    <main class="wrap">
        <slot />
        <SiteFooter />
    </main>
</template>
