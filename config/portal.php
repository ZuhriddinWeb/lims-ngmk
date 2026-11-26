<?php

return [
    /*
    | Bosh sahifadagi jonli ko'rsatkichlar login'siz ko'rinadimi.
    | false — mehmon faqat laboratoriyalar ro'yxatini ko'radi, sonlar admin uchun.
    */
    'public_stats' => env('PORTAL_PUBLIC_STATS', true),

    /*
    | Laboratoriya «onlayn» hisoblanadigan muddat: zavod yuborish oralig'i × 2 + shu daqiqa.
    | (Zavod oralig'ini o'zi xabar qiladi; xabar qilmasa — 5 daqiqa.)
    */
    'online_grace_minutes' => (int) env('PORTAL_ONLINE_GRACE', 3),

    /* Qabul qilinadigan eng katta hajm (bayt) va vaqt belgisi farqi (soniya) */
    'ingest_max_bytes' => 1024 * 1024,
    'ingest_max_skew' => 600,
];
