<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CeisaCredential extends Model
{
    protected $fillable = [
        'user_id',
        'app_id',
        'api_key',
        'token',
        'token_expires_at',
    ];

    protected $hidden = [
        'api_key',
        'token',
    ];

    protected function casts(): array
    {
        return [
            // api_key & token disimpan terenkripsi di DB (Laravel encrypted cast)
            'api_key' => 'encrypted',
            'token' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Apakah token masih ada & belum kadaluarsa (dengan margin keamanan).
     */
    public function hasValidToken(): bool
    {
        if (empty($this->token) || is_null($this->token_expires_at)) {
            return false;
        }

        $margin = (int) config('ceisa.token_refresh_margin', 60);

        return $this->token_expires_at->subSeconds($margin)->isFuture();
    }
}
