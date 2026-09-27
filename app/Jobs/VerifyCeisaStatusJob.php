<?php

namespace App\Jobs;

use App\Models\CeisaCredential;
use App\Models\Document;
use App\Models\WebhookLog;
use App\Services\CeisaService;
use App\Services\CeisaStatusMapper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class VerifyCeisaStatusJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $webhookPayload
     */
    public function __construct(
        public Document $document,
        public array $webhookPayload = [],
        public ?int $webhookLogId = null,
    ) {}

    /**
     * Execute the job: Zero-Trust verification against official CEISA status endpoint.
     */
    public function handle(): void
    {
        $credential = CeisaCredential::shared();

        if (! $credential) {
            Log::warning('VerifyCeisaStatusJob: Kredensial CEISA belum diatur, skip verifikasi resmi.', [
                'document_id' => $this->document->id,
                'nomor_aju' => $this->document->nomor_aju,
            ]);

            return;
        }

        if (empty($this->document->nomor_aju)) {
            Log::warning('VerifyCeisaStatusJob: Dokumen belum memiliki nomor aju.', [
                'document_id' => $this->document->id,
            ]);

            return;
        }

        try {
            $data = CeisaService::forCredential($credential)
                ->queryDocumentStatus($this->document->nomor_aju, $this->document->id_header);

            CeisaStatusMapper::apply($this->document, $data);

            if ($this->webhookLogId) {
                WebhookLog::query()->whereKey($this->webhookLogId)->update([
                    'verified' => true,
                    'processed' => true,
                ]);
            }

            Log::info('VerifyCeisaStatusJob: Status berhasil diverifikasi dari server resmi CEISA', [
                'document_id' => $this->document->id,
                'nomor_aju' => $this->document->nomor_aju,
                'status' => $this->document->fresh()->status,
            ]);
        } catch (Throwable $e) {
            Log::warning('VerifyCeisaStatusJob: Gagal memverifikasi status resmi ke CEISA', [
                'document_id' => $this->document->id,
                'nomor_aju' => $this->document->nomor_aju,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

