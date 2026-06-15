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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('doc_type', ['BC20', 'BC23', 'BC25', 'BC30'])->index();
            // Nomor pengajuan/aju internal & nomor pendaftaran dari CEISA
            $table->string('nomor_aju')->nullable()->index();
            $table->string('nomor_daftar')->nullable();
            $table->json('payload');
            $table->enum('status', [
                'draft',
                'submitting',
                'submitted',
                'accepted',
                'rejected',
                'error',
            ])->default('draft')->index();
            $table->json('ceisa_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('response_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
