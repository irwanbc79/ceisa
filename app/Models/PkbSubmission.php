<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rekam Pemberitahuan Kesiapan Barang (PKB) Online untuk dokumen Jalur Merah.
 */
class PkbSubmission extends Model
{
    public const STATUS_RECORDED = 'recorded';

    public const STATUS_SUBMITTED = 'submitted_to_ceisa';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'document_id',
        'user_id',
        'tanggal_periksa',
        'alamat_lokasi',
        'kode_gudang',
        'nama_pic',
        'telepon_pic',
        'catatan',
        'status',
        'ceisa_response',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_periksa' => 'date',
            'ceisa_response' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
