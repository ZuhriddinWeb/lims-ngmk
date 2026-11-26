import { onBeforeUnmount, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Sahifa ma'lumotini har N soniyada jim yangilaydi (Inertia partial reload).
 * Brauzer oynasi yashirin bo'lsa — kutadi; qaytganda darhol yangilaydi.
 */
export function useAutoRefresh(only, everyMs = 60000) {
    const refreshedAt = ref(Date.now());
    const busy = ref(false);
    let timer = null;

    function refresh() {
        if (busy.value || document.hidden) return;
        busy.value = true;
        router.reload({
            only,
            preserveScroll: true,
            preserveState: true,
            onFinish: () => { busy.value = false; refreshedAt.value = Date.now(); },
        });
    }
    const onVisible = () => { if (!document.hidden && Date.now() - refreshedAt.value > everyMs / 2) refresh(); };

    onMounted(() => {
        timer = setInterval(refresh, everyMs);
        document.addEventListener('visibilitychange', onVisible);
    });
    onBeforeUnmount(() => {
        clearInterval(timer);
        document.removeEventListener('visibilitychange', onVisible);
    });

    return { refreshedAt, busy, refresh };
}
