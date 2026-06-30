<?php

namespace App\Services;

use App\Exceptions\CeisaException;
use App\Models\CeisaCredential;
use App\Models\Document;
use App\Services\Concerns\HandlesCeisaAuth;
use App\Services\Concerns\HandlesCeisaHttp;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
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
 * Trait pemisahan:
 *   - HandlesCeisaAuth  → token lifecycle (login, refresh, store, extract).
 *   - HandlesCeisaHttp  → HTTP infrastructure (base request, headers, decode, guard).
 */
class CeisaService
{
    use HandlesCeisaAuth, HandlesCeisaHttp;

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
     * Akses credential yang di-inject (dipakai oleh traits).
     */
    protected function credential(): CeisaCredential
    {
        return $this->credential;
    }

    // ──────────────────────── API Operations ────────────────────────

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
        $endpoint = config('ceisa.endpoints.submit');

        $isFinal = $options['is_final'] ?? config('ceisa.submit_is_final_default', true);
        $isRevision = $options['is_revision'] ?? false;

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
            $response = $this->authorizedRequest(
                fn (string $token) => $this->baseRequest()->withToken($token)->post($endpoint, $body),
            );
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
                $this->httpStatusMessage($response->status()).' (submit dokumen)',
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
     * Delegasi ke generator modular CeisaPayloadBuilder.
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
        $endpoint = rtrim((string) config('ceisa.endpoints.status'), '/').'/'.rawurlencode($nomorAju);

        $query = ! empty($idHeader) ? ['idHeader' => $idHeader] : [];

        try {
            $response = $this->authorizedRequest(
                fn (string $token) => $this->baseRequest()->withToken($token)->get($endpoint, $query),
            );
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
        $endpoint = config('ceisa.endpoints.status');

        try {
            $response = $this->authorizedRequest(
                fn (string $token) => $this->baseRequest()->withToken($token)->get($endpoint, ['idPerusahaan' => $npwp]),
            );
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
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     *
     * @throws CeisaException
     */
    public function fetchReference(string $path, array $query = []): array
    {
        try {
            $response = $this->authorizedRequest(
                fn (string $token) => $this->baseRequest()->withToken($token)->get($path, $query),
            );
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
                $this->httpStatusMessage($response->status()).' (ambil referensi)',
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
     * Ambil daftar manifes (BC 1.1) dari CEISA untuk monitoring.
     * ⚠ Sub-path endpoint manifes masih asumsi (base /v1/openapi-manifes terverifikasi
     * dari API Gallery) — override via .env CEISA_MANIFEST_ENDPOINT bila berbeda.
     *
     * @param  array{jenis?: string, npwp?: ?string}  $params
     * @return array<int, array<string, mixed>>
     *
     * @throws CeisaException
     */
    public function fetchManifests(array $params = []): array
    {
        $jenis = ($params['jenis'] ?? 'inward') === 'outward' ? 'outward' : 'inward';
        $endpoint = rtrim((string) config('ceisa.endpoints.manifest'), '/').'/'.$jenis;

        $query = array_filter([
            'npwp' => $params['npwp'] ?? null,
            'idPerusahaan' => $params['npwp'] ?? null,
        ]);

        try {
            $response = $this->authorizedRequest(
                fn (string $token) => $this->baseRequest()->withToken($token)->get($endpoint, $query),
            );
        } catch (Throwable $e) {
            throw new CeisaException(
                'Gagal menghubungi CEISA saat ambil manifes: '.$e->getMessage(),
                previous: $e,
            );
        }

        $data = $this->decode($response);
        $this->guardAgainstErrorCode($data, $response);

        if (! $response->successful()) {
            throw new CeisaException(
                $this->httpStatusMessage($response->status()).' (ambil manifes)',
                context: $data,
            );
        }

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
     * Jalankan request probe custom (hanya untuk debugging/investigasi API).
     *
     * @param  string  $method  GET, POST, dll.
     * @param  string  $path  Endpoint relatif, contoh '/v2/openapi/status'
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $body
     */
    public function probe(string $method, string $path, array $query = [], array $body = []): Response
    {
        $token = $this->refreshTokenIfExpired();

        $req = $this->baseRequest()->withToken($token);

        if (strtoupper($method) === 'GET') {
            return $req->get($path, $query);
        }

        return $req->send($method, $path, [
            'query' => $query,
            'json' => $body,
        ]);
    }
}

