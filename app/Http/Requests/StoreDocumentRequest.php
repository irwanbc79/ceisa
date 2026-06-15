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
     * Aturan validasi untuk dokumen BC 3.0 (PEB Ekspor) — MVP.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'doc_type' => ['required', 'in:BC30'],

            // Data eksportir
            'nama_eksportir' => ['required', 'string', 'max:255'],
            'npwp_eksportir' => ['required', 'string', 'max:25'],
            'alamat_eksportir' => ['required', 'string', 'max:500'],

            // Data penerima / tujuan
            'nama_penerima' => ['required', 'string', 'max:255'],
            'negara_tujuan' => ['required', 'string', 'size:2'], // kode negara ISO 2 huruf

            // Pengangkutan
            'pelabuhan_muat' => ['required', 'string', 'max:10'],
            'pelabuhan_bongkar' => ['nullable', 'string', 'max:10'],

            // Nilai transaksi
            'kode_valuta' => ['required', 'string', 'size:3'], // ISO 4217, mis. USD
            'nilai_fob' => ['required', 'numeric', 'min:0'],
            'cara_pembayaran' => ['nullable', 'string', 'max:50'],

            // Barang (line items)
            'barang' => ['required', 'array', 'min:1'],
            'barang.*.hs_code' => ['required', 'string', 'max:12'],
            'barang.*.uraian' => ['required', 'string', 'max:500'],
            'barang.*.jumlah_satuan' => ['required', 'numeric', 'min:0'],
            'barang.*.kode_satuan' => ['required', 'string', 'max:5'],
            'barang.*.netto' => ['required', 'numeric', 'min:0'],
            'barang.*.nilai_fob' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'barang.*.hs_code' => 'kode HS',
            'barang.*.uraian' => 'uraian barang',
            'barang.*.jumlah_satuan' => 'jumlah satuan',
            'barang.*.kode_satuan' => 'kode satuan',
            'barang.*.netto' => 'netto',
            'barang.*.nilai_fob' => 'nilai FOB barang',
        ];
    }

    /**
     * Susun payload terstruktur untuk dikirim ke CEISA.
     *
     * @return array<string, mixed>
     */
    public function toCeisaPayload(): array
    {
        $v = $this->validated();

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
            'barang' => array_map(static fn (array $b, int $i): array => [
                'seri' => $i + 1,
                'hs_code' => $b['hs_code'],
                'uraian' => $b['uraian'],
                'jumlah_satuan' => (float) $b['jumlah_satuan'],
                'kode_satuan' => $b['kode_satuan'],
                'netto' => (float) $b['netto'],
                'nilai_fob' => (float) $b['nilai_fob'],
            ], $v['barang'], array_keys($v['barang'])),
        ];
    }
}
