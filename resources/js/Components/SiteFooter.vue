<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const year = new Date().getFullYear();
</script>

<template>
    <footer class="site">
        <div><strong>{{ t('footer.org') }}</strong><span class="sep">•</span>{{ t('footer.sub') }}</div>
        <div>{{ t('footer.note') }}<span class="sep">•</span>{{ year }}<span class="sep">•</span>v2.0</div>
    </footer>
</template>
