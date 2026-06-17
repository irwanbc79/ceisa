<?php

namespace App\Services;

use App\Models\Document;

/**
 * Pemetaan respons status CEISA → field internal Document.
 *
 * Dipakai dua jalur yang berbeda namun konsisten:
 *  - PUSH : WebhookController saat DJBC mengirim notifikasi "Respon".
 *  - POLL : DocumentController@refreshStatus saat user menarik status manual
 *           (memakai idHeader/nomorAju via CeisaService::queryDocumentStatus).
 */
class CeisaStatusMapper
{
    /**
     * Terapkan payload status CEISA ke dokumen (status, jalur, nomor daftar, idHeader).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function apply(Document $document, array $payload): Document
    {
        $document->forceFill([
            'status' => self::mapStatus($payload, $document),
            'jalur' => self::extractJalur($payload) ?? $document->jalur,
            'nomor_daftar' => data_get($payload, 'nomor_daftar')
                ?? data_get($payload, 'data.nomor_daftar')
                ?? data_get($payload, 'nomorDaftar')
                ?? $document->nomor_daftar,
            'id_header' => data_get($payload, 'idHeader')
                ?? data_get($payload, 'id_header')
                ?? data_get($payload, 'data.idHeader')
                ?? $document->id_header,
            'ceisa_response' => $payload,
            'response_at' => now(),
        ])->save();

        return $document;
    }

    /**
     * Petakan string status CEISA ke status internal dokumen.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function mapStatus(array $payload, Document $document): string
    {
        $raw = strtoupper((string) (data_get($payload, 'status') ?? data_get($payload, 'data.status') ?? ''));

        return match (true) {
            str_contains($raw, 'TERIMA'), str_contains($raw, 'ACCEPT'), str_contains($raw, 'SPPB'), str_contains($raw, 'SELESAI') => Document::STATUS_ACCEPTED,
            str_contains($raw, 'TOLAK'), str_contains($raw, 'REJECT'), str_contains($raw, 'NPP') => Document::STATUS_REJECTED,
            default => $document->status === Document::STATUS_SUBMITTING ? Document::STATUS_SUBMITTED : $document->status,
        };
    }

    /**
     * Ambil jalur pemeriksaan (H/K/M) dari payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function extractJalur(array $payload): ?string
    {
        $raw = strtoupper((string) (
            data_get($payload, 'jalur')
            ?? data_get($payload, 'data.jalur')
            ?? data_get($payload, 'kode_jalur')
            ?? data_get($payload, 'data.kode_jalur')
            ?? ''
        ));

        return match (true) {
            $raw === '' => null,
            str_contains($raw, 'HIJAU'), $raw === 'H' => Document::JALUR_HIJAU,
            str_contains($raw, 'KUNING'), $raw === 'K' => Document::JALUR_KUNING,
            str_contains($raw, 'MERAH'), $raw === 'M' => Document::JALUR_MERAH,
            default => null,
        };
    }
}
