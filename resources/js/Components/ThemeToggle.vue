<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useTheme } from '@/composables/useTheme';

const { theme, toggle } = useTheme();
const { t } = useI18n();

const icon = computed(() => (theme.value === 'light' ? '☀️' : '🌙'));
</script>

<template>
    <button class="iconbtn" type="button" :title="t('nav.theme')" @click="toggle">
        {{ icon }}
    </button>
</template>
