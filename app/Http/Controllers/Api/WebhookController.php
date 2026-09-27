<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\WebhookLog;
use App\Services\CeisaStatusMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public const TYPE_RESPON = 'Respon';

    public const TYPE_FORMULIR = 'Formulir';

    public const TYPE_INFORMASI = 'Informasi';

    /**
     * Terima response async dari CEISA dan update status dokumen terkait.
     */
    public function ceisa(Request $request): JsonResponse
    {
        $maxBytes = (int) config('ceisa.webhook_max_payload_bytes', 1_048_576);
        $contentLength = max(strlen($request->getContent()), (int) $request->header('Content-Length', 0));

        if ($contentLength > $maxBytes) {
            Log::warning('Webhook CEISA ditolak: payload terlalu besar', [
                'ip' => $request->ip(),
                'content_length' => $contentLength,
            ]);

            return response()->json(['message' => 'Payload too large'], 413);
        }

        // Rollout-safe: kontrak resmi CEISA belum menjamin dukungan signature.
        // Callback unsigned diakui agar CEISA tidak retry, tetapi dibuang dan
        // tidak pernah boleh menyentuh audit log maupun status dokumen.
        $secret = trim((string) config('ceisa.webhook_secret'));
        if ($secret === '') {
            Log::warning('Webhook CEISA unsigned diabaikan; status wajib direkonsiliasi melalui polling.');

            return response()->json(['message' => 'accepted for reconciliation'], 202);
        }

        if (! $this->hasValidSignature($request, $secret)) {
            Log::warning('Webhook CEISA ditolak: signature tidak valid', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $this->redactSecrets($request->all());

        // 2. Tentukan jenis notifikasi DJBC: Respon / Formulir / Informasi.
        $type = $this->notificationType($payload);
        $nomorAju = data_get($payload, 'nomor_aju') ?? data_get($payload, 'data.nomor_aju');
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        $fingerprint = hash('sha256', $encodedPayload ?: $request->getContent());

        return DB::transaction(function () use ($request, $payload, $type, $nomorAju, $fingerprint): JsonResponse {
            // Unique fingerprint + createOrFirst membuat retry CEISA idempoten,
            // termasuk bila dua callback identik tiba hampir bersamaan.
            $log = WebhookLog::query()->createOrFirst([
                'fingerprint' => $fingerprint,
            ], [
                'event' => data_get($payload, 'event') ?? data_get($payload, 'status'),
                'notification_type' => $type,
                'nomor_aju' => $nomorAju,
                'payload' => $payload,
                'ip_address' => $request->ip(),
                'verified' => true,
                'received_at' => now(),
            ]);

            if (! $log->wasRecentlyCreated) {
                return response()->json(['message' => 'duplicate'], 200);
            }

            // 4. Cocokkan ke dokumen via nomor_aju / nomor_daftar / id_header.
            $document = $this->matchDocument($payload, $nomorAju);

            // Hanya notifikasi "Respon" yang mengubah status/jalur dokumen.
            // Formulir & Informasi cukup tercatat (audit), tidak memutasi dokumen.
            if ($document && $type === self::TYPE_RESPON) {
                $this->applyStatus($document, $payload);
                $log->update(['document_id' => $document->id, 'processed' => true]);

                if (! empty($document->nomor_aju) && config('ceisa.zero_trust_verification', false)) {
                    \App\Jobs\VerifyCeisaStatusJob::dispatch($document, $payload, $log->id);
                }
            } elseif ($document) {
                $log->update(['document_id' => $document->id, 'processed' => true]);
            } else {
                Log::info('Webhook CEISA: dokumen tidak ditemukan', ['nomor_aju' => $nomorAju, 'type' => $type]);
            }

            // 5. Selalu balas 200 agar CEISA tidak retry berlebihan.
            return response()->json(['message' => 'received'], 200);
        });
    }

    /**
     * Terima HMAC SHA-256 (`X-CEISA-Signature: sha256=...`) dan mode
     * shared-secret lama untuk kompatibilitas integrasi yang sudah berjalan.
     */
    protected function hasValidSignature(Request $request, string $secret): bool
    {
        $signature = trim((string) $request->header('X-CEISA-Signature'));
        if ($signature !== '') {
            $normalized = str_starts_with(strtolower($signature), 'sha256=')
                ? substr($signature, 7)
                : $signature;
            $expectedHmac = hash_hmac('sha256', $request->getContent(), $secret);

            if (hash_equals($expectedHmac, strtolower($normalized)) || hash_equals($secret, $signature)) {
                return true;
            }
        }

        foreach ([$request->header('X-Webhook-Secret'), $request->query('secret'), $request->input('secret')] as $provided) {
            if (is_string($provided) && hash_equals($secret, $provided)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Jangan simpan kredensial yang mungkin ikut terbawa dalam payload callback.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function redactSecrets(array $payload): array
    {
        $sensitive = ['secret', 'password', 'api_key', 'access_token', 'refresh_token', 'token'];

        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                unset($payload[$key]);
            } elseif (is_array($value)) {
                $payload[$key] = $this->redactSecrets($value);
            }
        }

        return $payload;
    }

    /**
     * Klasifikasi jenis notifikasi DJBC dari payload.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function notificationType(array $payload): string
    {
        $raw = strtoupper((string) (
            data_get($payload, 'jenis')
            ?? data_get($payload, 'type')
            ?? data_get($payload, 'tipe')
            ?? data_get($payload, 'notification_type')
            ?? data_get($payload, 'kategori')
            ?? ''
        ));

        // Urutan penting: "INFORMASI" mengandung substring "FORM",
        // jadi cek Informasi sebelum Formulir.
        if (str_contains($raw, 'INFORMASI') || str_contains($raw, 'INFO') || str_contains($raw, 'PENGUMUMAN')) {
            return self::TYPE_INFORMASI;
        }

        if (str_contains($raw, 'FORMULIR')) {
            return self::TYPE_FORMULIR;
        }

        if (str_contains($raw, 'RESPON') || str_contains($raw, 'RESPONSE')) {
            return self::TYPE_RESPON;
        }

        // Tanpa penanda jenis: bila membawa status/nomor dokumen, anggap Respon.
        $hasDocSignal = data_get($payload, 'status')
            ?? data_get($payload, 'data.status')
            ?? data_get($payload, 'nomor_aju')
            ?? data_get($payload, 'data.nomor_aju')
            ?? data_get($payload, 'nomor_daftar')
            ?? data_get($payload, 'data.nomor_daftar');

        return $hasDocSignal ? self::TYPE_RESPON : self::TYPE_INFORMASI;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function matchDocument(array $payload, ?string $nomorAju): ?Document
    {
        $nomorDaftar = data_get($payload, 'nomor_daftar') ?? data_get($payload, 'data.nomor_daftar');
        $idHeader = data_get($payload, 'idHeader')
            ?? data_get($payload, 'id_header')
            ?? data_get($payload, 'data.idHeader')
            ?? data_get($payload, 'data.id_header');

        // Tanpa identifier apa pun, jangan sampai mencocokkan dokumen acak.
        if (empty($nomorAju) && empty($nomorDaftar) && empty($idHeader)) {
            return null;
        }

        return Document::query()
            ->where(function ($q) use ($nomorAju, $nomorDaftar, $idHeader) {
                $q->when($nomorAju, fn ($qq) => $qq->orWhere('nomor_aju', $nomorAju))
                    ->when($nomorDaftar, fn ($qq) => $qq->orWhere('nomor_daftar', $nomorDaftar))
                    ->when($idHeader, fn ($qq) => $qq->orWhere('id_header', $idHeader));
            })
            ->latest('id')
            ->first();
    }

    /**
     * Petakan status CEISA ke status internal dokumen (delegasi ke CeisaStatusMapper
     * agar konsisten dengan penarikan status manual / polling).
     *
     * @param  array<string, mixed>  $payload
     */
    protected function applyStatus(Document $document, array $payload): void
    {
        CeisaStatusMapper::apply($document, $payload);
    }
}
