<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useHealthPing } from '@/composables/useHealthPing';

const props = defineProps({
    lab: {
        type: Object,
        required: true,
    },
});

const { t, locale } = useI18n();

const isLive = computed(() => props.lab.status === 'live');
const hasAddress = computed(() => props.lab.has_address);

// Разбор кода «ГМЗ-5» → префикс «ГМЗ-» + номер «5».
const codeParts = computed(() => {
    const m = String(props.lab.code).match(/^(\D+)(.*)$/);
    return m ? { pre: m[1].trim(), n: m[2] } : { pre: props.lab.code, n: '' };
});

const name = computed(
    () => props.lab.name[locale.value] || props.lab.name.ru || props.lab.name.en,
);

const shortUrl = computed(() =>
    hasAddress.value ? props.lab.url.replace(/^https?:\/\//, '') : '',
);

// «Живой» пульс доступности (только для запущенных с адресом).
const { online } = useHealthPing(
    props.lab.url,
    isLive.value && hasAddress.value,
);
</script>

<template>
    <div class="card" :class="isLive ? 'live' : 'soon'">
        <div class="card-top">
            <div class="code">
                <span class="pre">{{ codeParts.pre }}</span>
                <span class="n">{{ codeParts.n || '\u00A0' }}</span>
            </div>

            <span v-if="isLive" class="badge live" :class="{ online }">
                <span class="bdot"></span>{{ t('card.live') }}
            </span>
            <span v-else class="badge soon">
                <span class="bdot"></span>{{ t('card.soon') }}
            </span>
        </div>

        <div class="name">{{ name }}</div>
        <div v-if="hasAddress" class="addr">{{ shortUrl }}</div>

        <div class="foot">
            <a
                v-if="isLive && hasAddress"
                class="open"
                :href="lab.url"
                target="_blank"
                rel="noopener"
            >
                {{ t('card.open') }} <span class="arrow">→</span>
            </a>
            <div v-else-if="isLive" class="open disabled">
                {{ t('card.noaddr') }}
            </div>
            <div v-else class="open disabled">
                {{ t('card.soon') }}
            </div>
        </div>
    </div>
</template>
