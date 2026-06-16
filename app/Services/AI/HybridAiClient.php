<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

/**
 * Klien AI hybrid: mencoba beberapa provider sesuai urutan konfigurasi dan
 * memakai yang pertama terkonfigurasi & sukses (failover). Cocok agar fitur
 * tetap jalan meski salah satu penyedia (Claude/Gemini/DeepSeek) bermasalah.
 */
class HybridAiClient
{
    /**
     * @param  array<int, AiProvider>  $providers  urutan failover.
     */
    public function __construct(
        protected array $providers,
    ) {}

    /**
     * Bangun dari config/ai.php (urutan + key + timeout) dengan registry driver.
     */
    public static function fromConfig(): self
    {
        $order = (array) config('ai.order', []);
        $timeout = (int) config('ai.timeout', 45);
        $maxTokens = (int) config('ai.max_tokens', 1500);

        $factories = [
            'claude' => ClaudeProvider::class,
            'gemini' => GeminiProvider::class,
            'deepseek' => DeepSeekProvider::class,
        ];

        $providers = [];
        foreach ($order as $name) {
            $class = $factories[$name] ?? null;
            $cfg = config("ai.providers.{$name}");

            if ($class !== null && is_array($cfg)) {
                $providers[] = new $class($cfg, $timeout, $maxTokens);
            }
        }

        return new self($providers);
    }

    /**
     * Apakah ada minimal satu provider yang terkonfigurasi.
     */
    public function hasConfiguredProvider(): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->isConfigured()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Jalankan prompt melalui provider pertama yang sukses.
     *
     * @return array{provider: string, text: string}
     *
     * @throws AiException bila tak ada provider terkonfigurasi atau semua gagal.
     */
    public function chat(string $system, string $user): array
    {
        $configured = array_filter($this->providers, fn (AiProvider $p) => $p->isConfigured());

        if (empty($configured)) {
            throw new AiException('Tidak ada provider AI yang terkonfigurasi (isi API key di .env).');
        }

        $errors = [];

        foreach ($configured as $provider) {
            try {
                return [
                    'provider' => $provider->name(),
                    'text' => $provider->chat($system, $user),
                ];
            } catch (AiException $e) {
                $errors[] = $provider->name().': '.$e->getMessage();
                Log::warning('AI provider gagal, failover ke berikutnya', [
                    'provider' => $provider->name(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw new AiException('Semua provider AI gagal. '.implode(' | ', $errors));
    }
}
