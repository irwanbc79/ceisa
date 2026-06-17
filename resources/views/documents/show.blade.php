<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 py-2">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-2xl bg-indigo-50/60 border border-indigo-100/80 flex items-center justify-center text-indigo-600 shadow-sm shrink-0">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-extrabold text-2xl text-slate-800 tracking-tight flex items-center gap-2">
                        Rincian Dokumen <span class="bg-indigo-600 text-white px-2.5 py-0.5 rounded-lg text-sm font-black tracking-wide shadow-sm">{{ $document->doc_type }}</span>
                    </h2>
                    <p class="text-xs text-slate-500 font-medium mt-0.5 flex items-center gap-1.5">
                        <span>ID #{{ $document->id }}</span>
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-slate-350"></span>
                        <span>Gateway Bea Cukai Host-to-Host</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 shrink-0">
                <x-status-badge :status="$document->status" class="shadow-sm font-bold text-xs px-3 py-1" />
                <x-jalur-badge :jalur="$document->jalur" class="px-3.5 py-1.5 text-xs font-bold shadow-sm" />
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50/70 min-h-screen">
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
                <div class="bg-white rounded-3xl shadow-sm border border-violet-150 overflow-hidden transition-all duration-300 hover:shadow-md">
                    <div class="bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-4 border-b border-violet-100 flex items-center justify-between">
                        <div class="flex items-center gap-2.5 text-white">
                            <svg class="h-5 w-5 text-violet-100 animate-pulse" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                            </svg>
                            <h3 class="font-extrabold text-white tracking-tight text-sm">Hasil Validasi Cerdas</h3>
                        </div>
                        <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-wider">
                            <span class="px-2.5 py-1 rounded-lg bg-rose-500/90 text-white shadow-sm border border-rose-450">{{ $errors }} error</span>
                            <span class="px-2.5 py-1 rounded-lg bg-amber-500/90 text-white shadow-sm border border-amber-450">{{ $warnings }} peringatan</span>
                            @if ($av['provider'])
                                <span class="px-2.5 py-1 rounded-lg bg-white/20 text-white border border-white/10">Engine: {{ $av['provider'] }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        @if ($av['ai_error'])
                            <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4 text-xs text-amber-850 flex items-start gap-2.5">
                                <svg class="h-5 w-5 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                <div>
                                    <span class="font-bold">Analisis AI tidak tersedia:</span> {{ $av['ai_error'] }}
                                    <span class="block mt-1 text-amber-700">Hasil di bawah hanya didasarkan dari pemeriksaan aturan deterministik.</span>
                                </div>
                            </div>
                        @endif

                        @if (empty($all))
                            <div class="flex items-center gap-2.5 text-sm text-emerald-750 font-bold p-4 bg-emerald-50/50 rounded-2xl border border-emerald-100">
                                <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                Tidak ada masalah terdeteksi. Dokumen lengkap dan siap disubmit ke CEISA.
                            </div>
                        @else
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach ($all as $item)
                                    @php
                                        $c = [
                                            'error' => ['bg-rose-50/45','border-rose-150','text-rose-900','text-rose-700','border-l-rose-500'], 
                                            'warning' => ['bg-amber-50/45','border-amber-150','text-amber-900','text-amber-700','border-l-amber-500'], 
                                            'info' => ['bg-sky-50/45','border-sky-150','text-sky-900','text-sky-700','border-l-sky-500']
                                        ][$item['level']] ?? ['bg-slate-50/45','border-slate-150','text-slate-900','text-slate-700','border-l-slate-500'];
                                    @endphp
                                    <li class="flex items-start gap-3 rounded-2xl border {{ $c[0] }} {{ $c[1] }} border-l-4 {{ $c[4] }} p-3.5 text-xs shadow-sm hover:shadow transition-shadow">
                                        <span class="shrink-0 text-[9px] font-black uppercase px-2 py-0.5 rounded-md {{ $c[3] }} bg-white border {{ $c[1] }}">{{ $item['level'] }}</span>
                                        <div class="{{ $c[2] }} font-medium">
                                            @if ($item['field'])<span class="font-extrabold">{{ $item['field'] }}:</span> @endif{{ $item['message'] }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <p class="text-[10px] text-slate-400 border-t border-slate-100 pt-3 flex items-center gap-1">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                            Validasi pintar didasarkan pada aturan validasi Bea Cukai &amp; AI hybrid. Hasil keputusan akhir tetap ada di portal CEISA DJBC resmi.
                        </p>
                    </div>
                </div>
            @endif

            {{-- 1. Stepper Progress & Aksi Utama --}}
            <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100/90 space-y-8 relative overflow-hidden transition-all duration-300 hover:shadow-md">
                <div class="absolute top-0 left-0 right-0 h-[4px] bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-500"></div>

                @php
                    $status = $document->status;
                    $isDraft = $status === 'draft';
                    $isSubmitting = $status === 'submitting';
                    $isSubmitted = $status === 'submitted';
                    $isAccepted = $status === 'accepted';
                    $isRejected = in_array($status, ['rejected', 'error']);

                    $step = 1;
                    if (session('ai_validation')) {
                        $step = 2;
                    }
                    if ($isSubmitting || $isSubmitted) {
                        $step = 3;
                    }
                    if ($isAccepted || $isRejected) {
                        $step = 4;
                    }
                @endphp

                <!-- Premium Stepper Progress Bar -->
                <div class="relative flex items-center justify-between w-full max-w-4xl mx-auto px-4 py-6">
                    <!-- Progress Line Backing -->
                    <div class="absolute left-14 right-14 top-1/2 h-1.5 bg-slate-100 -translate-y-1/2 z-0 rounded-full">
                        <div class="h-full bg-gradient-to-r from-indigo-650 via-violet-600 to-emerald-550 transition-all duration-1000 ease-out rounded-full shadow-[0_0_12px_rgba(99,102,241,0.6)]" 
                             style="width: {{ $step === 1 ? '0%' : ($step === 2 ? '33.33%' : ($step === 3 ? '66.66%' : '100%')) }}"></div>
                    </div>

                    <!-- Step 1: Draft -->
                    <div class="relative z-10 flex flex-col items-center group cursor-default">
                        <div @class([
                            'h-12 w-12 rounded-2xl flex items-center justify-center border-2 transition-all duration-350 font-bold text-sm shadow-sm transform group-hover:scale-110',
                            'bg-gradient-to-tr from-indigo-550 to-indigo-650 border-indigo-600 text-white shadow-md shadow-indigo-150 ring-4 ring-indigo-50' => $step >= 1,
                            'bg-white border-slate-200 text-slate-400' => $step < 1
                        ])>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <span class="text-[11px] font-black tracking-wider text-slate-800 uppercase mt-3 transition-colors group-hover:text-indigo-600">Draft</span>
                        <span class="text-[9px] text-slate-400 mt-0.5 font-medium">Buat Dokumen</span>
                    </div>

                    <!-- Step 2: Validasi AI -->
                    <div class="relative z-10 flex flex-col items-center group cursor-default">
                        <div @class([
                            'h-12 w-12 rounded-2xl flex items-center justify-center border-2 transition-all duration-350 font-bold text-sm shadow-sm transform group-hover:scale-110',
                            'bg-gradient-to-tr from-violet-550 to-violet-650 border-violet-600 text-white shadow-md shadow-violet-150 ring-4 ring-violet-50' => $step >= 2 || $document->isArchived(),
                            'bg-white border-slate-200 text-slate-400' => $step < 2 && !$document->isArchived()
                        ])>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                            </svg>
                        </div>
                        <span class="text-[11px] font-black tracking-wider text-slate-500 uppercase mt-3 transition-colors group-hover:text-violet-600" :class="{'text-violet-700': {{ $step >= 2 ? 'true' : 'false' }} }">Validasi AI</span>
                        <span class="text-[9px] text-slate-400 mt-0.5 font-medium">Cek Kepatuhan</span>
                    </div>

                    <!-- Step 3: Terkirim -->
                    <div class="relative z-10 flex flex-col items-center group cursor-default">
                        <div @class([
                            'h-12 w-12 rounded-2xl flex items-center justify-center border-2 transition-all duration-350 font-bold text-sm shadow-sm transform group-hover:scale-110',
                            'bg-gradient-to-tr from-blue-550 to-blue-655 border-blue-600 text-white shadow-md shadow-blue-150 ring-4 ring-blue-55' => $step >= 3,
                            'bg-white border-slate-200 text-slate-400' => $step < 3
                        ])>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                        </div>
                        <span class="text-[11px] font-black tracking-wider text-slate-500 uppercase mt-3 transition-colors group-hover:text-blue-650" :class="{'text-blue-700': {{ $step >= 3 ? 'true' : 'false' }} }">Terkirim</span>
                        <span class="text-[9px] text-slate-400 mt-0.5 font-medium">Kirim ke Bea Cukai</span>
                    </div>

                    <!-- Step 4: Respon Bea Cukai -->
                    <div class="relative z-10 flex flex-col items-center group cursor-default">
                        <div @class([
                            'h-12 w-12 rounded-2xl flex items-center justify-center border-2 transition-all duration-350 font-bold text-sm shadow-sm transform group-hover:scale-110',
                            'bg-gradient-to-tr from-emerald-500 to-emerald-600 border-emerald-600 text-white shadow-md shadow-emerald-150 ring-4 ring-emerald-50' => $isAccepted,
                            'bg-gradient-to-tr from-rose-550 to-rose-650 border-rose-600 text-white shadow-md shadow-rose-150 ring-4 ring-rose-50' => $isRejected,
                            'bg-white border-slate-200 text-slate-400' => !$isAccepted && !$isRejected
                        ])>
                            @if($isAccepted)
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            @elseif($isRejected)
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            @else
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                </svg>
                            @endif
                        </div>
                        <span @class([
                            'text-[11px] font-black tracking-wider text-slate-500 uppercase mt-3 transition-colors',
                            'group-hover:text-emerald-600 text-emerald-700' => $isAccepted,
                            'group-hover:text-rose-600 text-rose-700' => $isRejected,
                            'group-hover:text-slate-700' => !$isAccepted && !$isRejected
                        ])>Respon DJBC</span>
                        <span class="text-[9px] text-slate-400 mt-0.5 font-medium">Hasil Rekam Pabean</span>
                    </div>
                </div>

                <!-- Status Metadata Cards & Action Buttons -->
                <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-6 pt-8 border-t border-slate-100">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 grow">
                        <!-- Nomor Aju -->
                        <div x-data="{ copied: false }" class="relative bg-slate-50/50 hover:bg-white border border-slate-100 hover:border-indigo-150 hover:shadow-sm rounded-2xl p-4 transition-all duration-300 flex flex-col justify-between group">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-bold uppercase text-[9px] tracking-wider block">Nomor Aju</span>
                                <button type="button" @click="navigator.clipboard.writeText('{{ $document->nomor_aju }}'); copied = true; setTimeout(() => copied = false, 1500)" 
                                        class="text-slate-400 hover:text-indigo-600 transition-colors p-1 rounded-lg hover:bg-indigo-50/50 opacity-0 group-hover:opacity-100" 
                                        title="Salin Nomor Aju">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5" />
                                    </svg>
                                </button>
                            </div>
                            <span class="font-mono text-slate-800 font-extrabold mt-1.5 text-xs sm:text-sm truncate select-all block" title="{{ $document->nomor_aju }}">{{ $document->nomor_aju ?? '—' }}</span>
                            <span x-show="copied" x-transition class="absolute top-2 right-2 bg-indigo-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow">Tersalin!</span>
                        </div>
                        <!-- Nomor Pendaftaran -->
                        <div x-data="{ copied: false }" class="relative bg-slate-50/50 hover:bg-white border border-slate-100 hover:border-indigo-150 hover:shadow-sm rounded-2xl p-4 transition-all duration-300 flex flex-col justify-between group">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-bold uppercase text-[9px] tracking-wider block">No. Pendaftaran</span>
                                <button type="button" @click="navigator.clipboard.writeText('{{ $document->nomor_daftar }}'); copied = true; setTimeout(() => copied = false, 1500)" 
                                        class="text-slate-400 hover:text-indigo-600 transition-colors p-1 rounded-lg hover:bg-indigo-50/50 opacity-0 group-hover:opacity-100" 
                                        title="Salin Nomor Pendaftaran" @disabled(empty($document->nomor_daftar))>
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5" />
                                    </svg>
                                </button>
                            </div>
                            <span class="font-mono text-slate-800 font-extrabold mt-1.5 text-xs sm:text-sm truncate select-all block" title="{{ $document->nomor_daftar }}">{{ $document->nomor_daftar ?? '—' }}</span>
                            <span x-show="copied" x-transition class="absolute top-2 right-2 bg-indigo-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow">Tersalin!</span>
                        </div>
                        <!-- Disubmit Pada -->
                        <div class="bg-slate-50/50 border border-slate-100 rounded-2xl p-4 flex flex-col justify-between">
                            <span class="text-slate-400 font-bold uppercase text-[9px] tracking-wider block">Disubmit Pada</span>
                            <span class="text-slate-700 font-bold mt-1.5 text-xs sm:text-sm truncate block">{{ $document->submitted_at?->format('d/m/Y H:i') ?? '—' }}</span>
                        </div>
                        <!-- Terakhir Diupdate -->
                        <div class="bg-slate-50/50 border border-slate-100 rounded-2xl p-4 flex flex-col justify-between">
                            <span class="text-slate-400 font-bold uppercase text-[9px] tracking-wider block">Terakhir Diupdate</span>
                            <span class="text-slate-700 font-bold mt-1.5 text-xs sm:text-sm truncate block">{{ $document->response_at?->format('d/m/Y H:i') ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 shrink-0 lg:justify-end">
                        @if ($document->error_message)
                            <div class="rounded-2xl bg-rose-50 border border-rose-100 p-4 text-xs text-rose-800 max-w-xs shadow-sm flex items-start gap-2">
                                <svg class="h-5 w-5 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                </svg>
                                <div>
                                    <span class="font-extrabold block text-rose-900 mb-0.5">Pemberitahuan Rejeksi</span>
                                    {{ $document->error_message }}
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center gap-2">
                            @unless ($document->isArchived())
                                <form method="POST" action="{{ route('documents.validate', $document) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-750 hover:to-indigo-750 text-white text-xs font-black rounded-xl shadow-sm hover:shadow transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0">
                                        <svg class="h-4 w-4 text-violet-100 animate-pulse" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                                        Validasi AI
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('documents.duplicate', $document) }}">
                                    @csrf
                                    <button type="submit" class="px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-black rounded-xl shadow-sm hover:border-slate-350 transition-all duration-250 transform hover:-translate-y-0.5 active:translate-y-0"
                                            onclick="return confirm('Duplikasi dokumen ini sebagai draft baru?')">
                                        Duplikasi
                                    </button>
                                </form>
                            @endunless

                            @if ($document->isEditable())
                                <a href="{{ route('documents.edit', $document) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-amber-200 hover:bg-amber-50 text-amber-700 text-xs font-black rounded-xl shadow-sm transition-all duration-250 transform hover:-translate-y-0.5 active:translate-y-0">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                                    Ubah
                                </a>
                            @endif

                            @unless ($document->isArchived())
                                @if (in_array($document->status, ['submitting', 'submitted', 'accepted', 'rejected']) && $document->nomor_aju)
                                    <form method="POST" action="{{ route('documents.refresh-status', $document) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-black rounded-xl shadow-sm hover:border-slate-300 transition-all duration-250 transform hover:-translate-y-0.5 active:translate-y-0">
                                            <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                            Perbarui Status
                                        </button>
                                    </form>
                                @endif
                            @endunless

                            @unless ($document->isArchived())
                                @if (in_array($document->doc_type, ['BC30', 'TPB']) && in_array($document->status, ['submitted', 'accepted']))
                                    <form method="POST" action="{{ route('documents.submit-revision', $document) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-amber-200 hover:bg-amber-50 text-amber-700 text-xs font-black rounded-xl shadow-sm hover:border-amber-300 transition-all duration-250 transform hover:-translate-y-0.5 active:translate-y-0"
                                                onclick="return confirm('Kirim perbaikan data / Nota Pembetulan (NOTUL) untuk dokumen ini?')">
                                            <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                            </svg>
                                            Kirim Pembetulan (NOTUL)
                                        </button>
                                    </form>
                                @endif
                            @endunless

                            @if (in_array($document->status, ['draft', 'error']))
                                <form method="POST" action="{{ route('documents.submit', $document) }}">
                                    @csrf
                                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white text-xs font-black rounded-xl shadow-md shadow-indigo-100 hover:shadow-lg transition-all duration-250 transform hover:-translate-y-0.5 active:translate-y-0">
                                        {{ $document->status === 'draft' ? 'Submit ke CEISA H2H' : 'Kirim Ulang ke CEISA' }}
                                    </button>
                                </form>
                            @endif

                            @if ($document->canBeDeleted())
                                <form method="POST" action="{{ route('documents.destroy', $document) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 text-xs font-black rounded-xl shadow-sm transition-all duration-250 transform hover:-translate-y-0.5 active:translate-y-0"
                                            onclick="return confirm('Hapus dokumen ini secara permanen? Tindakan ini tidak dapat dibatalkan.')">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        Hapus
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
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden transition-all duration-300 hover:shadow-md">
                        <div class="bg-gradient-to-r from-slate-50 to-white px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="font-extrabold text-slate-800 text-sm tracking-tight flex items-center gap-2">
                                <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3m3-6v6m-6-10.5h3c.199 0 .39.078.53.22l5.03 5.03c.14.14.22.331.22.53v10.5c0 .621-.504 1.125-1.125 1.125h-12.75c-.621 0-1.125-.504-1.125-1.125v-17.25c0-.621.504-1.125 1.125-1.125Z" />
                                </svg>
                                Informasi Dokumen Terstruktur
                            </h3>
                            <span class="text-[9px] bg-indigo-50 border border-indigo-100/50 text-indigo-700 font-extrabold px-3 py-1 rounded-full uppercase tracking-wider">H2H Data Payload</span>
                        </div>
                        
                        <div class="p-6 md:p-8 space-y-8">
                            @if ($document->isArchived())
                                {{-- Panel data arsip (rekam manual dokumen lama DJBC) --}}
                                <div class="rounded-2xl border border-slate-150 bg-slate-50/50 p-6 space-y-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-black bg-indigo-100 text-indigo-750 border border-indigo-200/50 uppercase tracking-wider">Arsip Manual</span>
                                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Data Dokumen Terarsip</h4>
                                    </div>
                                    <div class="grid sm:grid-cols-2 gap-4 text-xs">
                                        <div class="flex flex-col gap-1 pb-2 border-b border-slate-200/40">
                                            <span class="text-slate-400 text-[9px] uppercase font-bold tracking-wider">Perusahaan</span>
                                            <span class="font-bold text-slate-800">{{ data_get($document->payload, 'nama_perusahaan') ?? '—' }}</span>
                                        </div>
                                        <div class="flex flex-col gap-1 pb-2 border-b border-slate-200/40">
                                            <span class="text-slate-400 text-[9px] uppercase font-bold tracking-wider">NPWP</span>
                                            <span class="font-mono text-slate-700 font-semibold">{{ data_get($document->payload, 'npwp') ?? '—' }}</span>
                                        </div>
                                        <div class="flex flex-col gap-1 pb-2 border-b border-slate-200/40">
                                            <span class="text-slate-400 text-[9px] uppercase font-bold tracking-wider">Kantor Pabean</span>
                                            <span class="font-semibold text-slate-700">{{ data_get($document->payload, 'kantor_pabean') ?? '—' }}</span>
                                        </div>
                                        <div class="flex flex-col gap-1 pb-2 border-b border-slate-200/40">
                                            <span class="text-slate-400 text-[9px] uppercase font-bold tracking-wider">Tanggal Dokumen</span>
                                            <span class="font-semibold text-slate-700">{{ data_get($document->payload, 'tanggal_dokumen') ?? '—' }}</span>
                                        </div>
                                        <div class="flex flex-col gap-1 pb-2 border-b border-slate-200/40 sm:col-span-2">
                                            <span class="text-slate-400 text-[9px] uppercase font-bold tracking-wider">Nilai Transaksi</span>
                                            <span class="font-black text-indigo-700 text-sm">{{ data_get($document->payload, 'nilai') !== null ? number_format(data_get($document->payload, 'nilai'), 2, ',', '.').' '.(data_get($document->payload, 'valuta') ?? '') : '—' }}</span>
                                        </div>
                                        <div class="flex flex-col gap-1 sm:col-span-2">
                                            <span class="text-slate-400 text-[9px] uppercase font-bold tracking-wider">Uraian Ringkas</span>
                                            <span class="text-slate-700 font-medium leading-relaxed">{{ data_get($document->payload, 'uraian') ?? '—' }}</span>
                                        </div>
                                        @if (data_get($document->payload, 'keterangan'))
                                            <div class="flex flex-col gap-1 sm:col-span-2 pt-2 border-t border-slate-200/40">
                                                <span class="text-slate-400 text-[9px] uppercase font-bold tracking-wider">Keterangan</span>
                                                <span class="text-slate-700">{{ data_get($document->payload, 'keterangan') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Header Row: Parties/Entities & Transport/Values --}}
                            <div class="grid md:grid-cols-2 gap-8" @if ($document->isArchived()) style="display:none" @endif>

                                {{-- Left Column: Entities (Importir/Eksportir dll) --}}
                                <div class="space-y-6">
                                    @if ($document->doc_type === 'BC30')
                                        <div class="bg-slate-50/50 hover:bg-slate-50 border border-slate-100 hover:border-slate-200 rounded-2xl p-5 transition-all duration-300">
                                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-200/40">
                                                <div class="h-6 w-6 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold shrink-0">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21h10.5V3.75c0-.621-.504-1.125-1.125-1.125h-8.25C6.146 2.625 5.625 3.146 5.625 3.75V21Z" />
                                                    </svg>
                                                </div>
                                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Eksportir Utama</h4>
                                            </div>
                                            <div class="space-y-1.5">
                                                <p class="font-extrabold text-slate-800 text-sm tracking-tight">{{ data_get($document->payload, 'header.eksportir.nama') }}</p>
                                                <p class="text-xs text-slate-500 font-mono bg-slate-100/60 border border-slate-250/30 px-2 py-0.5 rounded-md inline-block">NPWP: {{ data_get($document->payload, 'header.eksportir.npwp') }}</p>
                                                <p class="text-xs text-slate-600 pt-1 leading-relaxed">{{ data_get($document->payload, 'header.eksportir.alamat') }}</p>
                                            </div>
                                        </div>
                                        <div class="bg-slate-50/50 hover:bg-slate-50 border border-slate-100 hover:border-slate-200 rounded-2xl p-5 transition-all duration-300">
                                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-200/40">
                                                <div class="h-6 w-6 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold shrink-0">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                    </svg>
                                                </div>
                                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Penerima Luar Negeri</h4>
                                            </div>
                                            <div>
                                                <p class="font-extrabold text-slate-800 text-sm tracking-tight">{{ data_get($document->payload, 'header.penerima.nama') }}</p>
                                                <div class="text-xs text-slate-500 font-bold mt-2 flex items-center gap-2">
                                                    <span>Negara Tujuan:</span>
                                                    <span class="font-black text-indigo-700 bg-indigo-50 border border-indigo-100/50 px-2 py-0.5 rounded-lg">{{ data_get($document->payload, 'header.penerima.negara') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif ($document->doc_type === 'BC20' || $document->doc_type === 'BC24')
                                        <div class="bg-slate-50/50 hover:bg-slate-50 border border-slate-100 hover:border-slate-200 rounded-2xl p-5 transition-all duration-300">
                                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-200/40">
                                                <div class="h-6 w-6 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold shrink-0">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21h10.5V3.75c0-.621-.504-1.125-1.125-1.125h-8.25C6.146 2.625 5.625 3.146 5.625 3.75V21Z" />
                                                    </svg>
                                                </div>
                                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Importir Utama</h4>
                                            </div>
                                            <div class="space-y-1.5">
                                                <p class="font-extrabold text-slate-800 text-sm tracking-tight">{{ data_get($document->payload, 'header.importir.nama') }}</p>
                                                <p class="text-xs text-slate-500 font-mono bg-slate-100/60 border border-slate-250/30 px-2 py-0.5 rounded-md inline-block">NPWP: {{ data_get($document->payload, 'header.importir.npwp') }}</p>
                                                <p class="text-xs text-slate-600 pt-1 leading-relaxed">{{ data_get($document->payload, 'header.importir.alamat') }}</p>
                                            </div>
                                        </div>
                                        <div class="bg-slate-50/50 hover:bg-slate-50 border border-slate-100 hover:border-slate-200 rounded-2xl p-5 transition-all duration-300">
                                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-200/40">
                                                <div class="h-6 w-6 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold shrink-0">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                    </svg>
                                                </div>
                                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Pemasok (Supplier)</h4>
                                            </div>
                                            <div>
                                                <p class="font-extrabold text-slate-800 text-sm tracking-tight">{{ data_get($document->payload, 'header.pemasok.nama') }}</p>
                                                <div class="text-xs text-slate-500 font-bold mt-2 flex items-center gap-2">
                                                    <span>Negara Pengirim:</span>
                                                    <span class="font-black text-indigo-700 bg-indigo-50 border border-indigo-100/50 px-2 py-0.5 rounded-lg">{{ data_get($document->payload, 'header.pemasok.negara') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif ($document->doc_type === 'TPB')
                                        <div class="bg-slate-50/50 hover:bg-slate-50 border border-slate-100 hover:border-slate-200 rounded-2xl p-5 transition-all duration-300">
                                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-200/40">
                                                <div class="h-6 w-6 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold shrink-0">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21h10.5V3.75c0-.621-.504-1.125-1.125-1.125h-8.25C6.146 2.625 5.625 3.146 5.625 3.75V21Z" />
                                                    </svg>
                                                </div>
                                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Pengusaha TPB</h4>
                                            </div>
                                            <div class="space-y-1.5">
                                                <p class="font-extrabold text-slate-800 text-sm tracking-tight">{{ data_get($document->payload, 'header.pengusaha_tpb.nama') }}</p>
                                                <p class="text-xs text-slate-500 font-mono bg-slate-100/60 border border-slate-250/30 px-2 py-0.5 rounded-md inline-block">NPWP: {{ data_get($document->payload, 'header.pengusaha_tpb.npwp') }}</p>
                                                <p class="text-xs text-slate-600 pt-1 leading-relaxed">{{ data_get($document->payload, 'header.pengusaha_tpb.alamat') }}</p>
                                            </div>
                                        </div>
                                        <div class="bg-slate-50/50 hover:bg-slate-50 border border-slate-100 hover:border-slate-200 rounded-2xl p-5 transition-all duration-300 space-y-2">
                                            <div class="flex items-center gap-2 mb-2 pb-2 border-b border-slate-200/40">
                                                <div class="h-6 w-6 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold shrink-0">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-.621-.504-1.125-1.125-1.125H9.75M2.25 12a9.75 9.75 0 1 1 19.5 0 9.75 9.75 0 0 1-19.5 0Z" />
                                                    </svg>
                                                </div>
                                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Fasilitas TPB</h4>
                                            </div>
                                            <div class="flex justify-between items-center text-xs pb-1.5 border-b border-slate-100">
                                                <span class="text-slate-500">Jenis TPB:</span>
                                                <span class="font-extrabold text-slate-800">{{ data_get($document->payload, 'header.jenis_tpb') }}</span>
                                            </div>
                                            <div class="flex justify-between items-center text-xs pb-1.5 border-b border-slate-100">
                                                <span class="text-slate-500">Tujuan Pengiriman:</span>
                                                <span class="font-extrabold text-slate-800">{{ data_get($document->payload, 'header.tujuan_pengiriman') }}</span>
                                            </div>
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="text-slate-500">Kontrak Referensi:</span>
                                                <span class="font-bold text-slate-750 font-mono">{{ data_get($document->payload, 'header.dokumen_referensi') }}</span>
                                            </div>
                                        </div>
                                    @elseif ($document->doc_type === 'RUSH')
                                        <div class="bg-slate-50/50 hover:bg-slate-50 border border-slate-100 hover:border-slate-200 rounded-2xl p-5 transition-all duration-300">
                                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-200/40">
                                                <div class="h-6 w-6 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold shrink-0">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                    </svg>
                                                </div>
                                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Pemohon Rush Handling</h4>
                                            </div>
                                            <div class="space-y-1.5">
                                                <p class="font-extrabold text-slate-800 text-sm tracking-tight">{{ data_get($document->payload, 'header.pemohon.nama') }}</p>
                                                <p class="text-xs text-slate-500 font-mono bg-slate-100/60 border border-slate-250/30 px-2 py-0.5 rounded-md inline-block">NPWP: {{ data_get($document->payload, 'header.pemohon.npwp') }}</p>
                                                <p class="text-xs text-slate-600 pt-1 leading-relaxed">{{ data_get($document->payload, 'header.pemohon.alamat') }}</p>
                                            </div>
                                        </div>
                                        <div class="bg-rose-50/50 border border-rose-100 rounded-2xl p-5">
                                            <div class="flex items-center gap-2 mb-2 pb-2 border-b border-rose-200/30">
                                                <div class="h-6 w-6 rounded-lg bg-rose-100 flex items-center justify-center text-rose-600 font-bold shrink-0">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                    </svg>
                                                </div>
                                                <h4 class="text-[10px] font-black text-rose-700 uppercase tracking-wider">Alasan Pengeluaran Segera</h4>
                                            </div>
                                            <p class="text-xs text-rose-950 font-bold leading-relaxed">{{ data_get($document->payload, 'header.alasan_rush_handling') }}</p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Right Column: Logistics & Values --}}
                                <div class="space-y-6">
                                    <div>
                                        <div class="flex items-center gap-2 mb-3">
                                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.129-1.125V11.25c0-.621-.508-1.125-1.129-1.125H16.5a9 9 0 0 0-9 9M15 9.75a2.25 2.25 0 0 0-2.25-2.25h-3a2.25 2.25 0 0 0-2.25 2.25M6.75 21h10.5V3.75c0-.621-.504-1.125-1.125-1.125h-8.25C6.146 2.625 5.625 3.146 5.625 3.75V21Z" />
                                            </svg>
                                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Logistik &amp; Pengangkutan</h4>
                                        </div>
                                        <div class="bg-slate-50/50 hover:bg-slate-50 border border-slate-100 hover:border-slate-200 rounded-2xl p-5 space-y-3 transition-all duration-300">
                                            @if ($document->doc_type === 'RUSH')
                                                <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-100">
                                                    <span class="text-slate-500">Sarana Pengangkut:</span>
                                                    <span class="font-extrabold text-slate-800">{{ data_get($document->payload, 'header.pengangkutan.sarana') }} ({{ data_get($document->payload, 'header.pengangkutan.flight_no') }})</span>
                                                </div>
                                                <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-100">
                                                    <span class="text-slate-500">No. AWB / BL:</span>
                                                    <span class="font-mono font-extrabold text-slate-800">{{ data_get($document->payload, 'header.dokumen_pengangkutan.awb_bl') }}</span>
                                                </div>
                                                <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-100">
                                                    <span class="text-slate-500">Tanggal AWB:</span>
                                                    <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.dokumen_pengangkutan.tanggal') }}</span>
                                                </div>
                                                <div class="flex justify-between items-center text-xs">
                                                    <span class="text-slate-500">Kemasan:</span>
                                                    <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.kemasan.jumlah') }} {{ data_get($document->payload, 'header.kemasan.jenis') }}</span>
                                                </div>
                                            @elseif ($document->doc_type === 'BC30')
                                                <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-100">
                                                    <span class="text-slate-500">Pelabuhan Muat:</span>
                                                    <span class="font-bold text-slate-800 font-mono">{{ data_get($document->payload, 'header.pengangkutan.pelabuhan_muat') }}</span>
                                                </div>
                                                <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-100">
                                                    <span class="text-slate-500">Pelabuhan Tujuan:</span>
                                                    <span class="font-bold text-slate-800 font-mono">{{ data_get($document->payload, 'header.pengangkutan.pelabuhan_tujuan') ?? '—' }}</span>
                                                </div>
                                                <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-100">
                                                    <span class="text-slate-500">Cara Angkut:</span>
                                                    <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pengangkutan.cara_angkut') ?? '—' }}</span>
                                                </div>
                                                <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-100">
                                                    <span class="text-slate-500">Sarana / Voy:</span>
                                                    <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pengangkutan.sarana_angkut') ?? '—' }} {{ data_get($document->payload, 'header.pengangkutan.voy_flight') ? '· '.data_get($document->payload, 'header.pengangkutan.voy_flight') : '' }}</span>
                                                </div>
                                                <div class="flex justify-between items-center text-xs">
                                                    <span class="text-slate-500">Perkiraan Ekspor:</span>
                                                    <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pengangkutan.tanggal_ekspor') ?? '—' }}</span>
                                                </div>
                                            @else
                                                <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-100">
                                                    <span class="text-slate-500">Pelabuhan Muat:</span>
                                                    <span class="font-bold text-slate-800 font-mono">{{ data_get($document->payload, 'header.pengangkutan.pelabuhan_muat') }}</span>
                                                </div>
                                                <div class="flex justify-between items-center text-xs">
                                                    <span class="text-slate-500">Pelabuhan Bongkar:</span>
                                                    <span class="font-bold text-slate-800 font-mono">{{ data_get($document->payload, 'header.pengangkutan.pelabuhan_bongkar') ?? '—' }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <div class="bg-gradient-to-br from-indigo-900 to-indigo-950 text-white rounded-3xl p-6 shadow-md shadow-indigo-950/10 space-y-4 relative overflow-hidden">
                                            <div class="absolute -right-8 -bottom-8 h-28 w-28 rounded-full bg-indigo-800/20 blur-xl"></div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-indigo-200 text-[10px] font-black uppercase tracking-wider">Mata Uang &amp; Nilai</span>
                                                <span class="font-mono text-[10px] bg-indigo-800/80 text-indigo-100 font-black px-2.5 py-1 rounded-lg border border-indigo-700/50 uppercase tracking-widest">{{ data_get($document->payload, 'header.valuta') ?? 'USD' }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-indigo-300 text-[9px] uppercase font-bold tracking-wider">Nilai Total Dokumen</span>
                                                <span class="block text-2xl font-black text-white tracking-tight mt-1">
                                                    @if ($document->doc_type === 'BC30')
                                                        {{ number_format(data_get($document->payload, 'header.nilai_fob', 0), 2, ',', '.') }}
                                                    @elseif ($document->doc_type === 'BC20' || $document->doc_type === 'BC24')
                                                        {{ number_format(data_get($document->payload, 'header.nilai_cif', 0), 2, ',', '.') }}
                                                    @else
                                                        {{ number_format(data_get($document->payload, 'header.nilai_barang', 0), 2, ',', '.') }}
                                                    @endif
                                                    <span class="text-xs font-bold text-indigo-300 ml-1">{{ data_get($document->payload, 'header.valuta') ?? 'USD' }}</span>
                                                </span>
                                            </div>
                                            <div class="border-t border-indigo-800/60 pt-4 grid grid-cols-2 gap-4 text-[11px]">
                                                @if (data_get($document->payload, 'header.cara_pembayaran'))
                                                    <div>
                                                        <span class="block text-indigo-400 text-[9px] uppercase font-bold tracking-wider">Metode Pembayaran</span>
                                                        <span class="block font-bold text-indigo-50 mt-0.5">{{ data_get($document->payload, 'header.cara_pembayaran') }}</span>
                                                    </div>
                                                @endif
                                                @if ($document->doc_type === 'BC30')
                                                    <div>
                                                        <span class="block text-indigo-400 text-[9px] uppercase font-bold tracking-wider">Incoterm</span>
                                                        <span class="block font-bold text-indigo-50 mt-0.5">{{ data_get($document->payload, 'header.incoterm') ?? '—' }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            @if ($document->doc_type === 'BC30')
                                                <div class="mt-2 border-t border-indigo-800/60 pt-3 grid grid-cols-2 gap-2 text-[10px] text-indigo-205">
                                                    <div class="bg-indigo-950/40 p-2 rounded-xl text-center">
                                                        <span class="block text-indigo-400 font-bold uppercase tracking-wider text-[8px] mb-0.5">NDPBM</span>
                                                        <span class="font-extrabold text-white font-mono">{{ number_format(data_get($document->payload, 'header.ndpbm', 0), 0, ',', '.') }}</span>
                                                    </div>
                                                    <div class="bg-indigo-950/40 p-2 rounded-xl text-center">
                                                        <span class="block text-indigo-400 font-bold uppercase tracking-wider text-[8px] mb-0.5">Bruto</span>
                                                        <span class="font-extrabold text-white font-mono">{{ number_format(data_get($document->payload, 'header.bruto', 0), 2, ',', '.') }} kg</span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Items Table --}}
                            <div @if ($document->isArchived()) style="display:none" @endif class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-wider">Detail Pos Komoditas Barang</h4>
                                    <span class="text-[10px] bg-slate-100 text-slate-600 font-bold px-2 py-0.5 rounded-lg border border-slate-200/50">Total: {{ count(data_get($document->payload, 'barang', [])) }} Pos</span>
                                </div>
                                <div class="border border-slate-100 rounded-2xl overflow-hidden shadow-sm hover:shadow transition-shadow">
                                    <table class="min-w-full divide-y divide-slate-100 text-xs text-left">
                                        <thead class="bg-slate-50/60 text-slate-500 font-bold uppercase tracking-wider">
                                            <tr>
                                                <th class="px-4 py-3.5 text-center">Seri</th>
                                                <th class="px-4 py-3.5">Kode HS</th>
                                                <th class="px-4 py-3.5">Uraian Barang</th>
                                                <th class="px-4 py-3.5 text-right">Jumlah</th>
                                                <th class="px-4 py-3.5 text-center">Satuan</th>
                                                <th class="px-4 py-3.5 text-right">Netto</th>
                                                <th class="px-4 py-3.5 text-right">Nilai Barang</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @php
                                                $totalNetto = 0;
                                                $totalValue = 0;
                                            @endphp
                                            @foreach (data_get($document->payload, 'barang', []) as $item)
                                                @php
                                                    $totalNetto += (float)data_get($item, 'netto', 0);
                                                    $valueKey = match ($document->doc_type) {
                                                        'BC30' => 'nilai_fob',
                                                        'BC20', 'BC24' => 'nilai_cif',
                                                        default => 'nilai_barang',
                                                    };
                                                    $totalValue += (float)data_get($item, $valueKey, 0);
                                                @endphp
                                                <tr class="hover:bg-slate-50/45 transition-colors">
                                                    <td class="px-4 py-3 text-center">
                                                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-slate-100 text-slate-500 font-bold text-[10px]">
                                                            {{ data_get($item, 'seri') }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="font-mono font-bold text-slate-800 bg-slate-100/70 border border-slate-200/50 px-2 py-0.5 rounded text-[11px] block w-fit">
                                                            {{ data_get($item, 'hs_code') }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-700 font-medium leading-normal max-w-xs truncate" title="{{ data_get($item, 'uraian') }}">{{ data_get($item, 'uraian') }}</td>
                                                    <td class="px-4 py-3 text-right font-bold text-slate-800">{{ number_format(data_get($item, 'jumlah_satuan'), 2, ',', '.') }}</td>
                                                    <td class="px-4 py-3 text-center">
                                                        <span class="inline-block text-[10px] font-bold text-slate-500 bg-slate-100 border border-slate-200/50 px-1.5 py-0.5 rounded uppercase">
                                                            {{ data_get($item, 'kode_satuan') }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right text-slate-600 font-mono">{{ number_format(data_get($item, 'netto'), 2, ',', '.') }} kg</td>
                                                    <td class="px-4 py-3 text-right font-extrabold text-indigo-600 bg-indigo-50/10">
                                                        {{ number_format(data_get($item, $valueKey, 0), 2, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        @if(count(data_get($document->payload, 'barang', [])) > 0)
                                            <tfoot class="bg-slate-50/40 font-bold text-slate-800 border-t-2 border-slate-200/60">
                                                <tr>
                                                    <td colspan="3" class="px-4 py-3.5 text-right font-black uppercase text-[9px] tracking-wider text-slate-400">Total Akumulasi</td>
                                                    <td class="px-4 py-3.5 text-right text-slate-800 font-extrabold">—</td>
                                                    <td class="px-4 py-3.5 text-center text-slate-500">—</td>
                                                    <td class="px-4 py-3.5 text-right text-slate-800 font-mono font-extrabold">{{ number_format($totalNetto, 2, ',', '.') }} kg</td>
                                                    <td class="px-4 py-3.5 text-right font-black text-indigo-755 bg-indigo-50/20 text-sm">
                                                        {{ number_format($totalValue, 2, ',', '.') }}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- JSON Tabs (Payload & Response) --}}
                    <div class="bg-slate-950 text-slate-350 rounded-3xl shadow-lg border border-slate-900 overflow-hidden" 
                         x-data="{ 
                            activeTab: 'payload', 
                            copied: false,
                            copyJson() {
                                let payload = {{ json_encode($document->payload) }};
                                let response = {{ json_encode($document->ceisa_response) }};
                                let data = this.activeTab === 'payload' ? payload : response;
                                navigator.clipboard.writeText(JSON.stringify(data, null, 2));
                                this.copied = true;
                                setTimeout(() => this.copied = false, 1500);
                            }
                         }">
                        <div class="bg-slate-900 px-6 py-4 border-b border-slate-950 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <!-- Mac Window Controls Mockup -->
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="h-3 w-3 rounded-full bg-rose-500"></span>
                                    <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                                    <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                                </div>
                                <div class="flex gap-1 bg-slate-950/40 p-1 rounded-xl border border-slate-800">
                                    <button type="button" @click="activeTab = 'payload'"
                                            class="text-xs font-bold uppercase tracking-wider px-3.5 py-1.5 rounded-lg transition-all duration-200"
                                            :class="activeTab === 'payload' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'">
                                        Payload Kirim
                                    </button>
                                    @if ($document->ceisa_response)
                                        <button type="button" @click="activeTab = 'response'"
                                                class="text-xs font-bold uppercase tracking-wider px-3.5 py-1.5 rounded-lg transition-all duration-200"
                                                :class="activeTab === 'response' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'">
                                            Response CEISA
                                        </button>
                                    @endif
                                </div>
                            </div>
                            
                            <button type="button" @click="copyJson()" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800/80 hover:bg-slate-850 hover:text-white border border-slate-700/60 rounded-xl text-xs font-bold transition-all duration-200 text-slate-350 relative group">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5" />
                                </svg>
                                <span x-text="copied ? 'Tersalin!' : 'Salin JSON'">Salin JSON</span>
                            </button>
                        </div>
                        
                        <div class="p-6">
                            <div x-show="activeTab === 'payload'" x-transition class="overflow-x-auto">
                                <pre class="text-[11px] font-mono leading-relaxed text-emerald-400 max-h-[300px] overflow-y-auto">{{ json_encode($document->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                            @if ($document->ceisa_response)
                                <div x-show="activeTab === 'response'" x-transition class="overflow-x-auto">
                                    <pre class="text-[11px] font-mono leading-relaxed text-cyan-400 max-h-[300px] overflow-y-auto">{{ json_encode($document->ceisa_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
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
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100/90 space-y-4 transition-all duration-300 hover:shadow-md">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                            <h3 class="font-extrabold text-slate-800 text-xs uppercase tracking-wider">Radar Kepatuhan</h3>
                        </div>
                        <div class="space-y-2.5">
                            <!-- Nama Pihak Utama -->
                            <div @class([
                                'flex items-center justify-between p-3 rounded-2xl border text-xs font-semibold',
                                'bg-emerald-50/35 border-emerald-100/60 text-slate-850' => $hasEntity,
                                'bg-rose-50/45 border-rose-100/60 text-slate-850' => !$hasEntity
                            ])>
                                <span class="text-slate-600">Pihak Utama</span>
                                <span @class([
                                    'inline-flex items-center gap-1 text-[10px] font-black uppercase px-2 py-0.5 rounded-lg shadow-sm border',
                                    'bg-white border-emerald-200 text-emerald-700' => $hasEntity,
                                    'bg-white border-rose-200 text-rose-700' => !$hasEntity
                                ])>
                                    @if ($hasEntity)
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Lengkap
                                    @else
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse"></span> Kosong
                                    @endif
                                </span>
                            </div>

                            <!-- NPWP Pihak Utama -->
                            <div @class([
                                'flex items-center justify-between p-3 rounded-2xl border text-xs font-semibold',
                                'bg-emerald-50/35 border-emerald-100/60 text-slate-850' => $hasNpwp,
                                'bg-amber-50/45 border-amber-100/60 text-slate-850' => !$hasNpwp
                            ])>
                                <span class="text-slate-600">NPWP Pihak</span>
                                <span @class([
                                    'inline-flex items-center gap-1 text-[10px] font-black uppercase px-2 py-0.5 rounded-lg shadow-sm border',
                                    'bg-white border-emerald-200 text-emerald-700' => $hasNpwp,
                                    'bg-white border-amber-200 text-amber-700' => !$hasNpwp
                                ])>
                                    @if ($hasNpwp)
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Valid
                                    @else
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span> Kosong
                                    @endif
                                </span>
                            </div>

                            <!-- Komoditas Barang -->
                            <div @class([
                                'flex items-center justify-between p-3 rounded-2xl border text-xs font-semibold',
                                'bg-emerald-50/35 border-emerald-100/60 text-slate-850' => $hasBarang,
                                'bg-rose-50/45 border-rose-100/60 text-slate-850' => !$hasBarang
                            ])>
                                <span class="text-slate-600">Pos Barang</span>
                                <span @class([
                                    'inline-flex items-center gap-1 text-[10px] font-black uppercase px-2 py-0.5 rounded-lg shadow-sm border',
                                    'bg-white border-emerald-200 text-emerald-700' => $hasBarang,
                                    'bg-white border-rose-200 text-rose-700' => !$hasBarang
                                ])>
                                    @if ($hasBarang)
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Terisi
                                    @else
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse"></span> Kosong
                                    @endif
                                </span>
                            </div>

                            @if($hasBarang)
                                <!-- Format HS Code -->
                                <div @class([
                                    'flex items-center justify-between p-3 rounded-2xl border text-xs font-semibold',
                                    'bg-emerald-50/35 border-emerald-100/60 text-slate-850' => $hsValid,
                                    'bg-amber-50/45 border-amber-100/60 text-slate-850' => !$hsValid
                                ])>
                                    <span class="text-slate-600">HS Code 8-Digit</span>
                                    <span @class([
                                        'inline-flex items-center gap-1 text-[10px] font-black uppercase px-2 py-0.5 rounded-lg shadow-sm border',
                                        'bg-white border-emerald-200 text-emerald-700' => $hsValid,
                                        'bg-white border-amber-200 text-amber-700' => !$hsValid
                                    ])>
                                        @if ($hsValid)
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Ok
                                        @else
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span> Koreksi
                                        @endif
                                    </span>
                                </div>

                                <!-- Berat Netto Barang -->
                                <div @class([
                                    'flex items-center justify-between p-3 rounded-2xl border text-xs font-semibold',
                                    'bg-emerald-50/35 border-emerald-100/60 text-slate-850' => $nettoValid,
                                    'bg-amber-50/45 border-amber-100/60 text-slate-850' => !$nettoValid
                                ])>
                                    <span class="text-slate-600">Massa Netto</span>
                                    <span @class([
                                        'inline-flex items-center gap-1 text-[10px] font-black uppercase px-2 py-0.5 rounded-lg shadow-sm border',
                                        'bg-white border-emerald-200 text-emerald-700' => $nettoValid,
                                        'bg-white border-amber-200 text-amber-700' => !$nettoValid
                                    ])>
                                        @if ($nettoValid)
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Ok
                                        @else
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span> Berat 0
                                        @endif
                                    </span>
                                </div>

                                <!-- Nilai Barang -->
                                <div @class([
                                    'flex items-center justify-between p-3 rounded-2xl border text-xs font-semibold',
                                    'bg-emerald-50/35 border-emerald-100/60 text-slate-850' => $nilaiValid,
                                    'bg-amber-50/45 border-amber-100/60 text-slate-850' => !$nilaiValid
                                ])>
                                    <span class="text-slate-600">Nilai Barang</span>
                                    <span @class([
                                        'inline-flex items-center gap-1 text-[10px] font-black uppercase px-2 py-0.5 rounded-lg shadow-sm border',
                                        'bg-white border-emerald-200 text-emerald-700' => $nilaiValid,
                                        'bg-white border-amber-200 text-amber-700' => !$nilaiValid
                                    ])>
                                        @if ($nilaiValid)
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Ok
                                        @else
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span> Nilai 0
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Quick Action PDF Downloads Toolbar --}}
                    @if(in_array($document->status, ['accepted', 'submitted']))
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100/90 space-y-4 transition-all duration-300 hover:shadow-md">
                            <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                                <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                <h3 class="font-extrabold text-slate-800 text-xs uppercase tracking-wider">Unduh Dokumen Pabean</h3>
                            </div>
                            <div class="flex flex-col gap-2.5">
                                <!-- Cetak Formulir -->
                                <a href="{{ route('documents.cetak-formulir', $document) }}" 
                                   class="flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-indigo-50/40 hover:text-indigo-900 text-slate-700 rounded-2xl text-xs font-bold transition-all duration-200 border border-slate-200/60 hover:border-indigo-200 group">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-slate-400 group-hover:text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12-1.227H7.231c-.662 0-1.18-.567-1.12-1.227L6.34 18m11.318 0h-11.32" /></svg>
                                        Formulir Pabean (PDF)
                                    </span>
                                    <span class="text-[9px] bg-indigo-150 text-indigo-700 font-extrabold px-2 py-0.5 rounded-lg border border-indigo-200/30">PRINT</span>
                                </a>

                                <!-- Download Respon -->
                                <a href="{{ route('documents.download-respon', $document) }}" 
                                   class="flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-emerald-50/40 hover:text-emerald-900 text-slate-700 rounded-2xl text-xs font-bold transition-all duration-200 border border-slate-200/60 hover:border-emerald-200 group">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-slate-400 group-hover:text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                        Surat Persetujuan (SPPB)
                                    </span>
                                    <span class="text-[9px] bg-emerald-100 text-emerald-700 font-extrabold px-2 py-0.5 rounded-lg border border-emerald-200/30">SPPB</span>
                                </a>

                                <!-- Download Billing -->
                                <a href="{{ route('documents.download-billing', $document) }}" 
                                   class="flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-amber-50/40 hover:text-amber-900 text-slate-700 rounded-2xl text-xs font-bold transition-all duration-200 border border-slate-200/60 hover:border-amber-200 group">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-slate-400 group-hover:text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                                        Billing / SPJM (PDF)
                                    </span>
                                    <span class="text-[9px] bg-amber-100 text-amber-700 font-extrabold px-2 py-0.5 rounded-lg border border-amber-200/30">BILLING</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- Local Webhook Simulator Widget --}}
                    @if(config('app.env') === 'local')
                        <div class="bg-slate-900 text-white rounded-3xl p-6 shadow-lg border border-slate-800 space-y-4 shadow-indigo-950/20 relative overflow-hidden">
                            <div class="absolute -right-8 -bottom-8 h-24 w-24 rounded-full bg-indigo-500/10 blur-xl"></div>
                            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <h3 class="font-extrabold text-[11px] uppercase tracking-wider text-slate-250">CEISA Simulator Deck</h3>
                                </div>
                                <span class="text-[8px] font-black bg-indigo-600/60 border border-indigo-500/40 text-indigo-150 px-2 py-0.5 rounded-md tracking-wider">DEV PANEL</span>
                            </div>
                            <p class="text-[10px] text-slate-400 leading-normal">Trigger mock callback payload dari bea cukai ke webhook lokal untuk memperbarui status.</p>
                            
                            <div class="grid grid-cols-2 gap-2 pt-1">
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
                                    <button type="submit" class="w-full text-center py-2 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-650 text-white border-b-2 border-emerald-800 rounded-xl text-[10px] font-black transition-all duration-200 transform active:translate-y-0.5">
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
                                    <button type="submit" class="w-full text-center py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-450 hover:to-amber-550 text-white border-b-2 border-amber-800 rounded-xl text-[10px] font-black transition-all duration-200 transform active:translate-y-0.5">
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
                                    <button type="submit" class="w-full text-center py-2 bg-gradient-to-r from-rose-550 to-rose-650 hover:from-rose-500 hover:to-rose-600 text-white border-b-2 border-rose-800 rounded-xl text-[10px] font-black transition-all duration-200 transform active:translate-y-0.5">
                                        🔴 Jalur Merah
                                    </button>
                                </form>

                                <!-- Ditolak / NPP -->
                                <form method="POST" action="{{ route('webhook.ceisa') }}" target="_blank">
                                    @csrf
                                    <input type="hidden" name="nomor_aju" value="{{ $document->nomor_aju }}" />
                                    <input type="hidden" name="status" value="DITOLAK/NPP" />
                                    <input type="hidden" name="jenis" value="Respon" />
                                    <button type="submit" class="w-full text-center py-2 bg-slate-800 hover:bg-slate-700 text-slate-350 border border-slate-700/65 rounded-xl text-[10px] font-black transition-all duration-200 transform active:translate-y-0.5">
                                        ⚫ Ditolak / NPP
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    {{-- Webhook Logs History --}}
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100/90 transition-all duration-300 hover:shadow-md">
                        <div class="flex items-center gap-2 pb-3 border-b border-slate-100 mb-4">
                            <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider">Log Webhook Gateway</h3>
                        </div>
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
                                                        <span class="h-8 w-8 rounded-full bg-emerald-50 border-2 border-emerald-500 flex items-center justify-center ring-8 ring-white shadow-sm shrink-0">
                                                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                            </svg>
                                                        </span>
                                                    </div>
                                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                        <div>
                                                            <p class="text-xs text-slate-800 font-extrabold">Callback: <span class="font-mono text-indigo-600 bg-indigo-50 border border-indigo-100/50 px-2 py-0.5 rounded">{{ $log->event ?? 'DITERIMA' }}</span></p>
                                                        </div>
                                                        <div class="whitespace-nowrap text-right text-[10px] text-slate-400 font-bold uppercase">
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
                                <p class="text-[11px] font-medium">Menunggu callback webhook resmi...</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            <div class="flex items-center justify-start pt-4">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-slate-800 transition-colors bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-2xl px-5 py-3 shadow-sm hover:shadow">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
