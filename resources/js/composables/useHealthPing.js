import { ref, onMounted, onBeforeUnmount } from 'vue';

/**
 * Необязательная «живая» проверка доступности ЛИМС: пытается
 * загрузить /ngmk.png с адреса завода (та же сеть предприятия).
 * Работает полностью на клиенте, ничего не ломает при недоступности.
 *
 * @param {string|null} url
 * @param {boolean} enabled
 */
export function useHealthPing(url, enabled = true) {
    const online = ref(false);
    let img = null;
    let timer = null;

    onMounted(() => {
        if (!enabled || !url || !/^https?:\/\//i.test(url)) return;

        const base = url.replace(/\/+$/, '');
        img = new Image();
        let done = false;

        timer = setTimeout(() => { done = true; }, 4000);

        img.onload = () => {
            if (done) return;
            done = true;
            clearTimeout(timer);
            online.value = true;
        };
        img.onerror = () => {
            done = true;
            clearTimeout(timer);
        };
        img.src = `${base}/ngmk.png?_=${Date.now()}`;
    });

    onBeforeUnmount(() => {
        if (timer) clearTimeout(timer);
        if (img) { img.onload = null; img.onerror = null; }
    });

    return { online };
}
