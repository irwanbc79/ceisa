<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Driver DeepSeek (API kompatibel OpenAI chat/completions). Model: deepseek-chat.
 */
class DeepSeekProvider implements AiProvider
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
        return 'deepseek';
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
                ->withToken((string) $this->config['api_key'])
                ->acceptJson()
                ->asJson()
                ->post($base.'/chat/completions', [
                    'model' => $this->config['model'],
                    'max_tokens' => $this->maxTokens,
                    'temperature' => 0.2,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);
        } catch (Throwable $e) {
            throw new AiException('Gagal terhubung ke DeepSeek: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new AiException('DeepSeek HTTP '.$response->status().': '.$response->body());
        }

        $text = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($text) || $text === '') {
            throw new AiException('Response DeepSeek kosong / tak berisi teks.');
        }

        return $text;
    }
}
