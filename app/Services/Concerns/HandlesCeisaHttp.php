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
     * Jalankan request berotorisasi; bila CEISA membalas 401 (token kadaluarsa/
     * ditolak di tengah operasi), lakukan login ulang penuh lalu ulangi SEKALI.
     * Token H2H berumur 2 jam sehingga kasus ini jarang, tapi penting di prod.
     *
     * @param  callable(string $token): Response  $call
     */
    protected function authorizedRequest(callable $call): Response
    {
        $token = $this->refreshTokenIfExpired();
        $response = $call($token);

        if ($response->status() === 401) {
            $response = $call($this->getToken());
        }

        return $response;
    }

    /**
     * Pesan Bahasa Indonesia yang actionable per kode HTTP (REST Response Code DJBC).
     */
    protected function httpStatusMessage(int $status): string
    {
        return match ($status) {
            400 => 'Permintaan ditolak: parameter atau format data tidak valid (400).',
            401 => 'Sesi CEISA berakhir atau kredensial tidak sah (401).',
            403 => 'Akses ditolak CEISA (403) — periksa API Key & Whitelist IP di portal.',
            404 => 'Sumber daya tidak ditemukan di CEISA (404) — periksa endpoint/nomor aju.',
            405 => 'Metode HTTP tidak didukung endpoint CEISA (405).',
            406, 415 => 'Format konten tidak diterima CEISA ('.$status.').',
            409 => 'Konflik data saat memproses permintaan di CEISA (409).',
            429 => 'Terlalu banyak permintaan ke CEISA (429) — coba lagi beberapa saat.',
            500, 502, 503, 504 => 'Server DJBC sedang bermasalah ('.$status.') — coba lagi nanti.',
            default => 'CEISA mengembalikan status HTTP '.$status.'.',
        };
    }

    abstract public function getToken(): string;

    abstract public function refreshTokenIfExpired(): string;

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
        $baseUrl = $this->credential()->base_url ?: config('ceisa.base_url');
        $fileContents = file_get_contents($filePath);
        $fileName = basename($filePath);

        try {
            $response = $this->authorizedRequest(
                fn (string $token) => Http::baseUrl(rtrim($baseUrl, '/'))
                    ->timeout((int) config('ceisa.timeout', 30))
                    ->withHeaders($this->commonHeaders())
                    ->withToken($token)
                    ->attach('file', $fileContents, $fileName)
                    ->post($endpoint, [
                        'param' => json_encode($params),
                    ]),
            );
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
                $errorMessage.' — '.$this->httpStatusMessage($response->status()),
                context: $data,
            );
        }

        return $data;
    }
}
