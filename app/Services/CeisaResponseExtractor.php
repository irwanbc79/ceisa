<?php

namespace App\Services;

use App\Models\Document;

/**
 * Normalisasi pembacaan field penting dari payload respons CEISA
 * (konsisten antara sinkronisasi, webhook, dan unduh file).
 */
class CeisaResponseExtractor
{
    public static function responPdfPath(Document $document): ?string
    {
        $candidates = [
            data_get($document->ceisa_response, 'data.responPdf'),
            data_get($document->ceisa_response, 'data.pathRespon'),
            data_get($document->ceisa_response, 'responPdf'),
            data_get($document->ceisa_response, 'pathRespon'),
            data_get($document->ceisa_response, 'pdf'),
            data_get($document->payload, 'raw_respon.pdf'),
            data_get($document->payload, 'raw_respon.responPdf'),
            data_get($document->payload, 'raw_respon.pathRespon'),
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        if ($document->relationLoaded('webhookLogs') || $document->webhookLogs()->exists()) {
            $fromLog = $document->webhookLogs
                ->map(fn ($log) => data_get($log->payload, 'data.responPdf')
                    ?? data_get($log->payload, 'data.pathRespon')
                    ?? data_get($log->payload, 'pathRespon')
                    ?? data_get($log->payload, 'pdf'))
                ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                ->first();

            if (is_string($fromLog)) {
                return trim($fromLog);
            }
        }

        return null;
    }

    public static function kodeBilling(Document $document): ?string
    {
        $candidates = [
            data_get($document->ceisa_response, 'data.kodeBilling'),
            data_get($document->ceisa_response, 'kodeBilling'),
            data_get($document->ceisa_response, 'kode_billing'),
            data_get($document->payload, 'raw_respon.kodeBilling'),
            data_get($document->payload, 'raw_respon.kode_billing'),
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        if ($document->relationLoaded('webhookLogs') || $document->webhookLogs()->exists()) {
            $fromLog = $document->webhookLogs
                ->map(fn ($log) => data_get($log->payload, 'data.kodeBilling')
                    ?? data_get($log->payload, 'kodeBilling')
                    ?? data_get($log->payload, 'kode_billing'))
                ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                ->first();

            if (is_string($fromLog)) {
                return trim($fromLog);
            }
        }

        return null;
    }
}
