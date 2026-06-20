<?php

namespace App\Console\Commands;

use App\Models\CeisaCredential;
use App\Models\CeisaReference;
use App\Services\CeisaService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Sinkronisasi tabel referensi (ceisa_references) dari Reference Code resmi CEISA 4.0.
 *
 * Sumber kebenaran: tabel referensi di openapi.beacukai.go.id (Negara, Pelabuhan,
 * Valuta/Kurs, Satuan, Kemasan, Kantor Pabean, HS Code, dll). Master data WAJIB
 * disinkron berkala (cron harian) agar payload Impor/Ekspor tidak terkena error
 * validasi karena referensi kedaluwarsa.
 *
 * Path resource per-tipe dibaca dari config('ceisa.reference_endpoints') sehingga
 * dapat diisi via .env tanpa ubah kode. Tipe tanpa path otomatis dilewati.
 */
class SyncCeisaReferences extends Command
{
    protected $signature = 'ceisa:sync-references {--user= : ID user pemilik kredensial H2H} {--type=* : Batasi tipe referensi tertentu}';

    protected $description = 'Sinkron tabel referensi CEISA (negara, kantor pabean, pelabuhan, kurs, dll) dari API resmi DJBC';

    public function handle(): int
    {
        $credential = $this->resolveCredential();

        if (! $credential) {
            $this->error('Tidak ada kredensial CEISA. Isi Username/Password/API Key di Pengaturan dulu.');

            return self::FAILURE;
        }

        $endpoints = (array) config('ceisa.reference_endpoints', []);
        $only = (array) $this->option('type');

        $targets = array_filter(
            $endpoints,
            fn (mixed $path, string $type) => filled($path) && (empty($only) || in_array($type, $only, true)),
            ARRAY_FILTER_USE_BOTH,
        );

        if (empty($targets)) {
            $this->warn('Belum ada endpoint referensi yang dipetakan di config ceisa.reference_endpoints.');
            $this->line('Isi via .env (CEISA_REF_NEGARA, CEISA_REF_PELABUHAN, dst) saat path Swagger "openapi" (Pabean) diketahui.');
            $this->line('Sementara itu, data dasar tetap tersedia via: php artisan db:seed --class=CeisaReferenceSeeder');

            return self::SUCCESS;
        }

        $service = CeisaService::forCredential($credential);
        $totalUpserted = 0;
        $hadError = false;

        foreach ($targets as $type => $path) {
            try {
                $count = $this->syncType($service, $type, (string) $path);
                $totalUpserted += $count;
                $this->info("✓ {$type}: {$count} baris disinkron dari {$path}");
            } catch (Throwable $e) {
                $hadError = true;
                $this->error("✗ Gagal sinkron {$type}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->line("Selesai. Total {$totalUpserted} baris referensi disinkron.");

        return $hadError ? self::FAILURE : self::SUCCESS;
    }

    private function resolveCredential(): ?CeisaCredential
    {
        if ($userId = $this->option('user')) {
            return CeisaCredential::where('user_id', $userId)->first();
        }

        return CeisaCredential::query()->latest('id')->first();
    }

    /**
     * Ambil satu tipe referensi dari CEISA, normalisasi {code,label,meta}, upsert idempoten.
     */
    private function syncType(CeisaService $service, string $type, string $path): int
    {
        $rows = $service->fetchReference($path);

        $records = [];
        $sort = 0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = $this->extractCode($row);
            $label = $this->extractLabel($row) ?? $code;

            if (! filled($code)) {
                continue;
            }

            $records[$code] = [
                'type' => $type,
                'code' => (string) $code,
                'label' => (string) $label,
                'meta' => json_encode($row, JSON_UNESCAPED_UNICODE),
                'sort' => $sort++,
                'active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        if (empty($records)) {
            return 0;
        }

        CeisaReference::upsert(
            array_values($records),
            ['type', 'code'],
            ['label', 'meta', 'sort', 'active', 'updated_at'],
        );

        return count($records);
    }

    /**
     * Temukan kolom kode dari satu baris referensi (mendukung penamaan beragam CEISA).
     *
     * @param  array<string, mixed>  $row
     */
    private function extractCode(array $row): ?string
    {
        foreach (['kode', 'code', 'id', 'value'] as $k) {
            if (filled($row[$k] ?? null) && is_scalar($row[$k])) {
                return (string) $row[$k];
            }
        }

        // Penamaan spesifik: kodeNegara, kodeKantor, kodePelabuhan, posTarif, dll.
        foreach ($row as $k => $v) {
            $lk = strtolower((string) $k);
            if (is_scalar($v) && filled($v) && (str_starts_with($lk, 'kode') || $lk === 'postarif' || $lk === 'hscode')) {
                return (string) $v;
            }
        }

        return null;
    }

    /**
     * Temukan kolom label/uraian dari satu baris referensi.
     *
     * @param  array<string, mixed>  $row
     */
    private function extractLabel(array $row): ?string
    {
        foreach (['uraian', 'nama', 'label', 'keterangan', 'deskripsi', 'description', 'name'] as $k) {
            if (filled($row[$k] ?? null) && is_scalar($row[$k])) {
                return (string) $row[$k];
            }
        }

        foreach ($row as $k => $v) {
            $lk = strtolower((string) $k);
            if (is_scalar($v) && filled($v) && (str_starts_with($lk, 'uraian') || str_starts_with($lk, 'nama'))) {
                return (string) $v;
            }
        }

        return null;
    }
}
