<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Jalur pemeriksaan pabean: H=Hijau, K=Kuning, M=Merah (nullable).
            $table->string('jalur', 1)->nullable()->after('status');
        });

        Schema::table('webhook_logs', function (Blueprint $table) {
            // Jenis notifikasi DJBC: Respon / Formulir / Informasi.
            $table->string('notification_type', 20)->nullable()->after('event');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('jalur');
        });

        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->dropColumn('notification_type');
        });
    }
};
