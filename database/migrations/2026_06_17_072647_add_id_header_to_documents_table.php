<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // idHeader (UUID) yang dikembalikan CEISA 4.0 saat submit dokumen berhasil.
            // Ini kunci utama untuk menarik status/respon dokumen dari DJBC.
            $table->string('id_header')->nullable()->after('nomor_daftar')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['id_header']);
            $table->dropColumn('id_header');
        });
    }
};
