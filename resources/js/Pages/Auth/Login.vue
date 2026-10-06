<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LangSwitcher from '@/Components/LangSwitcher.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const { t } = useI18n();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head>
        <title>{{ t('auth.title') }}</title>
    </Head>

    <div class="auth-wrap">
        <div class="card-box">
            <div class="logo-row">
                <img src="/images/ngmk.png" alt="НГМК" />
                <div>
                    <h1>{{ t('auth.title') }}</h1>
                    <div class="sub">{{ t('auth.sub') }}</div>
                </div>
                <div class="spacer"></div>
                <ThemeToggle />
            </div>

            <form @submit.prevent="submit">
                <div class="field">
                    <label for="email">{{ t('auth.email') }}</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="input"
                        autocomplete="username"
                        autofocus
                    />
                    <div v-if="form.errors.email" class="err">{{ form.errors.email }}</div>
                </div>

                <div class="field">
                    <label for="password">{{ t('auth.password') }}</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="input"
                        autocomplete="current-password"
                    />
                    <div v-if="form.errors.password" class="err">{{ form.errors.password }}</div>
                </div>

                <div class="field">
                    <label class="checkrow">
                        <input v-model="form.remember" type="checkbox" />
                        <span>{{ t('auth.remember') }}</span>
                    </label>
                </div>

                <button class="btn btn-gold full" type="submit" :disabled="form.processing">
                    {{ t('auth.submit') }}
                </button>
            </form>

            <div style="margin-top: 16px; display: flex; align-items: center; justify-content: space-between;">
                <Link href="/" class="muted-link" style="font-size: 12.5px;">← {{ t('auth.back') }}</Link>
                <LangSwitcher />
            </div>
        </div>
    </div>
</template>
