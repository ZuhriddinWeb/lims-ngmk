<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import LabForm from '@/Components/LabForm.vue';
import Icon from '@/Components/Icon.vue';
import { useAgo } from '@/composables/useAgo';

const props = defineProps({
    lab: { type: Object, required: true },
    feed: { type: Object, default: () => ({}) },
    newKey: { type: String, default: null },
});

const { t } = useI18n();
const ago = useAgo();

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

const busy = ref(false);
function issue() {
    if (props.feed.connected && !confirm(t('key.rotateConfirm'))) return;
    busy.value = true;
    router.post(`/admin/labs/${props.lab.id}/key`, {}, { preserveScroll: true, onFinish: () => { busy.value = false; } });
}
function revoke() {
    if (!confirm(t('key.revokeConfirm'))) return;
    router.delete(`/admin/labs/${props.lab.id}/key`, { preserveScroll: true });
}

const copied = ref(false);
async function copy() {
    try {
        await navigator.clipboard.writeText(props.newKey);
    } catch (e) {
        const el = document.getElementById('newkey');
        const r = document.createRange();
        r.selectNodeContents(el);
        const s = window.getSelection();
        s.removeAllRanges();
        s.addRange(r);
        document.execCommand('copy');
    }
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}
const state = computed(() => (props.feed.online ? 'online' : props.feed.connected ? 'offline' : 'none'));
</script>

<template>
    <Head>
        <title>{{ t('admin.form.editTitle') }}</title>
    </Head>

    <AdminLayout :crumbs="[{ label: t('shell.adminShort'), href: '/admin/labs' }, { label: t('admin.title'), href: '/admin/labs' }, { label: lab.code }]">
        <div class="edit-grid">
            <LabForm :form="form" mode="edit" @submit="submit" />

            <section class="card-box" data-keypanel>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                    <Icon name="key" :size="18" style="color:var(--gold)" />
                    <h2 style="margin:0;font-size:15px;font-weight:600">{{ t('key.title') }}</h2>
                    <span style="margin-left:auto" class="status" :class="state === 'online' ? 'online' : state === 'offline' ? 'offline' : 'soon'"><span class="sd" />{{ state === 'none' ? t('admin.noKey') : t(`state.${state}`) }}</span>
                </div>
                <p class="sub" style="margin:0 0 14px;line-height:1.5">{{ t('key.hint') }}</p>

                <div v-if="newKey" class="keybox" style="margin-bottom:14px">
                    <strong style="font-size:13px">{{ t('key.newTitle') }}</strong>
                    <div class="sub" style="margin-top:2px">{{ t('key.newHint') }}</div>
                    <div class="k">
                        <code id="newkey">{{ newKey }}</code>
                        <button class="btn btn-gold btn-sm" type="button" @click="copy"><Icon :name="copied ? 'check' : 'copy'" :size="14" /> {{ copied ? t('key.copied') : t('key.copy') }}</button>
                    </div>
                    <ol class="steps">
                        <li>{{ t('key.step1') }}</li>
                        <li>{{ t('key.step2', { url: feed.ingest_url }) }}</li>
                        <li>{{ t('key.step3') }}</li>
                    </ol>
                </div>

                <dl class="dl">
                    <dt>{{ t('key.current') }}</dt><dd class="mono">{{ feed.key_hint || '—' }}</dd>
                    <dt>{{ t('key.created') }}</dt><dd>{{ feed.key_created_at ? ago(feed.key_created_at) : '—' }}</dd>
                    <dt>{{ t('key.lastSeen') }}</dt><dd>{{ feed.last_seen ? ago(feed.last_seen) : t('time.never') }}</dd>
                    <dt v-if="feed.last_ip">IP</dt><dd v-if="feed.last_ip" class="mono">{{ feed.last_ip }}</dd>
                    <dt v-if="feed.interval">{{ t('key.interval') }}</dt><dd v-if="feed.interval">{{ t('lab.interval', { n: feed.interval }) }}</dd>
                    <dt v-if="feed.bytes">{{ t('key.size') }}</dt><dd v-if="feed.bytes">{{ (feed.bytes / 1024).toFixed(1) }} KB</dd>
                </dl>
                <div v-if="feed.datasets?.length" style="margin-top:12px">
                    <div class="sub" style="margin-bottom:6px">{{ t('key.datasets') }}</div>
                    <div class="tags"><span v-for="d in feed.datasets" :key="d" class="tag">{{ t(`ds.${d}`) }}</span></div>
                </div>

                <div style="display:flex;gap:8px;margin-top:18px;flex-wrap:wrap">
                    <button class="btn btn-gold btn-sm" type="button" :disabled="busy" @click="issue"><Icon name="key" :size="14" /> {{ feed.connected ? t('key.rotate') : t('key.issue') }}</button>
                    <button v-if="feed.connected" class="btn btn-danger btn-sm" type="button" @click="revoke"><Icon name="trash" :size="14" /> {{ t('key.revoke') }}</button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
