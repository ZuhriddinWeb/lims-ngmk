<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    mode: {
        type: String,
        default: 'create', // 'create' | 'edit'
    },
});

const emit = defineEmits(['submit']);

const { t } = useI18n();
</script>

<template>
    <div class="card-box" style="max-width: 720px;">
        <h1 style="margin:0 0 4px; font-size:20px; font-weight:900;">
            {{ mode === 'edit' ? t('admin.form.editTitle') : t('admin.form.createTitle') }}
        </h1>
        <div class="sub" style="margin-bottom:20px;">{{ t('admin.subtitle') }}</div>

        <form @submit.prevent="emit('submit')">
            <div class="grid-2">
                <div class="field">
                    <label>{{ t('admin.form.code') }}</label>
                    <input v-model="form.code" type="text" class="input" :placeholder="t('admin.form.codeHint')" />
                    <div v-if="form.errors.code" class="err">{{ form.errors.code }}</div>
                </div>

                <div class="field">
                    <label>{{ t('admin.form.sort') }}</label>
                    <input v-model="form.sort_order" type="number" min="0" class="input" />
                    <div v-if="form.errors.sort_order" class="err">{{ form.errors.sort_order }}</div>
                </div>
            </div>

            <div class="field">
                <label>{{ t('admin.form.nameRu') }}</label>
                <input v-model="form.name_ru" type="text" class="input" />
                <div v-if="form.errors.name_ru" class="err">{{ form.errors.name_ru }}</div>
            </div>

            <div class="grid-2">
                <div class="field">
                    <label>{{ t('admin.form.nameUz') }}</label>
                    <input v-model="form.name_uz" type="text" class="input" />
                    <div v-if="form.errors.name_uz" class="err">{{ form.errors.name_uz }}</div>
                </div>

                <div class="field">
                    <label>{{ t('admin.form.nameEn') }}</label>
                    <input v-model="form.name_en" type="text" class="input" />
                    <div v-if="form.errors.name_en" class="err">{{ form.errors.name_en }}</div>
                </div>
            </div>

            <div class="grid-2">
                <div class="field">
                    <label>{{ t('admin.form.status') }}</label>
                    <select v-model="form.status" class="select">
                        <option value="live">{{ t('admin.form.statusLive') }}</option>
                        <option value="soon">{{ t('admin.form.statusSoon') }}</option>
                    </select>
                    <div v-if="form.errors.status" class="err">{{ form.errors.status }}</div>
                </div>

                <div class="field">
                    <label>{{ t('admin.form.url') }}</label>
                    <input v-model="form.url" type="text" class="input" placeholder="http://192.168.x.x:3010" />
                    <div v-if="form.errors.url" class="err">{{ form.errors.url }}</div>
                    <div class="sub" style="margin-top:5px;">{{ t('admin.form.urlHint') }}</div>
                </div>
            </div>

            <div style="display:flex; gap:10px; margin-top:8px;">
                <button class="btn btn-gold" type="submit" :disabled="form.processing">
                    {{ mode === 'edit' ? t('admin.form.save') : t('admin.form.create') }}
                </button>
                <Link href="/admin/labs" class="btn btn-ghost">{{ t('admin.form.cancel') }}</Link>
            </div>
        </form>
    </div>
</template>
