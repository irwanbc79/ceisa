<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col">
            <p class="text-[10px] font-mono uppercase tracking-[0.3em] text-ink-400">Lookup · Live DJBC</p>
            <h1 class="font-display text-2xl sm:text-3xl font-semibold text-ink-900 tracking-tightest leading-none mt-1">Cek Status &amp; Riwayat CEISA</h1>
        </div>
    </x-slot>

    <x-flash />

    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Info Hero --}}
        <div class="ink-hero rounded-2xl p-6 lg:p-8 overflow-hidden">
            <div class="relative flex items-start gap-5">
                <span class="h-12 w-12 rounded-xl bg-white/10 ring-1 ring-gold-300/30 text-gold-300 flex items-center justify-center shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607.197a7.5 7.5 0 0 1-10.607 0"/></svg>
                </span>
                <div>
                    <p class="eyebrow text-gold-300">Real-time query</p>
                    <h3 class="font-display text-xl lg:text-2xl text-cream font-semibold mt-2 leading-tight">
                        Cek status dokumen di Bea Cukai berdasarkan <em class="text-gold-400 not-italic font-medium">Nomor Aju</em>
                    </h3>
                    <p class="mt-2 text-cream/65 text-sm leading-relaxed">
                        Masukkan nomor aju (contoh <span class="font-mono text-gold-300">000020MOT83720260301000015</span>).
                        Data diambil langsung dari API H2H portal Bea Cukai.
                    </p>
                </div>
            </div>
        </div>

        {{-- Search Form --}}
        <form method="POST" action="{{ route('documents.lookup.search') }}" class="card p-6">
            @csrf
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <label for="nomor_aju" class="label">Nomor Aju / Nomor Pengajuan</label>
                    <input type="text" id="nomor_aju" name="nomor_aju"
                           value="{{ $nomorAju ?? old('nomor_aju') }}"
                           class="field-mono"
                           placeholder="000020MOT83720260301000015" required />
                    @error('nomor_aju')
                        <p class="text-xs text-crimson-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn-primary !py-3 !px-6">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607.197a7.5 7.5 0 0 1-10.607 0"/></svg>
                        Cek Status
                    </button>
                </div>
            </div>
        </form>

        {{-- Error --}}
        @isset($error)
            <div class="rounded-2xl bg-white border-l-[3px] border-crimson-500 shadow-soft p-5 flex items-start gap-4">
                <span class="h-10 w-10 rounded-xl bg-crimson-50 text-crimson-600 flex items-center justify-center shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                </span>
                <div class="flex-1">
                    <p class="font-bold text-ink-900">Tidak dapat mengambil data dari CEISA</p>
                    <p class="text-sm text-ink-600 mt-1">{{ $error }}</p>
                    <p class="text-xs text-ink-400 mt-2">Kemungkinan: nomor aju tidak ditemukan di sistem CEISA, token expired, atau endpoint status belum diaktifkan untuk akun ini.</p>
                    <a href="{{ route('documents.archive.create', ['nomor_aju' => $nomorAju]) }}" class="btn-danger mt-4 !py-2 !px-4 !text-xs">Rekam Manual Sebagai Arsip Lokal →</a>
                </div>
            </div>
        @endisset

        {{-- Local Match --}}
        @isset($localDoc)
            <div class="rounded-2xl bg-white border-l-[3px] border-sea-500 shadow-soft p-5 flex items-start gap-4">
                <span class="h-10 w-10 rounded-xl bg-sea-50 text-sea-700 flex items-center justify-center shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </span>
                <div class="flex-1">
                    <p class="font-bold text-ink-900">Dokumen sudah ada di database lokal M2B</p>
                    <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div><span class="text-ink-400 uppercase tracking-wider text-[10px] font-bold block">Jenis</span><span class="font-bold text-ink-900">{{ $localDoc->doc_type }}</span></div>
                        <div><span class="text-ink-400 uppercase tracking-wider text-[10px] font-bold block mb-0.5">Status</span><x-status-badge :status="$localDoc->status" /></div>
                        <div><span class="text-ink-400 uppercase tracking-wider text-[10px] font-bold block">Dibuat</span><span class="font-semibold text-ink-900 font-mono">{{ $localDoc->created_at->format('d/m/Y H:i') }}</span></div>
                        <div class="flex items-end"><a href="{{ route('documents.show', $localDoc) }}" class="font-bold text-ink-900 link-gold text-xs">Lihat Detail →</a></div>
                    </div>
                </div>
            </div>
        @endisset

        {{-- CEISA Response Result --}}
        @isset($result)
            <div class="card overflow-hidden">
                <div class="ink-hero px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3 relative">
                        <span class="h-8 w-8 rounded-xl bg-white/10 ring-1 ring-gold-300/30 text-gold-300 flex items-center justify-center">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        </span>
                        <div>
                            <p class="text-cream font-bold text-sm">Hasil dari Portal CEISA DJBC</p>
                            <p class="text-cream/50 text-[11px] font-mono">{{ $nomorAju ?? '—' }}</p>
                        </div>
                    </div>
                    @php
                        $ceisaStatus = data_get($result, 'status', data_get($result, 'data.status', 'UNKNOWN'));
                        $isAccepted = str_contains(strtoupper($ceisaStatus), 'TERIMA') || str_contains(strtoupper($ceisaStatus), 'SPPB');
                        $isProcess  = str_contains(strtoupper($ceisaStatus), 'PROSES') || str_contains(strtoupper($ceisaStatus), 'SUBMIT');
                        $isReject   = str_contains(strtoupper($ceisaStatus), 'TOLAK') || str_contains(strtoupper($ceisaStatus), 'ERROR');
                        $pillCls = $isAccepted ? 'pill-sea' : ($isProcess ? 'pill-gold' : ($isReject ? 'pill-crimson' : 'pill-ink'));
                        $dotCls  = $isAccepted ? 'bg-sea-400' : ($isProcess ? 'bg-gold-400' : ($isReject ? 'bg-crimson-400' : 'bg-cream'));
                    @endphp
                    <span class="{{ $pillCls }} relative"><span class="dot {{ $dotCls }}"></span>{{ $ceisaStatus }}</span>
                </div>

                <div class="p-6">
                    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-3 mb-6">
                        @foreach ([
                            ['Nomor Aju', data_get($result, 'nomor_aju', data_get($result, 'data.nomor_aju', $nomorAju))],
                            ['Nomor Daftar', data_get($result, 'nomor_daftar', data_get($result, 'data.nomor_daftar', '—'))],
                            ['Kantor Pabean', data_get($result, 'kantor', data_get($result, 'data.kantor', '—'))],
                            ['Jenis Dokumen', data_get($result, 'jenis_doc', data_get($result, 'data.jenis_doc', '—'))],
                            ['Tanggal Daftar', data_get($result, 'tanggal_daftar', data_get($result, 'data.tanggal_daftar', '—'))],
                            ['Nilai Pabean', data_get($result, 'nilai_pabean', data_get($result, 'data.nilai_pabean', '—'))],
                        ] as [$label, $value])
                            <div class="bg-cream-100 rounded-xl p-3 border border-cream-300">
                                <dt class="text-[10px] font-bold text-ink-400 uppercase tracking-[0.18em]">{{ $label }}</dt>
                                <dd class="font-bold text-ink-900 text-sm mt-1 font-mono">{{ $value ?? '—' }}</dd>
                            </div>
                        @endforeach
                    </div>

                    <div x-data="{ open: false }" class="border border-cream-300 rounded-xl overflow-hidden">
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 bg-cream-100 hover:bg-cream-200 transition-colors text-xs font-bold text-ink-700 uppercase tracking-widest">
                            <span>Raw Response JSON · Developer</span>
                            <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition class="bg-ink-950 p-4">
                            <pre class="text-[11px] font-mono text-gold-300 overflow-x-auto leading-relaxed">{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>

                    @if (!$localDoc)
                        <div class="mt-6 border-t border-cream-200 pt-6">
                            <div class="bg-sea-50/50 border border-sea-100 rounded-xl p-4">
                                <h4 class="eyebrow text-sea-700 mb-3">Impor ke arsip lokal</h4>
                                <p class="text-xs text-ink-500 mb-4">Simpan dokumen historis dari portal CEISA ini ke database lokal M2B agar tampil di dashboard.</p>
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
                                            <x-input-label for="nama_perusahaan" value="Nama Perusahaan" />
                                            <input type="text" id="nama_perusahaan" name="nama_perusahaan" value="PT. MORA MULTI BERKAH" class="field" required />
                                        </div>
                                        <div>
                                            <x-input-label for="uraian" value="Uraian Singkat Barang" />
                                            <input type="text" id="uraian" name="uraian" placeholder="mis. Spareparts, Textile" class="field" />
                                        </div>
                                    </div>
                                    <div class="flex justify-end pt-2">
                                        <button type="submit" class="btn-primary !py-2 !px-4 !text-xs">Simpan Sebagai Arsip Lokal</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endisset

        {{-- Examples --}}
        <div class="card p-5">
            <p class="eyebrow text-gold-700 mb-3">Nomor aju yang diketahui</p>
            <div class="space-y-2">
                @foreach ([
                    ['000020MOT83720260301000015', 'PIB BC 2.0 — PT. Pomeurah Acindo, KPPBC Belawan, 04/03/2026', 'sea'],
                    ['000419/KBC.0207/2026',        'SPPB — Tanjung Priok, 04/03/2026 (No. SPPB)',               'gold'],
                ] as [$aju, $desc, $tone])
                    @php
                        $bg = ['sea'=>'bg-sea-50 border-sea-200','gold'=>'bg-gold-50 border-gold-200'][$tone];
                        $codeColor = ['sea'=>'text-sea-700','gold'=>'text-gold-700'][$tone];
                        $btn = ['sea'=>'btn-primary !bg-sea-600 hover:!bg-sea-700','gold'=>'btn-gold'][$tone];
                    @endphp
                    <div class="flex items-center justify-between p-3 rounded-xl border {{ $bg }} gap-3">
                        <div>
                            <code class="font-mono font-bold text-sm {{ $codeColor }}">{{ $aju }}</code>
                            <p class="text-xs text-ink-500 mt-0.5">{{ $desc }}</p>
                        </div>
                        <form method="POST" action="{{ route('documents.lookup.search') }}">
                            @csrf
                            <input type="hidden" name="nomor_aju" value="{{ $aju }}" />
                            <button type="submit" class="{{ $btn }} !py-1.5 !px-3 !text-[11px]">Cek →</button>
                        </form>
                    </div>
                @endforeach
            </div>
            <p class="text-[11px] text-ink-400 mt-3">* Pastikan nomor aju yang dicek adalah dokumen yang diajukan menggunakan App ID yang sama dengan kredensial M2B.</p>
        </div>
    </div>
</x-app-layout>
