import { useI18n } from 'vue-i18n';

/** ISO vaqt → «hozirgina / 3 daqiqa oldin / 2 soat oldin / 26.11 14:05» (joriy tilda) */
export function useAgo() {
    const { t } = useI18n();
    return function ago(iso, now = Date.now()) {
        if (!iso) return t('time.never');
        const ts = new Date(iso).getTime();
        if (Number.isNaN(ts)) return '—';
        const s = Math.max(0, Math.round((now - ts) / 1000));
        if (s < 45) return t('time.justNow');
        const m = Math.round(s / 60);
        if (m < 60) return t('time.minAgo', { n: m });
        const h = Math.round(m / 60);
        if (h < 24) return t('time.hourAgo', { n: h });
        const d = new Date(ts);
        const p = (x) => String(x).padStart(2, '0');
        return `${p(d.getDate())}.${p(d.getMonth() + 1)} ${p(d.getHours())}:${p(d.getMinutes())}`;
    };
}
