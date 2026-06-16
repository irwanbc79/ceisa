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
            // Sumber dokumen: 'h2h' (dibuat/dikirim via aplikasi) atau
            // 'arsip' (rekam manual dokumen lama dari portal DJBC).
            $table->string('source', 20)->default('h2h')->after('doc_type');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
