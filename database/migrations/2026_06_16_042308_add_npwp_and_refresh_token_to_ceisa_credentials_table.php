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
        Schema::table('ceisa_credentials', function (Blueprint $table) {
            $table->string('npwp', 20)->nullable()->after('username');
            $table->text('refresh_token')->nullable()->after('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ceisa_credentials', function (Blueprint $table) {
            $table->dropColumn(['npwp', 'refresh_token']);
        });
    }
};
