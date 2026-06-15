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
        Schema::create('ceisa_references', function (Blueprint $table) {
            $table->id();
            // type = nama grup referensi CEISA (negara, kantor_pabean, pelabuhan, dll)
            $table->string('type', 50)->index();
            $table->string('code', 50);
            $table->string('label', 255);
            // meta untuk data tambahan (mis. negara pelabuhan, alpha-3, dll)
            $table->json('meta')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ceisa_references');
    }
};
