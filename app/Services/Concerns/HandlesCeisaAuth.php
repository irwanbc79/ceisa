<?php

namespace App\Services\Concerns;

use App\Exceptions\CeisaException;
use App\Models\CeisaCredential;
use Illuminate\Support\Carbon;

/**
 * Manajemen token akses CEISA H2H (login, refresh, penyimpanan).
 *
 * Trait ini menangani siklus hidup token:
 *   1. getToken()             → login penuh, simpan access_token + refresh_token.
 *   2. refreshAccessToken()   → tukar refresh_token jadi access_token baru.
 *   3. refreshTokenIfExpired() → pakai token valid, coba refresh, fallback login.
 */
trait HandlesCeisaAuth
{
    abstract protected function credential(): CeisaCredential;

    /**
     * Ambil token akses dari CEISA dan simpan ke DB.
     *
     * @throws CeisaException
     */
    public function getToken(): string
    {
        $endpoint = config('ceisa.endpoints.token');

        try {
            $response = $this->baseRequest()->post($endpoint, [
                'username' => $this->credential()->username,
                'password' => $this->credential()->password,
            ]);
        } catch (\Throwable $e) {
            throw new CeisaException(
                'Gagal terhubung ke server CEISA saat login: '.$e->getMessage(),
                previous: $e,
            );
        }

        $data = $this->decode($response);
        $this->guardAgainstErrorCode($data, $response);

        if (! $response->successful()) {
            throw new CeisaException(
                'Gagal login ke CEISA (HTTP '.$response->status().').',
                context: $data,
            );
        }

        $token = $this->extractToken($data);

        if (empty($token)) {
            throw new CeisaException(
                'Response login CEISA tidak mengandung access_token.',
                context: $data,
            );
        }

        $this->storeToken($token, $this->extractRefreshToken($data), $this->extractExpiresIn($data));

        return $token;
    }

    /**
     * Tukar refresh_token menjadi access_token baru tanpa login ulang penuh.
     * Endpoint: POST /nle-oauth/v1/user/update-token, header Authorization: {refresh_token}.
     *
     * @throws CeisaException
     */
    public function refreshAccessToken(): string
    {
        $refreshToken = $this->credential()->refresh_token;

        if (empty($refreshToken)) {
            return $this->getToken();
        }

        $endpoint = config('ceisa.endpoints.refresh_token');

        try {
            $response = $this->baseRequest()
                ->withHeaders(['Authorization' => $refreshToken])
                ->post($endpoint);
        } catch (\Throwable $e) {
            throw new CeisaException(
                'Gagal terhubung ke server CEISA saat refresh token: '.$e->getMessage(),
                previous: $e,
            );
        }

        $data = $this->decode($response);

        // Refresh token kadaluarsa / ditolak → jatuh ke login penuh.
        if (! $response->successful()) {
            return $this->getToken();
        }

        $token = $this->extractToken($data);

        if (empty($token)) {
            return $this->getToken();
        }

        // update-token bisa mengembalikan refresh_token baru; pertahankan yang lama bila tidak.
        $this->storeToken(
            $token,
            $this->extractRefreshToken($data) ?: $refreshToken,
            $this->extractExpiresIn($data),
        );

        return $token;
    }

    /**
     * Pastikan token masih valid; refresh bila sudah/akan kadaluarsa.
     * Pakai refresh_token bila tersedia, fallback login penuh.
     *
     * @throws CeisaException
     */
    public function refreshTokenIfExpired(): string
    {
        if ($this->credential()->hasValidToken()) {
            return $this->credential()->token;
        }

        return $this->refreshAccessToken();
    }

    /**
     * Simpan access_token (+ refresh_token & masa berlaku) ke DB.
     */
    protected function storeToken(string $token, ?string $refreshToken, int $expiresIn): void
    {
        $attributes = [
            'token' => $token,
            'token_expires_at' => Carbon::now()->addSeconds($expiresIn),
        ];

        if (! empty($refreshToken)) {
            $attributes['refresh_token'] = $refreshToken;
        }

        $this->credential()->forceFill($attributes)->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractToken(array $data): ?string
    {
        return $data['access_token']
            ?? $data['token']
            ?? data_get($data, 'item.access_token')
            ?? data_get($data, 'data.access_token');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractRefreshToken(array $data): ?string
    {
        return $data['refresh_token']
            ?? data_get($data, 'item.refresh_token')
            ?? data_get($data, 'data.refresh_token');
    }

    /**
     * expires_in (detik). Access Token CEISA 4.0 berumur ~5 menit;
     * fallback config token_ttl_fallback bila server tak menyertakannya.
     *
     * @param  array<string, mixed>  $data
     */
    protected function extractExpiresIn(array $data): int
    {
        return (int) ($data['expires_in']
            ?? data_get($data, 'item.expires_in')
            ?? data_get($data, 'data.expires_in')
            ?? config('ceisa.token_ttl_fallback', 300));
    }

    /**
     * Ambil idHeader (UUID) dari response submit CEISA.
     * Respons sukses resmi: {"status":"OK","message":"...","idHeader":"{UUID}"}.
     *
     * @param  array<string, mixed>  $data
     */
    protected function extractIdHeader(array $data): ?string
    {
        $value = $data['idHeader']
            ?? $data['id_header']
            ?? data_get($data, 'data.idHeader')
            ?? data_get($data, 'item.idHeader');

        return ! empty($value) ? (string) $value : null;
    }
}
