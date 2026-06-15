<?php

namespace App\Services;

use App\Exceptions\CeisaException;
use App\Models\CeisaCredential;
use App\Models\Document;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Klien integrasi CEISA H2H (Host-to-Host) Bea Cukai (CEISA 4.0).
 *
 * Auth resmi (openapi.beacukai.go.id):
 *   - SEMUA request membawa header `beacukai-api-key: {api_key}`.
 *   - Login: POST {host}/v1/openapi-auth/user/login dengan body username+password
 *     -> mengembalikan access_token (Bearer).
 *   - Layanan Pabean (kirim dokumen, dll) di {host}/v2/openapi memakai
 *     Authorization: Bearer {access_token} + header beacukai-api-key.
 *
 * Alur:
 *   1. getToken()       -> login, simpan access_token + masa berlaku.
 *   2. submitDocument() -> POST dokumen (auto-refresh token bila perlu).
 */
class CeisaService
{
    public function __construct(
        protected CeisaCredential $credential,
    ) {}

    /**
     * Buat instance untuk kredensial milik user tertentu.
     */
    public static function forCredential(CeisaCredential $credential): self
    {
        return new self($credential);
    }

    /**
     * Ambil token akses dari CEISA dan simpan ke DB.
     *
     * @throws CeisaException
     */
    public function getToken(): string
    {
        $endpoint = config('ceisa.endpoints.token');

        try {
            // Header beacukai-api-key sudah disisipkan di baseRequest().
            $response = $this->baseRequest()->post($endpoint, [
                'username' => $this->credential->username,
                'password' => $this->credential->password,
            ]);
        } catch (Throwable $e) {
            throw new CeisaException(
                'Gagal terhubung ke server CEISA saat login: '.$e->getMessage(),
                previous: $e,
            );
        }

        $data = $this->decode($response);
        $this->guardAgainstErrorCode($data, $response);

        if (! $response->successful()) {
            throw new CeisaException(
                'Gagal login ke CEISA (HTTP '.$response->status().').',
                context: $data,
            );
        }

        // CEISA dapat membungkus token di beberapa lokasi tergantung versi.
        $token = $data['access_token']
            ?? $data['token']
            ?? data_get($data, 'item.access_token')
            ?? data_get($data, 'data.access_token');

        if (empty($token)) {
            throw new CeisaException(
                'Response login CEISA tidak mengandung access_token.',
                context: $data,
            );
        }

        // expires_in dalam detik. Access Token CEISA 4.0 berumur ~5 menit;
        // pakai 300 dtk sebagai fallback bila server tak menyertakan expires_in.
        $expiresIn = (int) ($data['expires_in']
            ?? data_get($data, 'item.expires_in')
            ?? data_get($data, 'data.expires_in')
            ?? config('ceisa.token_ttl_fallback', 300));

        $this->credential->forceFill([
            'token' => $token,
            'token_expires_at' => Carbon::now()->addSeconds($expiresIn),
        ])->save();

        return $token;
    }

    /**
     * Pastikan token masih valid; refresh bila sudah/akan kadaluarsa.
     *
     * @throws CeisaException
     */
    public function refreshTokenIfExpired(): string
    {
        if ($this->credential->hasValidToken()) {
            return $this->credential->token;
        }

        return $this->getToken();
    }

    /**
     * Kirim dokumen ke CEISA. Mengembalikan response CEISA yang sudah di-decode.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws CeisaException
     */
    public function submitDocument(string $type, array $payload): array
    {
        $token = $this->refreshTokenIfExpired();
        $endpoint = config('ceisa.endpoints.submit');

        $body = [
            'doc_type' => $type,
            'data' => $payload,
        ];

        // app_id opsional; sebagian layanan masih memintanya pada body.
        if (! empty($this->credential->app_id)) {
            $body['app_id'] = $this->credential->app_id;
        }

        try {
            $response = $this->baseRequest()
                ->withToken($token)
                ->post($endpoint, $body);
        } catch (Throwable $e) {
            throw new CeisaException(
                'Gagal terhubung ke server CEISA saat submit dokumen: '.$e->getMessage(),
                previous: $e,
            );
        }

        $data = $this->decode($response);
        $this->guardAgainstErrorCode($data, $response);

        if (! $response->successful()) {
            throw new CeisaException(
                'CEISA menolak submit dokumen (HTTP '.$response->status().').',
                context: $data,
            );
        }

        return $data;
    }

    /**
     * Submit sebuah Document model: update status & simpan response CEISA.
     */
    public function submit(Document $document): Document
    {
        $document->forceFill([
            'status' => Document::STATUS_SUBMITTING,
            'submitted_at' => Carbon::now(),
            'error_message' => null,
        ])->save();

        try {
            $data = $this->submitDocument($document->doc_type, $document->payload);

            $document->forceFill([
                'status' => Document::STATUS_SUBMITTED,
                'nomor_aju' => data_get($data, 'nomor_aju', $document->nomor_aju),
                'nomor_daftar' => data_get($data, 'nomor_daftar', $document->nomor_daftar),
                'ceisa_response' => $data,
                'response_at' => Carbon::now(),
            ])->save();
        } catch (CeisaException $e) {
            Log::warning('CEISA submit gagal', [
                'document_id' => $document->id,
                'ceisa_code' => $e->ceisaCode,
                'context' => $e->context,
            ]);

            $document->forceFill([
                'status' => Document::STATUS_ERROR,
                'error_message' => $e->getMessage(),
                'ceisa_response' => $e->context,
                'response_at' => Carbon::now(),
            ])->save();

            throw $e;
        }

        return $document;
    }

    /**
     * Query status dokumen dari CEISA berdasarkan nomor aju.
     *
     * @return array<string, mixed>
     *
     * @throws CeisaException
     */
    public function queryDocumentStatus(string $nomorAju): array
    {
        $token = $this->refreshTokenIfExpired();
        $endpoint = config('ceisa.endpoints.status');

        try {
            $response = $this->baseRequest()
                ->withToken($token)
                ->get($endpoint, ['nomor_aju' => $nomorAju]);
        } catch (Throwable $e) {
            throw new CeisaException(
                'Gagal menghubungi CEISA saat query status: '.$e->getMessage(),
                previous: $e,
            );
        }

        $data = $this->decode($response);
        $this->guardAgainstErrorCode($data, $response);

        return $data;
    }

    /**
     * Request dasar dengan base URL, timeout, dan header umum.
     */
    protected function baseRequest(): PendingRequest
    {
        return Http::baseUrl(config('ceisa.base_url'))
            ->timeout((int) config('ceisa.timeout', 30))
            ->withHeaders([
                // Wajib pada SEMUA request CEISA 4.0 (auth maupun layanan).
                config('ceisa.api_key_header', 'beacukai-api-key') => (string) $this->credential->api_key,
            ])
            ->acceptJson()
            ->asJson();
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
}
