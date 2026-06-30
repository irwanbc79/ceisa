<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manifests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // inward = kedatangan (RKSP/manifes masuk), outward = keberangkatan.
            $table->enum('jenis_manifes', ['inward', 'outward'])->default('inward')->index();
            $table->string('nama_sarana')->nullable();
            $table->string('nomor_voyage')->nullable();
            $table->string('nomor_imo')->nullable();
            $table->string('call_sign')->nullable();
            $table->string('kode_bendera', 2)->nullable();
            $table->string('kode_kantor', 10)->nullable()->index();
            $table->string('nomor_daftar')->nullable()->index();
            $table->date('tanggal_sarana')->nullable();   // tanggal tiba/berangkat
            $table->date('tanggal_daftar')->nullable();
            $table->string('status')->nullable();
            $table->json('payload')->nullable();
            $table->json('ceisa_response')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manifests');
    }
};
