<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <section class="future">
        <div class="ic">📊</div>
        <div class="ft">
            <h3>{{ t('future.h') }}</h3>
            <p>{{ t('future.p') }}</p>
        </div>
        <span class="tag">{{ t('future.tag') }}</span>
    </section>
</template>
