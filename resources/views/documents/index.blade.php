<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col">
            <p class="text-[10px] font-mono uppercase tracking-[0.3em] text-ink-400">Workspace · Documents</p>
            <h1 class="font-display text-2xl sm:text-3xl font-semibold text-ink-900 tracking-tightest leading-none mt-1">Daftar Dokumen H2H</h1>
        </div>
    </x-slot>

    <x-flash />

    {{-- ── Filter rail (top) ───────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-[11px] text-ink-400 font-mono">
            <span class="font-bold text-ink-700">{{ number_format($documents->total()) }}</span> dokumen ditemukan
        </p>
        <div class="flex items-center gap-2">
            <a href="{{ route('documents.export', request()->query()) }}" class="btn-ghost">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                Export CSV
            </a>
            <a href="{{ route('documents.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Buat Dokumen
            </a>
        </div>
    </div>

    {{-- ── Rekap KPI (filter aware) ───────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @php
            $kpi = [
                ['Total (terfilter)', $rekap['total'],    'ink',     'bg-ink-900 text-cream'],
                ['Diterima',          $rekap['accepted'], 'sea',     'bg-sea-500 text-white'],
                ['Ditolak / Error',   $rekap['rejected'], 'crimson', 'bg-crimson-500 text-white'],
                ['Jalur Merah',       $rekap['merah'],    'crimson', 'bg-crimson-700 text-white'],
            ];
        @endphp
        @foreach ($kpi as $i => [$label, $value, $tone, $bg])
            <div class="stat shimmer-on-hover">
                <div class="flex items-center gap-2 mb-3">
                    <span class="h-2 w-2 rounded-full {{ ['ink'=>'bg-ink-900','sea'=>'bg-sea-500','crimson'=>'bg-crimson-500'][$tone] }}"></span>
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-ink-400">{{ $label }}</p>
                </div>
                <p class="num-display text-4xl text-ink-900">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    {{-- ── Filter Bar ───────────────────────────── --}}
    <form method="GET" action="{{ route('documents.index') }}" class="card p-5 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
            <div class="lg:col-span-2">
                <label class="label">Cari No. Aju / Pendaftaran</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-ink-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607.197a7.5 7.5 0 0 1-10.607 0"/></svg>
                    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Ketik nomor…" class="field pl-9 font-mono" />
                </div>
            </div>
            <div>
                <label class="label">Jenis</label>
                <select name="doc_type" class="field">
                    <option value="">Semua</option>
                    @foreach ($docTypes as $code => $label)
                        <option value="{{ $code }}" @selected($filters['doc_type'] === $code)>{{ $code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Status</label>
                <select name="status" class="field">
                    <option value="">Semua</option>
                    @foreach (['draft' => 'Draft', 'submitting' => 'Mengirim', 'submitted' => 'Terkirim', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', 'error' => 'Error'] as $val => $label)
                        <option value="{{ $val }}" @selected($filters['status'] === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Jalur</label>
                <select name="jalur" class="field">
                    <option value="">Semua</option>
                    <option value="H" @selected($filters['jalur'] === 'H')>Hijau</option>
                    <option value="K" @selected($filters['jalur'] === 'K')>Kuning</option>
                    <option value="M" @selected($filters['jalur'] === 'M')>Merah</option>
                </select>
            </div>
            <div>
                <label class="label">Sumber</label>
                <select name="source" class="field">
                    <option value="">Semua</option>
                    <option value="h2h" @selected($filters['source'] === 'h2h')>H2H</option>
                    <option value="arsip" @selected($filters['source'] === 'arsip')>Arsip</option>
                </select>
            </div>
            <div>
                <label class="label">Dari Tanggal</label>
                <input type="date" name="from" value="{{ $filters['from'] }}" class="field" />
            </div>
            <div>
                <label class="label">Sampai Tanggal</label>
                <input type="date" name="to" value="{{ $filters['to'] }}" class="field" />
            </div>
            <div class="flex items-end gap-2 lg:col-span-2">
                <button type="submit" class="btn-primary">Terapkan Filter</button>
                <a href="{{ route('documents.index') }}" class="btn-ghost">Reset</a>
            </div>
        </div>
    </form>

    {{-- ── Table ───────────────────────────── --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-cream-100 border-b border-cream-300">
                    <tr class="text-left text-[10px] font-bold uppercase tracking-[0.18em] text-ink-400">
                        <th class="px-5 py-4">No. Aju</th>
                        <th class="px-5 py-4">Jenis</th>
                        <th class="px-5 py-4">Pihak / Entitas</th>
                        <th class="px-5 py-4">Status &amp; Jalur</th>
                        <th class="px-5 py-4">Dibuat</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cream-200">
                    @forelse ($documents as $doc)
                        <tr class="group hover:bg-cream-100/60 transition-colors">
                            <td class="px-5 py-4 font-mono text-ink-700">
                                <span class="font-semibold">{{ $doc->nomor_aju ?? '—' }}</span>
                                @if ($doc->isArchived())
                                    <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-cream-200 text-ink-500 uppercase tracking-wider">Arsip</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-semibold text-ink-700">{{ $doc->doc_type }}</td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-ink-900 leading-snug">{{ $doc->partyName() ?? '—' }}</p>
                                @if ($doc->partyNpwp())
                                    <p class="text-[11px] text-ink-400 font-mono mt-0.5">NPWP {{ $doc->partyNpwp() }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <x-status-badge :status="$doc->status" />
                                    <x-jalur-badge :jalur="$doc->jalur" />
                                </div>
                            </td>
                            <td class="px-5 py-4 text-ink-400 text-[12px] font-mono whitespace-nowrap">{{ $doc->created_at->format('d/m/y · H:i') }}</td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('documents.show', $doc) }}" class="text-[12px] font-bold text-ink-900 link-gold">Detail</a>
                                @unless ($doc->isArchived())
                                    <form method="POST" action="{{ route('documents.duplicate', $doc) }}" class="inline ml-3">
                                        @csrf
                                        <button type="submit" class="text-[12px] font-bold text-ink-400 hover:text-ink-900"
                                                onclick="return confirm('Duplikasi dokumen ini sebagai draft baru?')">Duplikasi</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-ink-400">
                                    <span class="h-14 w-14 rounded-2xl bg-cream-200 flex items-center justify-center">
                                        <svg class="h-6 w-6 text-ink-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3l4.5 4.5M3 7.5h18M3 7.5v9A2.25 2.25 0 0 0 5.25 18.75H8.25"/></svg>
                                    </span>
                                    <p class="font-semibold text-ink-700">Tidak ada dokumen yang cocok</p>
                                    <p class="text-[11px]">Coba reset filter atau ubah rentang tanggal.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $documents->links() }}</div>
</x-app-layout>
