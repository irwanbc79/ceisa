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
        ];

        if ($docType === 'BC30') {
            $rules = array_merge($rules, [
                // Data eksportir
                'nama_eksportir' => ['required', 'string', 'max:255'],
                'npwp_eksportir' => ['required', 'string', 'max:25'],
                'alamat_eksportir' => ['required', 'string', 'max:500'],

                // Data penerima / tujuan
                'nama_penerima' => ['required', 'string', 'max:255'],
                'negara_tujuan' => ['required', 'string', 'size:2'], // ISO 2 huruf

                // Pengangkutan
                'pelabuhan_muat' => ['required', 'string', 'max:10'],
                'pelabuhan_bongkar' => ['nullable', 'string', 'max:10'],

                // Nilai transaksi
                'kode_valuta' => ['required', 'string', 'size:3'],
                'nilai_fob' => ['required', 'numeric', 'min:0'],
                'cara_pembayaran' => ['nullable', 'string', 'max:50'],
            ], $this->barangRules('nilai_fob'));
        } elseif (in_array($docType, ['BC20', 'BC24'], true)) {
            $rules = array_merge($rules, [
                // Data importir
                'nama_importir' => ['required', 'string', 'max:255'],
                'npwp_importir' => ['required', 'string', 'max:25'],
                'alamat_importir' => ['required', 'string', 'max:500'],

                // Data pemasok
                'nama_pemasok' => ['required', 'string', 'max:255'],
                'negara_pemasok' => ['required', 'string', 'size:2'],

                // Pengangkutan
                'pelabuhan_muat' => ['required', 'string', 'max:10'],
                'pelabuhan_bongkar' => ['required', 'string', 'max:10'],

                // Nilai transaksi
                'kode_valuta' => ['required', 'string', 'size:3'],
                'nilai_cif' => ['required', 'numeric', 'min:0'],
                'cara_pembayaran' => ['nullable', 'string', 'max:50'],
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
        $docType = $v['doc_type'];

        if ($docType === 'BC30') {
            return [
                'header' => [
                    'eksportir' => [
                        'nama' => $v['nama_eksportir'],
                        'npwp' => $v['npwp_eksportir'],
                        'alamat' => $v['alamat_eksportir'],
                    ],
                    'penerima' => [
                        'nama' => $v['nama_penerima'],
                        'negara' => strtoupper($v['negara_tujuan']),
                    ],
                    'pengangkutan' => [
                        'pelabuhan_muat' => $v['pelabuhan_muat'],
                        'pelabuhan_bongkar' => $v['pelabuhan_bongkar'] ?? null,
                    ],
                    'valuta' => strtoupper($v['kode_valuta']),
                    'nilai_fob' => (float) $v['nilai_fob'],
                    'cara_pembayaran' => $v['cara_pembayaran'] ?? null,
                ],
                'barang' => $this->mapBarang($v['barang'], 'nilai_fob'),
            ];
        } elseif (in_array($docType, ['BC20', 'BC24'], true)) {
            return [
                'header' => [
                    'importir' => [
                        'nama' => $v['nama_importir'],
                        'npwp' => $v['npwp_importir'],
                        'alamat' => $v['alamat_importir'],
                    ],
                    'pemasok' => [
                        'nama' => $v['nama_pemasok'],
                        'negara' => strtoupper($v['negara_pemasok']),
                    ],
                    'pengangkutan' => [
                        'pelabuhan_muat' => $v['pelabuhan_muat'],
                        'pelabuhan_bongkar' => $v['pelabuhan_bongkar'],
                    ],
                    'valuta' => strtoupper($v['kode_valuta']),
                    'nilai_cif' => (float) $v['nilai_cif'],
                    'cara_pembayaran' => $v['cara_pembayaran'] ?? null,
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
        return array_map(static fn (array $b, int $i): array => [
            'seri' => $i + 1,
            'hs_code' => $b['hs_code'],
            'uraian' => $b['uraian'],
            'jumlah_satuan' => (float) $b['jumlah_satuan'],
            'kode_satuan' => $b['kode_satuan'],
            'netto' => (float) $b['netto'],
            $valueKey => (float) $b[$valueKey],
        ], $barang, array_keys($barang));
    }
}
