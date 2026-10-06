# ЛИМС АО «НГМК» — laboratoriyalar yagona portali

«Navoiy kon-metallurgiya kombinati» AJ laboratoriyalari (ГМЗ-1…7, ЦНИЛ) uchun
LIMS tizimlariga yagona kirish portali. Oldingi bitta HTML-demo **to'laqonli
Laravel + Vue + Vite** loyihasiga aylantirildi: ma'lumotlar bazasi, admin panel,
3 til, dark/light mavzu va laboratoriyalar holatini real tekshirish bilan.

---

## Texnologiyalar

| Qatlam      | Texnologiya                                        |
|-------------|----------------------------------------------------|
| Backend     | Laravel 11 (PHP 8.2+)                              |
| Bog'lovchi  | Inertia.js (SSR-ready, bitta repo)                |
| Frontend    | Vue 3 (`<script setup>`) + Vite                   |
| Tillar      | vue-i18n (RU / UZ / EN)                            |
| Dizayn      | Toza CSS, `data-theme` orqali dark/light           |
| Baza        | MS SQL Server (prod) · SQLite (lokal) · MySQL/PgSQL |
| Testlar     | PHPUnit (Feature testlar)                          |

---

## Talablar

- PHP **8.2+** (`pdo_sqlite` yoki `pdo_mysql`, `mbstring`, `openssl`)
- Composer 2
- Node.js **18+** va npm

---

## Tezkor o'rnatish (1 buyruq)

```bash
bash setup.sh
```

Skript hammasini bajaradi: `.env`, `composer install`, `APP_KEY`, baza + seed,
`npm install`, `npm run build`.

## Qo'lda o'rnatish

```bash
# 1. Sozlamalar
cp .env.example .env

# 2. PHP paketlari
composer install

# 3. Ilova kaliti
php artisan key:generate

# 4. Baza (SQLite) + boshlang'ich ma'lumotlar (8 ta laboratoriya + admin)
touch database/database.sqlite
php artisan migrate --seed

# 5. Frontend
npm install
npm run dev     # ishlab chiqish rejimi (hot-reload)
# yoki
npm run build   # produksiya uchun
```

## Ishga tushirish

Ikki terminal:

```bash
php artisan serve     # http://localhost:8000
npm run dev           # Vite dev-server (frontend)
```

---

## Admin panel

```
URL:    http://localhost:8000/admin/login
Email:  admin@ngmk.uz
Parol:  password
```

> ⚠️ Produksiyada parolni **albatta** o'zgartiring:
> `php artisan tinker` → `\App\Models\User::first()->update(['password' => \Hash::make('yangi-parol')]);`

Admin panelda laboratoriyalarni qo'shish / tahrirlash / o'chirish mumkin:
kod, 3 tildagi nom, status (Ishlamoqda / Tez orada), LIMS manzili (URL),
chiqarish tartibi.

---

## Loyiha tuzilishi

```
app/
├── Models/Lab.php                      # Laboratoriya modeli
├── Http/Controllers/
│   ├── HomeController.php               # Public sahifa
│   ├── Auth/LoginController.php         # Kirish/chiqish
│   └── Admin/LabController.php          # Admin CRUD
└── Http/Middleware/HandleInertiaRequests.php

database/
├── migrations/…create_labs_table.php
└── seeders/LabSeeder.php               # 8 ta laboratoriya

resources/
├── css/app.css                         # Dizayn-tizim (dark/light)
├── js/
│   ├── app.js                          # Inertia + Vue bootstrap
│   ├── i18n.js                         # 3 til
│   ├── locales/{ru,uz,en}.json
│   ├── composables/                    # useTheme, useLang, useHealthPing
│   ├── Layouts/                        # PublicLayout, AdminLayout
│   ├── Components/                     # TopBar, LabCard, Hero, …
│   └── Pages/
│       ├── Home.vue
│       ├── Auth/Login.vue
│       └── Admin/Labs/{Index,Create,Edit}.vue
└── views/app.blade.php                 # Inertia root
```

---

## Laboratoriya manzilini o'rnatish

Avval seed'dagi qiymatlar: ГМЗ-5 → `http://192.168.26.8:3010`, qolganlari bo'sh.
Manzillarni **admin panel** orqali kiriting (yoki `LabSeeder.php` da).

Agar laboratoriya statusi "Ishlamoqda" bo'lsa va manzil ko'rsatilgan bo'lsa —
public sahifada "LIMSni ochish" tugmasi faol bo'ladi. Shu bilan birga, ag'ar
portal va zavod serveri bir tarmoqda bo'lsa, kartochkada **jonli holat pulsi**
yonadi (`useHealthPing` zavod serveridan `/ngmk.png` ni yuklab ko'radi).

---

## Microsoft SQL Server (boyeviy baza)

Loyiha MS SQL Server uchun tayyor (`enum`/`comment` ishlatilmagan — status oddiy
string + validatsiya, MSSQL bilan to'liq mos).

**1) PHP kengaytmalari va drayver** (bir marta, serverda):

- PHP: `pdo_sqlsrv` va `sqlsrv` kengaytmalari
- Microsoft **ODBC Driver 17 yoki 18 for SQL Server**

```bash
# Tekshirish:
php -m | grep -i sqlsrv      # pdo_sqlsrv, sqlsrv ko'rinishi kerak
```

> Windows'da: PECL'dan `php_sqlsrv.dll` + `php_pdo_sqlsrv.dll` ni `php.ini` ga
> qo'shing. Linux'da: `pecl install sqlsrv pdo_sqlsrv` + `msodbcsql18` paketi.

**2) `.env`** (sqlite o'rniga sqlsrv):

```env
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=lims_ngmk
DB_USERNAME=sa
DB_PASSWORD=YourStrong!Passw0rd

# ODBC Driver 18 uchun MUHIM (self-signed sertifikatda):
DB_ENCRYPT=yes
DB_TRUST_SERVER_CERTIFICATE=true
```

> `DB_TRUST_SERVER_CERTIFICATE=true` — Driver 18'da eng ko'p uchraydigan
> "SSL certificate" xatosini hal qiladi. Bu sozlama `config/database.php` dagi
> `sqlsrv` ulanishiga allaqachon ulangan.

**3) Bazani yarating va migratsiya qiling:**

```sql
-- SQL Server'da:
CREATE DATABASE lims_ngmk;
```
```bash
php artisan migrate --seed
```

### MySQL / PostgreSQL (muqobil)

`.env` da `DB_CONNECTION=mysql` (yoki `pgsql`) va mos parametrlar — `config/database.php`
da ikkala ulanish ham tayyor.

---

## Testlar

```bash
php artisan test
```

Public sahifa render bo'lishi, admin himoyasi va CRUD amallari tekshiriladi.

---

## Keyingi bosqich (yo'l xaritasi)

- [ ] Sводная аналитика / konsolidatsiyalangan dashboard (rahbariyat uchun)
- [ ] Rollar va kirish chegaralari (площадка ↔ руководство)
- [ ] Laboratoriyalar holatini server tomonda monitoring (cron + health log)
- [ ] Audit log (kim nimani o'zgartirgani)
