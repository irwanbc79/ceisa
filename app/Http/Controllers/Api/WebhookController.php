<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
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

        // 2. Selalu catat payload mentah untuk audit.
        $nomorAju = data_get($payload, 'nomor_aju') ?? data_get($payload, 'data.nomor_aju');

        $log = WebhookLog::create([
            'event' => data_get($payload, 'event') ?? data_get($payload, 'status'),
            'nomor_aju' => $nomorAju,
            'payload' => $payload,
            'ip_address' => $request->ip(),
            'received_at' => now(),
        ]);

        // 3. Cocokkan ke dokumen via nomor_aju / nomor_daftar.
        $document = $this->matchDocument($payload, $nomorAju);

        if ($document) {
            $this->applyStatus($document, $payload);
            $log->update(['document_id' => $document->id, 'processed' => true]);
        } else {
            Log::info('Webhook CEISA: dokumen tidak ditemukan', ['nomor_aju' => $nomorAju]);
        }

        // 4. Selalu balas 200 agar CEISA tidak retry berlebihan.
        return response()->json(['message' => 'received'], 200);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function matchDocument(array $payload, ?string $nomorAju): ?Document
    {
        $nomorDaftar = data_get($payload, 'nomor_daftar') ?? data_get($payload, 'data.nomor_daftar');

        return Document::query()
            ->when($nomorAju, fn ($q) => $q->orWhere('nomor_aju', $nomorAju))
            ->when($nomorDaftar, fn ($q) => $q->orWhere('nomor_daftar', $nomorDaftar))
            ->latest()
            ->first();
    }

    /**
     * Petakan status CEISA ke status internal dokumen.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function applyStatus(Document $document, array $payload): void
    {
        $raw = strtoupper((string) (data_get($payload, 'status') ?? data_get($payload, 'data.status') ?? ''));

        $status = match (true) {
            str_contains($raw, 'TERIMA'), str_contains($raw, 'ACCEPT'), str_contains($raw, 'SPPB') => Document::STATUS_ACCEPTED,
            str_contains($raw, 'TOLAK'), str_contains($raw, 'REJECT'), str_contains($raw, 'NPP') => Document::STATUS_REJECTED,
            default => $document->status === Document::STATUS_SUBMITTING ? Document::STATUS_SUBMITTED : $document->status,
        };

        $document->forceFill([
            'status' => $status,
            'nomor_daftar' => data_get($payload, 'nomor_daftar') ?? data_get($payload, 'data.nomor_daftar') ?? $document->nomor_daftar,
            'ceisa_response' => $payload,
            'response_at' => now(),
        ])->save();
    }
}
