<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LangSwitcher from '@/Components/LangSwitcher.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const { t } = useI18n();
</script>

<template>
    <header class="top">
        <div class="wrap topbar">
            <Link href="/" class="brand">
                <img src="/images/ngmk.png" alt="НГМК" />
                <div class="b-txt">
                    <div class="b-title">{{ t('brand.title') }}</div>
                    <div class="b-sub">{{ t('brand.sub') }}</div>
                </div>
            </Link>

            <div class="spacer"></div>

            <div class="controls">
                <LangSwitcher />
                <ThemeToggle />
            </div>
        </div>
    </header>
</template>
