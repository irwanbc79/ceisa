<?php

namespace App\Services\AI;

/**
 * Kontrak satu provider LLM (Gemini / DeepSeek) untuk validasi hybrid.
 */
interface AiProvider
{
    /**
     * Nama pendek provider (gemini/deepseek) untuk pelacakan.
     */
    public function name(): string;

    /**
     * Apakah provider siap dipakai (API key terisi).
     */
    public function isConfigured(): bool;

    /**
     * Kirim satu prompt (system + user) dan kembalikan teks jawaban model.
     *
     * @throws AiException bila gagal terhubung / response tidak valid.
     */
    public function chat(string $system, string $user): string;
}
