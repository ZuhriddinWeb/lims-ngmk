<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/Icon.vue';
import { useAgo } from '@/composables/useAgo';

defineProps({
    labs: {
        type: Array,
        default: () => [],
    },
});

const { t } = useI18n();
const ago = useAgo();

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
                <div class="sub">{{ t('admin.listHint') }}</div>
            </div>
            <Link href="/admin/labs/create" class="btn btn-gold"><Icon name="plus" :size="16" /> {{ t('admin.add') }}</Link>
        </div>

        <div v-if="labs.length === 0" class="table-box">
            <div class="empty">{{ t('admin.empty') }}</div>
        </div>

        <div v-else class="table-box">
            <table class="labs">
                <thead>
                    <tr>
                        <th style="width:44px;">{{ t('admin.cols.order') }}</th>
                        <th style="width:100px;">{{ t('admin.cols.code') }}</th>
                        <th>{{ t('admin.cols.name') }}</th>
                        <th style="width:120px;">{{ t('admin.cols.status') }}</th>
                        <th style="width:190px;">{{ t('admin.cols.feed') }}</th>
                        <th>{{ t('admin.cols.url') }}</th>
                        <th style="width:110px; text-align:right;">{{ t('admin.cols.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="lab in labs" :key="lab.id">
                        <td class="mono">{{ lab.sort_order }}</td>
                        <td class="code-cell">{{ lab.code }}</td>
                        <td>{{ lab.name_ru }}</td>
                        <td>
                            <span class="pill" :class="lab.status"><span class="d"></span>{{ lab.status === 'live' ? t('admin.form.statusLive') : t('admin.form.statusSoon') }}</span>
                        </td>
                        <td>
                            <span v-if="lab.online" class="status online"><span class="sd" />{{ t('state.online') }}</span>
                            <span v-else-if="lab.connected" class="status offline"><span class="sd" />{{ t('state.offline') }}</span>
                            <span v-else-if="lab.key_broken" class="status offline"><span class="sd" />{{ t('key.brokenShort') }}</span>
                            <span v-else class="status soon"><span class="sd" />{{ t('admin.noKey') }}</span>
                            <div v-if="lab.connected" class="dim" style="margin-top:4px">{{ lab.last_seen ? ago(lab.last_seen) : t('time.never') }} · {{ t('admin.blocksN', { n: lab.datasets.length }) }}</div>
                        </td>
                        <td class="mono">{{ lab.url ? lab.url.replace(/^https?:\/\//, '') : '—' }}</td>
                        <td>
                            <div class="row-actions">
                                <Link :href="`/admin/labs/${lab.id}/edit`" class="btn btn-ghost btn-sm" :title="t('admin.form.editTitle')"><Icon name="pencil" :size="14" /></Link>
                                <button class="btn btn-danger btn-sm" type="button" :title="t('admin.form.delete')" @click="destroy(lab)"><Icon name="trash" :size="14" /></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
