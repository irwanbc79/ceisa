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
            $table->string('base_url', 500)->nullable()->after('api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ceisa_credentials', function (Blueprint $table) {
            $table->dropColumn('base_url');
        });
    }
};
