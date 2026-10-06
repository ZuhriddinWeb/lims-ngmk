<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import LabForm from '@/Components/LabForm.vue';

const props = defineProps({
    lab: {
        type: Object,
        required: true,
    },
});

const { t } = useI18n();

const form = useForm({
    code: props.lab.code,
    name_ru: props.lab.name_ru,
    name_uz: props.lab.name_uz,
    name_en: props.lab.name_en,
    status: props.lab.status,
    url: props.lab.url ?? '',
    sort_order: props.lab.sort_order,
});

function submit() {
    form.put(`/admin/labs/${props.lab.id}`);
}
</script>

<template>
    <Head>
        <title>{{ t('admin.form.editTitle') }}</title>
    </Head>

    <AdminLayout>
        <LabForm :form="form" mode="edit" @submit="submit" />
    </AdminLayout>
</template>
