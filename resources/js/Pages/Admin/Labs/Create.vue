<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import LabForm from '@/Components/LabForm.vue';

const props = defineProps({
    nextSortOrder: {
        type: Number,
        default: 1,
    },
});

const { t } = useI18n();

const form = useForm({
    code: '',
    name_ru: '',
    name_uz: '',
    name_en: '',
    status: 'soon',
    url: '',
    sort_order: props.nextSortOrder,
});

function submit() {
    form.post('/admin/labs');
}
</script>

<template>
    <Head>
        <title>{{ t('admin.form.createTitle') }}</title>
    </Head>

    <AdminLayout :crumbs="[{ label: t('shell.adminShort'), href: '/admin/labs' }, { label: t('admin.title'), href: '/admin/labs' }, { label: t('admin.form.createTitle') }]">
        <LabForm :form="form" mode="create" @submit="submit" />
    </AdminLayout>
</template>
