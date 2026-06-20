<?php

namespace App\Services\Concerns;

use App\Exceptions\CeisaException;
use App\Models\CeisaCredential;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Infrastruktur HTTP untuk komunikasi CEISA H2H.
 *
 * Menangani: base request, header umum, decode response, dan guard error code.
 * Digunakan bersama HandlesCeisaAuth di CeisaService.
 */
trait HandlesCeisaHttp
{
    abstract protected function credential(): CeisaCredential;

    /**
     * Request dasar dengan base URL, timeout, dan header umum.
     */
    protected function baseRequest(): PendingRequest
    {
        $baseUrl = $this->credential()->base_url ?: config('ceisa.base_url');

        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->timeout((int) config('ceisa.timeout', 30))
            ->withHeaders($this->commonHeaders())
            ->acceptJson()
            ->asJson();
    }

    /**
     * Header umum wajib untuk SEMUA request CEISA 4.0.
     *
     * @return array<string, string>
     */
    protected function commonHeaders(): array
    {
        $headers = [];

        // API key dikirim pada semua nama header yang dikonfigurasi
        // (Beacukai-Api-Key & nle-api-key) demi kompatibilitas antar-versi gateway.
        $apiKey = (string) $this->credential()->api_key;
        $apiKeyHeaders = (array) config('ceisa.api_key_headers', ['Beacukai-Api-Key']);
        foreach ($apiKeyHeaders as $headerName) {
            $headers[$headerName] = $apiKey;
        }

        // id_platform: dari credential user atau fallback config.
        $idPlatform = $this->credential()->id_platform ?: config('ceisa.id_platform');
        if (! empty($idPlatform)) {
            $headers['id_platform'] = (string) $idPlatform;
        }

        // Origin: domain asal sistem klien.
        $origin = config('ceisa.origin');
        if (! empty($origin)) {
            $headers['Origin'] = (string) $origin;
        }

        return $headers;
    }

    /**
     * Decode body response menjadi array (aman bila bukan JSON).
     *
     * @return array<string, mixed>
     */
    protected function decode(Response $response): array
    {
        $json = $response->json();

        return is_array($json) ? $json : ['raw' => $response->body()];
    }

    /**
     * Lempar CeisaException bila response mengandung error code yang dikenal.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws CeisaException
     */
    protected function guardAgainstErrorCode(array $data, Response $response): void
    {
        // CEISA bisa menaruh kode di beberapa lokasi tergantung endpoint.
        $code = $data['error_code']
            ?? $data['code']
            ?? data_get($data, 'error.code')
            ?? null;

        if (is_null($code)) {
            return;
        }

        $code = (string) $code;

        // Kode sukses umum (0/200) bukan error.
        if (in_array($code, ['0', '200', 'success'], true)) {
            return;
        }

        // Hanya treat sebagai error bila terdaftar di map ATAU HTTP gagal.
        $known = config("ceisa.error_codes.{$code}");

        if ($known !== null || ! $response->successful()) {
            throw CeisaException::fromCode(
                $code,
                fallback: $data['message'] ?? $data['error_message'] ?? null,
                context: $data,
            );
        }
    }

    /**
     * Upload helper untuk file multipart ke CEISA.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws CeisaException
     */
    protected function uploadFile(string $endpoint, string $filePath, array $params, string $errorMessage): array
    {
        $token = $this->refreshTokenIfExpired();
        $baseUrl = $this->credential()->base_url ?: config('ceisa.base_url');

        try {
            $response = Http::baseUrl(rtrim($baseUrl, '/'))
                ->timeout((int) config('ceisa.timeout', 30))
                ->withHeaders($this->commonHeaders())
                ->withToken($token)
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post($endpoint, [
                    'param' => json_encode($params),
                ]);
        } catch (Throwable $e) {
            throw new CeisaException(
                $errorMessage.': '.$e->getMessage(),
                previous: $e,
            );
        }

        $data = $this->decode($response);
        $this->guardAgainstErrorCode($data, $response);

        if (! $response->successful()) {
            throw new CeisaException(
                $errorMessage.' (HTTP '.$response->status().').',
                context: $data,
            );
        }

        return $data;
    }
}
