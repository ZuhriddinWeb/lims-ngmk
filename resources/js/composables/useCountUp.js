import { onBeforeUnmount, ref, watch } from 'vue';

/** Son o'zgarganda silliq sanab chiqadi (kamroq harakat rejimida — darhol) */
export function useCountUp(source, ms = 700) {
    const shown = ref(typeof source() === 'number' ? 0 : source());
    let raf = null;
    const reduce = typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

    watch(source, (to) => {
        cancelAnimationFrame(raf);
        if (typeof to !== 'number' || reduce) { shown.value = to; return; }
        const from = typeof shown.value === 'number' ? shown.value : 0;
        const t0 = performance.now();
        const step = (t) => {
            const k = Math.min(1, (t - t0) / ms);
            const e = 1 - (1 - k) ** 3;
            shown.value = from + (to - from) * e;
            if (k < 1) raf = requestAnimationFrame(step);
            else shown.value = to;
        };
        raf = requestAnimationFrame(step);
    }, { immediate: true });
    onBeforeUnmount(() => cancelAnimationFrame(raf));

    return shown;
}
