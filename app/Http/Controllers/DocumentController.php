<?php

namespace App\Http\Controllers;

use App\Exceptions\CeisaException;
use App\Http\Requests\StoreArchiveDocumentRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\CeisaReference;
use App\Models\Document;
use App\Services\CeisaService;
use App\Services\CeisaStatusMapper;
use App\Services\DocumentValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Daftar dokumen lengkap dengan filter jenis/status/jalur/sumber/tanggal & pencarian.
     */
    public function index(Request $request): View
    {
        [$query, $filters] = $this->filteredDocuments($request);

        // Rekap agregat (mengikuti filter aktif) dalam satu query.
        $agg = (clone $query)
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as accepted', [Document::STATUS_ACCEPTED])
            ->selectRaw('sum(case when status in (?, ?) then 1 else 0 end) as rejected', [Document::STATUS_REJECTED, Document::STATUS_ERROR])
            ->selectRaw('sum(case when jalur = ? then 1 else 0 end) as merah', [Document::JALUR_MERAH])
            ->first();

        $rekap = [
            'total' => (int) $agg->total,
            'accepted' => (int) $agg->accepted,
            'rejected' => (int) $agg->rejected,
            'merah' => (int) $agg->merah,
        ];

        $documents = $query->latest()->paginate(20)->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'filters' => $filters,
            'rekap' => $rekap,
            'docTypes' => config('ceisa.doc_types'),
        ]);
    }

    /**
     * Ekspor daftar dokumen terfilter ke CSV (streamed, tanpa dependensi tambahan).
     */
    public function export(Request $request): StreamedResponse
    {
        [$query] = $this->filteredDocuments($request);

        $filename = 'dokumen-ceisa-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            // BOM agar Excel mengenali UTF-8.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['No. Aju', 'Nomor Daftar', 'Jenis', 'Status', 'Jalur', 'Sumber', 'Pihak/Entitas', 'NPWP', 'Dibuat']);

            $query->latest()->chunk(200, function ($chunk) use ($out) {
                foreach ($chunk as $doc) {
                    fputcsv($out, [
                        $doc->nomor_aju,
                        $doc->nomor_daftar,
                        $doc->doc_type,
                        $doc->status,
                        $doc->jalurInfo()['label'] ?? '',
                        $doc->isArchived() ? 'Arsip' : 'H2H',
                        $doc->partyName(),
                        $doc->partyNpwp(),
                        $doc->created_at?->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Bangun query dokumen milik user sesuai filter request + array filter.
     *
     * @return array{0: Builder, 1: array<string, mixed>}
     */
    protected function filteredDocuments(Request $request): array
    {
        $filters = [
            'q' => trim((string) $request->input('q')),
            'doc_type' => $request->input('doc_type'),
            'status' => $request->input('status'),
            'jalur' => $request->input('jalur'),
            'source' => $request->input('source'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        $query = $request->user()->documents()
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $term = '%'.$filters['q'].'%';
                $query->where(fn ($q) => $q
                    ->where('nomor_aju', 'like', $term)
                    ->orWhere('nomor_daftar', 'like', $term));
            })
            ->when($filters['doc_type'], fn ($q, $v) => $q->where('doc_type', $v))
            ->when($filters['status'], fn ($q, $v) => $q->where('status', $v))
            ->when($filters['jalur'], fn ($q, $v) => $q->where('jalur', $v))
            ->when($filters['source'], fn ($q, $v) => $q->where('source', $v))
            ->when($filters['from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v));

        return [$query, $filters];
    }

    /**
     * Duplikasi (Salin Data AJU) dokumen H2H menjadi draft baru siap kirim ulang.
     */
    public function duplicate(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeOwnership($request, $document);

        if ($document->isArchived()) {
            return back()->with('error', 'Dokumen arsip tidak dapat diduplikasi.');
        }

        $clone = $request->user()->documents()->create([
            'doc_type' => $document->doc_type,
            'source' => Document::SOURCE_H2H,
            'payload' => $document->payload,
            'status' => Document::STATUS_DRAFT,
        ]);

        return redirect()
            ->route('documents.show', $clone)
            ->with('success', 'Dokumen berhasil diduplikasi sebagai draft baru. Periksa data, lalu kirim ke CEISA.');
    }

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
     * Form ubah dokumen. Dokumen H2H (draft/error) memakai wizard; dokumen
     * arsip memakai form rekam manual. Keduanya di-pre-fill dari payload.
     */
    public function edit(Request $request, Document $document): View|RedirectResponse
    {
        $this->authorizeOwnership($request, $document);

        if (! $document->isEditable()) {
            return redirect()
                ->route('documents.show', $document)
                ->with('error', 'Dokumen ini tidak dapat diubah karena sudah dikirim/diterima CEISA.');
        }

        if ($document->isArchived()) {
            return view('documents.arsip', [
                'docTypes' => config('ceisa.doc_types'),
                'references' => CeisaReference::forWizard(),
                'editDocument' => $document,
                'values' => $document->toArchiveFormData(),
            ]);
        }

        if (! $request->user()->ceisaCredential) {
            return redirect()
                ->route('settings.ceisa.edit')
                ->with('error', 'Lengkapi kredensial CEISA terlebih dahulu sebelum mengubah dokumen.');
        }

        return view('documents.create', [
            'docTypes' => config('ceisa.doc_types'),
            'references' => CeisaReference::forWizard(),
            'editDocument' => $document,
            'editData' => $document->toFormData(),
        ]);
    }

    /**
     * Simpan perubahan dokumen H2H (wizard). Hanya draft/error yang boleh diubah.
     */
    public function update(StoreDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->authorizeOwnership($request, $document);

        if ($document->isArchived() || ! $document->isEditable()) {
            return redirect()
                ->route('documents.show', $document)
                ->with('error', 'Dokumen ini tidak dapat diubah.');
        }

        $document->update([
            'doc_type' => $request->validated('doc_type'),
            'payload' => $request->toCeisaPayload(),
        ]);

        if ($request->input('submit_action') === 'draft') {
            return redirect()
                ->route('documents.show', $document)
                ->with('success', 'Perubahan dokumen tersimpan sebagai draft.');
        }

        try {
            CeisaService::forCredential($request->user()->ceisaCredential)->submit($document);
        } catch (CeisaException $e) {
            return redirect()
                ->route('documents.show', $document)
                ->with('error', 'Perubahan tersimpan, namun submit gagal: '.$e->getMessage());
        }

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Dokumen berhasil diperbarui dan dikirim ke CEISA.');
    }

    /**
     * Simpan perubahan dokumen arsip (rekam manual).
     */
    public function updateArchive(StoreArchiveDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->authorizeOwnership($request, $document);

        if (! $document->isArchived()) {
            return redirect()
                ->route('documents.show', $document)
                ->with('error', 'Dokumen ini bukan dokumen arsip.');
        }

        $v = $request->validated();

        $document->update([
            'doc_type' => $v['doc_type'],
            'nomor_aju' => $v['nomor_aju'],
            'nomor_daftar' => $v['nomor_daftar'] ?? null,
            'status' => $v['status'],
            'jalur' => $v['jalur'] ?? null,
            'payload' => $request->toArchivePayload(),
            'submitted_at' => $v['tanggal_dokumen'] ?? $document->submitted_at,
        ]);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Dokumen arsip berhasil diperbarui.');
    }

    /**
     * Hapus dokumen milik user. Dokumen H2H yang sudah hidup di DJBC ditolak.
     */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeOwnership($request, $document);

        if (! $document->canBeDeleted()) {
            return back()->with('error', 'Dokumen yang sudah terkirim/diterima CEISA tidak dapat dihapus demi jejak audit.');
        }

        $document->delete();

        return redirect()
            ->route('documents.index')
            ->with('success', 'Dokumen berhasil dihapus.');
    }

    /**
     * Validasi cerdas (hybrid AI + aturan) sebelum dokumen dikirim ke CEISA.
     */
    public function validateAi(Request $request, Document $document, DocumentValidator $validator): RedirectResponse
    {
        $this->authorizeOwnership($request, $document);

        $result = $validator->validate($document);

        return redirect()
            ->route('documents.show', $document)
            ->with('ai_validation', $result);
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
     * Tarik status terbaru dokumen dari CEISA (polling manual) lalu terapkan
     * ke dokumen. Memprioritaskan idHeader hasil submit; fallback nomor aju.
     */
    public function refreshStatus(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeOwnership($request, $document);

        if ($document->isArchived()) {
            return back()->with('error', 'Dokumen arsip tidak memiliki status CEISA untuk ditarik.');
        }

        if (empty($document->nomor_aju)) {
            return back()->with('error', 'Dokumen belum memiliki nomor aju — kirim ke CEISA terlebih dahulu.');
        }

        $credential = $request->user()->ceisaCredential;
        abort_unless($credential, 403, 'Kredensial CEISA belum diatur.');

        try {
            $data = CeisaService::forCredential($credential)
                ->queryDocumentStatus($document->nomor_aju, $document->id_header);
        } catch (CeisaException $e) {
            return back()->with('error', 'Gagal menarik status: '.$e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghubungi server CEISA: '.$e->getMessage());
        }

        CeisaStatusMapper::apply($document, $data);

        return back()->with('success', 'Status dokumen berhasil diperbarui dari CEISA.');
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
        if (! empty($nilaiRaw)) {
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
     * Unduh file respon PDF dari CEISA.
     */
    public function downloadRespon(Request $request, Document $document)
    {
        $this->authorizeOwnership($request, $document);
        $credential = $request->user()->ceisaCredential;
        abort_unless($credential, 403, 'Kredensial CEISA belum diatur.');

        // Cari path di ceisa_response atau webhookLogs
        $path = data_get($document->ceisa_response, 'data.responPdf')
            ?? data_get($document->ceisa_response, 'data.pathRespon')
            ?? data_get($document->ceisa_response, 'responPdf')
            ?? data_get($document->ceisa_response, 'pathRespon')
            ?? $document->webhookLogs->map(fn ($log) => data_get($log->payload, 'data.responPdf') ?? data_get($log->payload, 'data.pathRespon') ?? data_get($log->payload, 'pathRespon'))->filter()->first();

        if (! $path) {
            $path = $request->query('path');
        }

        if (! $path) {
            return back()->with('error', 'Path file respon tidak ditemukan di data dokumen. Pastikan webhook respon sudah masuk.');
        }

        try {
            $service = CeisaService::forCredential($credential);
            $response = $service->downloadRespon($path);

            if ($response->successful()) {
                return response()->streamDownload(function () use ($response) {
                    echo $response->body();
                }, "respon-ceisa-{$document->nomor_aju}.pdf", [
                    'Content-Type' => 'application/pdf',
                ]);
            }

            return back()->with('error', 'Gagal mengunduh respon dari CEISA (HTTP '.$response->status().').');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengunduh: '.$e->getMessage());
        }
    }

    /**
     * Cetak formulir dokumen pabean PDF dari CEISA.
     */
    public function cetakFormulir(Request $request, Document $document)
    {
        $this->authorizeOwnership($request, $document);
        $credential = $request->user()->ceisaCredential;
        abort_unless($credential, 403, 'Kredensial CEISA belum diatur.');

        if (empty($document->nomor_aju)) {
            return back()->with('error', 'Nomor aju dokumen kosong. Kirim dokumen terlebih dahulu.');
        }

        try {
            $service = CeisaService::forCredential($credential);
            $response = $service->cetakFormulir($document->nomor_aju);

            if ($response->successful()) {
                return response()->streamDownload(function () use ($response) {
                    echo $response->body();
                }, "formulir-ceisa-{$document->nomor_aju}.pdf", [
                    'Content-Type' => 'application/pdf',
                ]);
            }

            return back()->with('error', 'Gagal mencetak formulir dari CEISA (HTTP '.$response->status().').');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mencetak: '.$e->getMessage());
        }
    }

    /**
     * Unduh PDF billing dari CEISA.
     */
    public function downloadBilling(Request $request, Document $document)
    {
        $this->authorizeOwnership($request, $document);
        $credential = $request->user()->ceisaCredential;
        abort_unless($credential, 403, 'Kredensial CEISA belum diatur.');

        $kodeBilling = data_get($document->ceisa_response, 'data.kodeBilling')
            ?? data_get($document->ceisa_response, 'kodeBilling')
            ?? $document->webhookLogs->map(fn ($log) => data_get($log->payload, 'data.kodeBilling') ?? data_get($log->payload, 'kodeBilling'))->filter()->first();

        if (! $kodeBilling) {
            $kodeBilling = $request->query('kode_billing');
        }

        if (! $kodeBilling) {
            return back()->with('error', 'Kode billing tidak ditemukan di data dokumen.');
        }

        try {
            $service = CeisaService::forCredential($credential);
            $response = $service->downloadBilling($kodeBilling);

            if ($response->successful()) {
                return response()->streamDownload(function () use ($response) {
                    echo $response->body();
                }, "billing-ceisa-{$kodeBilling}.pdf", [
                    'Content-Type' => 'application/pdf',
                ]);
            }

            return back()->with('error', 'Gagal mengunduh billing dari CEISA (HTTP '.$response->status().').');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengunduh billing: '.$e->getMessage());
        }
    }

    /**
     * Pastikan dokumen milik user yang sedang login.
     */
    protected function authorizeOwnership(Request $request, Document $document): void
    {
        abort_unless($document->user_id === $request->user()->id, 403);
    }
}
