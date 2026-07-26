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
 * Nilai fakta operasional tidak dibuat-buat oleh aplikasi. Draft lama yang
 * belum lengkap ditahan sebelum request HTTP dikirim ke CEISA.
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
     * Kode cara angkut CEISA dari kode resmi atau label draft lama.
     */
    public function caraAngkutCode(?string $label): string
    {
        $value = trim((string) ($label ?? ''));
        if (preg_match('/^(?:[1-9]|1[0-2])$/', $value)) {
            return $value;
        }

        return match (mb_strtolower($value)) {
            'laut' => '1',
            'kereta api' => '2',
            'darat' => '3',
            'udara' => '4',
            'pos' => '5',
            'multimoda' => '6',
            'instalasi / pipa', 'instalasi/pipa' => '7',
            'perairan' => '8',
            'lainnya' => '9',
            'instalasi' => '10',
            'pipa' => '11',
            'transmisi' => '12',
            default => '1',
        };
    }

    /**
     * Normalisasi kode referensi sambil menjaga draft lama yang masih menyimpan label.
     *
     * @param  array<string, string>  $legacyMap
     */
    private function referenceCode(?string $value, array $legacyMap): string
    {
        $value = trim((string) $value);
        if ($value === '' || ctype_digit($value)) {
            return $value;
        }

        return $legacyMap[mb_strtolower($value)] ?? $value;
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
        $jenisEkspor = $this->referenceCode($header['jenis_ekspor'] ?? null, [
            'biasa' => '1', 'akan diimpor kembali' => '4', 'reekspor' => '5',
            're-ekspor' => '5', 'reekspor ex impor sementara' => '6',
        ]);
        $kategoriEkspor = $this->referenceCode($header['kategori_ekspor'] ?? null, [
            'umum' => '10', 'kite' => '23', 'barang perwakilan negara asing' => '31',
            'barang kiriman' => '33', 'plb' => '51', 'pusat logistik berikat (plb)' => '51',
        ]);
        $caraBayar = $this->referenceCode($header['cara_bayar'] ?? null, [
            'biasa/tunai' => '1', 'biasa / tunai' => '1', 'berkala' => '2',
            'dengan jaminan' => '3', 'gabungan' => '9', 'gabungan/lainnya' => '9',
        ]);
        $caraDagang = $this->referenceCode($header['cara_dagang'] ?? null, [
            'biasa' => '1', 'imb' => '2', 'imb (imbal beli)' => '2', 'lainnya' => '15',
        ]);

        $flat = [
            'asalData' => 'S',
            'kodeDokumen' => '30',
            'disclaimer' => '1',
            'nomorAju' => $nomorAju,
            'tanggalAju' => date('Y-m-d'),

            'kodeKantor' => $header['kantor_pendaftaran'] ?? $header['kantor_muat'] ?? '',
            'kodeKantorMuat' => $header['kantor_muat'] ?? '',
            'kodeKantorEkspor' => $header['kantor_ekspor'] ?? $header['kantor_muat'] ?? '',
            'kodeJenisEkspor' => $jenisEkspor,
            'kodeKategoriEkspor' => $kategoriEkspor,
            'kodeCaraDagang' => $caraDagang,
            'kodeCaraBayar' => $caraBayar,
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

            'flagBarkir' => $kategoriEkspor === '33' ? 'Y' : 'T',
            'jumlahKontainer' => 0,
            'kodeLokasi' => $header['kode_lokasi'] ?? '',
            'tanggalPeriksa' => $header['tanggal_periksa'] ?? '',
            'tanggalEkspor' => $header['pengangkutan']['tanggal_ekspor'] ?? '',

            'kodeJenisPengangkutan' => $header['jenis_pengangkutan'] ?? '',
            'kodePelEkspor' => $header['pengangkutan']['pelabuhan_ekspor'] ?? $header['pengangkutan']['pelabuhan_muat'] ?? '',
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
        $flat['bankDevisa'] = ! empty($header['bank_devisa']) ? [[
            'seriBank' => 1,
            'namaBank' => $header['bank_devisa'],
        ]] : [];
        // Properti wajib pada schema, tetapi array boleh kosong. Jangan mengirim PIC,
        // nomor telepon, atau lokasi rekaan bila pengguna belum mengisinya.
        $flat['kesiapanBarang'] = [];
        if (isset($payload['dokumen']) && is_array($payload['dokumen']) && ! empty($payload['dokumen'])) {
            $flat['dokumen'] = array_map(fn ($d, $idx) => [
                'seriDokumen' => $idx + 1,
                'kodeDokumen' => $d['kode_dokumen'] ?? '',
                'nomorDokumen' => $d['nomor_dokumen'] ?? '',
                'tanggalDokumen' => $d['tanggal_dokumen'] ?? '',
            ], $payload['dokumen'], array_keys($payload['dokumen']));
        }

        if (isset($payload['kontainer']) && is_array($payload['kontainer']) && ! empty($payload['kontainer'])) {
            $flat['kontainer'] = array_map(fn ($c, $idx) => [
                'seriKontainer' => $idx + 1,
                'nomorKontainer' => $c['nomor_kontainer'] ?? '',
                'kodeUkuranKontainer' => $c['kode_ukuran'] ?? '',
                'kodeTipeKontainer' => $c['kode_tipe'] ?? '',
                'kodeStatusKontainer' => $c['kode_status'] ?? '',
                'kodeTipeIsi' => $c['kode_tipe_isi'] ?? 'FCL',
            ], $payload['kontainer'], array_keys($payload['kontainer']));
            $flat['jumlahKontainer'] = count($flat['kontainer']);
        }

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
                'kodeNegaraAsal' => strtoupper($item['negara_asal'] ?? ''),
                'kodeDaerahAsal' => $item['daerah_asal'] ?? '',
                'jumlahSatuan' => $qty,
                'kodeSatuanBarang' => $item['kode_satuan'] ?? '',
                'jumlahKemasan' => isset($item['jumlah_kemasan']) ? (float) $item['jumlah_kemasan'] : 0.0,
                'kodeJenisKemasan' => $item['kode_kemasan'] ?? '',
                'merkKemasan' => $item['merk_kemasan'] ?? '',
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
            $merkKemasan = $b['merkKemasan'] ?? '';
            $key = $kCode.'|'.$merkKemasan;
            if (! isset($kemasanCodes[$key])) {
                $kemasanCodes[$key] = [
                    'seriKemasan' => $seriKemasan++,
                    'jumlahKemasan' => 0.0,
                    'kodeJenisKemasan' => $kCode,
                    'merkKemasan' => $merkKemasan,
                ];
            }
            $kemasanCodes[$key]['jumlahKemasan'] += $b['jumlahKemasan'];
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
                'namaPengangkut' => $header['pengangkutan']['sarana_angkut'] ?? '',
                'nomorPengangkut' => $header['pengangkutan']['voy_flight'] ?? '',
                'kodeBendera' => strtoupper($header['pengangkutan']['bendera'] ?? ''),
                'kodeCaraAngkut' => $this->caraAngkutCode($header['pengangkutan']['cara_angkut'] ?? ''),
            ]];
        }

        return [[
            'seriPengangkut' => 1,
            'namaPengangkut' => '',
            'nomorPengangkut' => '',
            'kodeBendera' => '',
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
        $jenisImpor = $this->referenceCode($header['jenis_impor'] ?? '1', ['untuk dipakai' => '1']);
        $caraBayar = $this->referenceCode($header['cara_bayar'] ?? '1', [
            'biasa/tunai' => '1', 'biasa / tunai' => '1', 'berkala' => '2',
            'dengan jaminan' => '3', 'gabungan' => '9', 'gabungan/lainnya' => '9',
        ]);

        $flat = [
            'asalData' => 'S',
            'kodeDokumen' => $type === 'BC24' ? '24' : '20',
            'disclaimer' => '1',
            'nomorAju' => $nomorAju,
            'tanggalAju' => date('Y-m-d'),

            'kodeKantor' => $header['kode_kantor'] ?? '',
            'kodeJenisImpor' => $jenisImpor,
            'kodeCaraBayar' => $caraBayar,
            'kodeValuta' => $header['valuta'] ?? '',
            'ndpbm' => isset($header['ndpbm']) ? (float) $header['ndpbm'] : 0.0,
            'kodeIncoterm' => $header['incoterm'] ?? '',
            'kodePelMuat' => $header['pengangkutan']['pelabuhan_muat'] ?? '',
            'kodePelTujuan' => $header['pengangkutan']['pelabuhan_bongkar'] ?? '',
            'kodeTps' => $header['pengangkutan']['tps'] ?? '',
            'kodeTutupPu' => $header['kode_tutup_pu'] ?? '',

            'tanggalTiba' => $header['pengangkutan']['tanggal_tiba'] ?? '',
            'jumlahKontainer' => 0,

            'fob' => isset($header['fob']) ? (float) $header['fob'] : 0.0,
            'asuransi' => isset($header['asuransi']) ? (float) $header['asuransi'] : 0.0,
            'freight' => isset($header['freight']) ? (float) $header['freight'] : 0.0,
            'cif' => isset($header['nilai_cif']) ? (float) $header['nilai_cif'] : 0.0,

            'bruto' => isset($header['bruto']) ? (float) $header['bruto'] : 0.0,
            'netto' => array_reduce($barang, fn ($carry, $item) => $carry + (float) ($item['netto'] ?? 0.0), 0.0),

            'namaTtd' => $header['pernyataan']['nama'] ?? '',
            'jabatanTtd' => $header['pernyataan']['jabatan'] ?? '',
            'kotaTtd' => $header['pernyataan']['kota'] ?? '',
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
        $flat['kemasan'] = [[
            'seriKemasan' => 1,
            'jumlahKemasan' => (int) ($header['kemasan']['jumlah'] ?? 0),
            'kodeJenisKemasan' => $header['kemasan']['kode'] ?? '',
            'merkKemasan' => $header['kemasan']['merk'] ?? '',
        ]];
        $flat['pengangkut'] = [$this->pengangkutImpor($header)];
        if (isset($payload['dokumen']) && is_array($payload['dokumen']) && ! empty($payload['dokumen'])) {
            $flat['dokumen'] = array_map(fn ($d, $idx) => [
                'seriDokumen' => $idx + 1,
                'kodeDokumen' => $d['kode_dokumen'] ?? '',
                'nomorDokumen' => $d['nomor_dokumen'] ?? '',
                'tanggalDokumen' => $d['tanggal_dokumen'] ?? '',
            ], $payload['dokumen'], array_keys($payload['dokumen']));
        }

        if (isset($payload['kontainer']) && is_array($payload['kontainer']) && ! empty($payload['kontainer'])) {
            $flat['kontainer'] = array_map(fn ($c, $idx) => [
                'seriKontainer' => $idx + 1,
                'nomorKontainer' => $c['nomor_kontainer'] ?? '',
                'kodeUkuranKontainer' => $c['kode_ukuran'] ?? '',
                'kodeTipeKontainer' => $c['kode_tipe'] ?? '',
                'kodeStatusKontainer' => $c['kode_status'] ?? '',
                'kodeTipeIsi' => $c['kode_tipe_isi'] ?? 'FCL',
            ], $payload['kontainer'], array_keys($payload['kontainer']));
            $flat['jumlahKontainer'] = count($flat['kontainer']);
        }

        return $flat;
    }

    /**
     * Sub-blok pungutan per barang impor: tarif selalu berasal dari input operator.
     *
     * @param  array<string, mixed>  $item
     * @return array<int, array<string, mixed>>
     */
    private function barangTarif(int $seri, array $item): array
    {
        $fasilitas = (string) ($item['kode_fasilitas_tarif'] ?? '1'); // 1 = dibayar (tanpa fasilitas)

        $pungutan = [
            ['kode' => 'BM', 'tarif' => (float) ($item['tarif_bm'] ?? 0)],
            ['kode' => 'PPN', 'tarif' => (float) ($item['tarif_ppn'] ?? 0)],
            ['kode' => 'PPH', 'tarif' => (float) ($item['tarif_pph'] ?? 0)],
        ];

        return array_map(fn (array $p): array => [
            'seriBarang' => $seri,
            'kodeJenisTarif' => '1', // Advalorum
            'kodeJenisPungutan' => $p['kode'],
            'kodeFasilitasTarif' => $fasilitas,
            'tarif' => $p['tarif'],
            'tarifFasilitas' => 0.0,
            'nilaiBayar' => 0.0,
            'nilaiFasilitas' => 0.0,
        ], $pungutan);
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
                'kodeJenisApi' => $header['importir']['jenis_api'] ?? '',
                'kodeStatus' => $header['importir']['status'] ?? '',
            ];
            $entitas[] = [
                'seriEntitas' => 2,
                'kodeEntitas' => '7',
                'namaEntitas' => $header['importir']['nama'] ?? '',
                'alamatEntitas' => $header['importir']['alamat'] ?? '',
                'nomorIdentitas' => $this->digits($npwp),
                'kodeJenisIdentitas' => $this->jenisIdentitas($npwp),
                'kodeAfiliasi' => $header['importir']['afiliasi'] ?? '',
            ];
        }

        if (isset($header['pemasok'])) {
            $entitas[] = [
                'seriEntitas' => 3,
                'kodeEntitas' => '9',
                'namaEntitas' => $header['pemasok']['nama'] ?? '',
                'alamatEntitas' => $header['pemasok']['alamat'] ?? '',
                'kodeNegara' => strtoupper($header['pemasok']['negara'] ?? ''),
            ];
        }

        // Entitas PPJK (kode 4) — diisi bila importir memakai jasa PPJK (mis. PT Mora).
        // Aktif saat payload menyertakan header.ppjk; sesuai JSON Schema entitas tuple #6.
        if (isset($header['ppjk'])) {
            $npwp = $header['ppjk']['npwp'] ?? '';
            $entitas[] = [
                'seriEntitas' => count($entitas) + 1,
                'kodeEntitas' => '4',
                'namaEntitas' => $header['ppjk']['nama'] ?? '',
                'alamatEntitas' => $header['ppjk']['alamat'] ?? '',
                'nomorIdentitas' => $this->digits($npwp),
                'kodeJenisIdentitas' => $this->jenisIdentitas($npwp),
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
        $totalCifBarang = array_reduce($barang, fn ($carry, $item) => $carry + (float) ($item['nilai_cif'] ?? 0.0), 0.0);
        $totalNettoBarang = array_reduce($barang, fn ($carry, $item) => $carry + (float) ($item['netto'] ?? 0.0), 0.0);

        foreach ($barang as $i => $item) {
            $itemCif = isset($item['nilai_cif']) ? (float) $item['nilai_cif'] : 0.0;
            $qty = isset($item['jumlah_satuan']) ? (float) $item['jumlah_satuan'] : 1.0;
            $valueRatio = $totalCifBarang > 0 ? $itemCif / $totalCifBarang : 0.0;
            $itemNetto = isset($item['netto']) ? (float) $item['netto'] : 0.0;
            $weightRatio = $totalNettoBarang > 0 ? $itemNetto / $totalNettoBarang : 0.0;

            $out[] = [
                'seriBarang' => $i + 1,
                'posTarif' => $this->digits($item['hs_code'] ?? ''),
                'uraian' => $item['uraian'] ?? '',
                'merk' => $item['merk'] ?? '',
                'tipe' => $item['tipe'] ?? '',
                'kodeJenisKemasan' => $item['kode_kemasan'] ?? $header['kemasan']['kode'] ?? '',
                'kodeSatuanBarang' => $item['kode_satuan'] ?? '',
                'jumlahKemasan' => (float) ($item['jumlah_kemasan'] ?? $header['kemasan']['jumlah'] ?? 0),
                'jumlahSatuan' => $qty,
                'hargaSatuan' => $qty > 0 ? round($itemCif / $qty, 4) : 0.0,
                'fob' => round((float) ($header['fob'] ?? 0.0) * $valueRatio, 2),
                'asuransi' => round((float) ($header['asuransi'] ?? 0.0) * $valueRatio, 2),
                'freight' => round((float) ($header['freight'] ?? 0.0) * $valueRatio, 2),
                'cif' => $itemCif,
                'saldoAwal' => 0.0,
                'saldoAkhir' => 0.0,
                'metodePenentuanNilai' => $item['metode_penentuan_nilai'] ?? '',
                'alasanMetodePenentuanNilai' => null,
                'statementPerbedaanHarga' => 'T',
                'bruto' => round((float) ($header['bruto'] ?? 0.0) * $weightRatio, 4),
                'netto' => $itemNetto,
                'kodeNegaraAsal' => strtoupper($header['pemasok']['negara'] ?? ''),
                'barangTarif' => $this->barangTarif($i + 1, $item),
                // Wajib hadir per JSON Schema BC 2.0 (barang.required memuat "barangVd").
                // Kosong = tanpa voluntary declaration (kasus normal); diisi bila flagVd=Y.
                'barangVd' => [],
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
            'namaPengangkut' => $p['sarana_angkut'] ?? '',
            'nomorPengangkut' => $p['voy_flight'] ?? '',
            'kodeBendera' => strtoupper($p['bendera'] ?? ''),
            'kodeCaraAngkut' => $this->caraAngkutCode($p['cara_angkut'] ?? ''),
        ]];

        if (isset($payload['dokumen']) && is_array($payload['dokumen']) && ! empty($payload['dokumen'])) {
            $flat['dokumen'] = array_map(fn ($d, $idx) => [
                'seriDokumen' => $idx + 1,
                'kodeDokumen' => $d['kode_dokumen'] ?? '',
                'nomorDokumen' => $d['nomor_dokumen'] ?? '',
                'tanggalDokumen' => $d['tanggal_dokumen'] ?? '',
            ], $payload['dokumen'], array_keys($payload['dokumen']));
        } else {
            $flat['dokumen'] = [$this->dokumenInvoice($nomorAju)];
        }

        if (isset($payload['kontainer']) && is_array($payload['kontainer']) && ! empty($payload['kontainer'])) {
            $flat['kontainer'] = array_map(fn ($c, $idx) => [
                'seriKontainer' => $idx + 1,
                'nomorKontainer' => $c['nomor_kontainer'] ?? '',
                'kodeUkuranKontainer' => $c['kode_ukuran'] ?? '',
                'kodeTipeKontainer' => $c['kode_tipe'] ?? '',
                'kodeStatusKontainer' => $c['kode_status'] ?? '',
                'kodeTipeIsi' => $c['kode_tipe_isi'] ?? 'FCL',
            ], $payload['kontainer'], array_keys($payload['kontainer']));
            $flat['jumlahKontainer'] = count($flat['kontainer']);
        }

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
