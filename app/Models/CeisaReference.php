<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CeisaReference extends Model
{
    protected $fillable = [
        'type',
        'code',
        'label',
        'meta',
        'sort',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'active' => 'boolean',
        ];
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type)->where('active', true);
    }

    /**
     * Ambil daftar referensi satu grup sebagai array {code,label} untuk dropdown.
     *
     * @return array<int, array{code: string, label: string}>
     */
    public static function group(string $type): array
    {
        return static::ofType($type)
            ->orderBy('sort')
            ->orderBy('label')
            ->get(['code', 'label'])
            ->map(fn (self $r): array => ['code' => $r->code, 'label' => $r->label])
            ->all();
    }

    /**
     * Bangun seluruh grup referensi yang dibutuhkan wizard perekaman dokumen.
     * Key array = nama yang dipakai komponen Alpine `documentWizard()`.
     *
     * @return array<string, array<int, array{code: string, label: string}>>
     */
    public static function forWizard(): array
    {
        $map = [
            'countries' => 'negara',
            'ports' => 'pelabuhan',
            'currencies' => 'valuta',
            'paymentMethods' => 'cara_pembayaran',
            'units' => 'satuan',
            'packages' => 'kemasan',
            'tpbTypes' => 'tpb_jenis',
            'tpbDestinations' => 'tpb_tujuan',
            'kantorMuat' => 'kantor_pabean',
            'jenisEkspor' => 'jenis_ekspor',
            'kategoriEkspor' => 'kategori_ekspor',
            'caraDagang' => 'cara_dagang',
            'caraBayar' => 'cara_bayar',
            'incoterms' => 'incoterm',
            'caraAngkut' => 'cara_angkut',
        ];

        $out = [];
        foreach ($map as $jsKey => $type) {
            $out[$jsKey] = static::group($type);
        }

        return $out;
    }
}
