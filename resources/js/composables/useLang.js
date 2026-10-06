import { useI18n } from 'vue-i18n';
import { SUPPORTED_LOCALES } from '@/i18n';

export function useLang() {
    const { locale } = useI18n();

    function setLang(lang) {
        if (!SUPPORTED_LOCALES.includes(lang)) return;
        locale.value = lang;
        try {
            localStorage.setItem('lims_lang', lang);
        } catch (e) { /* ignore */ }
        if (typeof document !== 'undefined') {
            document.documentElement.lang = lang;
        }
    }

    return { locale, setLang, locales: SUPPORTED_LOCALES };
}
