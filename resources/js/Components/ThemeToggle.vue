<script setup>
import { useI18n } from 'vue-i18n';
import { useTheme } from '@/composables/useTheme';
import Icon from '@/Components/Icon.vue';

const { theme, toggle } = useTheme();
const { t } = useI18n();
</script>

<template>
    <button class="iconbtn" type="button" :title="t('nav.theme')" :aria-label="t('nav.theme')" @click="toggle">
        <Icon :name="theme === 'light' ? 'moon' : 'sun'" :size="17" />
    </button>
</template>
