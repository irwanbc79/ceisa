<?php

namespace App\Http\Controllers;

use App\Exceptions\CeisaException;
use App\Models\CeisaCredential;
use App\Models\Manifest;
use App\Services\CeisaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManifestController extends Controller
{
    /**
     * Monitoring Manifes (BC 1.1) — daftar sarana pengangkut kedatangan/keberangkatan.
     */
    public function index(Request $request): View
    {
        $filters = [
            'jenis' => $request->input('jenis'),
            'kantor' => $request->input('kantor'),
            'q' => trim((string) $request->input('q')),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        // Manifes milik perusahaan: seluruh staf melihat data yang sama.
        $manifests = Manifest::query()
            ->when($filters['jenis'], fn ($q, $v) => $q->where('jenis_manifes', $v))
            ->when($filters['kantor'], fn ($q, $v) => $q->where('kode_kantor', $v))
            ->when($filters['q'], fn ($q, $v) => $q->where(function ($qq) use ($v) {
                $qq->where('nama_sarana', 'like', "%{$v}%")
                    ->orWhere('nomor_voyage', 'like', "%{$v}%")
                    ->orWhere('nomor_imo', 'like', "%{$v}%")
                    ->orWhere('nomor_daftar', 'like', "%{$v}%");
            }))
            ->when($filters['from'], fn ($q, $v) => $q->whereDate('tanggal_sarana', '>=', $v))
            ->when($filters['to'], fn ($q, $v) => $q->whereDate('tanggal_sarana', '<=', $v))
            ->latest('tanggal_sarana')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('manifest.index', compact('manifests', 'filters'));
    }

    /**
     * Tarik data manifes dari CEISA (membutuhkan kredensial & endpoint manifes aktif).
     */
    public function sync(Request $request): RedirectResponse
    {
        $credential = CeisaCredential::shared();

        if (! $credential) {
            return back()->with('error', 'Simpan kredensial CEISA terlebih dahulu di Pengaturan sebelum menarik data manifes.');
        }

        try {
            $jenis = $request->input('jenis', Manifest::JENIS_INWARD);
            $rows = CeisaService::forCredential($credential)->fetchManifests([
                'jenis' => $jenis,
                'npwp' => $credential->npwp,
            ]);
        } catch (CeisaException $e) {
            return back()->with('error', 'Gagal menarik manifes dari CEISA: '.$e->getMessage());
        }

        $count = 0;
        foreach ($rows as $row) {
            $nomorDaftar = data_get($row, 'nomorDaftar') ?? data_get($row, 'nomor_daftar');
            // Dedup company-wide (tanpa user_id di key) — user_id mencatat
            // staf yang terakhir menarik data.
            Manifest::updateOrCreate(
                [
                    'nomor_daftar' => $nomorDaftar,
                    'nomor_voyage' => data_get($row, 'nomorVoyage') ?? data_get($row, 'nomor_voyage'),
                ],
                [
                    'user_id' => $request->user()->id,
                    'jenis_manifes' => $jenis,
                    'nama_sarana' => data_get($row, 'namaSarana') ?? data_get($row, 'nama_sarana'),
                    'nomor_imo' => data_get($row, 'nomorImo') ?? data_get($row, 'nomor_imo'),
                    'call_sign' => data_get($row, 'callSign'),
                    'kode_bendera' => data_get($row, 'kodeBendera'),
                    'kode_kantor' => data_get($row, 'kodeKantor') ?? data_get($row, 'kode_kantor'),
                    'tanggal_sarana' => data_get($row, 'tanggalTiba') ?? data_get($row, 'tanggalBerangkat') ?? data_get($row, 'tanggal_sarana'),
                    'tanggal_daftar' => data_get($row, 'tanggalDaftar') ?? data_get($row, 'tanggal_daftar'),
                    'status' => data_get($row, 'status'),
                    'payload' => $row,
                    'synced_at' => now(),
                ],
            );
            $count++;
        }

        return back()->with('success', "Berhasil menarik {$count} data manifes dari CEISA.");
    }
}
