import { onBeforeUnmount, onMounted, ref } from 'vue';

/** Har N soniyada yangilanadigan «hozir» — «2 daqiqa oldin» kabi yozuvlar uchun */
export function useNow(everyMs = 15000) {
    const now = ref(Date.now());
    let t = null;
    onMounted(() => { t = setInterval(() => { now.value = Date.now(); }, everyMs); });
    onBeforeUnmount(() => clearInterval(t));
    return now;
}
