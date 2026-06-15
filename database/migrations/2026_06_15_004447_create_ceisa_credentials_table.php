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
        Schema::create('ceisa_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('app_id');
            // api_key & token disimpan terenkripsi (cast 'encrypted' di model)
            $table->text('api_key');
            $table->text('token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ceisa_credentials');
    }
};
