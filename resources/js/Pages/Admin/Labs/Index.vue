<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    labs: {
        type: Array,
        default: () => [],
    },
});

const { t } = useI18n();

function destroy(lab) {
    if (!confirm(t('admin.form.deleteConfirm'))) return;
    router.delete(`/admin/labs/${lab.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head>
        <title>{{ t('admin.title') }}</title>
    </Head>

    <AdminLayout>
        <div class="admin-head">
            <div>
                <h1>{{ t('admin.title') }}</h1>
            </div>
            <Link href="/admin/labs/create" class="btn btn-gold">+ {{ t('admin.add') }}</Link>
        </div>

        <div v-if="labs.length === 0" class="table-box">
            <div class="empty">{{ t('admin.empty') }}</div>
        </div>

        <div v-else class="table-box">
            <table class="labs">
                <thead>
                    <tr>
                        <th style="width:44px;">{{ t('admin.cols.order') }}</th>
                        <th style="width:110px;">{{ t('admin.cols.code') }}</th>
                        <th>{{ t('admin.cols.name') }}</th>
                        <th style="width:130px;">{{ t('admin.cols.status') }}</th>
                        <th>{{ t('admin.cols.url') }}</th>
                        <th style="width:160px; text-align:right;">{{ t('admin.cols.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="lab in labs" :key="lab.id">
                        <td class="mono">{{ lab.sort_order }}</td>
                        <td class="code-cell">{{ lab.code }}</td>
                        <td>{{ lab.name_ru }}</td>
                        <td>
                            <span class="pill" :class="lab.status">
                                <span class="d"></span>
                                {{ lab.status === 'live' ? t('admin.form.statusLive') : t('admin.form.statusSoon') }}
                            </span>
                        </td>
                        <td class="mono">{{ lab.url ? lab.url.replace(/^https?:\/\//, '') : '—' }}</td>
                        <td>
                            <div class="row-actions">
                                <Link :href="`/admin/labs/${lab.id}/edit`" class="btn btn-ghost btn-sm">
                                    ✎
                                </Link>
                                <button class="btn btn-danger btn-sm" type="button" @click="destroy(lab)">
                                    🗑
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
