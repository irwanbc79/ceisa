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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            // Dokumen terkait (jika berhasil di-match dari payload), nullable
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event')->nullable()->index();
            $table->string('nomor_aju')->nullable()->index();
            $table->json('payload');
            $table->ipAddress('ip_address')->nullable();
            $table->boolean('processed')->default(false)->index();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
