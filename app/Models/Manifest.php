<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Manifest extends Model
{
    public const JENIS_INWARD = 'inward';

    public const JENIS_OUTWARD = 'outward';

    protected $fillable = [
        'user_id',
        'jenis_manifes',
        'nama_sarana',
        'nomor_voyage',
        'nomor_imo',
        'call_sign',
        'kode_bendera',
        'kode_kantor',
        'nomor_daftar',
        'tanggal_sarana',
        'tanggal_daftar',
        'status',
        'payload',
        'ceisa_response',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'ceisa_response' => 'array',
            'tanggal_sarana' => 'date',
            'tanggal_daftar' => 'date',
            'synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Manifest>  $query
     * @return Builder<Manifest>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function jenisLabel(): string
    {
        return $this->jenis_manifes === self::JENIS_OUTWARD ? 'Keberangkatan' : 'Kedatangan';
    }

    /**
     * Label kantor pabean dari referensi resmi (cache 1 jam), fallback ke kode.
     */
    public function kantorPabeanLabel(): ?string
    {
        if (empty($this->kode_kantor)) {
            return null;
        }

        $map = Cache::remember('ceisa.kantor_pabean_map', 3600, fn () => CeisaReference::ofType('kantor_pabean')->pluck('label', 'code')->all());

        return $map[$this->kode_kantor] ?? $this->kode_kantor;
    }
}
