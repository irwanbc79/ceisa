<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom id_platform — header wajib pada semua request CEISA 4.0.
     * Setiap perusahaan punya ID Platform sendiri dari Portal CEISA.
     */
    public function up(): void
    {
        Schema::table('ceisa_credentials', function (Blueprint $table) {
            $table->string('id_platform')->nullable()->after('api_key');
        });
    }

    public function down(): void
    {
        Schema::table('ceisa_credentials', function (Blueprint $table) {
            $table->dropColumn('id_platform');
        });
    }
};
