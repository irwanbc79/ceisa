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

    protected $fillable = [
        'user_id',
        'doc_type',
        'nomor_aju',
        'nomor_daftar',
        'payload',
        'status',
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

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACCEPTED => 'green',
            self::STATUS_SUBMITTED, self::STATUS_SUBMITTING => 'blue',
            self::STATUS_REJECTED, self::STATUS_ERROR => 'red',
            default => 'gray',
        };
    }
}
