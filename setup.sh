#!/usr/bin/env bash
#
# ЛИМС НГМК — быстрая установка / tezkor o'rnatish
# Ishga tushirish: bash setup.sh
#
set -e

echo "==> 1/6  .env fayli tayyorlanmoqda"
[ -f .env ] || cp .env.example .env

echo "==> 2/6  PHP paketlari (composer install)"
composer install

echo "==> 3/6  Ilova kaliti (APP_KEY)"
php artisan key:generate

echo "==> 4/6  Ma'lumotlar bazasi (SQLite) + migratsiya + seed"
touch database/database.sqlite
php artisan migrate --seed --force

echo "==> 5/6  JS paketlari (npm install)"
npm install

echo "==> 6/6  Frontend build"
npm run build

echo ""
echo "============================================================"
echo " Tayyor! Ishga tushirish:"
echo "   php artisan serve      (http://localhost:8000)"
echo "   npm run dev            (frontend hot-reload, alohida terminalda)"
echo ""
echo " Admin:  http://localhost:8000/admin/login"
echo "   email:    admin@ngmk.uz"
echo "   parol:    password   (ALBATTA o'zgartiring!)"
echo "============================================================"
