<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Driver Claude (Anthropic Messages API) via raw HTTP — konsisten dengan
 * pola Http facade di seluruh project. Model default: claude-opus-4-8.
 */
class ClaudeProvider implements AiProvider
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config,
        protected int $timeout = 45,
        protected int $maxTokens = 1500,
    ) {}

    public function name(): string
    {
        return 'claude';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['api_key']);
    }

    public function chat(string $system, string $user): string
    {
        $base = rtrim((string) $this->config['base_url'], '/');

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'x-api-key' => (string) $this->config['api_key'],
                    'anthropic-version' => (string) ($this->config['version'] ?? '2023-06-01'),
                ])
                ->acceptJson()
                ->asJson()
                ->post($base.'/v1/messages', [
                    'model' => $this->config['model'],
                    'max_tokens' => $this->maxTokens,
                    'system' => $system,
                    'messages' => [
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);
        } catch (Throwable $e) {
            throw new AiException('Gagal terhubung ke Claude: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new AiException('Claude HTTP '.$response->status().': '.$response->body());
        }

        $text = data_get($response->json(), 'content.0.text');

        if (! is_string($text) || $text === '') {
            throw new AiException('Response Claude kosong / tak berisi teks.');
        }

        return $text;
    }
}
