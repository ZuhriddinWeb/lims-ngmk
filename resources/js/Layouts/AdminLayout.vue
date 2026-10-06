<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LangSwitcher from '@/Components/LangSwitcher.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const { t } = useI18n();
const page = usePage();

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

function logout() {
    router.post('/admin/logout');
}
</script>

<template>
    <header class="admin-top">
        <div class="wrap bar">
            <Link href="/admin/labs" class="brand">
                <img src="/images/ngmk.png" alt="НГМК" style="width:36px;height:36px;" />
                <div class="b-txt">
                    <div class="b-title" style="font-size:15px;">{{ t('brand.title') }}</div>
                    <div class="b-sub">{{ t('admin.subtitle') }}</div>
                </div>
            </Link>

            <div class="spacer"></div>

            <div class="controls">
                <Link href="/" class="adminlink">{{ t('admin.toSite') }}</Link>
                <LangSwitcher />
                <ThemeToggle />
                <button class="btn btn-ghost btn-sm" type="button" @click="logout">
                    {{ t('admin.logout') }}
                </button>
            </div>
        </div>
    </header>

    <main class="wrap admin-main">
        <div v-if="flashSuccess" class="flash success">{{ flashSuccess }}</div>
        <div v-if="flashError" class="flash error">{{ flashError }}</div>

        <slot />
    </main>
</template>
