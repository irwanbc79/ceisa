<?php

namespace App\Jobs;

use App\Exceptions\CeisaException;
use App\Models\CeisaCredential;
use App\Models\Document;
use App\Services\CeisaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubmitCeisaDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [15, 60, 180];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Document $document,
        public bool $isRevision = false,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $credential = CeisaCredential::shared();

        if (! $credential) {
            Log::error('SubmitCeisaDocumentJob gagal: kredensial CEISA belum diatur', [
                'document_id' => $this->document->id,
                'nomor_aju' => $this->document->nomor_aju,
            ]);
            $this->document->update([
                'status' => Document::STATUS_ERROR,
                'error_message' => 'Kredensial CEISA belum diatur.',
            ]);

            return;
        }

        try {
            CeisaService::forCredential($credential)->submit($this->document, $this->isRevision);

            Log::info('SubmitCeisaDocumentJob sukses dikirim ke CEISA', [
                'document_id' => $this->document->id,
                'nomor_aju' => $this->document->nomor_aju,
                'status' => $this->document->status,
            ]);
        } catch (CeisaException $e) {
            Log::warning('SubmitCeisaDocumentJob CeisaException', [
                'document_id' => $this->document->id,
                'nomor_aju' => $this->document->nomor_aju,
                'message' => $e->getMessage(),
            ]);

            // Jika error adalah recoverable delivery state unknown, retry via queue
            if (data_get($e->context, 'delivery_state') === 'unknown' && $this->attempts() < $this->tries) {
                throw $e;
            }
        } catch (Throwable $e) {
            Log::error('SubmitCeisaDocumentJob unhandled error', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

