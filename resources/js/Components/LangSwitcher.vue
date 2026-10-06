<script setup>
import { useLang } from '@/composables/useLang';

const { locale, setLang, locales } = useLang();
</script>

<template>
    <div class="langs">
        <button
            v-for="lang in locales"
            :key="lang"
            type="button"
            :class="{ active: locale === lang }"
            @click="setLang(lang)"
        >
            {{ lang.toUpperCase() }}
        </button>
    </div>
</template>
