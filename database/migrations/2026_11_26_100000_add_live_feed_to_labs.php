<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zavod LIMS'laridan keladigan jonli ma'lumot.
 *
 *   labs.ingest_key       — ulanish kaliti (shifrlangan; ngmk1.<id>.<maxfiy>)
 *   labs.key_created_at   — kalit yaratilgan vaqt
 *   labs.last_seen_at     — oxirgi muvaffaqiyatli qabul
 *   labs.last_ip          — qaysi manzildan keldi
 *   lab_snapshots         — har laboratoriyaning oxirgi holati (bitta qator; zavod ruxsat bergan bloklar)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labs', function (Blueprint $table) {
            if (! Schema::hasColumn('labs', 'ingest_key')) {
                $table->text('ingest_key')->nullable();
                $table->timestamp('key_created_at')->nullable();
                $table->timestamp('last_seen_at')->nullable()->index();
                $table->string('last_ip', 64)->nullable();
            }
        });

        if (! Schema::hasTable('lab_snapshots')) {
            Schema::create('lab_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lab_id')->unique();
                $table->longText('payload');
                $table->string('datasets', 255)->nullable();
                $table->unsignedInteger('bytes')->default(0);
                $table->timestamp('generated_at')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_snapshots');
        Schema::table('labs', function (Blueprint $table) {
            $table->dropColumn(['ingest_key', 'key_created_at', 'last_seen_at', 'last_ip']);
        });
    }
};
