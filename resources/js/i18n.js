import { createI18n } from 'vue-i18n';
import ru from '@/locales/ru.json';
import uz from '@/locales/uz.json';
import en from '@/locales/en.json';

function savedLocale() {
    try {
        return localStorage.getItem('lims_lang');
    } catch (e) {
        return null;
    }
}

export const SUPPORTED_LOCALES = ['ru', 'uz', 'en'];

export const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: savedLocale() || 'ru',
    fallbackLocale: 'en',
    messages: { ru, uz, en },
});
