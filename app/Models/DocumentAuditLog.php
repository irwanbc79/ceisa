<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Jejak audit kepabeanan (append-only / immutable) untuk setiap aksi dokumen.
 */
class DocumentAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'user_id',
        'action',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
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

    /**
     * Catat satu entri audit secara immutable.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        Document $document,
        string $action,
        string $description,
        array $metadata = [],
        ?User $actor = null,
        ?Request $request = null,
    ): self {
        $req = $request ?? (app()->bound('request') ? request() : null);
        $user = $actor ?? $req?->user();

        return self::create([
            'document_id' => $document->id,
            'user_id' => $user?->id,
            'action' => Str::limit($action, 64, ''),
            'description' => Str::limit($description, 500),
            'metadata' => empty($metadata) ? null : $metadata,
            'ip_address' => $req?->ip(),
            'user_agent' => $req?->userAgent() ? Str::limit((string) $req->userAgent(), 255, '') : null,
            'created_at' => now(),
        ]);
    }
}
