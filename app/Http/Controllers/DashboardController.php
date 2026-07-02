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

        $documents = $user->documents()->latest()->paginate(15);

        // Satu query agregat alih-alih empat count() terpisah.
        $agg = $user->documents()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as submitted', [Document::STATUS_SUBMITTED])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as accepted', [Document::STATUS_ACCEPTED])
            ->selectRaw('sum(case when status in (?, ?) then 1 else 0 end) as rejected', [Document::STATUS_REJECTED, Document::STATUS_ERROR])
            ->first();

        $stats = [
            'total' => (int) $agg->total,
            'submitted' => (int) $agg->submitted,
            'accepted' => (int) $agg->accepted,
            'rejected' => (int) $agg->rejected,
        ];

        $hasCredential = CeisaCredential::shared() !== null;

        return view('dashboard', compact('documents', 'stats', 'hasCredential'));
    }
}
