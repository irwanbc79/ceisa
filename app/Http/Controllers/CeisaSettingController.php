<?php

namespace App\Http\Controllers;

use App\Exceptions\CeisaException;
use App\Services\CeisaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CeisaSettingController extends Controller
{
    /**
     * Form input app_id & api_key CEISA.
     */
    public function edit(Request $request): View
    {
        $credential = $request->user()->ceisaCredential;

        return view('settings.ceisa', compact('credential'));
    }

    /**
     * Simpan / perbarui kredensial CEISA milik user.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            // password & api_key opsional saat update (kosong = pertahankan yang lama)
            'password' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:1000'],
            'app_id' => ['nullable', 'string', 'max:255'],
            'environment' => ['required', 'string', 'in:production,sandbox,custom'],
            'custom_base_url' => ['required_if:environment,custom', 'nullable', 'string', 'max:500'],
        ]);

        $credential = $request->user()->ceisaCredential;

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
            'app_id' => $data['app_id'] ?? null,
            'base_url' => $baseUrl,
        ];

        $secretChanged = false;

        if ($credential && $credential->base_url !== $baseUrl) {
            $secretChanged = true;
        }

        foreach (['password', 'api_key'] as $secret) {
            if (! empty($data[$secret])) {
                $attributes[$secret] = $data[$secret];
                $secretChanged = true;
            }
        }

        // Kredensial rahasia berubah -> token lama tidak valid lagi.
        if ($secretChanged) {
            $attributes['token'] = null;
            $attributes['token_expires_at'] = null;
        }

        $request->user()->ceisaCredential()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $attributes,
        );

        return back()->with('success', 'Kredensial CEISA berhasil disimpan.');
    }

    /**
     * Uji koneksi: coba ambil token dari CEISA.
     */
    public function test(Request $request): RedirectResponse
    {
        $credential = $request->user()->ceisaCredential;

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
