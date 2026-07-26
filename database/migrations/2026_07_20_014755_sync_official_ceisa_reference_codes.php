<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->replace($this->officialReferences());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $types = array_keys($this->officialReferences());
        DB::table('ceisa_references')->whereIn('type', $types)->delete();

        $this->replace([
            'jenis_ekspor' => ['Biasa' => 'Ekspor Biasa', 'Akan Diimpor Kembali' => 'Akan Diimpor Kembali', 'Reekspor' => 'Reekspor', 'Reekspor ex Impor Sementara' => 'Reekspor ex Impor Sementara'],
            'kategori_ekspor' => ['Umum' => 'Umum', 'KITE' => 'KITE', 'Niper' => 'Niper', 'Barang Kiriman' => 'Barang Kiriman', 'PLB' => 'PLB'],
            'cara_dagang' => ['Biasa' => 'Biasa', 'IMB' => 'IMB', 'Lainnya' => 'Lainnya'],
            'cara_bayar' => ['Biasa/Tunai' => 'Biasa / Tunai', 'Berkala' => 'Berkala', 'Dengan Jaminan' => 'Dengan Jaminan', 'Gabungan' => 'Gabungan'],
            'cara_angkut' => ['Laut' => 'Laut', 'Kereta Api' => 'Kereta Api', 'Darat' => 'Darat', 'Udara' => 'Udara', 'Pos' => 'Pos', 'Multimoda' => 'Multimoda'],
        ]);
    }

    /**
     * @param  array<string, array<string, string>>  $groups
     */
    private function replace(array $groups): void
    {
        $now = now();

        foreach ($groups as $type => $items) {
            DB::table('ceisa_references')->where('type', $type)->delete();

            $rows = [];
            foreach ($items as $sort => $label) {
                $code = (string) $sort;

                $rows[] = [
                    'type' => $type,
                    'code' => $code,
                    'label' => $label,
                    'meta' => json_encode(['source' => 'openapi.beacukai.go.id/portal/pages/reference'], JSON_UNESCAPED_SLASHES),
                    'sort' => count($rows),
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows !== []) {
                DB::table('ceisa_references')->insert($rows);
            }
        }

        Cache::forget('ceisa.references.wizard');
    }

    /**
     * Referensi yang diverifikasi dari portal resmi DJBC pada 20 Juli 2026.
     *
     * @return array<string, array<string, string>>
     */
    private function officialReferences(): array
    {
        return [
            'jenis_ekspor' => ['1' => 'Biasa', '2' => 'Berkala', '3' => 'Fasilitas', '4' => 'Re-impor', '5' => 'Re-ekspor', '6' => 'Ex Impor Sementara', '7' => 'Ekspor Gabungan'],
            'kategori_ekspor' => [
                '10' => 'Umum', '21' => 'NIPER dengan Pembebasan', '22' => 'NIPER dengan Pengembalian', '23' => 'KITE dengan Pembebasan dan Pengembalian',
                '31' => 'Barang Perwakilan Negara Asing', '32' => 'Barang Perwakilan Badan Internasional', '33' => 'Barang Kiriman', '34' => 'Barang Pindahan',
                '35' => 'Barang Ibadah/Sosial/Pendidikan/Budaya/Olahraga/Bencana', '36' => 'Barang Cinderamata', '37' => 'Barang Contoh', '38' => 'Barang Keperluan Penelitian',
                '41' => 'TPB dari Kawasan Berikat', '42' => 'TPB dari Gudang Berikat', '43' => 'TPB dari Tempat Pameran Berikat', '44' => 'TPB dari Toko Bebas Bea',
                '45' => 'TPB dari Tempat Lelang Berikat', '46' => 'TPB dari Kawasan Daur Ulang Berikat', '51' => 'Pusat Logistik Berikat (PLB)', '61' => 'BKC yang Belum Dilunasi Cukainya',
            ],
            'cara_dagang' => [
                '1' => 'Biasa', '2' => 'IMB - Imbal Dagang', '3' => 'Pembayaran Dimuka / Advance Payment', '4' => 'Pembayaran Kemudian Bertahap',
                '5' => 'Pembayaran Kemudian Tunai', '6' => 'Sight Letter of Credit', '7' => 'Usance Letter of Credit', '8' => 'Red Clause Letter of Credit',
                '9' => 'Wessel Inkaso / Collection Draft', '10' => 'Konsinyasi / Consignment', '11' => 'Inter Company Account', '12' => 'Pembayaran di Dalam Negeri Tunai',
                '13' => 'Telegraph Transfer', '14' => 'Tanpa Pembayaran', '15' => 'Lainnya',
            ],
            'cara_bayar' => [
                '1' => 'Biasa / Tunai', '2' => 'Berkala', '3' => 'Dengan Jaminan', '4' => 'Perhitungan Kemudian', '5' => 'Konsinyasi',
                '6' => 'Usance Letter of Credit', '7' => 'Red Clause Letter of Credit', '8' => 'Inter-company Account', '9' => 'Gabungan / Lainnya',
                '10' => 'Open Account Bertahap', '11' => 'Open Account Tunai', '12' => 'Pembayaran Dalam Negeri Tunai', '13' => 'Pembayaran Dalam Negeri via Telegraph',
                '14' => 'Tanpa Pembayaran', '15' => 'Advance Payment', '16' => 'Sight Letter of Credit', '17' => 'Inkaso / Collection Draft',
            ],
            'cara_angkut' => ['1' => 'Laut', '2' => 'Kereta Api', '3' => 'Darat', '4' => 'Udara', '5' => 'Pos', '6' => 'Multimoda', '7' => 'Instalasi / Pipa', '8' => 'Perairan', '9' => 'Lainnya', '10' => 'Instalasi', '11' => 'Pipa', '12' => 'Transmisi'],
            'jenis_pengangkutan' => ['1' => 'Satu Sarana Angkut', '2' => 'Instalasi / Pipa / Transmisi', '3' => 'Angkut Lanjut', '4' => 'Angkut Lanjut Multimoda', '5' => 'Barang Bawaan Penumpang / Awak Sarana', '6' => 'Sarana Angkut Lainnya'],
            'jenis_impor' => ['1' => 'Untuk Dipakai', '2' => 'Sementara', '3' => 'Reimpor', '4' => 'TPB', '5' => 'Pelayanan Segera', '6' => 'Vooruitslag', '7' => 'Gabungan'],
            'tutup_pu' => ['11' => 'BC 1.1', '12' => 'BC 1.2', '14' => 'BC 1.4'],
        ];
    }
};
