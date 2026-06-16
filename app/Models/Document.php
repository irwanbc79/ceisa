<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTING = 'submitting';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ERROR = 'error';

    public const JALUR_HIJAU = 'H';

    public const JALUR_KUNING = 'K';

    public const JALUR_MERAH = 'M';

    public const SOURCE_H2H = 'h2h';

    public const SOURCE_ARSIP = 'arsip';

    protected $fillable = [
        'user_id',
        'doc_type',
        'source',
        'nomor_aju',
        'nomor_daftar',
        'payload',
        'status',
        'jalur',
        'ceisa_response',
        'error_message',
        'submitted_at',
        'response_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'ceisa_response' => 'array',
            'submitted_at' => 'datetime',
            'response_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    public function isArchived(): bool
    {
        return $this->source === self::SOURCE_ARSIP;
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACCEPTED => 'green',
            self::STATUS_SUBMITTED, self::STATUS_SUBMITTING => 'blue',
            self::STATUS_REJECTED, self::STATUS_ERROR => 'red',
            default => 'gray',
        };
    }

    /**
     * Label & warna jalur pemeriksaan pabean (Hijau/Kuning/Merah).
     *
     * @return array{label: string, color: string}|null
     */
    public function jalurInfo(): ?array
    {
        return match ($this->jalur) {
            self::JALUR_HIJAU => ['label' => 'Jalur Hijau', 'color' => 'emerald'],
            self::JALUR_KUNING => ['label' => 'Jalur Kuning', 'color' => 'amber'],
            self::JALUR_MERAH => ['label' => 'Jalur Merah', 'color' => 'rose'],
            default => null,
        };
    }
}
