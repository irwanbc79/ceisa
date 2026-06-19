<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 w-full">
            <div>
                <p class="text-[10px] font-mono uppercase tracking-[0.3em] text-ink-400">Workspace · Dokumen</p>
                <h1 class="font-display text-2xl sm:text-3xl font-semibold text-ink-900 tracking-tightest leading-none mt-1">
                    Rincian <em class="text-gold-700 not-italic">{{ $document->doc_type }}</em>
                    <span class="text-ink-300 font-normal text-sm ml-2 font-mono">#{{ $document->id }}</span>
                </h1>
                <p class="text-xs text-ink-400 mt-1.5">Status pengiriman ke Bea Cukai Indonesia</p>
            </div>
            <div class="flex items-center gap-2">
                <x-status-badge :status="$document->status" />
                <x-jalur-badge :jalur="$document->jalur" />
            </div>
        </div>
    </x-slot>

    <div class="-mx-4 sm:-mx-6 lg:-mx-10 -mt-6 sm:-mt-8 px-4 sm:px-6 lg:px-10 pt-6 sm:pt-8 pb-12 bg-cream">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            {{-- AI Validation Result Box --}}
            @if (session('ai_validation'))
                @php
                    $av = session('ai_validation');
                    $all = array_merge($av['rule_findings'], $av['ai_findings']);
                    $errors = collect($all)->where('level', 'error')->count();
                    $warnings = collect($all)->where('level', 'warning')->count();
                @endphp
                <div class="bg-white rounded-2xl shadow-sm border border-violet-100 overflow-hidden">
                    <div class="bg-violet-50 px-6 py-4 border-b border-violet-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                            <h3 class="font-bold text-slate-800 text-sm">Hasil Validasi Cerdas</h3>
                        </div>
                        <div class="flex items-center gap-2 text-[11px] font-bold">
                            <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">{{ $errors }} error</span>
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ $warnings }} peringatan</span>
                            @if ($av['provider'])
                                <span class="px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 uppercase">AI: {{ $av['provider'] }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        @if ($av['ai_error'])
                            <div class="rounded-xl bg-amber-50 border border-amber-100 p-3 text-xs text-amber-800">
                                <span class="font-bold">Analisis AI tidak tersedia:</span> {{ $av['ai_error'] }}
                                <span class="block mt-0.5 text-amber-600">Hasil di bawah hanya dari pemeriksaan aturan.</span>
                            </div>
                        @endif

                        @if (empty($all))
                            <div class="flex items-center gap-2 text-sm text-emerald-700 font-semibold">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                Tidak ada masalah terdeteksi. Dokumen tampak siap dikirim.
                            </div>
                        @else
                            <ul class="space-y-2">
                                @foreach ($all as $item)
                                    @php
                                        $c = ['error' => ['bg-rose-50','border-rose-100','text-rose-800','text-rose-500'], 'warning' => ['bg-amber-50','border-amber-100','text-amber-800','text-amber-500'], 'info' => ['bg-sky-50','border-sky-100','text-sky-800','text-sky-500']][$item['level']] ?? ['bg-slate-50','border-slate-100','text-slate-800','text-slate-500'];
                                    @endphp
                                    <li class="flex items-start gap-3 rounded-xl border {{ $c[0] }} {{ $c[1] }} p-3 text-sm">
                                        <span class="mt-0.5 shrink-0 text-[10px] font-bold uppercase px-1.5 py-0.5 rounded {{ $c[3] }} bg-white border {{ $c[1] }}">{{ $item['level'] }}</span>
                                        <span class="{{ $c[2] }}">
                                            @if ($item['field'])<span class="font-bold">{{ $item['field'] }}:</span> @endif{{ $item['message'] }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <p class="text-[11px] text-slate-400 border-t border-slate-100 pt-3">
                            Validasi ini bersifat bantuan (aturan deterministik + AI hybrid Claude/Gemini/DeepSeek). Keputusan akhir tetap pada operator; CEISA DJBC melakukan validasi resmi saat submit.
                        </p>
                    </div>
                </div>
            @endif

            {{-- 1. Stepper Progress & Aksi Utama --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-6">
                @php
                    $status = $document->status;
                    $isDraft = $status === 'draft';
                    $isSubmitting = $status === 'submitting';
                    $isSubmitted = $status === 'submitted';
                    $isAccepted = $status === 'accepted';
                    $isRejected = in_array($status, ['rejected', 'error']);

                    $step = 1;
                    if ($isSubmitting || $isSubmitted || $isAccepted || $isRejected) {
                        $step = 3;
                    }
                    if ($isAccepted || $isRejected) {
                        $step = 4;
                    }
                @endphp

                <!-- Stepper Progress Bar -->
                <div class="relative flex items-center justify-between w-full max-w-3xl mx-auto px-4">
                    <!-- Progress Line -->
                    <div class="absolute left-10 right-10 top-1/2 h-0.5 bg-slate-100 -translate-y-1/2 z-0">
                        <div class="h-full bg-indigo-600 transition-all duration-500" 
                             style="width: {{ $step === 1 ? '0%' : ($step === 3 ? '50%' : '100%') }}"></div>
                    </div>

                    <!-- Step 1: Draft -->
                    <div class="relative z-10 flex flex-col items-center">
                        <div @class([
                            'h-9 w-9 rounded-full flex items-center justify-center border-2 transition-all font-bold text-xs',
                            'bg-indigo-600 border-indigo-600 text-white shadow-md shadow-indigo-100' => $step >= 1,
                            'bg-white border-slate-200 text-slate-400' => $step < 1
                        ])>
                            @if($step > 1)
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            @else
                                1
                            @endif
                        </div>
                        <span class="text-[11px] font-bold text-slate-600 mt-2">Draft</span>
                    </div>

                    <!-- Step 2: Validasi AI -->
                    <div class="relative z-10 flex flex-col items-center">
                        <div @class([
                            'h-9 w-9 rounded-full flex items-center justify-center border-2 transition-all font-bold text-xs',
                            'bg-violet-600 border-violet-600 text-white shadow-md shadow-violet-100' => $step >= 2 || $document->isArchived(),
                            'bg-white border-slate-200 text-slate-400' => $step < 2 && !$document->isArchived()
                        ])>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                        </div>
                        <span class="text-[11px] font-bold text-slate-600 mt-2">Validasi AI</span>
                    </div>

                    <!-- Step 3: Terkirim -->
                    <div class="relative z-10 flex flex-col items-center">
                        <div @class([
                            'h-9 w-9 rounded-full flex items-center justify-center border-2 transition-all font-bold text-xs',
                            'bg-indigo-600 border-indigo-600 text-white shadow-md shadow-indigo-100' => $step >= 3,
                            'bg-white border-slate-200 text-slate-400' => $step < 3
                        ])>
                            @if($step > 3)
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            @else
                                3
                            @endif
                        </div>
                        <span class="text-[11px] font-bold text-slate-600 mt-2">Terkirim</span>
                    </div>

                    <!-- Step 4: Respon Bea Cukai -->
                    <div class="relative z-10 flex flex-col items-center">
                        <div @class([
                            'h-9 w-9 rounded-full flex items-center justify-center border-2 transition-all font-bold text-xs',
                            'bg-emerald-600 border-emerald-600 text-white shadow-md shadow-emerald-100' => $isAccepted,
                            'bg-rose-600 border-rose-600 text-white shadow-md shadow-rose-100' => $isRejected,
                            'bg-white border-slate-200 text-slate-400' => !$isAccepted && !$isRejected
                        ])>
                            @if($isAccepted)
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            @elseif($isRejected)
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            @else
                                4
                            @endif
                        </div>
                        <span class="text-[11px] font-bold text-slate-600 mt-2">Respon DJBC</span>
                    </div>
                </div>

                <!-- Status Metadata & Action Buttons -->
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 pt-6 border-t border-slate-100">
                    <dl class="grid grid-cols-2 md:grid-cols-4 gap-6 text-sm grow">
                        <div>
                            <dt class="text-slate-400 font-semibold uppercase text-[10px]">Nomor Aju</dt>
                            <dd class="font-mono text-slate-800 font-bold mt-1 text-sm">{{ $document->nomor_aju ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-semibold uppercase text-[10px]">Nomor Pendaftaran</dt>
                            <dd class="font-mono text-slate-800 font-bold mt-1 text-sm">{{ $document->nomor_daftar ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-semibold uppercase text-[10px]">Disubmit Pada</dt>
                            <dd class="text-slate-600 font-medium mt-1">{{ $document->submitted_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-semibold uppercase text-[10px]">Terakhir Diupdate</dt>
                            <dd class="text-slate-600 font-medium mt-1">{{ $document->response_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                    </dl>

                    <div class="flex items-center justify-end shrink-0 w-full lg:w-auto">
                        @if ($document->error_message)
                            <div class="rounded-xl bg-rose-50 border border-rose-100 p-3 text-xs text-rose-800 mr-4 max-w-xs">
                                <span class="font-bold">Error:</span> {{ $document->error_message }}
                            </div>
                        @endif

                        <div class="flex items-center gap-2">
                            @unless ($document->isArchived())
                                <form method="POST" action="{{ route('documents.validate', $document) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-xl shadow-md shadow-violet-100 transition-all">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                                        Validasi AI
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('documents.duplicate', $document) }}">
                                    @csrf
                                    <button type="submit" class="px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl shadow-sm transition-all"
                                            onclick="return confirm('Duplikasi dokumen ini sebagai draft baru?')">
                                        Duplikasi
                                    </button>
                                </form>
                            @endunless

                            @if (in_array($document->status, ['draft', 'error']))
                                <form method="POST" action="{{ route('documents.submit', $document) }}">
                                    @csrf
                                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-100 transition-all">
                                        {{ $document->status === 'draft' ? 'Kirim ke CEISA' : 'Kirim Ulang ke CEISA' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2-Column Dashboard Layout --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                
                {{-- Column 1: Left 2/3 (Structured Content & JSON Payload) --}}
                <div class="lg:col-span-2 space-y-6">
                    
                    {{-- Structured Document Content --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100">
                            <h3 class="font-bold text-slate-800 text-sm">Informasi Dokumen Terstruktur</h3>
                        </div>
                        
                        <div class="p-6 space-y-8">
                            @if ($document->isArchived())
                                {{-- Panel data arsip (rekam manual dokumen lama DJBC) --}}
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-600 uppercase tracking-wide">Arsip Manual</span>
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Data Dokumen Lama</h4>
                                    </div>
                                    <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                                        <div><dt class="text-slate-400 text-[10px] uppercase font-bold">Perusahaan</dt><dd class="font-bold text-slate-800">{{ data_get($document->payload, 'nama_perusahaan') ?? '—' }}</dd></div>
                                        <div><dt class="text-slate-400 text-[10px] uppercase font-bold">NPWP</dt><dd class="font-mono text-slate-700">{{ data_get($document->payload, 'npwp') ?? '—' }}</dd></div>
                                        <div><dt class="text-slate-400 text-[10px] uppercase font-bold">Kantor Pabean</dt><dd class="font-semibold text-slate-700">{{ data_get($document->payload, 'kantor_pabean') ?? '—' }}</dd></div>
                                        <div><dt class="text-slate-400 text-[10px] uppercase font-bold">Tanggal Dokumen</dt><dd class="font-semibold text-slate-700">{{ data_get($document->payload, 'tanggal_dokumen') ?? '—' }}</dd></div>
                                        <div><dt class="text-slate-400 text-[10px] uppercase font-bold">Nilai</dt><dd class="font-bold text-indigo-700">{{ data_get($document->payload, 'nilai') !== null ? number_format(data_get($document->payload, 'nilai'), 2, ',', '.').' '.(data_get($document->payload, 'valuta') ?? '') : '—' }}</dd></div>
                                        <div><dt class="text-slate-400 text-[10px] uppercase font-bold">Uraian</dt><dd class="text-slate-700">{{ data_get($document->payload, 'uraian') ?? '—' }}</dd></div>
                                        @if (data_get($document->payload, 'keterangan'))
                                            <div class="sm:col-span-2"><dt class="text-slate-400 text-[10px] uppercase font-bold">Keterangan</dt><dd class="text-slate-700">{{ data_get($document->payload, 'keterangan') }}</dd></div>
                                        @endif
                                    </dl>
                                </div>
                            @endif

                            {{-- Header Row: Parties/Entities & Transport/Values --}}
                            <div class="grid md:grid-cols-2 gap-8" @if ($document->isArchived()) style="display:none" @endif>

                                {{-- Left Column: Entities (Importir/Eksportir dll) --}}
                                <div class="space-y-4">
                                    @if ($document->doc_type === 'BC30')
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Eksportir</h4>
                                            <div class="text-sm bg-slate-50 p-4 rounded-xl space-y-1">
                                                <p class="font-bold text-slate-800">{{ data_get($document->payload, 'header.eksportir.nama') }}</p>
                                                <p class="text-xs text-slate-500 font-mono">NPWP: {{ data_get($document->payload, 'header.eksportir.npwp') }}</p>
                                                <p class="text-xs text-slate-600 mt-1">{{ data_get($document->payload, 'header.eksportir.alamat') }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Penerima (Consignee)</h4>
                                            <div class="text-sm bg-slate-50 p-4 rounded-xl">
                                                <p class="font-bold text-slate-800">{{ data_get($document->payload, 'header.penerima.nama') }}</p>
                                                <p class="text-xs text-slate-500 mt-1">Negara Tujuan: <span class="font-bold text-slate-700">{{ data_get($document->payload, 'header.penerima.negara') }}</span></p>
                                            </div>
                                        </div>
                                    @elseif ($document->doc_type === 'BC20' || $document->doc_type === 'BC24')
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Importir</h4>
                                            <div class="text-sm bg-slate-50 p-4 rounded-xl space-y-1">
                                                <p class="font-bold text-slate-800">{{ data_get($document->payload, 'header.importir.nama') }}</p>
                                                <p class="text-xs text-slate-500 font-mono">NPWP: {{ data_get($document->payload, 'header.importir.npwp') }}</p>
                                                <p class="text-xs text-slate-600 mt-1">{{ data_get($document->payload, 'header.importir.alamat') }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pemasok (Supplier)</h4>
                                            <div class="text-sm bg-slate-50 p-4 rounded-xl">
                                                <p class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pemasok.nama') }}</p>
                                                <p class="text-xs text-slate-500 mt-1">Negara Pemasok: <span class="font-bold text-slate-700">{{ data_get($document->payload, 'header.pemasok.negara') }}</span></p>
                                            </div>
                                        </div>
                                    @elseif ($document->doc_type === 'TPB')
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pengusaha TPB</h4>
                                            <div class="text-sm bg-slate-50 p-4 rounded-xl space-y-1">
                                                <p class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pengusaha_tpb.nama') }}</p>
                                                <p class="text-xs text-slate-500 font-mono">NPWP: {{ data_get($document->payload, 'header.pengusaha_tpb.npwp') }}</p>
                                                <p class="text-xs text-slate-600 mt-1">{{ data_get($document->payload, 'header.pengusaha_tpb.alamat') }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Fasilitas TPB</h4>
                                            <div class="text-sm bg-slate-50 p-4 rounded-xl space-y-1">
                                                <p class="text-xs text-slate-600">Jenis: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.jenis_tpb') }}</span></p>
                                                <p class="text-xs text-slate-600">Tujuan: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.tujuan_pengiriman') }}</span></p>
                                                <p class="text-xs text-slate-600">No. Kontrak: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.dokumen_referensi') }}</span></p>
                                            </div>
                                        </div>
                                    @elseif ($document->doc_type === 'RUSH')
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pemohon Rush Handling</h4>
                                            <div class="text-sm bg-slate-50 p-4 rounded-xl space-y-1">
                                                <p class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pemohon.nama') }}</p>
                                                <p class="text-xs text-slate-500 font-mono">NPWP: {{ data_get($document->payload, 'header.pemohon.npwp') }}</p>
                                                <p class="text-xs text-slate-600 mt-1">{{ data_get($document->payload, 'header.pemohon.alamat') }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Alasan Pengeluaran Segera</h4>
                                            <div class="text-sm bg-rose-50 border border-rose-100 p-4 rounded-xl text-rose-900">
                                                <p class="font-bold text-xs">Kebutuhan Mendadak:</p>
                                                <p class="mt-1 font-semibold text-sm">{{ data_get($document->payload, 'header.alasan_rush_handling') }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Right Column: Logistics & Values --}}
                                <div class="space-y-4">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Logistik &amp; Pengangkutan</h4>
                                        <div class="text-sm bg-slate-50 p-4 rounded-xl space-y-2">
                                            @if ($document->doc_type === 'RUSH')
                                                <p class="text-xs text-slate-600">Sarana Pengangkut: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pengangkutan.sarana') }} ({{ data_get($document->payload, 'header.pengangkutan.flight_no') }})</span></p>
                                                <p class="text-xs text-slate-600">No. AWB / BL: <span class="font-mono font-bold text-slate-800">{{ data_get($document->payload, 'header.dokumen_pengangkutan.awb_bl') }}</span></p>
                                                <p class="text-xs text-slate-600">Tanggal AWB: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.dokumen_pengangkutan.tanggal') }}</span></p>
                                                <p class="text-xs text-slate-600">Kemasan: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.kemasan.jumlah') }} {{ data_get($document->payload, 'header.kemasan.jenis') }}</span></p>
                                            @elseif ($document->doc_type === 'BC30')
                                                <p class="text-xs text-slate-600">Pelabuhan Muat: <span class="font-bold text-slate-800 font-mono">{{ data_get($document->payload, 'header.pengangkutan.pelabuhan_muat') }}</span></p>
                                                <p class="text-xs text-slate-600">Pelabuhan Tujuan: <span class="font-bold text-slate-800 font-mono">{{ data_get($document->payload, 'header.pengangkutan.pelabuhan_tujuan') ?? '—' }}</span></p>
                                                <p class="text-xs text-slate-600">Cara Angkut: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pengangkutan.cara_angkut') ?? '—' }}</span></p>
                                                <p class="text-xs text-slate-600">Sarana / Voy: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pengangkutan.sarana_angkut') ?? '—' }} {{ data_get($document->payload, 'header.pengangkutan.voy_flight') ? '· '.data_get($document->payload, 'header.pengangkutan.voy_flight') : '' }}</span></p>
                                                <p class="text-xs text-slate-600">Perkiraan Ekspor: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pengangkutan.tanggal_ekspor') ?? '—' }}</span></p>
                                            @else
                                                <p class="text-xs text-slate-600">Pelabuhan Muat: <span class="font-bold text-slate-800 font-mono">{{ data_get($document->payload, 'header.pengangkutan.pelabuhan_muat') }}</span></p>
                                                <p class="text-xs text-slate-600">Pelabuhan Bongkar: <span class="font-bold text-slate-800 font-mono">{{ data_get($document->payload, 'header.pengangkutan.pelabuhan_bongkar') ?? '—' }}</span></p>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Mata Uang &amp; Nilai</h4>
                                        <div class="text-sm bg-indigo-50/50 border border-indigo-100 p-4 rounded-xl space-y-1">
                                            <p class="text-xs text-slate-500">Mata Uang Transaksi: <span class="font-bold text-slate-700">{{ data_get($document->payload, 'header.valuta') ?? 'USD' }}</span></p>
                                            <div class="mt-2">
                                                @if ($document->doc_type === 'BC30')
                                                    <span class="text-xs text-slate-500 block">Nilai FOB Total:</span>
                                                    <span class="text-lg font-bold text-indigo-700">{{ number_format(data_get($document->payload, 'header.nilai_fob', 0), 2, ',', '.') }} {{ data_get($document->payload, 'header.valuta') ?? 'USD' }}</span>
                                                @elseif ($document->doc_type === 'BC20' || $document->doc_type === 'BC24')
                                                    <span class="text-xs text-slate-500 block">Nilai CIF Total:</span>
                                                    <span class="text-lg font-bold text-indigo-700">{{ number_format(data_get($document->payload, 'header.nilai_cif', 0), 2, ',', '.') }} {{ data_get($document->payload, 'header.valuta') ?? 'USD' }}</span>
                                                @else
                                                    <span class="text-xs text-slate-500 block">Nilai Total Barang:</span>
                                                    <span class="text-lg font-bold text-indigo-700">{{ number_format(data_get($document->payload, 'header.nilai_barang', 0), 2, ',', '.') }} {{ data_get($document->payload, 'header.valuta') ?? 'USD' }}</span>
                                                @endif
                                            </div>
                                            @if (data_get($document->payload, 'header.cara_pembayaran'))
                                                <p class="text-[11px] text-slate-500 mt-2 border-t border-indigo-100/50 pt-1">Metode Pembayaran: <span class="font-semibold text-slate-700">{{ data_get($document->payload, 'header.cara_pembayaran') }}</span></p>
                                            @endif
                                            @if ($document->doc_type === 'BC30')
                                                <div class="mt-2 border-t border-indigo-100/50 pt-2 grid grid-cols-3 gap-2 text-[11px]">
                                                    <p class="text-slate-500">Incoterm: <span class="font-bold text-slate-700">{{ data_get($document->payload, 'header.incoterm') ?? '—' }}</span></p>
                                                    <p class="text-slate-500">NDPBM: <span class="font-bold text-slate-700">{{ number_format(data_get($document->payload, 'header.ndpbm', 0), 0, ',', '.') }}</span></p>
                                                    <p class="text-slate-500">Bruto: <span class="font-bold text-slate-700">{{ number_format(data_get($document->payload, 'header.bruto', 0), 2, ',', '.') }} kg</span></p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Items Table --}}
                            <div @if ($document->isArchived()) style="display:none" @endif>
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Detail Pos Komoditas Barang</h4>
                                <div class="border border-slate-100 rounded-xl overflow-hidden shadow-sm">
                                    <table class="min-w-full divide-y divide-slate-100 text-xs text-left">
                                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                                            <tr>
                                                <th class="px-4 py-3 text-center">Seri</th>
                                                <th class="px-4 py-3">Kode HS</th>
                                                <th class="px-4 py-3">Uraian</th>
                                                <th class="px-4 py-3 text-right">Jumlah</th>
                                                <th class="px-4 py-3 text-center">Satuan</th>
                                                <th class="px-4 py-3 text-right">Netto</th>
                                                <th class="px-4 py-3 text-right">Nilai Barang</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @foreach (data_get($document->payload, 'barang', []) as $item)
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="px-4 py-3 font-bold text-slate-400 text-center">{{ data_get($item, 'seri') }}</td>
                                                    <td class="px-4 py-3 font-mono font-semibold text-slate-700">{{ data_get($item, 'hs_code') }}</td>
                                                    <td class="px-4 py-3 text-slate-600 font-medium">{{ data_get($item, 'uraian') }}</td>
                                                    <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ number_format(data_get($item, 'jumlah_satuan'), 2, ',', '.') }}</td>
                                                    <td class="px-4 py-3 text-center text-slate-500 font-bold">{{ data_get($item, 'kode_satuan') }}</td>
                                                    <td class="px-4 py-3 text-right text-slate-600 font-mono">{{ number_format(data_get($item, 'netto'), 2, ',', '.') }} kg</td>
                                                    <td class="px-4 py-3 text-right font-bold text-indigo-600">
                                                        @if ($document->doc_type === 'BC30')
                                                            {{ number_format(data_get($item, 'nilai_fob', 0), 2, ',', '.') }}
                                                        @elseif ($document->doc_type === 'BC20' || $document->doc_type === 'BC24')
                                                            {{ number_format(data_get($item, 'nilai_cif', 0), 2, ',', '.') }}
                                                        @else
                                                            {{ number_format(data_get($item, 'nilai_barang', 0), 2, ',', '.') }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- JSON Tabs (Payload & Response) --}}
                    <div class="bg-slate-900 text-slate-300 rounded-2xl shadow-sm border border-slate-800 overflow-hidden" x-data="{ activeTab: 'payload' }">
                        <div class="bg-slate-950 px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                            <div class="flex gap-2">
                                <button type="button" @click="activeTab = 'payload'"
                                        class="text-xs font-bold uppercase tracking-wider px-2 py-1 rounded transition-colors"
                                        :class="activeTab === 'payload' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200'">
                                    Payload Kirim
                                </button>
                                @if ($document->ceisa_response)
                                    <button type="button" @click="activeTab = 'response'"
                                            class="text-xs font-bold uppercase tracking-wider px-2 py-1 rounded transition-colors"
                                            :class="activeTab === 'response' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200'">
                                        Response CEISA
                                    </button>
                                @endif
                            </div>
                            <span class="text-[10px] text-slate-500 font-mono">Format JSON</span>
                        </div>
                        
                        <div class="p-4">
                            <div x-show="activeTab === 'payload'">
                                <pre class="text-[11px] font-mono leading-relaxed overflow-x-auto text-emerald-400 max-h-[300px]">{{ json_encode($document->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                            @if ($document->ceisa_response)
                                <div x-show="activeTab === 'response'">
                                    <pre class="text-[11px] font-mono leading-relaxed overflow-x-auto text-cyan-400 max-h-[300px]">{{ json_encode($document->ceisa_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Column 2: Right 1/3 (Checklists, PDF Actions, Simulator, Webhook History) --}}
                <div class="space-y-6">
                    
                    {{-- Pre-Submit Checklist Widget --}}
                    @php
                        $payload = $document->payload ?? [];
                        $barang = data_get($payload, 'barang', []);
                        
                        $hasEntity = !empty($document->partyName());
                        $hasNpwp = !empty($document->partyNpwp());
                        $hasBarang = !empty($barang);
                        
                        $hsValid = true;
                        $nettoValid = true;
                        $nilaiValid = true;
                        
                        foreach($barang as $item) {
                            $hs = preg_replace('/\D/', '', (string) data_get($item, 'hs_code'));
                            if (strlen($hs) !== 8) $hsValid = false;
                            if ((float)data_get($item, 'netto', 0) <= 0) $nettoValid = false;
                            
                            $valueKey = match ($document->doc_type) {
                                'BC30' => 'nilai_fob',
                                'BC20', 'BC24' => 'nilai_cif',
                                default => 'nilai_barang',
                            };
                            if ((float)data_get($item, $valueKey, 0) <= 0) $nilaiValid = false;
                        }
                    @endphp
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-4">
                        <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Kepatuhan Pre-Submit</h3>
                        <ul class="space-y-3 text-xs">
                            <li class="flex items-center justify-between">
                                <span class="text-slate-500">Nama Pihak Utama</span>
                                <span @class([
                                    'inline-flex items-center gap-1 font-bold',
                                    'text-emerald-600' => $hasEntity,
                                    'text-rose-600' => !$hasEntity
                                ])>
                                    {{ $hasEntity ? '✓ Lengkap' : '✗ Belum Terisi' }}
                                </span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span class="text-slate-500">NPWP Pihak Utama</span>
                                <span @class([
                                    'inline-flex items-center gap-1 font-bold',
                                    'text-emerald-600' => $hasNpwp,
                                    'text-amber-600' => !$hasNpwp
                                ])>
                                    {{ $hasNpwp ? '✓ Lengkap' : '⚠ Belum Terisi' }}
                                </span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span class="text-slate-500">Komoditas Barang</span>
                                <span @class([
                                    'inline-flex items-center gap-1 font-bold',
                                    'text-emerald-600' => $hasBarang,
                                    'text-rose-600' => !$hasBarang
                                ])>
                                    {{ $hasBarang ? '✓ Terisi' : '✗ Kosong' }}
                                </span>
                            </li>
                            @if($hasBarang)
                                <li class="flex items-center justify-between">
                                    <span class="text-slate-500">Format HS Code (8 Digit)</span>
                                    <span @class([
                                        'inline-flex items-center gap-1 font-bold',
                                        'text-emerald-600' => $hsValid,
                                        'text-amber-600' => !$hsValid
                                    ])>
                                        {{ $hsValid ? '✓ Valid' : '⚠ Perlu Koreksi' }}
                                    </span>
                                </li>
                                <li class="flex items-center justify-between">
                                    <span class="text-slate-500">Berat Netto Barang</span>
                                    <span @class([
                                        'inline-flex items-center gap-1 font-bold',
                                        'text-emerald-600' => $nettoValid,
                                        'text-amber-600' => !$nettoValid
                                    ])>
                                        {{ $nettoValid ? '✓ Valid' : '⚠ Berat 0 kg' }}
                                    </span>
                                </li>
                                <li class="flex items-center justify-between">
                                    <span class="text-slate-500">Nilai Barang</span>
                                    <span @class([
                                        'inline-flex items-center gap-1 font-bold',
                                        'text-emerald-600' => $nilaiValid,
                                        'text-amber-600' => !$nilaiValid
                                    ])>
                                        {{ $nilaiValid ? '✓ Valid' : '⚠ Nilai 0' }}
                                    </span>
                                </li>
                            @endif
                        </ul>
                    </div>

                    {{-- Quick Action PDF Downloads Toolbar --}}
                    @if(in_array($document->status, ['accepted', 'submitted']))
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-4">
                            <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Cetak & Unduh Dokumen</h3>
                            <div class="flex flex-col gap-2">
                                <!-- Cetak Formulir -->
                                <a href="{{ route('documents.cetak-formulir', $document) }}" 
                                   class="flex items-center justify-between px-4 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-xl text-xs font-bold transition border border-slate-200">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.567-1.12-1.227L6.34 18m11.318 0h-11.32" /></svg>
                                        Cetak Formulir Pabean
                                    </span>
                                    <span class="text-[9px] bg-indigo-50 text-indigo-600 font-bold px-1.5 py-0.5 rounded">PDF</span>
                                </a>

                                <!-- Download Respon -->
                                <a href="{{ route('documents.download-respon', $document) }}" 
                                   class="flex items-center justify-between px-4 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-xl text-xs font-bold transition border border-slate-200">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                        Unduh Respon Bea Cukai
                                    </span>
                                    <span class="text-[9px] bg-emerald-50 text-emerald-600 font-bold px-1.5 py-0.5 rounded">PDF</span>
                                </a>

                                <!-- Download Billing -->
                                <a href="{{ route('documents.download-billing', $document) }}" 
                                   class="flex items-center justify-between px-4 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-xl text-xs font-bold transition border border-slate-200">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                                        Unduh Billing PDF
                                    </span>
                                    <span class="text-[9px] bg-amber-50 text-amber-600 font-bold px-1.5 py-0.5 rounded">PDF</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- Local Webhook Simulator Widget --}}
                    @if(config('app.env') === 'local')
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-indigo-100/80 space-y-4 shadow-md shadow-indigo-50/50">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-indigo-600 animate-pulse"></span>
                                <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Simulator Respon CEISA</h3>
                            </div>
                            <p class="text-[11px] text-slate-500 leading-normal">Kirim respon simulasi langsung ke webhook lokal M2B untuk memperbarui status dokumen.</p>
                            
                            <div class="grid grid-cols-2 gap-2">
                                <!-- Jalur Hijau -->
                                <form method="POST" action="{{ route('webhook.ceisa') }}" target="_blank">
                                    @csrf
                                    <input type="hidden" name="nomor_aju" value="{{ $document->nomor_aju }}" />
                                    <input type="hidden" name="nomor_daftar" value="{{ $document->nomor_daftar ?: '010203' }}" />
                                    <input type="hidden" name="status" value="TERIMA/SPPB" />
                                    <input type="hidden" name="jalur" value="H" />
                                    <input type="hidden" name="jenis" value="Respon" />
                                    <input type="hidden" name="data[responPdf]" value="/respon-sppb-sample.pdf" />
                                    <input type="hidden" name="data[kodeBilling]" value="98765432101" />
                                    <button type="submit" class="w-full text-center py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-[10px] font-bold transition">
                                        🟢 Jalur Hijau
                                    </button>
                                </form>

                                <!-- Jalur Kuning -->
                                <form method="POST" action="{{ route('webhook.ceisa') }}" target="_blank">
                                    @csrf
                                    <input type="hidden" name="nomor_aju" value="{{ $document->nomor_aju }}" />
                                    <input type="hidden" name="nomor_daftar" value="{{ $document->nomor_daftar ?: '010203' }}" />
                                    <input type="hidden" name="status" value="TERIMA" />
                                    <input type="hidden" name="jalur" value="K" />
                                    <input type="hidden" name="jenis" value="Respon" />
                                    <button type="submit" class="w-full text-center py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-xl text-[10px] font-bold transition">
                                        🟡 Jalur Kuning
                                    </button>
                                </form>

                                <!-- Jalur Merah -->
                                <form method="POST" action="{{ route('webhook.ceisa') }}" target="_blank">
                                    @csrf
                                    <input type="hidden" name="nomor_aju" value="{{ $document->nomor_aju }}" />
                                    <input type="hidden" name="nomor_daftar" value="{{ $document->nomor_daftar ?: '010203' }}" />
                                    <input type="hidden" name="status" value="TERIMA" />
                                    <input type="hidden" name="jalur" value="M" />
                                    <input type="hidden" name="jenis" value="Respon" />
                                    <button type="submit" class="w-full text-center py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-[10px] font-bold transition">
                                        🔴 Jalur Merah
                                    </button>
                                </form>

                                <!-- Ditolak / NPP -->
                                <form method="POST" action="{{ route('webhook.ceisa') }}" target="_blank">
                                    @csrf
                                    <input type="hidden" name="nomor_aju" value="{{ $document->nomor_aju }}" />
                                    <input type="hidden" name="status" value="DITOLAK/NPP" />
                                    <input type="hidden" name="jenis" value="Respon" />
                                    <button type="submit" class="w-full text-center py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-xl text-[10px] font-bold transition">
                                        ⚫ Ditolak / NPP
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    {{-- Webhook Logs History --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                        <h3 class="text-xs font-bold text-slate-800 mb-4 uppercase tracking-wider">Riwayat Webhook</h3>
                        @if ($document->webhookLogs->isNotEmpty())
                            <div class="flow-root">
                                <ul role="list" class="-mb-8">
                                    @foreach ($document->webhookLogs->sortByDesc('received_at') as $idx => $log)
                                        <li>
                                            <div class="relative pb-8">
                                                @if ($idx < $document->webhookLogs->count() - 1)
                                                    <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-100" aria-hidden="true"></span>
                                                @endif
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span class="h-8 w-8 rounded-full bg-emerald-50 border-2 border-emerald-500 flex items-center justify-center ring-8 ring-white">
                                                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                            </svg>
                                                        </span>
                                                    </div>
                                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                        <div>
                                                            <p class="text-xs text-slate-700 font-bold">Status: <span class="text-indigo-600">{{ $log->event ?? 'DITERIMA' }}</span></p>
                                                        </div>
                                                        <div class="whitespace-nowrap text-right text-xs text-slate-400 font-medium">
                                                            <time>{{ $log->received_at?->format('d/m H:i') }}</time>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="text-center py-6 text-slate-400">
                                <svg class="h-8 w-8 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <p class="text-[11px]">Belum ada update webhook...</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            <div class="flex items-center justify-start pt-4">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-slate-800 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
