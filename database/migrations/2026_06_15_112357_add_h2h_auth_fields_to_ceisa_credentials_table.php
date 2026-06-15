<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kredensial sesuai auth resmi CEISA 4.0 H2H:
     * login pakai username + password + header beacukai-api-key (api_key).
     * app_id tidak lagi wajib untuk auth (jadi nullable).
     */
    public function up(): void
    {
        Schema::table('ceisa_credentials', function (Blueprint $table) {
            // username & password disimpan terenkripsi (cast 'encrypted' di model)
            $table->text('username')->nullable()->after('user_id');
            $table->text('password')->nullable()->after('username');
        });

        Schema::table('ceisa_credentials', function (Blueprint $table) {
            $table->string('app_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ceisa_credentials', function (Blueprint $table) {
            $table->dropColumn(['username', 'password']);
        });

        Schema::table('ceisa_credentials', function (Blueprint $table) {
            $table->string('app_id')->nullable(false)->change();
        });
    }
};
