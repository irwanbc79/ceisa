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
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->string('fingerprint', 64)->nullable()->unique();
            $table->boolean('verified')->default(false)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->dropUnique(['fingerprint']);
            $table->dropIndex(['verified']);
            $table->dropColumn(['fingerprint', 'verified']);
        });
    }
};
