<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-900 leading-tight tracking-tight">Daftar Dokumen</h2>
                <p class="text-xs text-slate-500 mt-1 font-medium">Lihat, cari, saring, dan ekspor daftar rekapitulasi dokumen pabean Bea Cukai Anda.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('documents.export', request()->query()) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 bg-panel border border-slate-200/80 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-50 hover:text-slate-900 shadow-sm transition-all duration-200 hover:-translate-y-0.5">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                    Ekspor CSV
                </a>
                <a href="{{ route('documents.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white text-xs font-extrabold rounded-xl hover:from-indigo-700 hover:to-indigo-800 shadow-md shadow-slate-900/10 hover:shadow-slate-900/10 transition-all duration-200 hover:-translate-y-0.5">
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
                <div class="bg-panel border border-slate-200/60 border-l-4 border-l-slate-600 shadow-sm shadow-slate-900/10 rounded-2xl p-4 relative overflow-hidden group hover:shadow-md transition-all duration-300">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total (Terfilter)</div>
                    <div class="text-2xl font-extrabold text-slate-800 mt-1 tracking-tight">{{ $rekap['total'] }}</div>
                </div>
                <!-- Diterima -->
                <div class="bg-panel border border-slate-200/60 border-l-4 border-l-emerald-500 shadow-sm shadow-slate-900/10 rounded-2xl p-4 relative overflow-hidden group hover:shadow-md transition-all duration-300">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Diterima</div>
                    <div class="text-2xl font-extrabold text-emerald-300 mt-1 tracking-tight">{{ $rekap['accepted'] }}</div>
                </div>
                <!-- Ditolak/Error -->
                <div class="bg-panel border border-slate-200/60 border-l-4 border-l-rose-500 shadow-sm shadow-slate-900/10 rounded-2xl p-4 relative overflow-hidden group hover:shadow-md transition-all duration-300">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ditolak / Error</div>
                    <div class="text-2xl font-extrabold text-rose-300 mt-1 tracking-tight">{{ $rekap['rejected'] }}</div>
                </div>
                <!-- Jalur Merah -->
                <div class="bg-panel border border-slate-200/60 border-l-4 border-l-red-600 shadow-sm shadow-slate-900/10 rounded-2xl p-4 relative overflow-hidden group hover:shadow-md transition-all duration-300">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jalur Merah</div>
                    <div class="text-2xl font-extrabold text-red-300 mt-1 tracking-tight">{{ $rekap['merah'] }}</div>
                </div>
            </div>

            {{-- Action bar ala Portal CEISA 4.0 --}}
            @php($bumnOn = $filters['bumn'] ?? false)
            <div class="bg-panel border border-slate-200/70 rounded-2xl shadow-sm px-4 py-3 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                {{-- Kiri: toggle identitas & BUMN Ekspor --}}
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider hidden sm:inline">Tampilan</span>
                    {{-- Toggle NPWP 16 / NITKU (client-side) --}}
                    <div class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 p-0.5 text-[11px] font-extrabold">
                        <button type="button" @click="idMode='npwp'" :class="idMode==='npwp' ? 'bg-panel text-indigo-300 shadow-sm' : 'text-slate-400 hover:text-slate-600'" class="px-3 py-1.5 rounded-lg transition cursor-pointer">NPWP 16</button>
                        <button type="button" @click="idMode='nitku'" :class="idMode==='nitku' ? 'bg-panel text-indigo-300 shadow-sm' : 'text-slate-400 hover:text-slate-600'" class="px-3 py-1.5 rounded-lg transition cursor-pointer">NITKU</button>
                    </div>
                    {{-- Toggle BUMN Ekspor (server filter) --}}
                    <a href="{{ request()->fullUrlWithQuery(['bumn' => $bumnOn ? null : 1, 'page' => null]) }}"
                       class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border text-[11px] font-extrabold transition {{ $bumnOn ? 'bg-amber-50 border-amber-200 text-amber-300' : 'bg-panel border-slate-200 text-slate-500 hover:bg-slate-50' }}">
                        <span class="h-3.5 w-7 rounded-full relative transition {{ $bumnOn ? 'bg-amber-500' : 'bg-slate-300' }}">
                            <span class="absolute top-0.5 h-2.5 w-2.5 rounded-full bg-panel transition-all {{ $bumnOn ? 'left-3.5' : 'left-0.5' }}"></span>
                        </span>
                        BUMN Ekspor
                    </a>
                </div>
                {{-- Kanan: Utilitas / Monitoring / Muat Ulang --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Utilitas dropdown --}}
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button type="button" @click="open = !open"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-panel border border-slate-200 text-slate-600 text-[11px] font-bold rounded-xl hover:bg-slate-50 transition cursor-pointer">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            Utilitas
                            <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-56 bg-panel border border-slate-200/80 rounded-2xl shadow-xl z-20 py-1.5 overflow-hidden">
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
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-panel border border-slate-200 text-slate-600 text-[11px] font-bold rounded-xl hover:bg-slate-50 transition cursor-pointer">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                            Monitoring
                            <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-56 bg-panel border border-slate-200/80 rounded-2xl shadow-xl z-20 py-1.5 overflow-hidden">
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
                    {{-- Tarik dari CEISA --}}
                    <form method="POST" action="{{ route('documents.sync') }}" class="inline">
                        @csrf
                        <button type="submit" title="Tarik data dokumen riil dari CEISA"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-panel border border-slate-200 text-slate-600 text-[11px] font-bold rounded-xl hover:bg-slate-50 transition cursor-pointer">
                            <svg class="h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                            Tarik dari CEISA
                        </button>
                    </form>
                    {{-- Muat Ulang --}}
                    <a href="{{ request()->fullUrl() }}" title="Muat ulang data terbaru"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-panel border border-slate-200 text-slate-600 text-[11px] font-bold rounded-xl hover:bg-slate-50 transition">
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                        Muat Ulang
                    </a>
                </div>
            </div>

            {{-- Filter bar --}}
            <form method="GET" action="{{ route('documents.index') }}"
                  class="bg-panel border border-slate-200/70 rounded-2xl shadow-sm p-5">
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

            {{-- Tabel ala Portal CEISA 4.0 --}}
            <div class="bg-panel border border-slate-200/70 rounded-2xl shadow-sm overflow-hidden mb-6">
                <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 class="font-extrabold text-slate-800 text-sm">Daftar Dokumen Pabean</h3>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Menampilkan <span class="font-bold text-slate-700">{{ $documents->total() }}</span> dokumen. Gunakan filter per kolom di bawah judul tabel untuk pencarian cepat.</p>
                    </div>
                    <div class="flex items-center gap-2 text-[11px] font-bold text-slate-500">
                        <span>Tampilkan:</span>
                        <form method="GET" action="{{ route('documents.index') }}" class="inline">
                            @foreach (request()->except(['per_page', 'page']) as $k => $v)
                                @if (is_scalar($v) && $v !== '')
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endif
                            @endforeach
                            <select name="per_page" onchange="this.form.submit()" class="rounded-lg border-slate-200 text-xs font-bold py-1 pl-2.5 pr-7 focus:border-indigo-500 focus:ring-indigo-500/20">
                                @foreach ([10, 20, 50, 100] as $size)
                                    <option value="{{ $size }}" @selected(($perPage ?? 20) === $size)>{{ $size }} / hal</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>

                <form method="GET" action="{{ route('documents.index') }}" id="inline-column-filter-form">
                    @foreach (['source', 'from', 'to', 'bumn', 'per_page'] as $keepKey)
                        @if (request()->filled($keepKey))
                            <input type="hidden" name="{{ $keepKey }}" value="{{ request()->input($keepKey) }}">
                        @endif
                    @endforeach
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/80">
                                <tr class="text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                                    <th class="px-3 py-3 w-12 text-center">No</th>
                                    <th class="px-3 py-3 whitespace-nowrap">Kode Dokumen</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Nomor Pengajuan</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Nomor / Tgl Daftar</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Nama Respon</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Tanggal Respon</th>
                                    <th class="px-3 py-3 text-center whitespace-nowrap">Jalur</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Nama Perusahaan</th>
                                    <th class="px-4 py-3 whitespace-nowrap">Kantor Pabean</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap sticky right-0 bg-slate-50/95">Aksi</th>
                                </tr>
                                {{-- Baris Filter Inline per Kolom (ala portal.beacukai.go.id) --}}
                                <tr class="bg-white border-b border-slate-200/80">
                                    <th class="px-2 py-2 text-center text-[10px] text-slate-300 font-normal">#</th>
                                    <th class="px-2 py-2">
                                        <select name="doc_type" onchange="this.form.submit()" class="w-full min-w-[92px] rounded-lg border-slate-200 text-[11px] font-semibold py-1 px-2 focus:border-indigo-500 focus:ring-indigo-500/20">
                                            <option value="">Semua</option>
                                            @foreach ($docTypes as $code => $label)
                                                <option value="{{ $code }}" @selected($filters['doc_type'] === $code)>{{ $code }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th class="px-2 py-2">
                                        <div class="flex items-center gap-1">
                                            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Cari No. Aju / Daftar…"
                                                   class="w-full min-w-[155px] rounded-lg border-slate-200 text-[11px] font-mono py-1 px-2 focus:border-indigo-500 focus:ring-indigo-500/20" />
                                        </div>
                                    </th>
                                    <th class="px-2 py-2">
                                        <select name="status" onchange="this.form.submit()" class="w-full min-w-[110px] rounded-lg border-slate-200 text-[11px] font-semibold py-1 px-2 focus:border-indigo-500 focus:ring-indigo-500/20">
                                            <option value="">Status: Semua</option>
                                            @foreach (['draft' => 'Draft', 'submitting' => 'Mengirim', 'submitted' => 'Terkirim', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', 'error' => 'Error'] as $val => $label)
                                                <option value="{{ $val }}" @selected($filters['status'] === $val)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th class="px-2 py-2">
                                        <input type="text" name="respon" value="{{ $filters['respon'] ?? '' }}" placeholder="SPPB / NPD…"
                                               class="w-full min-w-[95px] rounded-lg border-slate-200 text-[11px] py-1 px-2 focus:border-indigo-500 focus:ring-indigo-500/20" />
                                    </th>
                                    <th class="px-2 py-2 text-[10px] text-slate-300 font-normal">—</th>
                                    <th class="px-2 py-2">
                                        <select name="jalur" onchange="this.form.submit()" class="w-full min-w-[66px] rounded-lg border-slate-200 text-[11px] font-semibold py-1 px-1.5 focus:border-indigo-500 focus:ring-indigo-500/20">
                                            <option value="">All</option>
                                            <option value="H" @selected($filters['jalur'] === 'H')>H</option>
                                            <option value="K" @selected($filters['jalur'] === 'K')>K</option>
                                            <option value="M" @selected($filters['jalur'] === 'M')>M</option>
                                        </select>
                                    </th>
                                    <th class="px-2 py-2">
                                        <input type="text" name="perusahaan" value="{{ $filters['perusahaan'] ?? '' }}" placeholder="Perusahaan / NPWP…"
                                               class="w-full min-w-[140px] rounded-lg border-slate-200 text-[11px] py-1 px-2 focus:border-indigo-500 focus:ring-indigo-500/20" />
                                    </th>
                                    <th class="px-2 py-2">
                                        <input type="text" name="kantor" value="{{ $filters['kantor'] ?? '' }}" placeholder="Kode / Kantor…"
                                               class="w-full min-w-[105px] rounded-lg border-slate-200 text-[11px] py-1 px-2 focus:border-indigo-500 focus:ring-indigo-500/20" />
                                    </th>
                                    <th class="px-2 py-2 text-right sticky right-0 bg-white">
                                        <button type="submit" class="px-2.5 py-1 bg-indigo-600 text-white rounded-lg text-[10px] font-extrabold hover:bg-indigo-700 transition cursor-pointer">
                                            Cari
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($documents as $idx => $doc)
                                    @php($rowNum = ($documents->currentPage() - 1) * $documents->perPage() + $idx + 1)
                                    @php($resp = $doc->responseSummary())
                                    @php($jalurBadge = match($doc->jalur) {
                                        'H' => ['letter' => 'H', 'cls' => 'bg-emerald-500 text-white border-emerald-600', 'title' => 'Jalur Hijau'],
                                        'K' => ['letter' => 'K', 'cls' => 'bg-amber-400 text-slate-900 border-amber-500', 'title' => 'Jalur Kuning'],
                                        'M' => ['letter' => 'M', 'cls' => 'bg-rose-600 text-white border-rose-700', 'title' => 'Jalur Merah'],
                                        default => null,
                                    })
                                    <tr class="hover:bg-slate-50/70 transition-colors duration-150">
                                        <td class="px-3 py-3.5 text-center text-xs font-semibold text-slate-400">{{ $rowNum }}</td>
                                        <td class="px-3 py-3.5 whitespace-nowrap">
                                            @if($doc->doc_type === 'BC30')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200/80">BC 3.0</span>
                                            @elseif($doc->doc_type === 'BC20')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200/80">BC 2.0</span>
                                            @elseif($doc->doc_type === 'BC24')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-sky-50 text-sky-700 border border-sky-200/80">BC 2.4</span>
                                            @elseif($doc->doc_type === 'TPB')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-purple-50 text-purple-700 border border-purple-200/80">TPB</span>
                                            @elseif($doc->doc_type === 'RUSH')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200/80">RUSH</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-slate-100 text-slate-700 border border-slate-200">{{ $doc->doc_type }}</span>
                                            @endif
                                            @if ($doc->isArchived())
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-500 border border-slate-200 uppercase">Arsip</span>
                                                </div>
                                            @endif
                                        </td>
                                        {{-- Nomor Pengajuan + Status Pill langsung di bawahnya --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            <div class="font-mono text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                                <a href="{{ route('documents.show', $doc) }}" class="hover:text-indigo-600 hover:underline">
                                                    {{ $doc->nomor_aju ?: '—' }}
                                                </a>
                                            </div>
                                            <div class="mt-1 flex items-center gap-1.5">
                                                <x-status-badge :status="$doc->status" />
                                                <span class="text-[10px] text-slate-400">{{ $doc->created_at->format('d-m-Y H:i') }}</span>
                                            </div>
                                        </td>
                                        {{-- Nomor / Tanggal Pendaftaran --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            @if ($doc->nomor_daftar)
                                                <div class="font-mono text-xs font-bold text-slate-800">{{ $doc->nomor_daftar }}</div>
                                                @if ($doc->tanggalDaftar())
                                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $doc->tanggalDaftar()->format('d-m-Y') }}</div>
                                                @endif
                                            @else
                                                <span class="text-[11px] text-slate-400 italic">Belum terdaftar</span>
                                            @endif
                                        </td>
                                        {{-- Nama Respon --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap">
                                            @if ($resp)
                                                @php($respClass = $resp['nama'] === 'SPPB' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (($resp['nama'] === 'PENOLAKAN' || str_contains($resp['nama'], 'NPP') || str_contains($resp['nama'], 'TOLAK')) ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-sky-50 text-sky-700 border-sky-200'))
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide border {{ $respClass }}">{{ $resp['nama'] }}</span>
                                                @if ($resp['no_surat'])
                                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ $resp['no_surat'] }}</div>
                                                @endif
                                            @else
                                                <span class="text-[11px] text-slate-400 italic">—</span>
                                            @endif
                                        </td>
                                        {{-- Tanggal Respon --}}
                                        <td class="px-4 py-3.5 whitespace-nowrap text-[11px] text-slate-600 font-mono">
                                            @if ($resp && $resp['tanggal'])
                                                {{ $resp['tanggal']->format('d-m-Y H:i:s') }}
                                            @else
                                                <span class="text-slate-300 font-sans">—</span>
                                            @endif
                                        </td>
                                        {{-- Jalur (single-letter square badge) --}}
                                        <td class="px-3 py-3.5 text-center whitespace-nowrap">
                                            @if ($jalurBadge)
                                                <span title="{{ $jalurBadge['title'] }}" class="inline-flex items-center justify-center w-6 h-6 rounded-md text-xs font-black border shadow-xs {{ $jalurBadge['cls'] }}">
                                                    {{ $jalurBadge['letter'] }}
                                                </span>
                                            @else
                                                <span class="text-slate-300 text-xs">—</span>
                                            @endif
                                        </td>
                                        {{-- Nama Perusahaan (2-baris: NPWP16/NITKU di atas, Nama PT di bawah) --}}
                                        <td class="px-4 py-3.5">
                                            @if ($doc->partyNpwp() || $doc->partyNitku())
                                                <div class="text-[10px] text-slate-400 font-mono" x-show="idMode==='npwp'">{{ $doc->partyNpwp() ?? '—' }}</div>
                                                <div class="text-[10px] text-slate-400 font-mono" x-show="idMode==='nitku'" x-cloak>{{ $doc->partyNitku() ?? '—' }}</div>
                                            @endif
                                            <div class="font-semibold text-xs text-slate-800 leading-snug">{{ $doc->partyName() ?? '—' }}</div>
                                        </td>
                                        {{-- Kantor Pabean --}}
                                        <td class="px-4 py-3.5 text-xs text-slate-600 whitespace-nowrap">
                                            {{ $doc->kantorPabeanLabel() ?? '—' }}
                                        </td>
                                        {{-- Sticky Right Aksi Dropdown --}}
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap text-xs font-semibold sticky right-0 bg-white/95 backdrop-blur-xs">
                                            <div class="inline-flex items-center gap-2">
                                                <button type="button"
                                                        @click="$dispatch('quick-view', @js($doc->quickViewData()))"
                                                        class="text-indigo-600 hover:text-indigo-900 font-bold hover:underline cursor-pointer">
                                                    Detail
                                                </button>
                                                <div class="relative inline-block text-left" x-data="{ openMenu: false }" @click.away="openMenu = false">
                                                    <button type="button" @click="openMenu = !openMenu"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-[11px] font-bold transition cursor-pointer">
                                                        Aksi
                                                        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                                    </button>
                                                    <div x-show="openMenu" x-cloak x-transition
                                                         class="absolute right-0 mt-1.5 w-48 bg-white border border-slate-200 rounded-xl shadow-xl z-30 py-1 text-left">
                                                        <a href="{{ route('documents.show', $doc) }}" class="block px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                            Buka Detail & Tracking
                                                        </a>
                                                        <a href="{{ route('documents.preview-cetak', $doc) }}" target="_blank" class="block px-3.5 py-2 text-xs font-semibold text-indigo-600 hover:bg-indigo-50/60">
                                                            Preview / Cetak PDF
                                                        </a>
                                                        @if ($doc->isEditable())
                                                            <a href="{{ route('documents.edit', $doc) }}" class="block px-3.5 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50/60">
                                                                Ubah Data
                                                            </a>
                                                        @endif
                                                        @unless ($doc->isArchived())
                                                            <button type="button"
                                                                    onclick="if(confirm('Duplikasi dokumen ini sebagai draft baru?')){ document.getElementById('dup-form-{{ $doc->id }}').submit(); }"
                                                                    class="w-full text-left px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                                                                Salin Data (Duplikasi)
                                                            </button>
                                                        @endunless
                                                        @if ($doc->canBeDeleted())
                                                            <div class="border-t border-slate-100 my-1"></div>
                                                            <button type="button"
                                                                    onclick="if(confirm('Hapus dokumen ini secara permanen?')){ document.getElementById('del-form-{{ $doc->id }}').submit(); }"
                                                                    class="w-full text-left px-3.5 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 cursor-pointer">
                                                                Hapus Data
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-6 py-10 text-center text-slate-400">
                                            Tidak ada dokumen yang cocok dengan saringan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>

                {{-- Hidden POST/DELETE forms outside GET filter form --}}
                @foreach ($documents as $doc)
                    @unless ($doc->isArchived())
                        <form id="dup-form-{{ $doc->id }}" method="POST" action="{{ route('documents.duplicate', $doc) }}" class="hidden">@csrf</form>
                    @endunless
                    @if ($doc->canBeDeleted())
                        <form id="del-form-{{ $doc->id }}" method="POST" action="{{ route('documents.destroy', $doc) }}" class="hidden">@csrf @method('DELETE')</form>
                    @endif
                @endforeach
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="text-xs text-slate-500 font-semibold">
                    Total <span class="font-extrabold text-slate-800">{{ $documents->total() }}</span> dokumen
                </div>
                <div>{{ $documents->links() }}</div>
            </div>

            <x-doc-quick-view-modal />
        </div>
    </div>
</x-app-layout>
