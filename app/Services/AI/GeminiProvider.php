<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Driver Google Gemini (generateContent API). Model default: gemini-2.0-flash.
 */
class GeminiProvider implements AiProvider
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
        return 'gemini';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['api_key']);
    }

    public function chat(string $system, string $user): string
    {
        $base = rtrim((string) $this->config['base_url'], '/');
        $model = (string) $this->config['model'];
        $url = $base.'/models/'.$model.':generateContent';

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->withQueryParameters(['key' => (string) $this->config['api_key']])
                ->post($url, [
                    'systemInstruction' => [
                        'parts' => [['text' => $system]],
                    ],
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $user]]],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => $this->maxTokens,
                        'temperature' => 0.2,
                    ],
                ]);
        } catch (Throwable $e) {
            throw new AiException('Gagal terhubung ke Gemini: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new AiException('Gemini HTTP '.$response->status().': '.$response->body());
        }

        $parts = data_get($response->json(), 'candidates.0.content.parts', []);
        $text = collect($parts)->pluck('text')->filter()->implode('');

        if ($text === '') {
            throw new AiException('Response Gemini kosong / tak berisi teks.');
        }

        return $text;
    }
}
