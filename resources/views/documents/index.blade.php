<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-900 leading-tight tracking-tight">Daftar Dokumen</h2>
                <p class="text-xs text-slate-500 mt-1 font-medium">Lihat, cari, saring, dan ekspor daftar rekapitulasi dokumen pabean Bea Cukai Anda.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('documents.export', request()->query()) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 bg-white border border-slate-200/80 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-50 hover:text-slate-900 shadow-sm transition-all duration-200 hover:-translate-y-0.5">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                    Ekspor CSV
                </a>
                <a href="{{ route('documents.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white text-xs font-extrabold rounded-xl hover:from-indigo-700 hover:to-indigo-800 shadow-md shadow-indigo-200 hover:shadow-indigo-300 transition-all duration-200 hover:-translate-y-0.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Buat Dokumen
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6" x-data="{ idMode: 'npwp' }">
            <x-flash />

            {{-- Rekap (mengikuti filter aktif) --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Terfilter -->
                <div class="bg-white border border-slate-200/60 border-l-4 border-l-slate-600 shadow-sm shadow-slate-100/40 rounded-2xl p-4 relative overflow-hidden group hover:shadow-md transition-all duration-300">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total (Terfilter)</div>
                    <div class="text-2xl font-extrabold text-slate-800 mt-1 tracking-tight">{{ $rekap['total'] }}</div>
                </div>
                <!-- Diterima -->
                <div class="bg-white border border-slate-200/60 border-l-4 border-l-emerald-500 shadow-sm shadow-slate-100/40 rounded-2xl p-4 relative overflow-hidden group hover:shadow-md transition-all duration-300">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Diterima</div>
                    <div class="text-2xl font-extrabold text-emerald-600 mt-1 tracking-tight">{{ $rekap['accepted'] }}</div>
                </div>
                <!-- Ditolak/Error -->
                <div class="bg-white border border-slate-200/60 border-l-4 border-l-rose-500 shadow-sm shadow-slate-100/40 rounded-2xl p-4 relative overflow-hidden group hover:shadow-md transition-all duration-300">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ditolak / Error</div>
                    <div class="text-2xl font-extrabold text-rose-600 mt-1 tracking-tight">{{ $rekap['rejected'] }}</div>
                </div>
                <!-- Jalur Merah -->
                <div class="bg-white border border-slate-200/60 border-l-4 border-l-red-600 shadow-sm shadow-slate-100/40 rounded-2xl p-4 relative overflow-hidden group hover:shadow-md transition-all duration-300">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jalur Merah</div>
                    <div class="text-2xl font-extrabold text-red-600 mt-1 tracking-tight">{{ $rekap['merah'] }}</div>
                </div>
            </div>

            {{-- Action bar ala Portal CEISA 4.0 --}}
            @php($bumnOn = $filters['bumn'] ?? false)
            <div class="bg-white border border-slate-200/70 rounded-2xl shadow-sm px-4 py-3 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                {{-- Kiri: toggle identitas & BUMN Ekspor --}}
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[10px] font-black text-slate-300 uppercase tracking-wider hidden sm:inline">Tampilan</span>
                    {{-- Toggle NPWP 16 / NITKU (client-side) --}}
                    <div class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 p-0.5 text-[11px] font-extrabold">
                        <button type="button" @click="idMode='npwp'" :class="idMode==='npwp' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-400 hover:text-slate-600'" class="px-3 py-1.5 rounded-lg transition cursor-pointer">NPWP 16</button>
                        <button type="button" @click="idMode='nitku'" :class="idMode==='nitku' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-400 hover:text-slate-600'" class="px-3 py-1.5 rounded-lg transition cursor-pointer">NITKU</button>
                    </div>
                    {{-- Toggle BUMN Ekspor (server filter) --}}
                    <a href="{{ request()->fullUrlWithQuery(['bumn' => $bumnOn ? null : 1, 'page' => null]) }}"
                       class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border text-[11px] font-extrabold transition {{ $bumnOn ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50' }}">
                        <span class="h-3.5 w-7 rounded-full relative transition {{ $bumnOn ? 'bg-amber-500' : 'bg-slate-300' }}">
                            <span class="absolute top-0.5 h-2.5 w-2.5 rounded-full bg-white transition-all {{ $bumnOn ? 'left-3.5' : 'left-0.5' }}"></span>
                        </span>
                        BUMN Ekspor
                    </a>
                </div>
                {{-- Kanan: Utilitas / Monitoring / Muat Ulang --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Utilitas dropdown --}}
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button type="button" @click="open = !open"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-slate-200 text-slate-600 text-[11px] font-bold rounded-xl hover:bg-slate-50 transition cursor-pointer">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            Utilitas
                            <svg class="h-3 w-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-56 bg-white border border-slate-200/80 rounded-2xl shadow-xl z-20 py-1.5 overflow-hidden">
                            <a href="{{ route('documents.export', request()->query()) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                                <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                                Ekspor CSV (terfilter)
                            </a>
                            <button type="button" onclick="window.print()" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Z" /></svg>
                                Cetak Daftar
                            </button>
                        </div>
                    </div>
                    {{-- Monitoring dropdown --}}
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button type="button" @click="open = !open"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-slate-200 text-slate-600 text-[11px] font-bold rounded-xl hover:bg-slate-50 transition cursor-pointer">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                            Monitoring
                            <svg class="h-3 w-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-56 bg-white border border-slate-200/80 rounded-2xl shadow-xl z-20 py-1.5 overflow-hidden">
                            <a href="{{ route('notifications.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span> Pusat Notifikasi DJBC
                            </a>
                            <a href="{{ route('documents.index', ['jalur' => 'M']) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Dokumen Jalur Merah
                            </a>
                            <a href="{{ route('documents.index', ['status' => 'submitted']) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                                <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span> Menunggu Respon
                            </a>
                            <a href="{{ route('documents.index', ['status' => 'rejected']) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Ditolak / Error
                            </a>
                        </div>
                    </div>
                    {{-- Muat Ulang --}}
                    <a href="{{ request()->fullUrl() }}" title="Muat ulang data terbaru"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-slate-200 text-slate-600 text-[11px] font-bold rounded-xl hover:bg-slate-50 transition">
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                        Muat Ulang
                    </a>
                </div>
            </div>

            {{-- Filter bar --}}
            <form method="GET" action="{{ route('documents.index') }}"
                  class="bg-white border border-slate-200/70 rounded-2xl shadow-sm p-5">
                @if ($bumnOn)
                    <input type="hidden" name="bumn" value="1">
                @endif
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-4">
                    <div class="lg:col-span-2">
                         <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Cari Nomor Aju / Daftar</label>
                         <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Ketik nomor…"
                                class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Jenis</label>
                        <select name="doc_type" class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm font-medium">
                            <option value="">Semua</option>
                            @foreach ($docTypes as $code => $label)
                                <option value="{{ $code }}" @selected($filters['doc_type'] === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Status</label>
                        <select name="status" class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm font-medium">
                            <option value="">Semua</option>
                            @foreach (['draft' => 'Draft', 'submitting' => 'Mengirim', 'submitted' => 'Terkirim', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', 'error' => 'Error'] as $val => $label)
                                <option value="{{ $val }}" @selected($filters['status'] === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Jalur</label>
                        <select name="jalur" class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm font-medium">
                            <option value="">Semua</option>
                            <option value="H" @selected($filters['jalur'] === 'H')>Hijau</option>
                            <option value="K" @selected($filters['jalur'] === 'K')>Kuning</option>
                            <option value="M" @selected($filters['jalur'] === 'M')>Merah</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Sumber</label>
                        <select name="source" class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm font-medium">
                            <option value="">Semua</option>
                            <option value="h2h" @selected($filters['source'] === 'h2h')>H2H</option>
                            <option value="arsip" @selected($filters['source'] === 'arsip')>Arsip</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold rounded-xl shadow-sm transition-colors cursor-pointer">
                            Terapkan
                        </button>
                        <a href="{{ route('documents.index') }}" class="inline-flex items-center justify-center px-3 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
                            Reset
                        </a>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 border-t border-slate-100 pt-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                        <input type="date" name="from" value="{{ $filters['from'] }}"
                               class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
                        <input type="date" name="to" value="{{ $filters['to'] }}"
                               class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm" />
                    </div>
                </div>
            </form>

            {{-- Tabel --}}
            <div class="bg-white border border-slate-200/70 rounded-2xl shadow-sm overflow-hidden mb-6">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="font-extrabold text-slate-800 text-sm">Dokumen Terfilter</h3>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Menampilkan <span class="font-bold text-slate-700">{{ $documents->total() }}</span> dokumen hasil penyaringan.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70">
                            <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <th class="px-6 py-4">Nomor Pengajuan</th>
                                <th class="px-6 py-4">Jenis</th>
                                <th class="px-6 py-4">No. / Tgl Pendaftaran</th>
                                <th class="px-6 py-4">Respon DJBC</th>
                                <th class="px-6 py-4">Status & Jalur</th>
                                <th class="px-6 py-4">Perusahaan / Kantor Pabean</th>
                                <th class="px-6 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($documents as $doc)
                                <tr class="hover:bg-slate-50/60 transition-colors duration-150">
                                    <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-600">
                                        <div class="flex items-center gap-1.5">
                                            @if ($doc->nomor_aju)
                                                <span>{{ $doc->nomor_aju }}</span>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                            @if ($doc->isArchived())
                                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-500 border border-slate-200 uppercase tracking-wide">Arsip</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-sans mt-1">Dibuat {{ $doc->created_at->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($doc->doc_type === 'BC30')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100/60 shadow-sm">BC 3.0 (Ekspor)</span>
                                        @elseif($doc->doc_type === 'BC20')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100/60 shadow-sm">BC 2.0 (Impor)</span>
                                        @elseif($doc->doc_type === 'BC24')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100/60 shadow-sm">BC 2.4 (Impor TPB)</span>
                                        @elseif($doc->doc_type === 'TPB')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700 border border-purple-100/60 shadow-sm">Portal TPB</span>
                                        @elseif($doc->doc_type === 'RUSH')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 border border-rose-100/60 shadow-sm">Rush Handling</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-50 text-slate-700 border border-slate-150">{{ $doc->doc_type }}</span>
                                        @endif
                                    </td>
                                    {{-- No. & Tgl Pendaftaran --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($doc->nomor_daftar)
                                            <div class="font-mono text-xs font-bold text-slate-700">{{ $doc->nomor_daftar }}</div>
                                            @if ($doc->tanggalDaftar())
                                                <div class="text-[10px] text-slate-400 mt-0.5">{{ $doc->tanggalDaftar()->format('d/m/Y') }}</div>
                                            @endif
                                        @else
                                            <span class="text-[11px] text-slate-300 font-medium italic">Belum terdaftar</span>
                                        @endif
                                    </td>
                                    {{-- Respon DJBC (Nama + No. Surat + Tgl) --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php($resp = $doc->responseSummary())
                                        @if ($resp)
                                            @php($respClass = $resp['nama'] === 'SPPB' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : (($resp['nama'] === 'PENOLAKAN' || str_contains($resp['nama'], 'NPP') || str_contains($resp['nama'], 'TOLAK')) ? 'bg-rose-50 text-rose-700 border-rose-100' : 'bg-sky-50 text-sky-700 border-sky-100'))
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide border {{ $respClass }}">{{ $resp['nama'] }}</span>
                                            @if ($resp['no_surat'])
                                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $resp['no_surat'] }}</div>
                                            @endif
                                            @if ($resp['tanggal'])
                                                <div class="text-[10px] text-slate-400 mt-0.5">{{ $resp['tanggal']->format('d/m/Y H:i') }}</div>
                                            @endif
                                        @else
                                            <span class="text-[11px] text-slate-300 font-medium italic">Menunggu respon</span>
                                        @endif
                                    </td>
                                    {{-- Status & Jalur --}}
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <x-status-badge :status="$doc->status" />
                                            <x-jalur-badge :jalur="$doc->jalur" />
                                        </div>
                                    </td>
                                    {{-- Perusahaan + Kantor Pabean --}}
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-800 leading-snug">{{ $doc->partyName() ?? '—' }}</div>
                                        @if ($doc->partyNpwp() || $doc->partyNitku())
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5 bg-slate-50 border border-slate-100 px-1.5 py-0.5 rounded-md inline-block" x-show="idMode==='npwp'">NPWP: {{ $doc->partyNpwp() ?? '—' }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5 bg-slate-50 border border-slate-100 px-1.5 py-0.5 rounded-md inline-block" x-show="idMode==='nitku'" x-cloak>NITKU: {{ $doc->partyNitku() ?? '—' }}</div>
                                        @endif
                                        @if ($doc->kantorPabeanLabel())
                                            <div class="text-[10px] text-slate-500 mt-1 flex items-start gap-1">
                                                <svg class="h-3 w-3 text-slate-300 mt-px flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" /></svg>
                                                <span class="leading-tight">{{ $doc->kantorPabeanLabel() }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap text-xs font-semibold">
                                        <div class="flex items-center justify-end gap-3.5">
                                            <button type="button"
                                                    @click="$dispatch('quick-view', @js($doc->quickViewData()))"
                                                    class="text-indigo-600 hover:text-indigo-950 inline-flex items-center gap-1 hover:underline transition-colors cursor-pointer">
                                                Detail
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                            </button>
                                            @if ($doc->isEditable())
                                                <a href="{{ route('documents.edit', $doc) }}"
                                                   class="text-slate-400 hover:text-amber-600 inline-flex items-center gap-1 transition-colors" title="Ubah dokumen">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                                                    Ubah
                                                </a>
                                            @endif
                                            @unless ($doc->isArchived())
                                                <form method="POST" action="{{ route('documents.duplicate', $doc) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-slate-400 hover:text-slate-700 inline-flex items-center gap-1 transition-colors cursor-pointer"
                                                            onclick="return confirm('Duplikasi dokumen ini sebagai draft baru?')">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                                                        </svg>
                                                        Duplikasi
                                                    </button>
                                                </form>
                                            @endunless
                                            @if ($doc->canBeDeleted())
                                                <form method="POST" action="{{ route('documents.destroy', $doc) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-slate-400 hover:text-rose-600 inline-flex items-center gap-1 transition-colors cursor-pointer"
                                                            onclick="return confirm('Hapus dokumen ini secara permanen?')" title="Hapus dokumen">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-slate-400">
                                        Tidak ada dokumen yang cocok dengan saringan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $documents->links() }}</div>

            <x-doc-quick-view-modal />
        </div>
    </div>
</x-app-layout>
