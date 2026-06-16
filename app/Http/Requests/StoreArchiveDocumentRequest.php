<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArchiveDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'doc_type' => ['required', 'in:BC20,BC24,TPB,BC30,RUSH'],
            'nomor_aju' => ['required', 'string', 'max:100'],
            'nomor_daftar' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in([
                Document::STATUS_SUBMITTED,
                Document::STATUS_ACCEPTED,
                Document::STATUS_REJECTED,
            ])],
            'jalur' => ['nullable', 'in:H,K,M'],
            'tanggal_dokumen' => ['nullable', 'date'],
            'nama_perusahaan' => ['required', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:25'],
            'kantor_pabean' => ['nullable', 'string', 'max:100'],
            'kode_valuta' => ['nullable', 'string', 'max:3'],
            'nilai' => ['nullable', 'numeric', 'min:0'],
            'uraian' => ['nullable', 'string', 'max:1000'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'doc_type' => 'jenis dokumen',
            'nomor_aju' => 'nomor aju',
            'nama_perusahaan' => 'nama perusahaan',
            'kode_valuta' => 'valuta',
        ];
    }

    /**
     * Payload arsip terstruktur untuk disimpan ke kolom JSON documents.payload.
     *
     * @return array<string, mixed>
     */
    public function toArchivePayload(): array
    {
        $v = $this->validated();

        return [
            'arsip' => true,
            'nama_perusahaan' => $v['nama_perusahaan'],
            'npwp' => $v['npwp'] ?? null,
            'kantor_pabean' => $v['kantor_pabean'] ?? null,
            'tanggal_dokumen' => $v['tanggal_dokumen'] ?? null,
            'valuta' => isset($v['kode_valuta']) ? strtoupper($v['kode_valuta']) : null,
            'nilai' => isset($v['nilai']) ? (float) $v['nilai'] : null,
            'uraian' => $v['uraian'] ?? null,
            'keterangan' => $v['keterangan'] ?? null,
        ];
    }
}
