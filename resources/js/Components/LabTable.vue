<script setup>
/**
 * Laboratoriyalar jadvali (katta ekran): har qatorda holat, bugungi sonlar, 30 kunlik natija, uskunalar, dinamika va oxirgi aloqa.
 * Telefon ekranida o'rniga LabCard kartalari chiqadi (CSS).
 */
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Icon from '@/Components/Icon.vue';
import Sparkline from '@/Components/Sparkline.vue';
import StatusPill from '@/Components/StatusPill.vue';
import { labState } from '@/Components/LabCard.vue';
import { useAgo } from '@/composables/useAgo';
import { fmtNum } from '@/lib/charts';

defineProps({
    labs: { type: Array, default: () => [] },
    now: { type: Number, default: () => Date.now() },
});
const { t, locale } = useI18n();
const ago = useAgo();
const name = (l) => l.name?.[locale.value] || l.name?.ru || l.name?.en;
const has = (l) => !!(l.feed?.today || l.feed?.month || l.feed?.spark);
const n = (v) => fmtNum(v ?? null, locale.value);
const readyColor = (v) => (v >= 90 ? 'var(--ok)' : v >= 70 ? 'var(--warn)' : 'var(--crit)');
const badge = (l) => { const m = String(l.code).match(/(\d+)\s*$/); return m ? m[1] : String(l.code).slice(0, 4); };
const open = (l) => { if (has(l)) router.visit(`/labs/${l.id}`); };
</script>

<template>
    <div class="tbl-scroll">
        <table class="tbl labtbl">
            <thead>
                <tr>
                    <th>{{ t('table.lab') }}</th>
                    <th>{{ t('table.state') }}</th>
                    <th class="num">{{ t('table.todayReceived') }}</th>
                    <th class="num">{{ t('table.todayCompleted') }}</th>
                    <th class="num">{{ t('table.inWork') }}</th>
                    <th class="num">{{ t('table.overdue') }}</th>
                    <th class="num hide-lg">{{ t('table.month') }}</th>
                    <th class="num hide-lg">{{ t('table.median') }}</th>
                    <th class="hide-lg">{{ t('table.equipment') }}</th>
                    <th class="hide-md">{{ t('table.trend') }}</th>
                    <th class="hide-md">{{ t('table.seen') }}</th>
                    <th />
                </tr>
            </thead>
            <tbody>
                <tr v-for="l in labs" :key="l.id" :class="{ clickable: has(l) }" @click="open(l)">
                    <td>
                        <div class="lab-cell">
                            <span class="lab-badge" :class="{ off: !has(l) }" :style="badge(l).length > 2 ? 'font-size:10.5px' : 'font-size:16px'">{{ badge(l) }}</span>
                            <div style="min-width:0">
                                <div class="c">{{ l.code }} <span v-if="l.feed?.demo" class="demo-tag" style="margin-left:4px">DEMO</span></div>
                                <div class="n">{{ name(l) }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <StatusPill :state="labState(l)" />
                    </td>
                    <template v-if="has(l)">
                        <td class="num">{{ n(l.feed.today?.received) }}</td>
                        <td class="num">{{ n(l.feed.today?.completed) }}</td>
                        <td class="num">{{ n(l.feed.today?.in_work ?? l.feed.month?.in_work) }}</td>
                        <td class="num" :class="{ alert: (l.feed.today?.overdue ?? l.feed.month?.overdue) > 0 }">{{ n(l.feed.today?.overdue ?? l.feed.month?.overdue) }}</td>
                        <td class="num hide-lg">{{ n(l.feed.month?.completed) }}</td>
                        <td class="num hide-lg">{{ l.feed.month?.turnaround_h != null ? `${n(l.feed.month.turnaround_h)} ${t('kpi.hShort')}` : '—' }}</td>
                        <td class="hide-lg">
                            <span v-if="l.feed.readiness != null" class="mini-ring" :title="t('card.readinessHint')">
                                <span class="mini-bar"><i :style="{ width: l.feed.readiness + '%', background: readyColor(l.feed.readiness) }" /></span>
                                <span class="tnum" style="font-size:12.5px">{{ l.feed.readiness }}%</span>
                            </span>
                            <span v-else class="muted">—</span>
                        </td>
                        <td class="spark-cell hide-md" :title="t('card.sparkHint')"><Sparkline v-if="l.feed.spark" :values="l.feed.spark" color="var(--s2)" :height="30" /></td>
                        <td class="seen hide-md">{{ l.feed.last_seen ? ago(l.feed.last_seen, now) : t('time.never') }}</td>
                    </template>
                    <template v-else>
                        <td colspan="9" class="ph">
                            <span style="display:inline-flex;align-items:center;gap:8px">
                                <Icon :name="labState(l) === 'soon' || labState(l) === 'live' ? 'clock' : labState(l) === 'unlinked' ? 'key' : 'wifi-off'" :size="15" />
                                {{ l.feed ? t(`card.placeholder.${labState(l)}`) : t('lab.hidden') }}
                            </span>
                        </td>
                    </template>
                    <td>
                        <div class="acts" @click.stop>
                            <Link v-if="has(l)" :href="`/labs/${l.id}`" class="btn btn-outline btn-sm" :title="t('card.details')"><Icon name="arrow-right" :size="14" /></Link>
                            <a v-if="l.has_address" :href="l.url" target="_blank" rel="noopener" class="btn btn-gold btn-sm" :title="t('card.open')" :aria-label="t('card.open')"><Icon name="external" :size="14" /></a>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
