/**
 * Grafiklar uchun sof funksiyalar (DOM'siz): shkala, «chiroyli» bo'linmalar, chiziq/maydon yo'li.
 * Vue komponentlari va statik prototip bir xil ishlatadi.
 */

/** [d0,d1] → [r0,r1] chiziqli shkala */
export function scaleLinear([d0, d1], [r0, r1]) {
    const span = d1 - d0 || 1;
    const f = (v) => r0 + ((v - d0) / span) * (r1 - r0);
    f.domain = [d0, d1];
    return f;
}

/** 0 dan boshlanadigan «chiroyli» yuqori chegara va bo'linmalar */
export function niceTicks(max, count = 4) {
    if (!max || max <= 0) return { max: 4, ticks: [0, 1, 2, 3, 4] };
    const raw = max / count;
    const pow = 10 ** Math.floor(Math.log10(raw));
    const step = [1, 2, 2.5, 5, 10].map((m) => m * pow).find((s) => s >= raw) ?? 10 * pow;
    const top = Math.ceil(max / step) * step;
    const ticks = [];
    for (let v = 0; v <= top + step / 2; v += step) ticks.push(Math.round(v * 1000) / 1000);
    return { max: top, ticks };
}

/** Nuqtalar → silliq bo'lmagan (aniq) chiziq yo'li; null — uzilish */
export function linePath(points) {
    let d = '';
    let pen = false;
    for (const p of points) {
        if (p == null || p.y == null || Number.isNaN(p.y)) { pen = false; continue; }
        d += `${pen ? 'L' : 'M'}${p.x.toFixed(1)},${p.y.toFixed(1)}`;
        pen = true;
    }
    return d;
}

/** Chiziq ostidagi maydon (birinchi uzluksiz bo'lak bo'yicha) */
export function areaPath(points, baseY) {
    const seg = points.filter((p) => p && p.y != null && !Number.isNaN(p.y));
    if (seg.length < 2) return '';
    let d = `M${seg[0].x.toFixed(1)},${baseY.toFixed(1)}`;
    for (const p of seg) d += `L${p.x.toFixed(1)},${p.y.toFixed(1)}`;
    return `${d}L${seg[seg.length - 1].x.toFixed(1)},${baseY.toFixed(1)}Z`;
}

/** Kichik grafik (sparkline) uchun nuqtalar */
export function sparkPoints(values, w, h, pad = 2) {
    const vals = (values || []).map((v) => (v == null ? null : Number(v)));
    const max = Math.max(1, ...vals.filter((v) => v != null));
    const x = scaleLinear([0, Math.max(1, vals.length - 1)], [pad, w - pad]);
    const y = scaleLinear([0, max], [h - pad, pad]);
    return vals.map((v, i) => (v == null ? null : { x: x(i), y: y(v), v }));
}

/** Son: 12 345 (bo'sh joy bilan), null → «—» */
export function fmtNum(v, locale = 'ru') {
    if (v == null || Number.isNaN(Number(v))) return '—';
    return new Intl.NumberFormat(locale === 'en' ? 'en-US' : 'ru-RU', { maximumFractionDigits: 1 }).format(v);
}

/** Foiz o'zgarish: +12% / −4% */
export function fmtChange(v) {
    if (v == null) return null;
    const r = Math.round(v);
    return `${r > 0 ? '+' : r < 0 ? '−' : '±'}${Math.abs(r)}%`;
}

/** 2026-11-26 → 26.11 */
export function shortDate(key) {
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(key || '');
    return m ? `${m[3]}.${m[2]}` : key;
}

const MONTHS = {
    uz: ['yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr'],
    ru: ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'],
    en: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
};
const WEEKDAYS = {
    uz: ['yak', 'dush', 'sesh', 'chor', 'pay', 'jum', 'shan'],
    ru: ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'],
    en: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
};

/** Sana brauzer Intl'iga bog'liq emas (Chrome'da «uz» oy nomlari yo'q — «M11» chiqardi): «pay, 26-noyabr» / «чт, 26 ноября» / «Thu, 26 Nov» */
export function longDate(ts, locale = 'ru') {
    const d = new Date(ts);
    const l = MONTHS[locale] ? locale : 'ru';
    const wd = WEEKDAYS[l][d.getDay()];
    const day = d.getDate();
    const mon = MONTHS[l][d.getMonth()];
    if (l === 'uz') return `${wd}, ${day}-${mon}`;
    if (l === 'en') return `${wd}, ${day} ${mon}`;
    return `${wd}, ${day} ${mon}`;
}

/** 14:44:34 */
export function clockTime(ts, seconds = true) {
    const d = new Date(ts);
    const p = (x) => String(x).padStart(2, '0');
    return seconds ? `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}` : `${p(d.getHours())}:${p(d.getMinutes())}`;
}

/**
 * O'tgan davr bilan solishtirish — ma'noli bo'lsagina.
 * Asos juda kichik (prev < 10) bo'lsa — ko'rsatilmaydi («+2591%» kabi shovqin bo'lmasin);
 * 2 baravardan ko'p o'sish — «×N» ko'rinishida.
 */
export function deltaInfo(change, prev, cur) {
    if (change == null) return null;
    if (prev != null && prev < 10) return { kind: 'small' };
    const r = Math.round(change);
    if (cur != null && prev && change >= 100) {
        const x = cur / prev;
        return { kind: 'x', dir: 1, text: `×${x >= 10 ? Math.round(x) : x.toFixed(1).replace('.', ',')}` };
    }
    return { kind: 'pct', dir: Math.sign(r), text: `${Math.abs(r)}%` };
}
