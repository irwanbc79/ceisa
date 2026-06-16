<?php

namespace App\Http\Controllers;

use App\Exceptions\CeisaException;
use App\Http\Requests\StoreArchiveDocumentRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\CeisaReference;
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
            'references' => CeisaReference::forWizard(),
        ]);
    }

    /**
     * Form rekam manual (arsip) dokumen lama PIB/PEB dari portal DJBC.
     */
    public function archiveCreate(Request $request): View
    {
        return view('documents.arsip', [
            'docTypes' => config('ceisa.doc_types'),
            'references' => CeisaReference::forWizard(),
        ]);
    }

    /**
     * Simpan dokumen arsip (tidak dikirim ke CEISA) agar tampil di riwayat.
     */
    public function archiveStore(StoreArchiveDocumentRequest $request): RedirectResponse
    {
        $v = $request->validated();

        $document = $request->user()->documents()->create([
            'doc_type' => $v['doc_type'],
            'source' => Document::SOURCE_ARSIP,
            'nomor_aju' => $v['nomor_aju'],
            'nomor_daftar' => $v['nomor_daftar'] ?? null,
            'status' => $v['status'],
            'jalur' => $v['jalur'] ?? null,
            'payload' => $request->toArchivePayload(),
            'submitted_at' => $v['tanggal_dokumen'] ?? null,
        ]);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Dokumen arsip berhasil direkam ke riwayat M2B.');
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

        if ($request->input('submit_action') === 'draft') {
            return redirect()
                ->route('documents.show', $document)
                ->with('success', 'Dokumen berhasil disimpan sebagai draft.');
        }

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
     * Halaman pencarian / lookup dokumen historis dari CEISA berdasarkan nomor aju.
     */
    public function lookup(Request $request): View|RedirectResponse
    {
        if (! $request->user()->ceisaCredential) {
            return redirect()
                ->route('settings.ceisa.edit')
                ->with('error', 'Lengkapi kredensial CEISA terlebih dahulu.');
        }

        return view('documents.lookup');
    }

    /**
     * Query status dokumen ke CEISA berdasarkan nomor aju yang diinput user.
     */
    public function lookupSearch(Request $request): View
    {
        $request->validate([
            'nomor_aju' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $credential = $user->ceisaCredential;
        abort_unless($credential, 403, 'Kredensial CEISA belum diatur.');

        $nomorAju = trim($request->input('nomor_aju'));
        $result = null;
        $error = null;
        $localDoc = Document::where('nomor_aju', $nomorAju)->where('user_id', $user->id)->first();

        try {
            $service = CeisaService::forCredential($credential);
            $result = $service->queryDocumentStatus($nomorAju);
        } catch (CeisaException $e) {
            $error = $e->getMessage();
        } catch (\Throwable $e) {
            $error = 'Gagal menghubungi server CEISA: '.$e->getMessage();
        }

        return view('documents.lookup', compact('result', 'error', 'nomorAju', 'localDoc'));
    }

    /**
     * Pastikan dokumen milik user yang sedang login.
     */
    protected function authorizeOwnership(Request $request, Document $document): void
    {
        abort_unless($document->user_id === $request->user()->id, 403);
    }
}
