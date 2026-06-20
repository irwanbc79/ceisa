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
                'kantor_muat' => 'IDJKT',
                'jenis_ekspor' => 'Biasa',
                'kategori_ekspor' => 'Umum',
                'cara_bayar' => 'Biasa/Tunai',
                'komoditi' => 'NON_MIGAS',
                'curah' => 'NON_CURAH',
                'valuta' => 'USD',
                'ndpbm' => 15800,
                'incoterm' => 'FOB',
                'nilai_fob' => 1500.50,
                'bruto' => 130,
                'eksportir' => ['nama' => 'PT M2B', 'npwp' => '0123456789012000', 'alamat' => 'Jakarta'],
                'penerima' => ['nama' => 'ACME', 'negara' => 'sg'],
                'pengangkutan' => ['cara_angkut' => 'Laut', 'pelabuhan_muat' => 'IDJKT', 'pelabuhan_tujuan' => 'SGSIN'],
                'pernyataan' => ['nama' => 'Irwan', 'jabatan' => 'Direktur'],
            ],
            'barang' => [
                ['hs_code' => '6109.10.00', 'uraian' => 'Kaos', 'jumlah_satuan' => 100, 'kode_satuan' => 'PCE', 'netto' => 25.5, 'nilai_fob' => 1500.50, 'kode_kemasan' => 'CT'],
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
                ['hs_code' => '8517.12.00', 'uraian' => 'Ponsel', 'jumlah_satuan' => 10, 'kode_satuan' => 'UNT', 'netto' => 5, 'nilai_cif' => 1000],
            ],
        ];

        $flat = CeisaPayloadBuilder::make()->build('BC20', $payload, 'AJU-IMP-1');

        $this->assertSame('20', $flat['kodeDokumen']);
        // Sub-blok Pungutan (barangTarif) hadir dengan jenis pungutan BM.
        $this->assertSame('BM', $flat['barang'][0]['barangTarif'][0]['kodeJenisPungutan']);
        // NPWP 15 digit -> kodeJenisIdentitas 5.
        $this->assertSame('5', $flat['entitas'][0]['kodeJenisIdentitas']);
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
