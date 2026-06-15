<?php

namespace App\Http\Controllers;

use App\Exceptions\CeisaException;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Services\CeisaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    /**
     * Form input dokumen BC 3.0 (PEB Ekspor).
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->ceisaCredential) {
            return redirect()
                ->route('settings.ceisa.edit')
                ->with('error', 'Lengkapi kredensial CEISA (App ID & API Key) terlebih dahulu sebelum membuat dokumen.');
        }

        return view('documents.create', [
            'docTypes' => config('ceisa.doc_types'),
        ]);
    }

    /**
     * Simpan dokumen lalu submit ke CEISA.
     */
    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $user = $request->user();

        $credential = $user->ceisaCredential;
        abort_unless($credential, 403, 'Kredensial CEISA belum diatur.');

        $document = $user->documents()->create([
            'doc_type' => $request->validated('doc_type'),
            'payload' => $request->toCeisaPayload(),
            'status' => Document::STATUS_DRAFT,
        ]);

        try {
            CeisaService::forCredential($credential)->submit($document);
        } catch (CeisaException $e) {
            return redirect()
                ->route('documents.show', $document)
                ->with('error', 'Submit gagal: '.$e->getMessage());
        }

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Dokumen berhasil dikirim ke CEISA. Menunggu response.');
    }

    /**
     * Detail dokumen + status & response CEISA.
     */
    public function show(Request $request, Document $document): View
    {
        $this->authorizeOwnership($request, $document);

        $document->load('webhookLogs');

        return view('documents.show', compact('document'));
    }

    /**
     * Submit/resubmit dokumen yang masih draft atau error.
     */
    public function submit(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeOwnership($request, $document);

        $credential = $request->user()->ceisaCredential;
        abort_unless($credential, 403, 'Kredensial CEISA belum diatur.');

        if (! in_array($document->status, [Document::STATUS_DRAFT, Document::STATUS_ERROR], true)) {
            return back()->with('error', 'Dokumen ini sudah disubmit dan tidak dapat dikirim ulang.');
        }

        try {
            CeisaService::forCredential($credential)->submit($document);
        } catch (CeisaException $e) {
            return back()->with('error', 'Submit gagal: '.$e->getMessage());
        }

        return back()->with('success', 'Dokumen berhasil dikirim ulang ke CEISA.');
    }

    /**
     * Pastikan dokumen milik user yang sedang login.
     */
    protected function authorizeOwnership(Request $request, Document $document): void
    {
        abort_unless($document->user_id === $request->user()->id, 403);
    }
}
