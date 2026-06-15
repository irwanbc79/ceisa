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
 * Klien integrasi CEISA H2H (Host-to-Host) Bea Cukai.
 *
 * Alur:
 *   1. getToken()  -> GET ke endpoint auth dengan Basic Auth + app_id, simpan token.
 *   2. submitDocument() -> POST dokumen dengan Bearer token (auto-refresh bila perlu).
 *
 * Catatan: endpoint & bentuk payload mengikuti config/ceisa.php dan WAJIB
 * diselaraskan dengan dokumentasi H2H resmi Bea Cukai saat onboarding.
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
            $response = $this->baseRequest()
                ->withBasicAuth($this->credential->app_id, $this->credential->api_key)
                ->get($endpoint, [
                    'app_id' => $this->credential->app_id,
                ]);
        } catch (Throwable $e) {
            throw new CeisaException(
                'Gagal terhubung ke server CEISA saat mengambil token: '.$e->getMessage(),
                previous: $e,
            );
        }

        $data = $this->decode($response);
        $this->guardAgainstErrorCode($data, $response);

        if (! $response->successful()) {
            throw new CeisaException(
                'Gagal mengambil token CEISA (HTTP '.$response->status().').',
                context: $data,
            );
        }

        $token = $data['access_token']
            ?? $data['token']
            ?? data_get($data, 'data.access_token');

        if (empty($token)) {
            throw new CeisaException(
                'Response CEISA tidak mengandung access_token.',
                context: $data,
            );
        }

        // expires_in dalam detik; default 1 jam bila tidak disediakan.
        $expiresIn = (int) ($data['expires_in'] ?? data_get($data, 'data.expires_in') ?? 3600);

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
            'app_id' => $this->credential->app_id,
            'doc_type' => $type,
            'data' => $payload,
        ];

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
        } catch (\Throwable $e) {
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
