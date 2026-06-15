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
 * Sumber kebenaran: 59 tabel referensi di openapi.beacukai.go.id (Referensi Kantor,
 * Negara, Pelabuhan, Satuan, Kemasan, dll). Memerlukan kredensial H2H yang valid
 * (login menghasilkan token) milik user --user atau user pertama yang punya kredensial.
 *
 * CATATAN: pemetaan endpoint per-tipe referensi BELUM diisi karena path resource
 * spesifik berada di Swagger JSON 'openapi' (Pabean) yang butuh auth untuk diunduh.
 * Isi array $endpoints di bawah setelah Swagger tersedia, lalu command siap dipakai.
 */
class SyncCeisaReferences extends Command
{
    protected $signature = 'ceisa:sync-references {--user= : ID user pemilik kredensial H2H} {--type=* : Batasi tipe referensi tertentu}';

    protected $description = 'Sinkron tabel referensi CEISA (negara, kantor pabean, pelabuhan, dll) dari API resmi DJBC';

    /**
     * Pemetaan tipe referensi internal -> path resource API CEISA.
     * Lengkapi nilai path setelah Swagger JSON "openapi" (Pabean) tersedia.
     *
     * @var array<string, string|null>
     */
    private array $endpoints = [
        'negara' => null,         // mis. /v2/openapi/referensi/negara
        'kantor_pabean' => null,  // Referensi Kantor
        'pelabuhan' => null,
        'satuan' => null,         // Referensi Satuan Barang
        'kemasan' => null,        // Referensi Jenis Kemasan
        'valuta' => null,         // Referensi Valuta
        'incoterm' => null,       // Referensi Incoterm
    ];

    public function handle(): int
    {
        $credential = $this->resolveCredential();

        if (! $credential) {
            $this->error('Tidak ada kredensial CEISA. Isi Username/Password/API Key di Pengaturan dulu.');

            return self::FAILURE;
        }

        $targets = array_filter(
            $this->endpoints,
            fn (?string $path, string $type) => $path !== null
                && (empty($this->option('type')) || in_array($type, (array) $this->option('type'), true)),
            ARRAY_FILTER_USE_BOTH,
        );

        if (empty($targets)) {
            $this->warn('Belum ada endpoint referensi yang dipetakan.');
            $this->line('Lengkapi properti $endpoints di '.static::class.' setelah Swagger JSON "openapi" (Pabean) tersedia.');
            $this->line('Sementara itu, data referensi dasar tetap tersedia via CeisaReferenceSeeder (php artisan db:seed --class=CeisaReferenceSeeder).');

            return self::SUCCESS;
        }

        $service = CeisaService::forCredential($credential);

        foreach ($targets as $type => $path) {
            try {
                $this->syncType($service, $type, $path);
            } catch (Throwable $e) {
                $this->error("Gagal sinkron {$type}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    private function resolveCredential(): ?CeisaCredential
    {
        if ($userId = $this->option('user')) {
            return CeisaCredential::where('user_id', $userId)->first();
        }

        return CeisaCredential::query()->latest('id')->first();
    }

    /**
     * Ambil satu tipe referensi dari CEISA lalu upsert ke ceisa_references.
     * Implementasi pemanggilan HTTP menyusul saat path & bentuk respons diketahui.
     */
    private function syncType(CeisaService $service, string $type, string $path): void
    {
        // TODO: panggil $service untuk GET $path, normalisasi {code,label}, lalu:
        // CeisaReference::upsert([...], ['type','code'], ['label','sort','active','updated_at']);
        $this->line("(stub) siap sinkron '{$type}' dari {$path} — implementasi HTTP menyusul.");
    }
}
