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
            'app_id' => ['required', 'string', 'max:255'],
            // api_key opsional saat update (kosong = pertahankan yang lama)
            'api_key' => ['nullable', 'string', 'max:1000'],
        ]);

        $credential = $request->user()->ceisaCredential;

        if (! $credential && empty($data['api_key'])) {
            return back()->withErrors(['api_key' => 'API Key wajib diisi saat pertama kali menyimpan kredensial.']);
        }

        $attributes = ['app_id' => $data['app_id']];

        if (! empty($data['api_key'])) {
            $attributes['api_key'] = $data['api_key'];
            // Kredensial berubah -> token lama tidak valid lagi.
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
