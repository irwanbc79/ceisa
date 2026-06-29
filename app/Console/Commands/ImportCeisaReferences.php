<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Impor data Reference Code resmi CEISA 4.0 dari file Markdown
 * (hasil ekstraksi openapi.beacukai.go.id/portal/pages/reference) ke tabel
 * `ceisa_references`. Idempotent: upsert per (type, code).
 *
 * Usage:
 *   php artisan ceisa:import-references                       (default: docs/ceisa/reference-code.md)
 *   php artisan ceisa:import-references storage/app/ref.md
 *   php artisan ceisa:import-references path/to/file.md --dry (cek tanpa tulis DB)
 */
class ImportCeisaReferences extends Command
{
    protected $signature = 'ceisa:import-references
                            {path? : Path file Markdown referensi (relatif base_path)}
                            {--dry : Hanya parsing & laporkan jumlah, tanpa menulis ke DB}';

    protected $description = 'Impor Reference Code resmi CEISA 4.0 dari file Markdown ke ceisa_references';

    /**
     * Judul section di Markdown (setelah "## N. ") → key `type` di ceisa_references.
     * Hanya section yang dipetakan di sini yang diimpor; sisanya dilewati.
     *
     * @var array<string, string>
     */
    private array $map = [
        'Referensi Negara' => 'negara',
        'Referensi Valuta' => 'valuta',
        'Referensi Satuan Barang' => 'satuan',
        'Referensi Jenis Kemasan' => 'kemasan',
        'Referensi Incoterm' => 'incoterm',
        'Referensi Cara Bayar' => 'cara_bayar',
        'Referensi Cara Angkut' => 'cara_angkut',
        'Referensi Cara Dagang' => 'cara_dagang',
        'Referensi Kantor' => 'kantor_pabean',
        'Referensi Jenis Ekspor' => 'jenis_ekspor',
        'Referensi Bank' => 'bank',
        'Referensi Dokumen' => 'dokumen',
        'Referensi Entitas' => 'entitas',
        'Referensi Fasilitas' => 'fasilitas',
        'Referensi Fasilitas Tarif' => 'fasilitas_tarif',
        'Referensi Ijin' => 'ijin',
        'Referensi Jenis API' => 'jenis_api',
        'Referensi Jenis Identitas' => 'jenis_identitas',
        'Referensi Jenis Kontainer' => 'jenis_kontainer',
        'Referensi Tipe Kontainer' => 'tipe_kontainer',
        'Referensi Ukuran Kontainer' => 'ukuran_kontainer',
        'Referensi Jenis Transaksi Perdagangan' => 'jenis_transaksi',
        'Referensi Jenis Pengangkutan' => 'jenis_pengangkutan',
        'Referensi Jenis PIB / Prosedur' => 'jenis_prosedur',
        'Referensi Kode Jenis Impor' => 'jenis_impor',
        'Referensi Jenis Pungutan' => 'jenis_pungutan',
        'Referensi Jenis Tarif' => 'jenis_tarif',
        'Referensi Jenis TPB' => 'tpb_jenis',
        'Referensi Jenis VD' => 'jenis_vd',
        'Referensi Jenis Jaminan' => 'jenis_jaminan',
        'Referensi Jenis Tanda Pengaman' => 'jenis_tanda_pengaman',
        'Referensi Kondisi Barang' => 'kondisi_barang',
        'Referensi Daerah Asal' => 'daerah_asal',
        'Referensi Lokasi Bayar' => 'lokasi_bayar',
        'Referensi Status' => 'status_proses',
        'Referensi Status Pengusaha' => 'status_pengusaha',
        'Referensi Tutup Pu' => 'tutup_pu',
        'Referensi Komoditi Cukai' => 'komoditi_cukai',
        'Referensi Kategori Konsolidator' => 'kategori_konsolidator',
    ];

    public function handle(): int
    {
        $path = $this->argument('path') ?: 'docs/ceisa/reference-code.md';
        $full = str_starts_with($path, '/') ? $path : base_path($path);

        if (! is_file($full)) {
            $this->error("File tidak ditemukan: {$full}");

            return self::FAILURE;
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) file_get_contents($full));
        $dry = (bool) $this->option('dry');

        $currentType = null;
        $rows = []; // type => [code => label]

        foreach ($lines as $line) {
            // Heading section: "## 3. Referensi Bank"
            if (preg_match('/^##\s+\d+\.\s+(.+?)\s*$/', $line, $m)) {
                $title = trim($m[1]);
                $currentType = $this->map[$title] ?? null;

                continue;
            }

            if ($currentType === null || ! str_contains($line, '|')) {
                continue;
            }

            // Pisah kolom tabel Markdown.
            $cells = array_map('trim', explode('|', trim($line, ' |')));
            if (count($cells) < 2) {
                continue;
            }

            [$code, $label] = [$cells[0], $cells[1]];

            // Lewati baris pemisah (---) & header (KODE.../Kode).
            if ($code === '' || preg_match('/^:?-{2,}:?$/', $code)) {
                continue;
            }
            if (preg_match('/^(kode|kd)\b/i', $code) || strcasecmp($code, 'Kode') === 0) {
                continue;
            }

            $code = trim($code);
            $label = trim(preg_replace('/\s+/', ' ', $label));
            if ($code === '') {
                continue;
            }

            $rows[$currentType][$code] = $label; // dup code → last wins
        }

        if ($rows === []) {
            $this->warn('Tidak ada data yang ter-parse. Periksa format file.');

            return self::FAILURE;
        }

        $total = 0;
        foreach ($rows as $type => $items) {
            $this->line(sprintf('  %-22s %d', $type, count($items)));
            $total += count($items);

            if ($dry) {
                continue;
            }

            $now = now();
            $payload = [];
            $i = 0;
            foreach ($items as $code => $label) {
                $payload[] = [
                    'type' => $type,
                    'code' => (string) $code,
                    'label' => $label !== '' ? $label : (string) $code,
                    'meta' => null,
                    'sort' => $i++,
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($payload, 500) as $chunk) {
                DB::table('ceisa_references')->upsert($chunk, ['type', 'code'], ['label', 'sort', 'active', 'updated_at']);
            }
        }

        $this->newLine();
        $this->info(($dry ? '[DRY] ' : '').sprintf('%d tipe, %d baris referensi%s.', count($rows), $total, $dry ? ' (tidak ditulis)' : ' diimpor'));

        return self::SUCCESS;
    }
}
