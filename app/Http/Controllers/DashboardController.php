<?php

namespace App\Http\Controllers;

use App\Models\CeisaCredential;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Daftar dokumen yang pernah disubmit user beserta statusnya.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Dashboard tim: tampilkan seluruh dokumen perusahaan.
        $documents = Document::query()->latest()->paginate(15);

        // Satu query agregat alih-alih beberapa count() terpisah.
        $agg = Document::query()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as draft', [Document::STATUS_DRAFT])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as submitted', [Document::STATUS_SUBMITTED])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as accepted', [Document::STATUS_ACCEPTED])
            ->selectRaw('sum(case when status in (?, ?) then 1 else 0 end) as rejected', [Document::STATUS_REJECTED, Document::STATUS_ERROR])
            ->selectRaw('sum(case when jalur = ? then 1 else 0 end) as jalur_hijau', [Document::JALUR_HIJAU])
            ->selectRaw('sum(case when jalur = ? then 1 else 0 end) as jalur_kuning', [Document::JALUR_KUNING])
            ->selectRaw('sum(case when jalur = ? then 1 else 0 end) as jalur_merah', [Document::JALUR_MERAH])
            ->first();

        $stats = [
            'total' => (int) $agg->total,
            'draft' => (int) $agg->draft,
            'submitted' => (int) $agg->submitted,
            'accepted' => (int) $agg->accepted,
            'rejected' => (int) $agg->rejected,
            'jalur_hijau' => (int) $agg->jalur_hijau,
            'jalur_kuning' => (int) $agg->jalur_kuning,
            'jalur_merah' => (int) $agg->jalur_merah,
        ];

        $credential = CeisaCredential::shared();
        $hasCredential = $credential !== null;
        $tokenActive = $credential?->hasValidToken() ?? false;
        $credentialEnv = match (true) {
            ! $credential => 'none',
            str_contains((string) $credential->base_url, 'apisdev-gw') => 'sandbox',
            str_contains((string) $credential->base_url, 'apis-gw') => 'production',
            default => 'custom',
        };

        return view('dashboard', compact('documents', 'stats', 'hasCredential', 'tokenActive', 'credentialEnv'));
    }
}
