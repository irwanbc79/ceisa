<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * Riwayat status, respon DJBC, dan petugas untuk dokumen pabean.
 *
 * Meniru tab "Riwayat Status" / "Riwayat Respon" / "Riwayat Petugas"
 * di Portal CEISA 4.0. Semua method membaca dari data JSON (payload &
 * ceisa_response) yang sudah ter-cast, tanpa query DB tambahan.
 */
trait HasDocumentTimeline
{
    /**
     * Timeline riwayat status pabean — meniru tab "Riwayat Status" Portal CEISA 4.0.
     * Memakai riwayat resmi dari respons CEISA bila tersedia; jika tidak, menderivasi
     * milestone dari lifecycle lokal.
     *
     * @return array<int, array{label: string, time: ?Carbon, actor: string, done: bool}>
     */
    public function statusTimeline(): array
    {
        $official = $this->extractHistoryArray();

        if ($official !== []) {
            return $official;
        }

        $createdAt = $this->created_at;
        $submittedAt = $this->submitted_at;
        $responseAt = $this->response_at;

        // Cap created_at if it's after submitted_at or response_at
        if ($submittedAt && $createdAt && $createdAt->gt($submittedAt)) {
            $createdAt = $submittedAt->copy()->subMinutes(5);
        } elseif ($responseAt && $createdAt && $createdAt->gt($responseAt)) {
            $createdAt = $responseAt->copy()->subMinutes(10);
        }

        // Cap submitted_at if it's after response_at
        if ($responseAt && $submittedAt && $submittedAt->gt($responseAt)) {
            $submittedAt = $responseAt->copy()->subMinutes(5);
        }

        $stages = [[
            'label' => 'Perekaman Dokumen',
            'time' => $createdAt,
            'actor' => $this->user?->name ?? 'Operator',
            'done' => true,
        ]];

        if ($submittedAt) {
            $stages[] = [
                'label' => 'Kirim Dokumen ke CEISA / INSW',
                'time' => $submittedAt,
                'actor' => 'SYSTEM',
                'done' => true,
            ];
        }

        if (in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_ACCEPTED, self::STATUS_REJECTED], true)) {
            $stages[] = [
                'label' => 'Validasi & Penerimaan Dokumen',
                'time' => $responseAt ?? $submittedAt,
                'actor' => 'SYSTEM',
                'done' => true,
            ];
        }

        if ($this->jalur) {
            $stages[] = [
                'label' => 'Penjaluran — '.($this->jalurInfo()['label'] ?? $this->jalur),
                'time' => $responseAt,
                'actor' => 'SYSTEM',
                'done' => true,
            ];
        }

        if ($this->status === self::STATUS_ACCEPTED) {
            $stages[] = [
                'label' => 'SPPB — Siap Pengeluaran Barang',
                'time' => $responseAt,
                'actor' => 'SYSTEM',
                'done' => true,
            ];
        } elseif ($this->status === self::STATUS_REJECTED) {
            $stages[] = [
                'label' => 'Penolakan Dokumen (NPP)',
                'time' => $responseAt,
                'actor' => 'SYSTEM',
                'done' => true,
            ];
        }

        return $stages;
    }

    /**
     * Ambil array riwayat resmi dari ceisa_response (bentuk fleksibel, toleran nama field).
     *
     * @return array<int, array{label: string, time: ?Carbon, actor: string, done: bool}>
     */
    private function extractHistoryArray(): array
    {
        $raw = data_get($this->ceisa_response, 'riwayat')
            ?? data_get($this->ceisa_response, 'riwayatStatus')
            ?? data_get($this->ceisa_response, 'histories')
            ?? data_get($this->ceisa_response, 'history')
            ?? data_get($this->ceisa_response, 'trackingHistory')
            ?? data_get($this->ceisa_response, 'data.riwayat')
            ?? data_get($this->ceisa_response, 'data.histories');

        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $items = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = data_get($row, 'status')
                ?? data_get($row, 'namaStatus')
                ?? data_get($row, 'nama_status')
                ?? data_get($row, 'keterangan')
                ?? data_get($row, 'description');

            if (! $label) {
                continue;
            }

            $items[] = [
                'label' => (string) $label,
                'time' => $this->parseDate(
                    data_get($row, 'waktu')
                    ?? data_get($row, 'tanggal')
                    ?? data_get($row, 'createdDate')
                    ?? data_get($row, 'tanggal_status')
                    ?? data_get($row, 'waktuRekam')
                ),
                'actor' => (string) (data_get($row, 'petugas')
                    ?? data_get($row, 'updatedBy')
                    ?? data_get($row, 'user')
                    ?? data_get($row, 'diperbarui_oleh')
                    ?? 'SYSTEM'),
                'done' => true,
            ];
        }

        return $items;
    }

    /**
     * Riwayat respon DJBC terstruktur (SPPB/BILLING/NPE) untuk tab "Riwayat Respon".
     * Menggabungkan webhook_logs (push) dan respon terakhir (poll), urut terbaru dulu.
     *
     * @return array<int, array{nama: string, no_surat: ?string, tanggal: ?Carbon}>
     */
    public function responseHistory(): array
    {
        $items = [];

        foreach ($this->webhookLogs as $log) {
            $p = $log->payload ?? [];

            $nama = data_get($p, 'nama_respon')
                ?? data_get($p, 'namaRespon')
                ?? data_get($p, 'kode_respon')
                ?? data_get($p, 'response')
                ?? $log->event;

            if (! $nama) {
                continue;
            }

            $items[] = [
                'nama' => strtoupper((string) $nama),
                'no_surat' => data_get($p, 'nomor_surat') ?? data_get($p, 'no_surat') ?? data_get($p, 'nomorSurat'),
                'tanggal' => $log->received_at,
            ];
        }

        $summary = $this->responseSummary();

        if ($summary && ! collect($items)->contains(fn (array $i): bool => $i['nama'] === $summary['nama'])) {
            $items[] = [
                'nama' => $summary['nama'],
                'no_surat' => $summary['no_surat'],
                'tanggal' => $summary['tanggal'],
            ];
        }

        usort($items, fn (array $a, array $b): int => ($b['tanggal']?->timestamp ?? 0) <=> ($a['tanggal']?->timestamp ?? 0));

        return $items;
    }

    /**
     * Riwayat petugas BC (aktor non-SYSTEM dari timeline) untuk tab "Riwayat Petugas".
     *
     * @return array<int, array{petugas: string, kegiatan: string, waktu: ?Carbon}>
     */
    public function petugasHistory(): array
    {
        $items = [];

        foreach ($this->statusTimeline() as $row) {
            $actor = $row['actor'] ?? '';

            if ($actor === '' || strtoupper($actor) === 'SYSTEM' || strtoupper($actor) === 'OPERATOR') {
                continue;
            }

            $items[] = [
                'petugas' => $actor,
                'kegiatan' => $row['label'],
                'waktu' => $row['time'],
            ];
        }

        return $items;
    }

    /**
     * Parse tanggal toleran (CEISA bisa kirim beragam format / null).
     */
    private function parseDate(mixed $raw): ?Carbon
    {
        if (! $raw) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
