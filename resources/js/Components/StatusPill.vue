<script setup>
/** Laboratoriya aloqa holati — doim nuqta + so'z (rang yolg'iz ma'no tashimaydi) */
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({ state: { type: String, required: true } }); // online | offline | unlinked | soon
const { t } = useI18n();
const cls = computed(() => ({ online: 'online', offline: 'offline', unlinked: 'live', live: 'online', soon: 'soon' }[props.state] ?? 'soon'));
</script>

<template>
    <span class="status" :class="cls"><span class="sd" />{{ t(`state.${state}`) }}</span>
</template>
