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
 * Klien integrasi CEISA H2H (Host-to-Host) Bea Cukai (CEISA 4.0 / PIA).
 *
 * Auth resmi (ceisa40.gitbook.io/pia-ceisa40, openapi.beacukai.go.id):
 *   - SEMUA request membawa header `Beacukai-Api-Key: {api_key}` dan `id_platform: {id_platform}`.
 *   - Login : POST {host}/nle-oauth/v1/user/login (body username+password)
 *     -> mengembalikan access_token (Bearer) + refresh_token.
 *   - Refresh: POST {host}/nle-oauth/v1/user/update-token
 *     dengan header Authorization: {refresh_token} -> access_token baru.
 *   - Layanan Pabean (kirim dokumen, status) di {host}/openapi memakai
 *     Authorization: Bearer {access_token} + header Beacukai-Api-Key + id_platform.
 *
 * Alur token (access token CEISA berumur ~5 menit):
 *   1. getToken()             -> login penuh, simpan access_token + refresh_token + masa berlaku.
 *   2. refreshAccessToken()   -> tukar refresh_token jadi access_token baru tanpa login ulang.
 *   3. refreshTokenIfExpired()-> dipakai sebelum tiap request: pakai token valid,
 *      coba refresh_token bila ada, fallback login penuh bila gagal.
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
        $token = $this->extractToken($data);

        if (empty($token)) {
            throw new CeisaException(
                'Response login CEISA tidak mengandung access_token.',
                context: $data,
            );
        }

        $this->storeToken($token, $this->extractRefreshToken($data), $this->extractExpiresIn($data));

        return $token;
    }

    /**
     * Tukar refresh_token menjadi access_token baru tanpa login ulang penuh.
     * Endpoint: POST /nle-oauth/v1/user/update-token, header Authorization: {refresh_token}.
     *
     * @throws CeisaException
     */
    public function refreshAccessToken(): string
    {
        $refreshToken = $this->credential->refresh_token;

        if (empty($refreshToken)) {
            // Tak ada refresh_token tersimpan -> harus login penuh.
            return $this->getToken();
        }

        $endpoint = config('ceisa.endpoints.refresh_token');

        try {
            $response = $this->baseRequest()
                ->withHeaders(['Authorization' => $refreshToken])
                ->post($endpoint);
        } catch (Throwable $e) {
            throw new CeisaException(
                'Gagal terhubung ke server CEISA saat refresh token: '.$e->getMessage(),
                previous: $e,
            );
        }

        $data = $this->decode($response);

        // Refresh token kadaluarsa / ditolak -> jatuh ke login penuh.
        if (! $response->successful()) {
            return $this->getToken();
        }

        $token = $this->extractToken($data);

        if (empty($token)) {
            return $this->getToken();
        }

        // update-token bisa mengembalikan refresh_token baru; pertahankan yang lama bila tidak.
        $this->storeToken(
            $token,
            $this->extractRefreshToken($data) ?: $refreshToken,
            $this->extractExpiresIn($data),
        );

        return $token;
    }

    /**
     * Pastikan token masih valid; refresh bila sudah/akan kadaluarsa.
     * Pakai refresh_token bila tersedia, fallback login penuh.
     *
     * @throws CeisaException
     */
    public function refreshTokenIfExpired(): string
    {
        if ($this->credential->hasValidToken()) {
            return $this->credential->token;
        }

        return $this->refreshAccessToken();
    }

    /**
     * Simpan access_token (+ refresh_token & masa berlaku) ke DB.
     */
    protected function storeToken(string $token, ?string $refreshToken, int $expiresIn): void
    {
        $attributes = [
            'token' => $token,
            'token_expires_at' => Carbon::now()->addSeconds($expiresIn),
        ];

        if (! empty($refreshToken)) {
            $attributes['refresh_token'] = $refreshToken;
        }

        $this->credential->forceFill($attributes)->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractToken(array $data): ?string
    {
        return $data['access_token']
            ?? $data['token']
            ?? data_get($data, 'item.access_token')
            ?? data_get($data, 'data.access_token');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractRefreshToken(array $data): ?string
    {
        return $data['refresh_token']
            ?? data_get($data, 'item.refresh_token')
            ?? data_get($data, 'data.refresh_token');
    }

    /**
     * expires_in (detik). Access Token CEISA 4.0 berumur ~5 menit;
     * fallback config token_ttl_fallback bila server tak menyertakannya.
     *
     * @param  array<string, mixed>  $data
     */
    protected function extractExpiresIn(array $data): int
    {
        return (int) ($data['expires_in']
            ?? data_get($data, 'item.expires_in')
            ?? data_get($data, 'data.expires_in')
            ?? config('ceisa.token_ttl_fallback', 300));
    }

    /**
     * Ambil idHeader (UUID) dari response submit CEISA.
     * Respons sukses resmi: {"status":"OK","message":"...","idHeader":"{UUID}"}.
     * Disimpan sebagai kunci untuk menarik status/respon dokumen nanti.
     *
     * @param  array<string, mixed>  $data
     */
    protected function extractIdHeader(array $data): ?string
    {
        $value = $data['idHeader']
            ?? $data['id_header']
            ?? data_get($data, 'data.idHeader')
            ?? data_get($data, 'item.idHeader');

        return ! empty($value) ? (string) $value : null;
    }

    /**
     * Kirim dokumen ke CEISA. Mengembalikan response CEISA yang sudah di-decode.
     *
     * Query param (Beacukai Developer Portal):
     *   - isFinal    : true = submit sungguhan ke DJBC; false = hanya draft di portal CEISA.
     *   - isRevision : true = kirim data perbaikan / BCF (khusus BC 3.0 & TPB).
     *
     * @param  array<string, mixed>  $payload
     * @param  array{is_final?: bool, is_revision?: bool}  $options
     * @return array<string, mixed>
     *
     * @throws CeisaException
     */
    public function submitDocument(string $type, array $payload, array $options = []): array
    {
        $token = $this->refreshTokenIfExpired();
        $endpoint = config('ceisa.endpoints.submit');

        $isFinal = $options['is_final'] ?? config('ceisa.submit_is_final_default', true);
        $isRevision = $options['is_revision'] ?? false;

        // Query param dikodekan sebagai string "true"/"false" sesuai harapan gateway.
        $query = http_build_query([
            'isFinal' => $isFinal ? 'true' : 'false',
            'isRevision' => $isRevision ? 'true' : 'false',
        ]);
        $endpoint .= (str_contains($endpoint, '?') ? '&' : '?').$query;

        $body = $payload;

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
    public function submit(Document $document, bool $isRevision = false): Document
    {
        // Generate a unique 26-digit nomor_aju if missing
        $nomorAju = $document->nomor_aju;
        if (empty($nomorAju)) {
            $kantorCode = data_get($document->payload, 'header.kantor_muat')
                ?? data_get($document->payload, 'header.pengangkutan.pelabuhan_bongkar')
                ?? data_get($document->payload, 'header.pengusaha_tpb.kantor')
                ?? '040100'; // fallback KPU Soetta
            $kkkk = substr(preg_replace('/\D/', '', $kantorCode), 0, 4);
            if (strlen($kkkk) < 4) {
                $kkkk = str_pad($kkkk, 4, '0', STR_PAD_RIGHT);
            }
            $dd = match ($document->doc_type) {
                'BC30' => '30',
                'BC20' => '20',
                'BC24' => '24',
                default => '30',
            };
            $npwp = $this->credential->npwp ?: '012345678901000';
            $uuuuuu = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', md5($npwp)), 0, 6));
            $tttttttt = date('Ymd');
            $ssssss = str_pad((string) $document->id, 6, '0', STR_PAD_LEFT);
            $nomorAju = $kkkk.$dd.$uuuuuu.$tttttttt.$ssssss;

            $document->forceFill(['nomor_aju' => $nomorAju])->save();
        }

        $document->forceFill([
            'status' => Document::STATUS_SUBMITTING,
            'submitted_at' => Carbon::now(),
            'error_message' => null,
        ])->save();

        try {
            $formattedPayload = $this->transformPayloadForCeisa($document->doc_type, $document->payload, $nomorAju);
            $data = $this->submitDocument($document->doc_type, $formattedPayload, [
                'is_final' => true,
                'is_revision' => $isRevision,
            ]);

            $document->forceFill([
                'status' => Document::STATUS_SUBMITTED,
                'nomor_aju' => data_get($data, 'nomor_aju', $document->nomor_aju),
                'nomor_daftar' => data_get($data, 'nomor_daftar', $document->nomor_daftar),
                'id_header' => $this->extractIdHeader($data) ?? $document->id_header,
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
     * Ubah format bersarang database M2B menjadi format flat JSON schema CEISA H2H.
     * Delegasi ke generator modular CeisaPayloadBuilder (per-blok: Entitas,
     * Pengangkut, Barang, Kemasan, Pungutan, Dokumen).
     */
    public function transformPayloadForCeisa(string $type, array $payload, string $nomorAju): array
    {
        return CeisaPayloadBuilder::make()->build($type, $payload, $nomorAju);
    }

    /**
     * Query status dokumen dari CEISA berdasarkan nomor aju.
     *
     * @return array<string, mixed>
     *
     * @throws CeisaException
     */
    public function queryDocumentStatus(string $nomorAju, ?string $idHeader = null): array
    {
        $token = $this->refreshTokenIfExpired();
        // CEISA: GET /openapi/status/{nomorAju}
        $endpoint = rtrim((string) config('ceisa.endpoints.status'), '/').'/'.rawurlencode($nomorAju);

        // idHeader (UUID submit) disertakan bila tersedia — kunci utama pelacakan respon.
        $query = ! empty($idHeader) ? ['idHeader' => $idHeader] : [];

        try {
            $response = $this->baseRequest()
                ->withToken($token)
                ->get($endpoint, $query);
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
     * Query status semua dokumen perusahaan dari CEISA.
     *
     * @return array<string, mixed>
     *
     * @throws CeisaException
     */
    public function fetchAllStatuses(string $npwp): array
    {
        $token = $this->refreshTokenIfExpired();
        $endpoint = config('ceisa.endpoints.status');

        try {
            $response = $this->baseRequest()
                ->withToken($token)
                ->get($endpoint, ['idPerusahaan' => $npwp]);
        } catch (Throwable $e) {
            throw new CeisaException(
                'Gagal menghubungi CEISA saat query semua status: '.$e->getMessage(),
                previous: $e,
            );
        }

        $data = $this->decode($response);
        $this->guardAgainstErrorCode($data, $response);

        return $data;
    }

    /**
     * Ambil satu tabel referensi (master data) dari CEISA 4.0.
     * Dipakai cron sinkronisasi (HS Code, pelabuhan, kurs, kemasan, dll)
     * agar master data tidak kedaluwarsa & payload lolos validasi.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     *
     * @throws CeisaException
     */
    public function fetchReference(string $path, array $query = []): array
    {
        $token = $this->refreshTokenIfExpired();

        try {
            $response = $this->baseRequest()
                ->withToken($token)
                ->get($path, $query);
        } catch (Throwable $e) {
            throw new CeisaException(
                'Gagal menghubungi CEISA saat ambil referensi: '.$e->getMessage(),
                previous: $e,
            );
        }

        $data = $this->decode($response);
        $this->guardAgainstErrorCode($data, $response);

        if (! $response->successful()) {
            throw new CeisaException(
                'CEISA menolak permintaan referensi (HTTP '.$response->status().').',
                context: $data,
            );
        }

        // Daftar bisa di root, atau dibungkus data/item/rows/content.
        foreach (['data', 'item', 'rows', 'content', 'result'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return array_values(array_filter($data[$key], 'is_array'));
            }
        }

        return array_values(array_filter($data, 'is_array'));
    }

    /**
     * Download respon PDF dari CEISA.
     *
     * @throws CeisaException
     */
    public function downloadRespon(string $path): Response
    {
        $token = $this->refreshTokenIfExpired();
        $endpoint = config('ceisa.endpoints.download_respon');

        try {
            return $this->baseRequest()
                ->withToken($token)
                ->accept('application/pdf')
                ->get($endpoint, ['path' => $path]);
        } catch (Throwable $e) {
            throw new CeisaException(
                'Gagal download respon dari CEISA: '.$e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * Cetak formulir dokumen pabean (PDF).
     *
     * @throws CeisaException
     */
    public function cetakFormulir(string $nomorAju): Response
    {
        $token = $this->refreshTokenIfExpired();
        $endpoint = config('ceisa.endpoints.cetak_formulir');

        try {
            return $this->baseRequest()
                ->withToken($token)
                ->accept('application/pdf')
                ->get($endpoint, ['nomorAju' => $nomorAju]);
        } catch (Throwable $e) {
            throw new CeisaException(
                'Gagal cetak formulir dari CEISA: '.$e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * Download billing PDF dari CEISA.
     *
     * @throws CeisaException
     */
    public function downloadBilling(string $kodeBilling): Response
    {
        $token = $this->refreshTokenIfExpired();
        $endpoint = config('ceisa.endpoints.billing');

        try {
            return $this->baseRequest()
                ->withToken($token)
                ->accept('application/pdf')
                ->get($endpoint, ['kodeBilling' => $kodeBilling]);
        } catch (Throwable $e) {
            throw new CeisaException(
                'Gagal download billing dari CEISA: '.$e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * Upload dokumen pelengkap (dokap) ke CEISA.
     * Endpoint: POST /v2/openapi/file/dokumen (multipart/form-data).
     *
     * @param  string  $filePath  Absolute path ke file PDF
     * @param  array<string, mixed>  $params  {nomorAju, seriDokumen, npwp}
     * @return array<string, mixed>
     *
     * @throws CeisaException
     */
    public function uploadDokap(string $filePath, array $params): array
    {
        return $this->uploadFile(
            config('ceisa.endpoints.upload_dokap'),
            $filePath,
            $params,
            'Gagal upload dokumen pelengkap ke CEISA',
        );
    }

    /**
     * Upload gambar barang ke CEISA.
     * Endpoint: POST /v2/openapi/file/barang (multipart/form-data).
     *
     * @param  string  $filePath  Absolute path ke file gambar
     * @param  array<string, mixed>  $params  {keterangan, nomorAju, seriBarang, npwp}
     * @return array<string, mixed>
     *
     * @throws CeisaException
     */
    public function uploadGambar(string $filePath, array $params): array
    {
        return $this->uploadFile(
            config('ceisa.endpoints.upload_gambar'),
            $filePath,
            $params,
            'Gagal upload gambar barang ke CEISA',
        );
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
        $baseUrl = $this->credential->base_url ?: config('ceisa.base_url');

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

    /**
     * Request dasar dengan base URL, timeout, dan header umum.
     */
    protected function baseRequest(): PendingRequest
    {
        $baseUrl = $this->credential->base_url ?: config('ceisa.base_url');

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
        $apiKey = (string) $this->credential->api_key;
        $apiKeyHeaders = (array) config('ceisa.api_key_headers', ['Beacukai-Api-Key']);
        foreach ($apiKeyHeaders as $headerName) {
            $headers[$headerName] = $apiKey;
        }

        // id_platform: dari credential user atau fallback config (opsional untuk H2H murni).
        $idPlatform = $this->credential->id_platform ?: config('ceisa.id_platform');
        if (! empty($idPlatform)) {
            $headers['id_platform'] = (string) $idPlatform;
        }

        // Origin: domain asal sistem klien (standard header H2H Beacukai Developer Portal).
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
}
