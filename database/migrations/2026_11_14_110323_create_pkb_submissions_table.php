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
        Schema::create('pkb_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('tanggal_periksa');
            $table->text('alamat_lokasi');
            $table->string('kode_gudang', 64);
            $table->string('nama_pic', 120);
            $table->string('telepon_pic', 32);
            $table->text('catatan')->nullable();
            $table->string('status', 32)->default('recorded')->index();
            $table->json('ceisa_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pkb_submissions');
    }
};
