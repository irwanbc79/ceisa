<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CeisaCredential extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'npwp',
        'password',
        'app_id',
        'api_key',
        'id_platform',
        'base_url',
        'token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $hidden = [
        'username',
        'password',
        'api_key',
        'token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            // Kredensial sensitif disimpan terenkripsi di DB (Laravel encrypted cast)
            'username' => 'encrypted',
            'password' => 'encrypted',
            'api_key' => 'encrypted',
            'token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Kredensial CEISA PERUSAHAAN (dipakai bersama seluruh staf).
     *
     * Aplikasi ini satu-perusahaan per instalasi: admin mengisi kredensial
     * portal CEISA sekali via Pengaturan, semua operator memakainya —
     * termasuk access/refresh token yang tersimpan di baris yang sama,
     * sehingga tidak ada login CEISA ganda antar staf. user_id pada baris
     * hanya menandai admin yang mengelola.
     */
    public static function shared(): ?self
    {
        return static::query()->orderBy('id')->first();
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
