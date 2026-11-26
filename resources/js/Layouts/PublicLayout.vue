<script setup>
import AppShell from '@/Layouts/AppShell.vue';

defineProps({
    crumbs: { type: Array, default: () => [] },
    activeLab: { type: Number, default: null },
    live: { type: Boolean, default: false },
});
</script>

<template>
    <AppShell mode="public" :crumbs="crumbs" :active-lab="activeLab" :live="live"><slot /></AppShell>
</template>
