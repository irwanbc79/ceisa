<?php

namespace App\Http\Controllers;

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

        $stats = [
            'total' => $user->documents()->count(),
            'submitted' => $user->documents()->where('status', 'submitted')->count(),
            'accepted' => $user->documents()->where('status', 'accepted')->count(),
            'rejected' => $user->documents()->whereIn('status', ['rejected', 'error'])->count(),
        ];

        $hasCredential = (bool) $user->ceisaCredential;

        return view('dashboard', compact('documents', 'stats', 'hasCredential'));
    }
}
