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
     * Impor dokumen hasil query status CEISA menjadi dokumen arsip lokal.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'nomor_aju' => ['required', 'string', 'max:100'],
            'nomor_daftar' => ['nullable', 'string', 'max:100'],
            'jenis_doc' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:100'],
            'kantor' => ['nullable', 'string', 'max:100'],
            'tanggal_daftar' => ['nullable', 'string', 'max:100'],
            'nilai_pabean' => ['nullable', 'string', 'max:100'],
            'nama_perusahaan' => ['required', 'string', 'max:255'],
            'uraian' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        // 1. Cek duplikasi
        $exists = Document::where('nomor_aju', $request->input('nomor_aju'))->where('user_id', $user->id)->exists();
        if ($exists) {
            return back()->with('error', 'Dokumen dengan nomor aju ini sudah ada di database lokal.');
        }

        // 2. Normalisasi jenis_doc ke doc_type (BC30, BC20, BC24, TPB, RUSH)
        $jenisDoc = strtoupper((string) $request->input('jenis_doc'));
        $docType = 'BC30'; // fallback
        if (str_contains($jenisDoc, '30') || str_contains($jenisDoc, 'PEB') || str_contains($jenisDoc, 'EKSPOR')) {
            $docType = 'BC30';
        } elseif (str_contains($jenisDoc, '20') || str_contains($jenisDoc, 'PIB') || str_contains($jenisDoc, 'IMPOR')) {
            $docType = 'BC20';
        } elseif (str_contains($jenisDoc, '24')) {
            $docType = 'BC24';
        } elseif (str_contains($jenisDoc, 'TPB')) {
            $docType = 'TPB';
        } elseif (str_contains($jenisDoc, 'RUSH') || str_contains($jenisDoc, 'SEGERA')) {
            $docType = 'RUSH';
        }

        // 3. Normalisasi status
        $rawStatus = strtoupper((string) $request->input('status'));
        $status = Document::STATUS_SUBMITTED; // fallback
        if (str_contains($rawStatus, 'TERIMA') || str_contains($rawStatus, 'ACCEPT') || str_contains($rawStatus, 'SPPB') || str_contains($rawStatus, 'SELESAI')) {
            $status = Document::STATUS_ACCEPTED;
        } elseif (str_contains($rawStatus, 'TOLAK') || str_contains($rawStatus, 'REJECT') || str_contains($rawStatus, 'NPP')) {
            $status = Document::STATUS_REJECTED;
        }

        // Parse nilai numeric if possible
        $nilaiRaw = $request->input('nilai_pabean');
        $nilai = null;
        if (!empty($nilaiRaw)) {
            $clean = trim($nilaiRaw);
            $lastComma = strrpos($clean, ',');
            $lastDot = strrpos($clean, '.');
            
            if ($lastComma !== false && $lastDot !== false) {
                if ($lastComma > $lastDot) {
                    $clean = str_replace('.', '', $clean);
                    $clean = str_replace(',', '.', $clean);
                } else {
                    $clean = str_replace(',', '', $clean);
                }
            } elseif ($lastComma !== false) {
                $clean = str_replace(',', '.', $clean);
            }
            
            $nilai = is_numeric($clean) ? (float) $clean : null;
        }

        // 4. Buat dokumen dengan source = 'arsip' agar muncul sebagai dokumen arsip historis
        $document = $user->documents()->create([
            'doc_type' => $docType,
            'source' => Document::SOURCE_ARSIP,
            'nomor_aju' => $request->input('nomor_aju'),
            'nomor_daftar' => $request->input('nomor_daftar'),
            'status' => $status,
            'payload' => [
                'arsip' => true,
                'nama_perusahaan' => $request->input('nama_perusahaan'),
                'kantor_pabean' => $request->input('kantor'),
                'tanggal_dokumen' => $request->input('tanggal_daftar'),
                'nilai' => $nilai,
                'valuta' => 'USD', // default fallback
                'uraian' => $request->input('uraian'),
                'keterangan' => 'Diimpor dari Portal CEISA DJBC',
            ],
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Dokumen berhasil diimpor dari portal CEISA ke riwayat M2B.');
    }

    /**
     * Pastikan dokumen milik user yang sedang login.
     */
    protected function authorizeOwnership(Request $request, Document $document): void
    {
        abort_unless($document->user_id === $request->user()->id, 403);
    }
}
