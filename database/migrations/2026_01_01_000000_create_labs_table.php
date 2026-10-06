<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labs', function (Blueprint $table) {
            $table->id();

            // Код лаборатории, напр. «ГМЗ-1», «ЦНИЛ».
            $table->string('code', 50)->unique();

            // Название на трёх языках.
            $table->string('name_ru');
            $table->string('name_uz');
            $table->string('name_en');

            // Статус: live | soon.
            // Строка (а не enum) — для полной совместимости с MS SQL Server,
            // значение проверяется валидацией на уровне приложения.
            $table->string('status', 20)->default('soon')->index();

            // Адрес ЛИМС завода (может быть пустым).
            $table->string('url')->nullable();

            $table->unsignedInteger('sort_order')->default(0)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labs');
    }
};
