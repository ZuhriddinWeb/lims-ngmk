<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const year = new Date().getFullYear();
</script>

<template>
    <footer class="site">
        <div><strong>{{ t('footer.org') }}</strong></div>
        <div>
            <span>{{ t('footer.sub') }}</span>
            <span class="sep">•</span> {{ year }}
            <span class="sep">•</span> v1.0
        </div>
    </footer>
</template>
