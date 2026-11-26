<script setup>
/**
 * Laboratoriya kartasi: aloqa holati, bugungi asosiy sonlar, 30 kunlik yakunlanganlar chizig'i, oxirgi aloqa vaqti.
 * Ma'lumot zavod LIMS'idan keladi (faqat zavod admini ruxsat bergan bloklar).
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Icon from '@/Components/Icon.vue';
import Sparkline from '@/Components/Sparkline.vue';
import StatusPill from '@/Components/StatusPill.vue';
import { useAgo } from '@/composables/useAgo';
import { fmtNum } from '@/lib/charts';

const props = defineProps({
    lab: { type: Object, required: true },
    now: { type: Number, default: () => Date.now() },
});
const { t, locale } = useI18n();
const ago = useAgo();

const feed = computed(() => props.lab.feed ?? null);
const state = computed(() => labState(props.lab));
const name = computed(() => props.lab.name?.[locale.value] || props.lab.name?.ru || props.lab.name?.en);
const today = computed(() => feed.value?.today ?? null);
const month = computed(() => feed.value?.month ?? null);
const hasData = computed(() => !!(today.value || month.value || feed.value?.spark));
const isDemo = computed(() => !!feed.value?.demo);
</script>

<script>
/** Holat: online | offline (kalit bor, aloqa uzilgan) | unlinked (ishlayapti, lekin ulanmagan) | live (sonlar yashirin) | soon */
export function labState(lab) {
    const f = lab.feed;
    if (f === undefined) return lab.status === 'live' ? 'live' : 'soon'; // sonlar yashirilgan (mehmon)
    if (f?.online) return 'online';
    if (f?.connected) return 'offline';
    return lab.status === 'live' ? 'unlinked' : 'soon';
}
</script>

<template>
    <article class="panel lab">
        <div class="lab-top">
            <div style="min-width:0">
                <div class="c">{{ lab.code }}</div>
                <div class="n">{{ name }}</div>
            </div>
            <div class="r">
                <StatusPill :state="state" />
                <span v-if="isDemo" class="demo-tag">DEMO</span>
            </div>
        </div>

        <template v-if="hasData">
            <div class="lab-stats">
                <div class="ls"><b class="tnum">{{ fmtNum(today?.received ?? null, locale) }}</b><span>{{ t('card.todayReceived') }}</span></div>
                <div class="ls"><b class="tnum">{{ fmtNum(today?.in_work ?? month?.in_work ?? null, locale) }}</b><span>{{ t('card.inWork') }}</span></div>
                <div class="ls" :class="{ alert: (today?.overdue ?? month?.overdue) > 0 }"><b class="tnum">{{ fmtNum(today?.overdue ?? month?.overdue ?? null, locale) }}</b><span>{{ t('card.overdue') }}</span></div>
            </div>
            <Sparkline v-if="feed.spark" :values="feed.spark" color="var(--s2)" :height="36" />
            <div class="lab-meta">
                <Icon :name="state === 'online' ? 'radio' : 'clock'" :size="13" />
                <span>{{ state === 'online' ? t('card.updated', { t: ago(feed.last_seen, now) }) : t('card.lastSeen', { t: ago(feed.last_seen, now) }) }}</span>
                <span v-if="feed.readiness != null" style="margin-left:auto" :title="t('card.readinessHint')">{{ t('card.readinessHint') }}: {{ feed.readiness }}%</span>
            </div>
        </template>
        <div v-else-if="feed" class="lab-placeholder">
            <Icon :name="state === 'soon' ? 'clock' : state === 'unlinked' ? 'key' : 'wifi-off'" :size="16" />
            <span>{{ t(`card.placeholder.${state}`) }}</span>
        </div>

        <div v-if="hasData || lab.has_address" class="lab-actions">
            <Link v-if="hasData" :href="`/labs/${lab.id}`" class="btn btn-outline btn-sm flex1">{{ t('card.details') }} <Icon name="arrow-right" :size="14" /></Link>
            <a v-if="lab.has_address" :href="lab.url" target="_blank" rel="noopener" class="btn btn-gold btn-sm flex1">{{ t('card.open') }} <Icon name="external" :size="14" /></a>
        </div>
    </article>
</template>
