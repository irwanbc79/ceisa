<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Aturan validasi dinamis berdasarkan jenis dokumen kepabeanan CEISA.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $docType = $this->input('doc_type');

        $rules = [
            'doc_type' => ['required', 'in:BC20,BC24,TPB,BC30,RUSH'],
            'nomor_aju' => ['nullable', 'string', 'size:26', 'regex:/^[A-Za-z0-9]+$/'],
            // Dokumen Pelengkap
            'dokumen' => ['nullable', 'array'],
            'dokumen.*.kode_dokumen' => ['required_with:dokumen', 'string', 'max:10'],
            'dokumen.*.nomor_dokumen' => ['required_with:dokumen', 'string', 'max:100'],
            'dokumen.*.tanggal_dokumen' => ['required_with:dokumen', 'date'],
            // Peti Kemas
            'kontainer' => ['nullable', 'array'],
            'kontainer.*.nomor_kontainer' => ['required_with:kontainer', 'string', 'max:50'],
            'kontainer.*.kode_ukuran' => ['required_with:kontainer', 'string', 'max:10'],
            'kontainer.*.kode_tipe' => ['required_with:kontainer', 'string', 'max:10'],
            'kontainer.*.kode_status' => ['required_with:kontainer', 'string', 'max:10'],
        ];

        if ($docType === 'BC30') {
            $rules = array_merge($rules, [
                'dokumen' => ['required', 'array', 'min:1'],
                // Data Header (klasifikasi ekspor — CEISA 4.0)
                'kantor_muat' => ['required', 'string', 'max:10'],
                'kantor_pendaftaran' => ['required', 'string', 'max:10'],
                'kantor_ekspor' => ['required', 'string', 'max:10'],
                'jenis_ekspor' => ['required', 'in:1,2,3,4,5,6,7'],
                'kategori_ekspor' => ['required', 'in:10,21,22,23,31,32,33,34,35,36,37,38,41,42,43,44,45,46,51,61'],
                'cara_dagang' => ['nullable', 'in:1,2,3,4,5,6,7,8,9,10,11,12,13,14,15'],
                'cara_bayar' => ['required', 'in:1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17'],
                'jenis_pengangkutan' => ['required', 'in:1,2,3,4,5,6'],
                'kode_lokasi' => ['required', 'in:1,2,3,4,5,6,7,8'],
                'tanggal_periksa' => ['required', 'date'],
                'komoditi' => ['required', 'in:MIGAS,NON_MIGAS'],
                'curah' => ['required', 'in:CURAH,NON_CURAH'],

                // Data Entitas — Eksportir
                'nama_eksportir' => ['required', 'string', 'max:255'],
                'npwp_eksportir' => ['required', 'string', 'max:25'],
                'alamat_eksportir' => ['required', 'string', 'max:500'],

                // Data Entitas — Penerima / Pembeli (consignee)
                'nama_penerima' => ['required', 'string', 'max:255'],
                'negara_tujuan' => ['required', 'string', 'size:2'], // ISO 2 huruf
                'alamat_penerima' => ['required', 'string', 'max:500'],

                // Data Pengangkut
                'cara_angkut' => ['required', 'in:1,2,3,4,5,6,7,8,9,10,11,12'],
                'kode_bendera' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
                'nama_sarana' => ['required', 'string', 'max:255'],
                'voy_flight' => ['required', 'string', 'max:50'],
                'pelabuhan_muat' => ['required', 'string', 'max:10'],
                'pelabuhan_ekspor' => ['required', 'string', 'max:10'],
                'pelabuhan_bongkar' => ['nullable', 'string', 'max:10'],
                'pelabuhan_tujuan' => ['required', 'string', 'max:10'],
                'tanggal_ekspor' => ['required', 'date'],

                // Data Transaksi
                'kode_valuta' => ['required', 'string', 'size:3'],
                'ndpbm' => ['required', 'numeric', 'min:0'],
                'incoterm' => ['required', 'string', 'max:5'],
                'nilai_fob' => ['required', 'numeric', 'min:0'],
                'freight' => ['nullable', 'numeric', 'min:0'],
                'asuransi_jenis' => ['required', 'in:DN,LN'],
                'nilai_asuransi' => ['required', 'numeric', 'min:0'],
                'bruto' => ['required', 'numeric', 'min:0'],
                'bank_devisa' => ['nullable', 'string', 'max:100'],
                'cara_pembayaran' => ['nullable', 'string', 'max:50'],

                // Pernyataan
                'pernyataan_nama' => ['required', 'string', 'max:255'],
                'pernyataan_jabatan' => ['required', 'string', 'max:100'],
                'pernyataan_kota' => ['required', 'string', 'max:100'],

                // Data Barang (field tambahan ekspor)
                'barang.*.merk' => ['nullable', 'string', 'max:100'],
                'barang.*.tipe' => ['nullable', 'string', 'max:100'],
                'barang.*.ukuran' => ['nullable', 'string', 'max:100'],
                'barang.*.negara_asal' => ['nullable', 'string', 'size:2'],
                'barang.*.daerah_asal' => ['nullable', 'string', 'max:100'],
                'barang.*.jumlah_kemasan' => ['required', 'numeric', 'min:1'],
                'barang.*.kode_kemasan' => ['required', 'string', 'max:5'],
                'barang.*.merk_kemasan' => ['required', 'string', 'max:100'],
                'barang.*.volume' => ['nullable', 'numeric', 'min:0'],
            ], $this->barangRules('nilai_fob'));
        } elseif (in_array($docType, ['BC20', 'BC24'], true)) {
            $rules = array_merge($rules, [
                'dokumen' => ['required', 'array', 'min:1'],
                // Data importir
                'nama_importir' => ['required', 'string', 'max:255'],
                'npwp_importir' => ['required', 'string', 'max:25'],
                'alamat_importir' => ['required', 'string', 'max:500'],
                'nib_importir' => ['required', 'string', 'max:30'],
                'jenis_api' => ['required', 'string', 'max:5'],
                'status_importir' => ['required', 'in:0,1,2,3,4,5,6,7,8,9,10'],

                // Data pemasok
                'nama_pemasok' => ['required', 'string', 'max:255'],
                'negara_pemasok' => ['required', 'string', 'size:2'],
                'alamat_pemasok' => ['required', 'string', 'max:500'],

                // Data Header impor (kantor pabean / kode jenis impor / cara bayar CEISA)
                'kode_kantor' => ['required', 'string', 'max:10'],
                'jenis_impor' => ['required', 'in:1,2,3,4,5,6,7'],
                'cara_bayar' => ['required', 'in:1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17'],
                'kode_tutup_pu' => ['required', 'in:11,12,14'],

                // Pengangkutan
                'pelabuhan_muat' => ['required', 'string', 'max:10'],
                'pelabuhan_bongkar' => ['required', 'string', 'max:10'],
                'cara_angkut' => ['required', 'in:1,2,3,4,5,6,7,8,9'],
                'nama_sarana' => ['required', 'string', 'max:255'],
                'voy_flight' => ['required', 'string', 'max:50'],
                'kode_bendera' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
                'kode_tps' => ['required', 'string', 'max:20'],
                'tanggal_tiba' => ['required', 'date'],

                // Nilai transaksi
                'kode_valuta' => ['required', 'string', 'size:3'],
                'ndpbm' => ['required', 'numeric', 'gt:0'],
                'incoterm' => ['required', 'string', 'max:5'],
                'nilai_fob_impor' => ['required', 'numeric', 'min:0'],
                'nilai_cif' => ['required', 'numeric', 'min:0'],
                'freight' => ['required', 'numeric', 'min:0'],
                'asuransi_jenis' => ['nullable', 'in:DN,LN'],
                'nilai_asuransi' => ['required', 'numeric', 'min:0'],
                'bruto' => ['required', 'numeric', 'min:0'],
                'cara_pembayaran' => ['nullable', 'string', 'max:50'],

                // Kemasan agregat BC 2.0
                'jumlah_kemasan_impor' => ['required', 'integer', 'min:1'],
                'kode_kemasan_impor' => ['required', 'string', 'max:5'],
                'merk_kemasan_impor' => ['required', 'string', 'max:100'],

                // Pernyataan (penanggung jawab)
                'pernyataan_nama' => ['required', 'string', 'max:255'],
                'pernyataan_jabatan' => ['required', 'string', 'max:100'],
                'pernyataan_kota' => ['required', 'string', 'max:100'],

                // Tarif harus dinyatakan operator; jangan mengasumsikan tarif pajak.
                'barang.*.tarif_bm' => ['required', 'numeric', 'min:0', 'max:100'],
                'barang.*.tarif_ppn' => ['required', 'numeric', 'min:0', 'max:100'],
                'barang.*.tarif_pph' => ['required', 'numeric', 'min:0', 'max:100'],
            ], $this->barangRules('nilai_cif'));
        } elseif ($docType === 'TPB') {
            $rules = array_merge($rules, [
                // Pengusaha TPB
                'nama_tpb' => ['required', 'string', 'max:255'],
                'npwp_tpb' => ['required', 'string', 'max:25'],
                'alamat_tpb' => ['required', 'string', 'max:500'],

                // Fasilitas TPB
                'jenis_tpb' => ['required', 'string', 'max:100'],
                'tujuan_tpb' => ['required', 'string', 'max:100'],
                'dokumen_referensi' => ['required', 'string', 'max:100'],

                // Transaksi
                'kode_valuta' => ['required', 'string', 'size:3'],
                'nilai_barang' => ['required', 'numeric', 'min:0'],

                // Pengangkutan & Kantor (dynamic fallbacks)
                'kode_kantor' => ['nullable', 'string', 'max:10'],
                'cara_angkut' => ['nullable', 'string', 'max:50'],
                'nama_sarana' => ['nullable', 'string', 'max:255'],
                'voy_flight' => ['nullable', 'string', 'max:50'],
                'kode_bendera' => ['nullable', 'string', 'size:2'],
            ], $this->barangRules('nilai_barang'));
        } elseif ($docType === 'RUSH') {
            $rules = array_merge($rules, [
                // Data Pemohon
                'nama_pemohon' => ['required', 'string', 'max:255'],
                'npwp_pemohon' => ['required', 'string', 'max:25'],
                'alamat_pemohon' => ['required', 'string', 'max:500'],

                // Transport & Dokumen
                'nama_sarana_pengangkut' => ['required', 'string', 'max:255'],
                'nomor_flight' => ['required', 'string', 'max:50'],
                'nomor_awb_bl' => ['required', 'string', 'max:100'],
                'tanggal_awb_bl' => ['required', 'date'],

                // Alasan Rush Handling
                'alasan_segera' => ['required', 'string', 'max:500'],

                // Kemasan
                'jumlah_kemasan' => ['required', 'integer', 'min:1'],
                'jenis_kemasan' => ['required', 'string', 'max:50'],

                // Pengangkutan & Kantor (dynamic fallbacks)
                'kode_kantor' => ['nullable', 'string', 'max:10'],
                'kode_bendera' => ['nullable', 'string', 'size:2'],
                'cara_angkut' => ['nullable', 'string', 'max:50'],
            ], $this->barangRules('nilai_barang'));
        }

        return $rules;
    }

    /**
     * Aturan validasi untuk daftar barang. Sama untuk semua jenis dokumen,
     * hanya field nilai transaksi yang berbeda namanya.
     *
     * @return array<string, array<mixed>>
     */
    protected function barangRules(string $valueKey): array
    {
        return [
            'barang' => ['required', 'array', 'min:1'],
            'barang.*.hs_code' => ['required', 'string', 'max:12'],
            'barang.*.uraian' => ['required', 'string', 'max:500'],
            'barang.*.jumlah_satuan' => ['required', 'numeric', 'min:0'],
            'barang.*.kode_satuan' => ['required', 'string', 'max:5'],
            'barang.*.netto' => ['required', 'numeric', 'min:0'],
            "barang.*.{$valueKey}" => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Labels atribut validasi.
     */
    public function attributes(): array
    {
        return [
            'barang.*.hs_code' => 'kode HS',
            'barang.*.uraian' => 'uraian barang',
            'barang.*.jumlah_satuan' => 'jumlah satuan',
            'barang.*.kode_satuan' => 'kode satuan',
            'barang.*.netto' => 'netto',
            'barang.*.nilai_fob' => 'nilai FOB barang',
            'barang.*.nilai_cif' => 'nilai CIF barang',
            'barang.*.nilai_barang' => 'nilai barang',
            'npwp_eksportir' => 'NPWP Eksportir',
            'npwp_importir' => 'NPWP Importir',
            'npwp_tpb' => 'NPWP Pengusaha TPB',
            'npwp_pemohon' => 'NPWP Pemohon',
            'nomor_awb_bl' => 'nomor AWB/BL',
            'tanggal_awb_bl' => 'tanggal AWB/BL',
            'alasan_segera' => 'alasan segera',
            // BC 3.0 ekspor (CEISA 4.0)
            'kantor_muat' => 'kantor muat',
            'jenis_ekspor' => 'jenis ekspor',
            'kategori_ekspor' => 'kategori ekspor',
            'cara_bayar' => 'cara bayar',
            'pelabuhan_tujuan' => 'pelabuhan tujuan',
            'ndpbm' => 'NDPBM (kurs)',
            'incoterm' => 'cara penyerahan (Incoterm)',
            'bruto' => 'berat kotor (bruto)',
            'pernyataan_nama' => 'nama penanggung jawab',
            'pernyataan_jabatan' => 'jabatan penanggung jawab',
            'pernyataan_kota' => 'kota penandatanganan',
            'dokumen' => 'dokumen pelengkap',
            'barang.*.merk_kemasan' => 'merek kemasan',
        ];
    }

    /**
     * Susun payload terstruktur untuk dikirim ke CEISA berdasarkan jenis dokumen.
     *
     * @return array<string, mixed>
     */
    public function toCeisaPayload(): array
    {
        $v = $this->validated();
        $payload = $this->buildRawPayload($v['doc_type'], $v);

        if (isset($v['dokumen'])) {
            $payload['dokumen'] = $v['dokumen'];
        }
        if (isset($v['kontainer'])) {
            $payload['kontainer'] = $v['kontainer'];
        }

        return $payload;
    }

    protected function buildRawPayload(string $docType, array $v): array
    {
        if ($docType === 'BC30') {
            $ndpbm = (float) $v['ndpbm'];

            return [
                'header' => [
                    // Klasifikasi ekspor (CEISA 4.0 Data Header)
                    'kantor_muat' => $v['kantor_muat'],
                    'kantor_pendaftaran' => $v['kantor_pendaftaran'],
                    'kantor_ekspor' => $v['kantor_ekspor'],
                    'jenis_ekspor' => $v['jenis_ekspor'],
                    'kategori_ekspor' => $v['kategori_ekspor'],
                    'cara_dagang' => $v['cara_dagang'] ?? null,
                    'cara_bayar' => $v['cara_bayar'],
                    'jenis_pengangkutan' => $v['jenis_pengangkutan'],
                    'kode_lokasi' => $v['kode_lokasi'],
                    'tanggal_periksa' => $v['tanggal_periksa'],
                    'komoditi' => $v['komoditi'],
                    'curah' => $v['curah'],

                    // Entitas
                    'eksportir' => [
                        'nama' => $v['nama_eksportir'],
                        'npwp' => $v['npwp_eksportir'],
                        'alamat' => $v['alamat_eksportir'],
                    ],
                    'penerima' => [
                        'nama' => $v['nama_penerima'],
                        'negara' => strtoupper($v['negara_tujuan']),
                        'alamat' => $v['alamat_penerima'] ?? null,
                    ],

                    // Pengangkutan
                    'pengangkutan' => [
                        'cara_angkut' => $v['cara_angkut'] ?? null,
                        'bendera' => strtoupper($v['kode_bendera']),
                        'sarana_angkut' => $v['nama_sarana'] ?? null,
                        'voy_flight' => $v['voy_flight'] ?? null,
                        'pelabuhan_muat' => $v['pelabuhan_muat'],
                        'pelabuhan_ekspor' => $v['pelabuhan_ekspor'],
                        'pelabuhan_bongkar' => $v['pelabuhan_bongkar'] ?? null,
                        'pelabuhan_tujuan' => $v['pelabuhan_tujuan'],
                        'tanggal_ekspor' => $v['tanggal_ekspor'] ?? null,
                    ],

                    // Transaksi
                    'valuta' => strtoupper($v['kode_valuta']),
                    'ndpbm' => $ndpbm,
                    'incoterm' => strtoupper($v['incoterm']),
                    'nilai_fob' => (float) $v['nilai_fob'],
                    'freight' => isset($v['freight']) ? (float) $v['freight'] : null,
                    'asuransi' => [
                        'jenis' => $v['asuransi_jenis'] ?? null,
                        'nilai' => isset($v['nilai_asuransi']) ? (float) $v['nilai_asuransi'] : null,
                    ],
                    'bruto' => (float) $v['bruto'],
                    'bank_devisa' => $v['bank_devisa'] ?? null,
                    'cara_pembayaran' => $v['cara_pembayaran'] ?? null,

                    // Pernyataan
                    'pernyataan' => [
                        'nama' => $v['pernyataan_nama'],
                        'jabatan' => $v['pernyataan_jabatan'],
                        'kota' => $v['pernyataan_kota'] ?? null,
                        'tanggal' => now()->toDateString(),
                    ],
                ],
                'barang' => array_map(static function (array $b, int $i): array {
                    $fob = (float) $b['nilai_fob'];
                    $qty = (float) $b['jumlah_satuan'];

                    return [
                        'seri' => $i + 1,
                        'hs_code' => $b['hs_code'],
                        'uraian' => $b['uraian'],
                        'merk' => $b['merk'] ?? null,
                        'tipe' => $b['tipe'] ?? null,
                        'ukuran' => $b['ukuran'] ?? null,
                        'negara_asal' => isset($b['negara_asal']) ? strtoupper($b['negara_asal']) : null,
                        'daerah_asal' => $b['daerah_asal'] ?? null,
                        'jumlah_satuan' => $qty,
                        'kode_satuan' => $b['kode_satuan'],
                        'jumlah_kemasan' => isset($b['jumlah_kemasan']) ? (float) $b['jumlah_kemasan'] : null,
                        'kode_kemasan' => $b['kode_kemasan'] ?? null,
                        'merk_kemasan' => $b['merk_kemasan'] ?? null,
                        'netto' => (float) $b['netto'],
                        'volume' => isset($b['volume']) ? (float) $b['volume'] : null,
                        'nilai_fob' => $fob,
                        // Harga satuan FOB (auto, sesuai perilaku portal CEISA)
                        'harga_satuan' => $qty > 0 ? round($fob / $qty, 4) : 0.0,
                    ];
                }, $v['barang'], array_keys($v['barang'])),
            ];
        } elseif (in_array($docType, ['BC20', 'BC24'], true)) {
            return [
                'header' => [
                    'importir' => [
                        'nama' => $v['nama_importir'],
                        'npwp' => $v['npwp_importir'],
                        'alamat' => $v['alamat_importir'],
                        'nib' => $v['nib_importir'] ?? null,
                        'jenis_api' => $v['jenis_api'] ?? null,
                        'status' => $v['status_importir'] ?? null,
                    ],
                    'pemasok' => [
                        'nama' => $v['nama_pemasok'],
                        'negara' => strtoupper($v['negara_pemasok']),
                        'alamat' => $v['alamat_pemasok'],
                    ],
                    'kode_kantor' => $v['kode_kantor'] ?? null,
                    'jenis_impor' => $v['jenis_impor'] ?? null,
                    'cara_bayar' => $v['cara_bayar'] ?? null,
                    'kode_tutup_pu' => $v['kode_tutup_pu'],
                    'pengangkutan' => [
                        'pelabuhan_muat' => $v['pelabuhan_muat'],
                        'pelabuhan_bongkar' => $v['pelabuhan_bongkar'],
                        'cara_angkut' => $v['cara_angkut'] ?? null,
                        'sarana_angkut' => $v['nama_sarana'] ?? null,
                        'voy_flight' => $v['voy_flight'] ?? null,
                        'bendera' => isset($v['kode_bendera']) ? strtoupper($v['kode_bendera']) : null,
                        'tps' => $v['kode_tps'] ?? null,
                        'tanggal_tiba' => $v['tanggal_tiba'] ?? null,
                    ],
                    'valuta' => strtoupper($v['kode_valuta']),
                    'ndpbm' => isset($v['ndpbm']) ? (float) $v['ndpbm'] : null,
                    'incoterm' => isset($v['incoterm']) ? strtoupper($v['incoterm']) : null,
                    'nilai_cif' => (float) $v['nilai_cif'],
                    'fob' => (float) $v['nilai_fob_impor'],
                    'freight' => isset($v['freight']) ? (float) $v['freight'] : null,
                    'asuransi' => isset($v['nilai_asuransi']) ? (float) $v['nilai_asuransi'] : null,
                    'asuransi_jenis' => $v['asuransi_jenis'] ?? null,
                    'bruto' => isset($v['bruto']) ? (float) $v['bruto'] : null,
                    'kemasan' => [
                        'jumlah' => (int) $v['jumlah_kemasan_impor'],
                        'kode' => $v['kode_kemasan_impor'],
                        'merk' => $v['merk_kemasan_impor'],
                    ],
                    'cara_pembayaran' => $v['cara_pembayaran'] ?? null,
                    'pernyataan' => [
                        'nama' => $v['pernyataan_nama'] ?? null,
                        'jabatan' => $v['pernyataan_jabatan'] ?? null,
                        'kota' => $v['pernyataan_kota'] ?? null,
                    ],
                ],
                'barang' => $this->mapBarang($v['barang'], 'nilai_cif'),
            ];
        } elseif ($docType === 'TPB') {
            return [
                'header' => [
                    'pengusaha_tpb' => [
                        'nama' => $v['nama_tpb'],
                        'npwp' => $v['npwp_tpb'],
                        'alamat' => $v['alamat_tpb'],
                    ],
                    'jenis_tpb' => $v['jenis_tpb'],
                    'tujuan_pengiriman' => $v['tujuan_tpb'],
                    'dokumen_referensi' => $v['dokumen_referensi'],
                    'valuta' => strtoupper($v['kode_valuta']),
                    'nilai_barang' => (float) $v['nilai_barang'],
                    'kode_kantor' => $v['kode_kantor'] ?? null,
                    'pengangkutan' => [
                        'cara_angkut' => $v['cara_angkut'] ?? null,
                        'sarana_angkut' => $v['nama_sarana'] ?? null,
                        'voy_flight' => $v['voy_flight'] ?? null,
                        'bendera' => isset($v['kode_bendera']) ? strtoupper($v['kode_bendera']) : null,
                    ],
                ],
                'barang' => $this->mapBarang($v['barang'], 'nilai_barang'),
            ];
        } elseif ($docType === 'RUSH') {
            return [
                'header' => [
                    'pemohon' => [
                        'nama' => $v['nama_pemohon'],
                        'npwp' => $v['npwp_pemohon'],
                        'alamat' => $v['alamat_pemohon'],
                    ],
                    'pengangkutan' => [
                        'sarana' => $v['nama_sarana_pengangkut'],
                        'flight_no' => $v['nomor_flight'],
                        'bendera' => isset($v['kode_bendera']) ? strtoupper($v['kode_bendera']) : null,
                        'cara_angkut' => $v['cara_angkut'] ?? null,
                    ],
                    'dokumen_pengangkutan' => [
                        'awb_bl' => $v['nomor_awb_bl'],
                        'tanggal' => $v['tanggal_awb_bl'],
                    ],
                    'alasan_rush_handling' => $v['alasan_segera'],
                    'kemasan' => [
                        'jumlah' => (int) $v['jumlah_kemasan'],
                        'jenis' => $v['jenis_kemasan'],
                    ],
                    'kode_kantor' => $v['kode_kantor'] ?? null,
                ],
                'barang' => $this->mapBarang($v['barang'], 'nilai_barang'),
            ];
        }

        return $v;
    }

    /**
     * Petakan daftar barang ke struktur seragam CEISA (seri + nilai per jenis dokumen).
     *
     * @param  array<int, array<string, mixed>>  $barang
     * @return array<int, array<string, mixed>>
     */
    protected function mapBarang(array $barang, string $valueKey): array
    {
        return array_map(static function (array $b, int $i) use ($valueKey): array {
            $mapped = [
                'seri' => $i + 1,
                'hs_code' => $b['hs_code'],
                'uraian' => $b['uraian'],
                'jumlah_satuan' => (float) $b['jumlah_satuan'],
                'kode_satuan' => $b['kode_satuan'],
                'netto' => (float) $b['netto'],
                $valueKey => (float) $b[$valueKey],
            ];

            foreach (['tarif_bm', 'tarif_ppn', 'tarif_pph'] as $tariff) {
                if (array_key_exists($tariff, $b)) {
                    $mapped[$tariff] = (float) $b[$tariff];
                }
            }

            return $mapped;
        }, $barang, array_keys($barang));
    }
}
