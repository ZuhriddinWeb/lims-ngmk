<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppShell from '@/Layouts/AppShell.vue';

const props = defineProps({ crumbs: { type: Array, default: null } });
const { t } = useI18n();
const trail = computed(() => props.crumbs ?? [{ label: t('shell.adminShort'), href: '/admin/labs' }, { label: t('admin.title') }]);
</script>

<template>
    <AppShell mode="admin" :crumbs="trail"><slot /></AppShell>
</template>
