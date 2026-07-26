<?php

namespace Tests\Unit;

use App\Services\CeisaPayloadBuilder;
use PHPUnit\Framework\TestCase;

class CeisaPayloadBuilderTest extends TestCase
{
    private function bc30Payload(): array
    {
        return [
            'header' => [
                'kantor_muat' => '010700',
                'kantor_pendaftaran' => '010700',
                'kantor_ekspor' => '010700',
                'jenis_ekspor' => '1',
                'kategori_ekspor' => '10',
                'cara_bayar' => '1',
                'jenis_pengangkutan' => '1',
                'kode_lokasi' => '2',
                'tanggal_periksa' => '2026-07-20',
                'komoditi' => 'NON_MIGAS',
                'curah' => 'NON_CURAH',
                'valuta' => 'USD',
                'ndpbm' => 15800,
                'incoterm' => 'FOB',
                'nilai_fob' => 1500.50,
                'bruto' => 130,
                'eksportir' => ['nama' => 'PT M2B', 'npwp' => '0123456789012000', 'alamat' => 'Jakarta'],
                'penerima' => ['nama' => 'ACME', 'negara' => 'sg', 'alamat' => 'Singapore'],
                'pengangkutan' => [
                    'cara_angkut' => '1',
                    'sarana_angkut' => 'MV TEST',
                    'voy_flight' => 'V001',
                    'bendera' => 'ID',
                    'pelabuhan_muat' => 'IDJKT',
                    'pelabuhan_ekspor' => 'IDJKT',
                    'pelabuhan_tujuan' => 'SGSIN',
                    'tanggal_ekspor' => '2026-07-22',
                ],
                'asuransi' => ['jenis' => 'DN', 'nilai' => 0],
                'pernyataan' => ['nama' => 'Irwan', 'jabatan' => 'Direktur', 'kota' => 'Medan'],
            ],
            'barang' => [
                ['hs_code' => '6109.10.00', 'uraian' => 'Kaos', 'jumlah_satuan' => 100, 'kode_satuan' => 'PCE', 'netto' => 25.5, 'nilai_fob' => 1500.50, 'jumlah_kemasan' => 1, 'kode_kemasan' => 'CT', 'merk_kemasan' => 'M2B'],
            ],
            'dokumen' => [
                ['kode_dokumen' => '380', 'nomor_dokumen' => 'INV-001', 'tanggal_dokumen' => '2026-07-20'],
                ['kode_dokumen' => '217', 'nomor_dokumen' => 'PL-001', 'tanggal_dokumen' => '2026-07-20'],
            ],
        ];
    }

    public function test_bc30_builds_modular_blocks(): void
    {
        $flat = CeisaPayloadBuilder::make()->build('BC30', $this->bc30Payload(), '301012ABC20260617000001');

        $this->assertSame('S', $flat['asalData']);
        $this->assertSame('30', $flat['kodeDokumen']);

        // Entitas: Eksportir(2), NPWP Pemusatan(7), Penerima(8), Pembeli(6) = 4 baris.
        $this->assertCount(4, $flat['entitas']);
        $this->assertSame(['2', '7', '8', '6'], array_column($flat['entitas'], 'kodeEntitas'));
        // NPWP 16 digit -> kodeJenisIdentitas 6; posTarif & negara dinormalisasi.
        $this->assertSame('6', $flat['entitas'][0]['kodeJenisIdentitas']);
        $this->assertSame('SG', $flat['entitas'][2]['kodeNegara']);

        // Barang: posTarif hanya digit, hargaSatuan = fob/qty auto.
        $this->assertSame('61091000', $flat['barang'][0]['posTarif']);
        $this->assertEqualsWithDelta(15.005, $flat['barang'][0]['hargaSatuan'], 0.0001);

        // Kemasan diagregasi per kode (CT) dengan total jumlah dari barang.
        $this->assertCount(1, $flat['kemasan']);
        $this->assertSame('CT', $flat['kemasan'][0]['kodeJenisKemasan']);
        $this->assertEqualsWithDelta(1.0, $flat['kemasan'][0]['jumlahKemasan'], 0.0001);

        // Pengangkut & dokumen (Invoice + Packing List).
        $this->assertSame('1', $flat['pengangkut'][0]['kodeCaraAngkut']);
        $this->assertCount(2, $flat['dokumen']);
    }

    public function test_bc20_builds_pungutan_block_per_barang(): void
    {
        $payload = [
            'header' => [
                'importir' => ['nama' => 'PT Importir', 'npwp' => '012345678901000', 'alamat' => 'Jkt'],
                'pemasok' => ['nama' => 'Acme Inc', 'negara' => 'us'],
                'pengangkutan' => ['pelabuhan_muat' => 'SGSIN', 'pelabuhan_bongkar' => 'IDTPP'],
                'valuta' => 'USD',
                'nilai_cif' => 1000,
            ],
            'barang' => [
                ['hs_code' => '8517.12.00', 'uraian' => 'Ponsel', 'jumlah_satuan' => 10, 'kode_satuan' => 'UNT', 'netto' => 5, 'nilai_cif' => 1000, 'tarif_bm' => 0, 'tarif_ppn' => 11, 'tarif_pph' => 2.5],
            ],
        ];

        $flat = CeisaPayloadBuilder::make()->build('BC20', $payload, 'AJU-IMP-1');

        $this->assertSame('20', $flat['kodeDokumen']);
        // Sub-blok Pungutan (barangTarif) hadir dengan jenis pungutan BM.
        $this->assertSame('BM', $flat['barang'][0]['barangTarif'][0]['kodeJenisPungutan']);
        // barangVd wajib hadir per JSON Schema BC 2.0 (barang.required) — kosong = tanpa VD.
        $this->assertArrayHasKey('barangVd', $flat['barang'][0]);
        $this->assertSame([], $flat['barang'][0]['barangVd']);
        // NPWP 15 digit -> kodeJenisIdentitas 5.
        $this->assertSame('5', $flat['entitas'][0]['kodeJenisIdentitas']);

        // Pungutan lengkap: BM + PPN + PPH, seluruh tarif berasal dari operator.
        $this->assertSame(
            ['BM', 'PPN', 'PPH'],
            array_column($flat['barang'][0]['barangTarif'], 'kodeJenisPungutan'),
        );
        $this->assertSame(11.0, $flat['barang'][0]['barangTarif'][1]['tarif']);
    }

    public function test_bc20_includes_ppjk_entity_and_house_bl_when_provided(): void
    {
        $payload = [
            'header' => [
                'importir' => ['nama' => 'PT Importir', 'npwp' => '012345678901000', 'alamat' => 'Jkt'],
                'pemasok' => ['nama' => 'Acme Inc', 'negara' => 'us'],
                'ppjk' => ['nama' => 'PT Mora Multi Berkah', 'npwp' => '960208833125000', 'alamat' => 'Medan'],
                'pengangkutan' => ['pelabuhan_muat' => 'SGSIN', 'pelabuhan_bongkar' => 'IDTPP', 'cara_angkut' => 'Laut'],
                'dokumen_pengangkutan' => ['awb_bl' => 'BL-998877', 'tanggal' => '2026-06-20'],
                'valuta' => 'USD',
                'nilai_cif' => 1000,
            ],
            'barang' => [
                ['hs_code' => '8517.12.00', 'uraian' => 'Ponsel', 'jumlah_satuan' => 10, 'kode_satuan' => 'UNT', 'netto' => 5, 'nilai_cif' => 1000, 'tarif_bm' => 0, 'tarif_ppn' => 11, 'tarif_pph' => 2.5],
            ],
            'dokumen' => [
                ['kode_dokumen' => '380', 'nomor_dokumen' => 'INV-002', 'tanggal_dokumen' => '2026-06-20'],
                ['kode_dokumen' => '705', 'nomor_dokumen' => 'BL-998877', 'tanggal_dokumen' => '2026-06-20'],
            ],
        ];

        $flat = CeisaPayloadBuilder::make()->build('BC20', $payload, 'AJU-IMP-2');

        // Entitas PPJK (kode 4) ikut tergenerate setelah importir(1), pemilik(7), pengirim(9).
        $this->assertContains('4', array_column($flat['entitas'], 'kodeEntitas'));

        // Dokumen House-BL (705 untuk laut) berasal dari input operator.
        $this->assertSame(['380', '705'], array_column($flat['dokumen'], 'kodeDokumen'));
        $this->assertSame('BL-998877', $flat['dokumen'][1]['nomorDokumen']);
    }

    public function test_bc20_kode_kantor_prefers_header_over_pelabuhan_bongkar(): void
    {
        $payload = [
            'header' => [
                'kode_kantor' => '040300',
                'importir' => ['nama' => 'PT Impor', 'npwp' => '012345678901000', 'alamat' => 'Jakarta'],
                'pemasok' => ['nama' => 'ACME', 'negara' => 'SG'],
                'valuta' => 'USD',
                'ndpbm' => 15800,
                'nilai_cif' => 1000,
                'pengangkutan' => ['pelabuhan_muat' => 'SGSIN', 'pelabuhan_bongkar' => 'IDTPP'],
            ],
            'barang' => [
                ['hs_code' => '39269099', 'uraian' => 'Plastic Parts', 'jumlah_satuan' => 10, 'kode_satuan' => 'PCE', 'netto' => 5, 'nilai_cif' => 1000],
            ],
        ];

        $flat = CeisaPayloadBuilder::make()->build('BC20', $payload, str_repeat('0', 26));

        // Field wizard baru (step Data Header): kantor pabean eksplisit menang.
        $this->assertSame('040300', $flat['kodeKantor']);

        // Tanpa kode_kantor: builder tidak menebak kode kantor dari kode pelabuhan.
        unset($payload['header']['kode_kantor']);
        $flat = CeisaPayloadBuilder::make()->build('BC20', $payload, str_repeat('0', 26));
        $this->assertSame('', $flat['kodeKantor']);
    }

    public function test_cara_angkut_code_mapping(): void
    {
        $b = CeisaPayloadBuilder::make();
        $this->assertSame('1', $b->caraAngkutCode('Laut'));
        $this->assertSame('4', $b->caraAngkutCode('Udara'));
        $this->assertSame('1', $b->caraAngkutCode('Tidak dikenal'));
        $this->assertSame('1', $b->caraAngkutCode(null));
    }
}
