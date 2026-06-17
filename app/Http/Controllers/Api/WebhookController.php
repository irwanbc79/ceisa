<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\WebhookLog;
use App\Services\CeisaStatusMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        // 1. Verifikasi shared secret (bila dikonfigurasi).
        $secret = config('ceisa.webhook_secret');

        if (! empty($secret)) {
            $provided = $request->header('X-CEISA-Signature')
                ?? $request->header('X-Webhook-Secret')
                ?? $request->input('secret');

            if (! hash_equals($secret, (string) $provided)) {
                Log::warning('Webhook CEISA ditolak: secret tidak valid', ['ip' => $request->ip()]);

                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        $payload = $request->all();

        // 2. Tentukan jenis notifikasi DJBC: Respon / Formulir / Informasi.
        $type = $this->notificationType($payload);
        $nomorAju = data_get($payload, 'nomor_aju') ?? data_get($payload, 'data.nomor_aju');

        // 3. Selalu catat payload mentah untuk audit.
        $log = WebhookLog::create([
            'event' => data_get($payload, 'event') ?? data_get($payload, 'status'),
            'notification_type' => $type,
            'nomor_aju' => $nomorAju,
            'payload' => $payload,
            'ip_address' => $request->ip(),
            'received_at' => now(),
        ]);

        // 4. Cocokkan ke dokumen via nomor_aju / nomor_daftar.
        $document = $this->matchDocument($payload, $nomorAju);

        // Hanya notifikasi "Respon" yang mengubah status/jalur dokumen.
        // Formulir & Informasi cukup tercatat (audit), tidak memutasi dokumen.
        if ($document && $type === self::TYPE_RESPON) {
            $this->applyStatus($document, $payload);
            $log->update(['document_id' => $document->id, 'processed' => true]);
        } elseif ($document) {
            $log->update(['document_id' => $document->id, 'processed' => true]);
        } else {
            Log::info('Webhook CEISA: dokumen tidak ditemukan', ['nomor_aju' => $nomorAju, 'type' => $type]);
        }

        // 5. Selalu balas 200 agar CEISA tidak retry berlebihan.
        return response()->json(['message' => 'received'], 200);
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

        // Tanpa identifier apa pun, jangan sampai mencocokkan dokumen acak.
        if (empty($nomorAju) && empty($nomorDaftar)) {
            return null;
        }

        return Document::query()
            ->where(function ($q) use ($nomorAju, $nomorDaftar) {
                $q->when($nomorAju, fn ($qq) => $qq->orWhere('nomor_aju', $nomorAju))
                    ->when($nomorDaftar, fn ($qq) => $qq->orWhere('nomor_daftar', $nomorDaftar));
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
