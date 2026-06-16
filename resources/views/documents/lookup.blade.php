<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 tracking-tight">
                    Cek Status & Riwayat Dokumen CEISA
                </h2>
                <p class="text-sm text-slate-500 mt-1">Query dokumen historis M2B di portal Bea Cukai berdasarkan Nomor Aju</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                    &larr; Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            {{-- Info Banner --}}
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-2xl p-5 shadow-lg shadow-indigo-100 flex items-start gap-4">
                <div class="shrink-0 h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607.197a7.5 7.5 0 0 1-10.607 0" />
                    </svg>
                </div>
                <div>
                    <p class="text-white font-bold text-sm">Cek Status Dokumen CEISA Secara Real-time</p>
                    <p class="text-indigo-100 text-xs mt-0.5">Masukkan Nomor Aju (contoh: <span class="font-mono font-semibold">000020MOT83720260301000015</span>) untuk melihat status dokumen M2B di portal Bea Cukai (DJBC). Data diambil langsung dari API H2H CEISA.</p>
                </div>
            </div>

            {{-- Search Form --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <form method="POST" action="{{ route('documents.lookup.search') }}" class="flex flex-col sm:flex-row gap-3">
                    @csrf
                    <div class="flex-1">
                        <label for="nomor_aju" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Nomor Aju / Nomor Pengajuan</label>
                        <input type="text" id="nomor_aju" name="nomor_aju"
                               value="{{ $nomorAju ?? old('nomor_aju') }}"
                               class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm font-mono"
                               placeholder="Contoh: 000020MOT83720260301000015"
                               required />
                        @error('nomor_aju')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-indigo-100 h-[42px]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607.197a7.5 7.5 0 0 1-10.607 0" />
                            </svg>
                            Cek Status CEISA
                        </button>
                    </div>
                </form>
            </div>

            {{-- Error Result --}}
            @isset($error)
                <div class="bg-rose-50 border border-rose-200 rounded-2xl p-5 flex items-start gap-3">
                    <svg class="h-5 w-5 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <div>
                        <p class="font-bold text-rose-800 text-sm">Tidak dapat mengambil data dari CEISA</p>
                        <p class="text-rose-700 text-xs mt-1">{{ $error }}</p>
                        <p class="text-rose-500 text-xs mt-2">Kemungkinan: nomor aju tidak ditemukan di sistem CEISA, token expired, atau endpoint status belum diaktifkan untuk akun ini.</p>
                        <div class="mt-3">
                            <a href="{{ route('documents.archive.create', ['nomor_aju' => $nomorAju]) }}" 
                               class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-sm transition-colors">
                                Rekam Manual Sebagai Arsip Lokal &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            @endisset

            {{-- Local Document Match --}}
            @isset($localDoc)
                <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 flex items-start gap-3">
                    <svg class="h-5 w-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <div class="flex-1">
                        <p class="font-bold text-emerald-800 text-sm">Dokumen ini sudah ada di database lokal M2B</p>
                        <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div><span class="text-emerald-600">Jenis:</span> <span class="font-bold text-emerald-900">{{ $localDoc->doc_type }}</span></div>
                            <div><span class="text-emerald-600">Status Lokal:</span> <x-status-badge :status="$localDoc->status" /></div>
                            <div><span class="text-emerald-600">Dibuat:</span> <span class="font-semibold text-emerald-900">{{ $localDoc->created_at->format('d/m/Y H:i') }}</span></div>
                            <div>
                                <a href="{{ route('documents.show', $localDoc) }}" class="text-indigo-600 hover:underline font-bold text-xs">Lihat Detail →</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endisset

            {{-- CEISA Response Result --}}
            @isset($result)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    {{-- Result Header --}}
                    <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-xl bg-white/10 flex items-center justify-center">
                                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-white font-bold text-sm">Hasil dari Portal CEISA DJBC</p>
                                <p class="text-slate-400 text-xs font-mono">{{ $nomorAju ?? '—' }}</p>
                            </div>
                        </div>
                        @php
                            $ceisaStatus = data_get($result, 'status', data_get($result, 'data.status', 'UNKNOWN'));
                        @endphp
                        <span @class([
                            'px-3 py-1 text-xs font-bold rounded-full border',
                            'bg-emerald-100 text-emerald-800 border-emerald-200' => str_contains(strtoupper($ceisaStatus), 'TERIMA') || str_contains(strtoupper($ceisaStatus), 'SPPB'),
                            'bg-blue-100 text-blue-800 border-blue-200' => str_contains(strtoupper($ceisaStatus), 'PROSES') || str_contains(strtoupper($ceisaStatus), 'SUBMIT'),
                            'bg-rose-100 text-rose-800 border-rose-200' => str_contains(strtoupper($ceisaStatus), 'TOLAK') || str_contains(strtoupper($ceisaStatus), 'ERROR'),
                            'bg-slate-100 text-slate-700 border-slate-200' => true,
                        ])>{{ $ceisaStatus }}</span>
                    </div>

                    {{-- Structured Result Display --}}
                    <div class="p-6">
                        {{-- Key Fields Grid --}}
                        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                            @foreach ([
                                ['Nomor Aju', data_get($result, 'nomor_aju', data_get($result, 'data.nomor_aju', $nomorAju))],
                                ['Nomor Daftar', data_get($result, 'nomor_daftar', data_get($result, 'data.nomor_daftar', '—'))],
                                ['Kantor Pabean', data_get($result, 'kantor', data_get($result, 'data.kantor', '—'))],
                                ['Jenis Dokumen', data_get($result, 'jenis_doc', data_get($result, 'data.jenis_doc', '—'))],
                                ['Tanggal Daftar', data_get($result, 'tanggal_daftar', data_get($result, 'data.tanggal_daftar', '—'))],
                                ['Nilai Pabean', data_get($result, 'nilai_pabean', data_get($result, 'data.nilai_pabean', '—'))],
                            ] as [$label, $value])
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                    <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $label }}</dt>
                                    <dd class="font-bold text-slate-800 text-sm mt-1 font-mono">{{ $value ?? '—' }}</dd>
                                </div>
                            @endforeach
                        </div>

                        {{-- Raw JSON Response (collapsible) --}}
                        <div x-data="{ open: false }" class="border border-slate-200 rounded-xl overflow-hidden">
                            <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition-colors text-sm font-bold text-slate-600">
                                <span>Raw Response JSON (Developer)</span>
                                <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="bg-slate-900 p-4">
                                <pre class="text-[11px] font-mono text-emerald-400 overflow-x-auto">{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>

                        @if (!$localDoc)
                            <div class="mt-6 border-t border-slate-100 pt-6">
                                <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-4">
                                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        Impor ke Riwayat Lokal (Arsip)
                                    </h4>
                                    <p class="text-xs text-slate-500 mb-4">Simpan dokumen historis dari portal CEISA ini ke database lokal M2B agar tampil di dashboard.</p>
                                    
                                    <form method="POST" action="{{ route('documents.import') }}" class="space-y-4">
                                        @csrf
                                        <input type="hidden" name="nomor_aju" value="{{ data_get($result, 'nomor_aju', data_get($result, 'data.nomor_aju', $nomorAju)) }}" />
                                        <input type="hidden" name="nomor_daftar" value="{{ data_get($result, 'nomor_daftar', data_get($result, 'data.nomor_daftar')) }}" />
                                        <input type="hidden" name="jenis_doc" value="{{ data_get($result, 'jenis_doc', data_get($result, 'data.jenis_doc')) }}" />
                                        <input type="hidden" name="status" value="{{ $ceisaStatus }}" />
                                        <input type="hidden" name="kantor" value="{{ data_get($result, 'kantor', data_get($result, 'data.kantor')) }}" />
                                        <input type="hidden" name="tanggal_daftar" value="{{ data_get($result, 'tanggal_daftar', data_get($result, 'data.tanggal_daftar')) }}" />
                                        <input type="hidden" name="nilai_pabean" value="{{ data_get($result, 'nilai_pabean', data_get($result, 'data.nilai_pabean')) }}" />
                                        
                                        <div class="grid sm:grid-cols-2 gap-4">
                                            <div>
                                                <x-input-label for="nama_perusahaan" value="Nama Perusahaan / Importir / Eksportir" />
                                                <input type="text" id="nama_perusahaan" name="nama_perusahaan" 
                                                       value="PT. MORA MULTI BERKAH" 
                                                       class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" 
                                                       required />
                                            </div>
                                            <div>
                                                <x-input-label for="uraian" value="Uraian Singkat Barang (Opsional)" />
                                                <input type="text" id="uraian" name="uraian" 
                                                       placeholder="mis. Spareparts Mesin, Textile" 
                                                       class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                                            </div>
                                        </div>
                                        
                                        <div class="flex justify-end pt-2">
                                            <button type="submit" 
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-100 transition-colors">
                                                Simpan Sebagai Arsip Lokal
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endisset

            {{-- Example Nomor Aju from Images --}}
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                <h3 class="text-sm font-bold text-slate-700 mb-3">Nomor Aju Dokumen M2B yang Diketahui (dari portal.beacukai.go.id)</h3>
                <div class="space-y-2">
                    @foreach ([
                        ['000020MOT83720260301000015', 'PIB BC 2.0 — PT. Pomeurah Acindo, KPPBC Belawan, 04/03/2026', 'emerald'],
                        ['000419/KBC.0207/2026', 'SPPB — Tanjung Priok, 04/03/2026 (No. SPPB)', 'blue'],
                    ] as [$aju, $desc, $color])
                        <div @class([
                            'flex items-center justify-between p-3 rounded-xl border text-xs gap-3',
                            'bg-emerald-50 border-emerald-200' => $color === 'emerald',
                            'bg-blue-50 border-blue-200' => $color === 'blue',
                        ])>
                            <div>
                                <code @class([
                                    'font-mono font-bold text-sm',
                                    'text-emerald-700' => $color === 'emerald',
                                    'text-blue-700' => $color === 'blue',
                                ])>{{ $aju }}</code>
                                <p @class([
                                    'mt-0.5',
                                    'text-emerald-600' => $color === 'emerald',
                                    'text-blue-600' => $color === 'blue',
                                ])>{{ $desc }}</p>
                            </div>
                            <form method="POST" action="{{ route('documents.lookup.search') }}">
                                @csrf
                                <input type="hidden" name="nomor_aju" value="{{ $aju }}" />
                                <button type="submit" @class([
                                    'px-3 py-1.5 text-xs font-bold rounded-lg transition-colors shrink-0',
                                    'bg-emerald-600 hover:bg-emerald-700 text-white' => $color === 'emerald',
                                    'bg-blue-600 hover:bg-blue-700 text-white' => $color === 'blue',
                                ])>Cek Status →</button>
                            </form>
                        </div>
                    @endforeach
                </div>
                <p class="text-[11px] text-slate-400 mt-3">* Pastikan nomor aju yang dicek adalah dokumen yang diajukan menggunakan App ID yang sama dengan kredensial yang terdaftar di sistem M2B.</p>
            </div>

        </div>
    </div>
</x-app-layout>
