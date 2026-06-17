<?php

namespace App\Services;

/**
 * Generator payload JSON H2H CEISA 4.0 secara MODULAR.
 *
 * Mengubah format bersarang database M2B (header + barang) menjadi struktur flat
 * JSON schema CEISA. Disusun per-blok (Entitas, Pengangkut, Barang, Kemasan,
 * Pungutan, Dokumen) lalu digabung — sesuai saran integrasi DJBC agar
 * troubleshooting validation error per-blok lebih mudah dilacak.
 *
 * Output dijaga identik dengan transformasi sebelumnya (lihat CeisaFlowTest).
 */
class CeisaPayloadBuilder
{
    public static function make(): self
    {
        return new self;
    }

    /**
     * Bangun payload sesuai jenis dokumen.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function build(string $type, array $payload, string $nomorAju): array
    {
        return match ($type) {
            'BC30' => $this->buildBc30($payload, $nomorAju),
            'BC20', 'BC24' => $this->buildBc20($payload, $nomorAju, $type),
            'TPB' => $this->buildTpb($payload, $nomorAju),
            'RUSH' => $this->buildRush($payload, $nomorAju),
            default => $payload,
        };
    }

    /**
     * Kode cara angkut CEISA dari label (1=Laut..5=Pos).
     */
    public function caraAngkutCode(?string $label): string
    {
        return match ($label ?? 'Laut') {
            'Laut' => '1',
            'Kereta Api' => '2',
            'Darat' => '3',
            'Udara' => '4',
            'Pos' => '5',
            default => '1',
        };
    }

    /**
     * Kode jenis identitas: 6 untuk NPWP 16 digit, selain itu 5.
     */
    private function jenisIdentitas(?string $npwp): string
    {
        return strlen(preg_replace('/\D/', '', $npwp ?? '')) === 16 ? '6' : '5';
    }

    /**
     * @param  array<string, mixed>  $npwpSource
     */
    private function digits(?string $value): string
    {
        return preg_replace('/\D/', '', $value ?? '');
    }

    // ───────────────────────────── BC 3.0 (Ekspor) ─────────────────────────────

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function buildBc30(array $payload, string $nomorAju): array
    {
        $header = $payload['header'] ?? [];
        $barang = $payload['barang'] ?? [];

        $flat = [
            'asalData' => 'S',
            'kodeDokumen' => '30',
            'disclaimer' => '1',
            'nomorAju' => $nomorAju,
            'tanggalAju' => date('Y-m-d'),

            'kodeKantor' => $header['kantor_muat'] ?? '',
            'kodeKantorMuat' => $header['kantor_muat'] ?? '',
            'kodeKantorEkspor' => $header['kantor_muat'] ?? '',
            'kodeJenisEkspor' => $header['jenis_ekspor'] ?? '',
            'kodeKategoriEkspor' => $header['kategori_ekspor'] ?? '',
            'kodeCaraDagang' => $header['cara_dagang'] ?? '',
            'kodeCaraBayar' => $header['cara_bayar'] ?? '',
            'flagMigas' => ($header['komoditi'] ?? '') === 'MIGAS' ? '1' : '2',
            'flagCurah' => ($header['curah'] ?? '') === 'CURAH' ? '1' : '2',

            'kodeValuta' => $header['valuta'] ?? '',
            'ndpbm' => isset($header['ndpbm']) ? (float) $header['ndpbm'] : 0.0,
            'kodeIncoterm' => $header['incoterm'] ?? '',
            'fob' => isset($header['nilai_fob']) ? (float) $header['nilai_fob'] : 0.0,
            'freight' => isset($header['freight']) ? (float) $header['freight'] : 0.0,

            'asuransi' => isset($header['asuransi']['nilai']) ? (float) $header['asuransi']['nilai'] : 0.0,
            'kodeAsuransi' => $header['asuransi']['jenis'] ?? '',

            'bruto' => isset($header['bruto']) ? (float) $header['bruto'] : 0.0,
            'netto' => array_reduce($barang, fn ($carry, $item) => $carry + (float) ($item['netto'] ?? 0.0), 0.0),

            'namaTtd' => $header['pernyataan']['nama'] ?? '',
            'jabatanTtd' => $header['pernyataan']['jabatan'] ?? '',
            'kotaTtd' => $header['pernyataan']['kota'] ?? '',
            'tanggalTtd' => $header['pernyataan']['tanggal'] ?? date('Y-m-d'),

            'flagBarkir' => 'T',
            'jumlahKontainer' => 0,
            'kodeLokasi' => '2',
            'tanggalPeriksa' => date('Y-m-d', strtotime('+1 day')),

            'kodeJenisPengangkutan' => $this->caraAngkutCode($header['pengangkutan']['cara_angkut'] ?? 'Laut'),
            'kodePelEkspor' => $header['pengangkutan']['pelabuhan_muat'] ?? '',
            'kodePelMuat' => $header['pengangkutan']['pelabuhan_muat'] ?? '',
            'kodePelTujuan' => $header['pengangkutan']['pelabuhan_tujuan'] ?? '',

            'entitas' => [],
            'barang' => [],
            'kemasan' => [],
            'kontainer' => [],
            'dokumen' => [],
            'pengangkut' => [],
            'bankDevisa' => [],
            'kesiapanBarang' => [],
        ];

        $flat['entitas'] = $this->entitasBc30($header);
        $flat['barang'] = $this->barangBc30($barang, $flat['kodeJenisEkspor']);
        $flat['kemasan'] = $this->kemasanFromBarang($flat['barang']);
        $flat['pengangkut'] = $this->pengangkutBc30($header);
        $flat['bankDevisa'] = [[
            'seriBank' => 1,
            'kodeBank' => '008',
            'namaBank' => $header['bank_devisa'] ?? 'Bank Mandiri',
        ]];
        $flat['kesiapanBarang'] = [[
            'kodeJenisGudang' => '4',
            'namaPic' => $flat['namaTtd'],
            'alamat' => $header['eksportir']['alamat'] ?? 'Jakarta',
            'nomorTelpPic' => '08123456789',
            'lokasiSiapPeriksa' => 'Gudang Eksportir',
            'tanggalPkb' => date('Y-m-d'),
            'waktuSiapPeriksa' => date('Y-m-d\TH:i:s.000\Z'),
        ]];
        $flat['dokumen'] = [
            ['seriDokumen' => 1, 'kodeDokumen' => '380', 'nomorDokumen' => 'INV-'.$nomorAju, 'tanggalDokumen' => date('Y-m-d')],
            ['seriDokumen' => 2, 'kodeDokumen' => '217', 'nomorDokumen' => 'PL-'.$nomorAju, 'tanggalDokumen' => date('Y-m-d')],
        ];

        return $flat;
    }

    /**
     * Blok Entitas ekspor: Eksportir (2), NPWP Pemusatan (7), Penerima (8), Pembeli (6).
     *
     * @param  array<string, mixed>  $header
     * @return array<int, array<string, mixed>>
     */
    protected function entitasBc30(array $header): array
    {
        $entitas = [];

        if (isset($header['eksportir'])) {
            $npwp = $header['eksportir']['npwp'] ?? '';
            foreach (['2', '7'] as $i => $kode) {
                $entitas[] = [
                    'seriEntitas' => $i + 1,
                    'kodeEntitas' => $kode,
                    'namaEntitas' => $header['eksportir']['nama'] ?? '',
                    'alamatEntitas' => $header['eksportir']['alamat'] ?? '',
                    'nomorIdentitas' => $this->digits($npwp),
                    'kodeJenisIdentitas' => $this->jenisIdentitas($npwp),
                ];
            }
        }

        if (isset($header['penerima'])) {
            foreach (['8', '6'] as $i => $kode) {
                $entitas[] = [
                    'seriEntitas' => count($entitas) + 1,
                    'kodeEntitas' => $kode,
                    'namaEntitas' => $header['penerima']['nama'] ?? '',
                    'alamatEntitas' => $header['penerima']['alamat'] ?? '',
                    'kodeNegara' => strtoupper($header['penerima']['negara'] ?? ''),
                ];
            }
        }

        return $entitas;
    }

    /**
     * Blok Barang ekspor (termasuk harga satuan FOB auto).
     *
     * @param  array<int, array<string, mixed>>  $barang
     * @return array<int, array<string, mixed>>
     */
    protected function barangBc30(array $barang, string $kodeJenisEkspor): array
    {
        $out = [];
        foreach ($barang as $i => $item) {
            $fob = isset($item['nilai_fob']) ? (float) $item['nilai_fob'] : 0.0;
            $qty = isset($item['jumlah_satuan']) ? (float) $item['jumlah_satuan'] : 0.0;

            $out[] = [
                'seriBarang' => $i + 1,
                'posTarif' => $this->digits($item['hs_code'] ?? ''),
                'uraian' => $item['uraian'] ?? '',
                'merk' => $item['merk'] ?? '',
                'tipe' => $item['tipe'] ?? '',
                'ukuran' => $item['ukuran'] ?? '',
                'kodeNegaraAsal' => strtoupper($item['negara_asal'] ?? 'ID'),
                'kodeDaerahAsal' => $item['daerah_asal'] ?? '',
                'jumlahSatuan' => $qty,
                'kodeSatuanBarang' => $item['kode_satuan'] ?? '',
                'jumlahKemasan' => isset($item['jumlah_kemasan']) ? (float) $item['jumlah_kemasan'] : 1.0,
                'kodeJenisKemasan' => $item['kode_kemasan'] ?? 'CT',
                'netto' => isset($item['netto']) ? (float) $item['netto'] : 0.0,
                'volume' => isset($item['volume']) ? (float) $item['volume'] : 0.0,
                'fob' => $fob,
                'hargaSatuan' => $qty > 0 ? round($fob / $qty, 4) : 0.0,
                'hargaPatokan' => 0.0,
                'spesifikasiLain' => '',
                'kodeJenisEkspor' => $kodeJenisEkspor,
            ];
        }

        return $out;
    }

    /**
     * Blok Kemasan diagregasi dari daftar barang (per kode jenis kemasan).
     *
     * @param  array<int, array<string, mixed>>  $barangFlat
     * @return array<int, array<string, mixed>>
     */
    protected function kemasanFromBarang(array $barangFlat): array
    {
        $kemasanCodes = [];
        $seriKemasan = 1;
        foreach ($barangFlat as $b) {
            $kCode = $b['kodeJenisKemasan'];
            if (! isset($kemasanCodes[$kCode])) {
                $kemasanCodes[$kCode] = [
                    'seriKemasan' => $seriKemasan++,
                    'jumlahKemasan' => 0.0,
                    'kodeJenisKemasan' => $kCode,
                    'merkKemasan' => 'M2B PKG',
                ];
            }
            $kemasanCodes[$kCode]['jumlahKemasan'] += $b['jumlahKemasan'];
        }

        if (empty($kemasanCodes)) {
            return [$this->kemasanDefault()];
        }

        return array_values($kemasanCodes);
    }

    /**
     * @return array<string, mixed>
     */
    private function kemasanDefault(): array
    {
        return [
            'seriKemasan' => 1,
            'jumlahKemasan' => 1,
            'kodeJenisKemasan' => 'CT',
            'merkKemasan' => 'UNMARKED',
        ];
    }

    /**
     * @param  array<string, mixed>  $header
     * @return array<int, array<string, mixed>>
     */
    protected function pengangkutBc30(array $header): array
    {
        if (isset($header['pengangkutan'])) {
            return [[
                'seriPengangkut' => 1,
                'namaPengangkut' => $header['pengangkutan']['sarana_angkut'] ?? 'MV Sinar Bintang',
                'nomorPengangkut' => $header['pengangkutan']['voy_flight'] ?? 'V-1024',
                'kodeBendera' => 'ID',
                'kodeCaraAngkut' => $this->caraAngkutCode($header['pengangkutan']['cara_angkut'] ?? 'Laut'),
            ]];
        }

        return [[
            'seriPengangkut' => 1,
            'namaPengangkut' => 'MV STAR',
            'nomorPengangkut' => 'V-100',
            'kodeBendera' => 'ID',
            'kodeCaraAngkut' => '1',
        ]];
    }

    // ───────────────────────────── BC 2.0 / 2.4 (Impor) ─────────────────────────

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function buildBc20(array $payload, string $nomorAju, string $type): array
    {
        $header = $payload['header'] ?? [];
        $barang = $payload['barang'] ?? [];

        $flat = [
            'asalData' => 'S',
            'kodeDokumen' => $type === 'BC24' ? '24' : '20',
            'disclaimer' => '1',
            'nomorAju' => $nomorAju,
            'tanggalAju' => date('Y-m-d'),

            'kodeKantor' => $header['pengangkutan']['pelabuhan_bongkar'] ?? '040100',
            'kodeJenisImpor' => $header['jenis_impor'] ?? '1',
            'kodeCaraBayar' => $header['cara_bayar'] ?? '1',
            'kodeValuta' => $header['valuta'] ?? 'USD',
            'ndpbm' => isset($header['ndpbm']) ? (float) $header['ndpbm'] : 0.0,
            'kodeIncoterm' => $header['incoterm'] ?? 'FOB',
            'kodePelMuat' => $header['pengangkutan']['pelabuhan_muat'] ?? '',
            'kodePelTujuan' => $header['pengangkutan']['pelabuhan_bongkar'] ?? '',
            'kodeTps' => $header['pengangkutan']['tps'] ?? '',
            'kodeTutupPu' => $header['kode_tutup_pu'] ?? '11',

            'tanggalTiba' => $header['pengangkutan']['tanggal_tiba'] ?? date('Y-m-d', strtotime('+3 days')),
            'jumlahKontainer' => 0,

            // Nilai CIF dipecah: pakai nilai eksplisit bila ada, jika tidak baru estimasi proporsional.
            'fob' => $this->cifComponent($header, 'fob', 0.9),
            'asuransi' => $this->cifComponent($header, 'asuransi', 0.01),
            'freight' => $this->cifComponent($header, 'freight', 0.09),
            'cif' => isset($header['nilai_cif']) ? (float) $header['nilai_cif'] : 0.0,

            'bruto' => isset($header['bruto'])
                ? (float) $header['bruto']
                : array_reduce($barang, fn ($carry, $item) => $carry + (float) ($item['netto'] ?? 0.0), 0.0) * 1.1,
            'netto' => array_reduce($barang, fn ($carry, $item) => $carry + (float) ($item['netto'] ?? 0.0), 0.0),

            'namaTtd' => $header['pernyataan']['nama'] ?? $header['importir']['nama'] ?? 'M2B Staff',
            'jabatanTtd' => $header['pernyataan']['jabatan'] ?? 'Manager',
            'kotaTtd' => $header['pernyataan']['kota'] ?? 'Jakarta',
            'tanggalTtd' => date('Y-m-d'),

            'biayaTambahan' => isset($header['biaya_tambahan']) ? (float) $header['biaya_tambahan'] : 0.0,
            'biayaPengurang' => isset($header['biaya_pengurang']) ? (float) $header['biaya_pengurang'] : 0.0,

            'entitas' => [],
            'barang' => [],
            'kemasan' => [],
            'kontainer' => [],
            'dokumen' => [],
            'pengangkut' => [],
        ];

        $flat['entitas'] = $this->entitasBc20($header);
        $flat['barang'] = $this->barangBc20($barang, $header);
        $flat['kemasan'] = [$this->kemasanDefault()];
        $flat['pengangkut'] = [$this->pengangkutImpor($header)];
        $flat['dokumen'] = [$this->dokumenInvoice($nomorAju)];

        return $flat;
    }

    /**
     * Komponen nilai (fob/asuransi/freight): pakai nilai eksplisit dari form
     * bila diisi, jika tidak estimasi proporsional dari CIF.
     *
     * @param  array<string, mixed>  $header
     */
    private function cifComponent(array $header, string $key, float $ratio): float
    {
        if (isset($header[$key]) && $header[$key] !== '' && $header[$key] !== null) {
            return (float) $header[$key];
        }

        return isset($header['nilai_cif']) ? (float) $header['nilai_cif'] * $ratio : 0.0;
    }

    /**
     * @param  array<string, mixed>  $header
     * @return array<int, array<string, mixed>>
     */
    protected function entitasBc20(array $header): array
    {
        $entitas = [];

        if (isset($header['importir'])) {
            $npwp = $header['importir']['npwp'] ?? '';
            $entitas[] = [
                'seriEntitas' => 1,
                'kodeEntitas' => '1',
                'namaEntitas' => $header['importir']['nama'] ?? '',
                'alamatEntitas' => $header['importir']['alamat'] ?? '',
                'nomorIdentitas' => $this->digits($npwp),
                'kodeJenisIdentitas' => $this->jenisIdentitas($npwp),
                'nibEntitas' => $header['importir']['nib'] ?? '',
                'kodeJenisApi' => $header['importir']['jenis_api'] ?? '01',
                'kodeStatus' => $header['importir']['status'] ?? '1',
            ];
            $entitas[] = [
                'seriEntitas' => 2,
                'kodeEntitas' => '7',
                'namaEntitas' => $header['importir']['nama'] ?? '',
                'alamatEntitas' => $header['importir']['alamat'] ?? '',
                'nomorIdentitas' => $this->digits($npwp),
                'kodeJenisIdentitas' => $this->jenisIdentitas($npwp),
                'kodeAfiliasi' => 'TAH',
            ];
        }

        if (isset($header['pemasok'])) {
            $entitas[] = [
                'seriEntitas' => 3,
                'kodeEntitas' => '9',
                'namaEntitas' => $header['pemasok']['nama'] ?? '',
                'alamatEntitas' => 'Overseas Address',
                'kodeNegara' => strtoupper($header['pemasok']['negara'] ?? 'US'),
            ];
        }

        return $entitas;
    }

    /**
     * Blok Barang impor + sub-blok Pungutan (barangTarif: BM, dst).
     *
     * @param  array<int, array<string, mixed>>  $barang
     * @param  array<string, mixed>  $header
     * @return array<int, array<string, mixed>>
     */
    protected function barangBc20(array $barang, array $header): array
    {
        $out = [];
        foreach ($barang as $i => $item) {
            $itemCif = isset($item['nilai_cif']) ? (float) $item['nilai_cif'] : 0.0;
            $qty = isset($item['jumlah_satuan']) ? (float) $item['jumlah_satuan'] : 1.0;

            $out[] = [
                'seriBarang' => $i + 1,
                'posTarif' => $this->digits($item['hs_code'] ?? ''),
                'uraian' => $item['uraian'] ?? '',
                'merk' => $item['merk'] ?? 'UNBRANDED',
                'tipe' => $item['tipe'] ?? 'STANDARD',
                'kodeJenisKemasan' => $item['kode_kemasan'] ?? 'CT',
                'kodeSatuanBarang' => $item['kode_satuan'] ?? 'UNT',
                'jumlahKemasan' => 1.0,
                'jumlahSatuan' => $qty,
                'hargaSatuan' => $qty > 0 ? round($itemCif / $qty, 4) : 0.0,
                'fob' => $itemCif * 0.9,
                'asuransi' => $itemCif * 0.01,
                'freight' => $itemCif * 0.09,
                'cif' => $itemCif,
                'saldoAwal' => 0.0,
                'saldoAkhir' => 0.0,
                'metodePenentuanNilai' => 'Metode 1',
                'alasanMetodePenentuanNilai' => null,
                'statementPerbedaanHarga' => 'T',
                'bruto' => isset($item['netto']) ? (float) $item['netto'] * 1.1 : 1.1,
                'netto' => isset($item['netto']) ? (float) $item['netto'] : 1.0,
                'kodeNegaraAsal' => strtoupper($header['pemasok']['negara'] ?? 'US'),
                'barangTarif' => [
                    [
                        'seriBarang' => $i + 1,
                        'kodeJenisTarif' => '1',
                        'kodeJenisPungutan' => 'BM',
                        'kodeFasilitasTarif' => '1',
                        'tarif' => 0.0,
                        'tarifFasilitas' => 0.0,
                        'nilaiBayar' => 0.0,
                        'nilaiFasilitas' => 0.0,
                    ],
                ],
            ];
        }

        return $out;
    }

    // ───────────────────────────── TPB ─────────────────────────────

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function buildTpb(array $payload, string $nomorAju): array
    {
        $header = $payload['header'] ?? [];
        $barang = $payload['barang'] ?? [];

        $flat = [
            'asalData' => 'S',
            'kodeDokumen' => '23',
            'disclaimer' => '1',
            'nomorAju' => $nomorAju,
            'tanggalAju' => date('Y-m-d'),

            'kodeKantor' => $header['kode_kantor'] ?? '040100',
            'kodeValuta' => $header['valuta'] ?? 'USD',
            'nilaiBarang' => isset($header['nilai_barang']) ? (float) $header['nilai_barang'] : 0.0,

            'entitas' => [],
            'barang' => [],
            'kemasan' => [],
            'pengangkut' => [],
            'dokumen' => [],
        ];

        if (isset($header['pengusaha_tpb'])) {
            $npwp = $header['pengusaha_tpb']['npwp'] ?? '';
            $flat['entitas'][] = [
                'seriEntitas' => 1,
                'kodeEntitas' => '3',
                'namaEntitas' => $header['pengusaha_tpb']['nama'] ?? '',
                'alamatEntitas' => $header['pengusaha_tpb']['alamat'] ?? '',
                'nomorIdentitas' => $this->digits($npwp),
                'kodeJenisIdentitas' => $this->jenisIdentitas($npwp),
            ];
        }

        $flat['barang'] = $this->barangNilaiSederhana($barang);
        $flat['kemasan'] = [$this->kemasanDefault()];
        
        $p = $header['pengangkutan'] ?? [];
        $flat['pengangkut'] = [[
            'seriPengangkut' => 1,
            'namaPengangkut' => $p['sarana_angkut'] ?? 'MV CONTAINER',
            'nomorPengangkut' => $p['voy_flight'] ?? 'V-100',
            'kodeBendera' => $p['bendera'] ?? 'US',
            'kodeCaraAngkut' => $this->caraAngkutCode($p['cara_angkut'] ?? 'Laut'),
        ]];
        
        $flat['dokumen'] = [$this->dokumenInvoice($nomorAju)];

        return $flat;
    }

    // ───────────────────────────── Rush Handling ─────────────────────────────

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function buildRush(array $payload, string $nomorAju): array
    {
        $header = $payload['header'] ?? [];
        $barang = $payload['barang'] ?? [];

        $flat = [
            'asalData' => 'S',
            'kodeDokumen' => 'RH',
            'disclaimer' => '1',
            'nomorAju' => $nomorAju,
            'tanggalAju' => date('Y-m-d'),

            'kodeKantor' => $header['kode_kantor'] ?? '040100',
            'alasanSegera' => $header['alasan_rush_handling'] ?? '',

            'entitas' => [],
            'barang' => [],
            'kemasan' => [],
            'pengangkut' => [],
            'dokumen' => [],
        ];

        if (isset($header['pemohon'])) {
            $npwp = $header['pemohon']['npwp'] ?? '';
            $flat['entitas'][] = [
                'seriEntitas' => 1,
                'kodeEntitas' => '5',
                'namaEntitas' => $header['pemohon']['nama'] ?? '',
                'alamatEntitas' => $header['pemohon']['alamat'] ?? '',
                'nomorIdentitas' => $this->digits($npwp),
                'kodeJenisIdentitas' => $this->jenisIdentitas($npwp),
            ];
        }

        $flat['barang'] = $this->barangNilaiSederhana($barang);
        $flat['kemasan'] = [[
            'seriKemasan' => 1,
            'jumlahKemasan' => (int) ($header['kemasan']['jumlah'] ?? 1),
            'kodeJenisKemasan' => $header['kemasan']['jenis'] ?? 'CT',
            'merkKemasan' => 'UNMARKED',
        ]];
        
        $p = $header['pengangkutan'] ?? [];
        $flat['pengangkut'] = [[
            'seriPengangkut' => 1,
            'namaPengangkut' => $p['sarana'] ?? 'MV CARGO',
            'nomorPengangkut' => $p['flight_no'] ?? 'V-100',
            'kodeBendera' => $p['bendera'] ?? 'US',
            'kodeCaraAngkut' => $this->caraAngkutCode($p['cara_angkut'] ?? 'Udara'),
        ]];
        
        $flat['dokumen'] = [[
            'seriDokumen' => 1,
            'kodeDokumen' => '740',
            'nomorDokumen' => $header['dokumen_pengangkutan']['awb_bl'] ?? 'AWB-100',
            'tanggalDokumen' => $header['dokumen_pengangkutan']['tanggal'] ?? date('Y-m-d'),
        ]];

        return $flat;
    }

    // ───────────────────────────── Blok bersama ─────────────────────────────

    /**
     * Blok barang sederhana (TPB & Rush): hanya nilai barang.
     *
     * @param  array<int, array<string, mixed>>  $barang
     * @return array<int, array<string, mixed>>
     */
    protected function barangNilaiSederhana(array $barang): array
    {
        $out = [];
        foreach ($barang as $i => $item) {
            $val = isset($item['nilai_barang']) ? (float) $item['nilai_barang'] : 0.0;
            $qty = isset($item['jumlah_satuan']) ? (float) $item['jumlah_satuan'] : 1.0;
            $out[] = [
                'seriBarang' => $i + 1,
                'posTarif' => $this->digits($item['hs_code'] ?? ''),
                'uraian' => $item['uraian'] ?? '',
                'jumlahSatuan' => $qty,
                'kodeSatuanBarang' => $item['kode_satuan'] ?? 'UNT',
                'netto' => isset($item['netto']) ? (float) $item['netto'] : 1.0,
                'nilaiBarang' => $val,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function pengangkutImporDefault(): array
    {
        return [
            'seriPengangkut' => 1,
            'namaPengangkut' => 'MV CONTAINER',
            'nomorPengangkut' => 'V-100',
            'kodeBendera' => 'US',
            'kodeCaraAngkut' => '1',
        ];
    }

    /**
     * Blok pengangkut impor dari data form (fallback default bila kosong).
     *
     * @param  array<string, mixed>  $header
     * @return array<string, mixed>
     */
    private function pengangkutImpor(array $header): array
    {
        $p = $header['pengangkutan'] ?? [];

        return [
            'seriPengangkut' => 1,
            'namaPengangkut' => $p['sarana_angkut'] ?? 'MV CONTAINER',
            'nomorPengangkut' => $p['voy_flight'] ?? 'V-100',
            'kodeBendera' => $p['bendera'] ?? 'US',
            'kodeCaraAngkut' => $this->caraAngkutCode($p['cara_angkut'] ?? 'Laut'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dokumenInvoice(string $nomorAju): array
    {
        return [
            'seriDokumen' => 1,
            'kodeDokumen' => '380',
            'nomorDokumen' => 'INV-'.$nomorAju,
            'tanggalDokumen' => date('Y-m-d'),
        ];
    }
}
