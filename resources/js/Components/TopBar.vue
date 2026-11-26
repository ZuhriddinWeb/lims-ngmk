<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LangSwitcher from '@/Components/LangSwitcher.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import Icon from '@/Components/Icon.vue';
import { useNow } from '@/composables/useNow';

const props = defineProps({ online: { type: Number, default: null }, total: { type: Number, default: null } });
const { t, locale } = useI18n();
const page = usePage();
const now = useNow(1000);
const time = computed(() => new Date(now.value).toLocaleTimeString(locale.value === 'en' ? 'en-GB' : 'ru-RU', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
const date = computed(() => new Date(now.value).toLocaleDateString(locale.value === 'uz' ? 'uz-UZ' : locale.value === 'en' ? 'en-GB' : 'ru-RU', { weekday: 'short', day: 'numeric', month: 'long' }));
const loggedIn = computed(() => !!page.props.auth?.user);
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
                <div class="clock" :title="t('nav.live')">
                    <span class="livedot" :class="{ off: !online }" />
                    <span class="ct">{{ date }}</span>
                    <b>{{ time }}</b>
                </div>
                <LangSwitcher />
                <ThemeToggle />
                <Link :href="loggedIn ? '/admin/labs' : '/admin/login'" class="adminlink" :title="t('nav.admin')">
                    <Icon name="shield" :size="15" /><span>{{ t('nav.admin') }}</span>
                </Link>
            </div>
        </div>
    </header>
</template>
