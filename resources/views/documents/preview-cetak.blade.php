<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Formulir {{ $document->doc_type }} — {{ $document->nomor_aju ?: 'DRAFT' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }
        @media print {
            body {
                background: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased print:bg-white">
    @php
        $docTypeLabels = config('ceisa.doc_types', []);
        $normalized = array_merge([
            'nomor_aju' => $document->nomor_aju ?? '',
            'barang' => [],
            'dokumen' => [],
            'kontainer' => [],
        ], $payload);
        $normalized['barang'] = is_array($normalized['barang'] ?? null) ? array_values($normalized['barang']) : [];
        $normalized['dokumen'] = is_array($normalized['dokumen'] ?? null) ? array_values($normalized['dokumen']) : [];
        $normalized['kontainer'] = is_array($normalized['kontainer'] ?? null) ? array_values($normalized['kontainer']) : [];
    @endphp

    <div x-data="{
            doc_type: @js($document->doc_type ?? 'BC30'),
            docTypes: @js($docTypeLabels),
            formData: @js($normalized),
            getDocTypeLabel() {
                return this.docTypes[this.doc_type] || this.doc_type;
            },
            getPartyNPWP() {
                return this.doc_type === 'BC30'
                    ? (this.formData.npwp_eksportir || '')
                    : (this.formData.npwp_importir || '');
            },
            getPartyName() {
                return this.doc_type === 'BC30'
                    ? (this.formData.nama_eksportir || '')
                    : (this.formData.nama_importir || '');
            },
            getTotalValue() {
                const items = Array.isArray(this.formData.barang) ? this.formData.barang : [];
                return items.reduce((sum, item) => sum + (parseFloat(item.fob || item.cif || 0) || 0), 0);
            },
            todayFormatted() {
                const d = new Date();
                return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
            }
        }"
        class="min-h-screen flex flex-col">

        {{-- Non-printable Toolbar --}}
        <div class="sticky top-0 z-20 bg-slate-900 text-white px-5 py-3 flex items-center justify-between border-b border-slate-700 shadow-md print:hidden">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 rounded bg-indigo-500/30 border border-indigo-400/40 text-[11px] font-extrabold tracking-wide">{{ $document->doc_type }}</span>
                <div>
                    <div class="font-bold text-sm leading-tight">Preview &amp; Cetak Formulir Resmi CEISA 4.0</div>
                    <div class="text-[11px] text-slate-300 font-mono">
                        No. Aju: {{ $document->nomor_aju ?: 'DRAFT' }}
                        @if ($document->nomor_daftar)
                            &nbsp;|&nbsp; No. Daftar: {{ $document->nomor_daftar }}
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                @if ($document->nomor_daftar)
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-500/20 text-emerald-300 text-[11px] font-bold rounded-full border border-emerald-400/30">
                        Terdaftar: {{ $document->nomor_daftar }}
                    </span>
                @else
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-500/20 text-amber-300 text-[11px] font-bold rounded-full border border-amber-400/30">
                        {{ strtoupper($document->status ?? 'DRAFT') }}
                    </span>
                @endif
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-extrabold rounded-lg shadow-sm transition cursor-pointer">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Z" /></svg>
                    Cetak / Simpan PDF
                </button>
                <a href="{{ route('documents.show', $document) }}"
                   class="inline-flex items-center px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-lg transition">
                    Kembali
                </a>
            </div>
        </div>

        {{-- A4 Sheet Container --}}
        <div class="max-w-5xl w-full mx-auto my-4 print:my-0 print:max-w-none">
            @include('documents.partials._official-form-sheet')
        </div>
    </div>
</body>
</html>
