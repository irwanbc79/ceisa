<?php

namespace App\Http\Controllers;

use App\Exceptions\CeisaException;
use App\Models\CeisaCredential;
use App\Services\CeisaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CeisaSettingController extends Controller
{
    /**
     * Halaman kredensial CEISA perusahaan (form edit hanya untuk admin).
     */
    public function edit(Request $request): View
    {
        $credential = CeisaCredential::shared();

        return view('settings.ceisa', compact('credential'));
    }

    /**
     * Simpan / perbarui kredensial CEISA perusahaan (khusus admin, via route).
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:20'],
            // password & api_key opsional saat update (kosong = pertahankan yang lama)
            'password' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:1000'],
            'id_platform' => ['nullable', 'string', 'max:255'],
            'app_id' => ['nullable', 'string', 'max:255'],
            'environment' => ['required', 'string', 'in:production,sandbox,custom'],
            'custom_base_url' => ['required_if:environment,custom', 'nullable', 'string', 'max:500'],
        ]);

        $credential = CeisaCredential::shared();

        // Saat pertama kali menyimpan, password & beacukai-api-key wajib diisi.
        if (! $credential) {
            $missing = [];
            if (empty($data['password'])) {
                $missing['password'] = 'Password wajib diisi saat pertama kali menyimpan kredensial.';
            }
            if (empty($data['api_key'])) {
                $missing['api_key'] = 'Beacukai API Key wajib diisi saat pertama kali menyimpan kredensial.';
            }
            if ($missing) {
                return back()->withErrors($missing);
            }
        }

        $baseUrl = match ($data['environment']) {
            'sandbox' => 'https://apisdev-gw.beacukai.go.id',
            'production' => 'https://apis-gw.beacukai.go.id',
            'custom' => $data['custom_base_url'] ? rtrim($data['custom_base_url'], '/') : null,
        };

        $attributes = [
            'username' => $data['username'],
            'npwp' => $data['npwp'] ?? null,
            'id_platform' => $data['id_platform'] ?? null,
            'app_id' => $data['app_id'] ?? null,
            'base_url' => $baseUrl,
        ];

        $secretChanged = false;

        if ($credential && $credential->base_url !== $baseUrl) {
            $secretChanged = true;
        }

        foreach (['password', 'api_key', 'id_platform'] as $secret) {
            if (! empty($data[$secret])) {
                $attributes[$secret] = $data[$secret];
                $secretChanged = true;
            }
        }

        // Kredensial rahasia berubah -> token & refresh_token lama tidak valid lagi.
        if ($secretChanged) {
            $attributes['token'] = null;
            $attributes['refresh_token'] = null;
            $attributes['token_expires_at'] = null;
        }

        // Perbarui baris kredensial PERUSAHAAN (bukan per-user) — bila belum
        // ada, buat baru dan tandai admin ini sebagai pengelolanya.
        if ($credential) {
            $credential->update($attributes);
        } else {
            $request->user()->ceisaCredential()->create($attributes);
        }

        return back()->with('success', 'Kredensial CEISA perusahaan berhasil disimpan.');
    }

    /**
     * Uji koneksi: coba ambil token dari CEISA.
     */
    public function test(Request $request): RedirectResponse
    {
        $credential = CeisaCredential::shared();

        if (! $credential) {
            return back()->with('error', 'Simpan kredensial terlebih dahulu sebelum menguji koneksi.');
        }

        try {
            CeisaService::forCredential($credential)->getToken();
        } catch (CeisaException $e) {
            return back()->with('error', 'Uji koneksi gagal: '.$e->getMessage());
        }

        return back()->with('success', 'Koneksi CEISA berhasil. Token diperoleh dan disimpan.');
    }
}
