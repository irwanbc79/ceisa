<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col">
            <p class="text-[10px] font-mono uppercase tracking-[0.3em] text-ink-400">Dashboard · CEISA H2H</p>
            <h1 class="font-display text-2xl sm:text-3xl font-semibold text-ink-900 tracking-tightest leading-none mt-1">
                Selamat datang, <em class="text-gold-700 not-italic">{{ \Illuminate\Support\Str::words(Auth::user()->name, 1, '') }}</em>.
            </h1>
        </div>
    </x-slot>

    <x-flash />

    @unless ($hasCredential)
        <div class="mb-6 rounded-2xl bg-white border border-gold-200 shadow-soft p-5 flex flex-col sm:flex-row sm:items-center gap-4">
            <span class="h-12 w-12 rounded-xl bg-gold-100 text-gold-700 flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            </span>
            <div class="flex-1">
                <p class="font-bold text-ink-900">Kredensial CEISA belum diatur</p>
                <p class="text-sm text-ink-500 mt-0.5">Atur Username, Password, dan Beacukai API Key sebelum membuat dokumen H2H.</p>
            </div>
            <a href="{{ route('settings.ceisa.edit') }}" class="btn-primary">Atur Sekarang</a>
        </div>
    @endunless

    {{-- ── Hero KPI Strip ─────────────────────────────────── --}}
    @php
        $total = max($stats['total'], 1);
        $pctAccepted = round(($stats['accepted'] / $total) * 100);
        $pctRejected = round(($stats['rejected'] / $total) * 100);
        $pctSubmitted = round(($stats['submitted'] / $total) * 100);
        // Ring chart circumference
        $circ = 2 * pi() * 56; // r=56
    @endphp

    <section class="grid lg:grid-cols-3 gap-5 mb-8">
        {{-- Big editorial KPI --}}
        <div class="lg:col-span-2 stat-ink p-8 lg:p-10 ink-hero">
            <div class="relative grid sm:grid-cols-2 gap-8 items-center">
                <div>
                    <p class="eyebrow text-gold-300">Total dokumen anda</p>
                    <p class="num-display text-7xl lg:text-8xl text-cream mt-3">{{ number_format($stats['total']) }}</p>
                    <p class="text-cream/60 mt-3 text-sm leading-relaxed max-w-xs">
                        sudah terkurasi di workspace M2B · siap dikirim, dipantau, atau diaudit.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-2">
                        <a href="{{ route('documents.create') }}" class="btn-gold !py-2 !px-4">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Buat Dokumen
                        </a>
                        <a href="{{ route('documents.lookup') }}" class="btn-ghost-dark !py-2 !px-4">Cek Status</a>
                    </div>
                </div>

                {{-- Donut Ring --}}
                <div class="relative flex items-center justify-center">
                    <svg viewBox="0 0 140 140" class="w-56 h-56 -rotate-90">
                        <circle cx="70" cy="70" r="56" stroke="rgba(255,255,255,.06)" stroke-width="14" fill="none"/>
                        @if ($stats['accepted'] > 0)
                            <circle cx="70" cy="70" r="56" stroke="#0E867E" stroke-width="14" fill="none"
                                stroke-dasharray="{{ ($stats['accepted']/$total) * $circ }} {{ $circ }}"
                                stroke-linecap="butt" />
                        @endif
                        @if ($stats['submitted'] > 0)
                            <circle cx="70" cy="70" r="56" stroke="#C9A55C" stroke-width="14" fill="none"
                                stroke-dasharray="{{ ($stats['submitted']/$total) * $circ }} {{ $circ }}"
                                stroke-dashoffset="-{{ ($stats['accepted']/$total) * $circ }}"
                                stroke-linecap="butt" />
                        @endif
                        @if ($stats['rejected'] > 0)
                            <circle cx="70" cy="70" r="56" stroke="#B73239" stroke-width="14" fill="none"
                                stroke-dasharray="{{ ($stats['rejected']/$total) * $circ }} {{ $circ }}"
                                stroke-dashoffset="-{{ (($stats['accepted']+$stats['submitted'])/$total) * $circ }}"
                                stroke-linecap="butt" />
                        @endif
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <p class="num-display text-4xl text-cream">{{ $pctAccepted }}<span class="text-gold-400 text-2xl">%</span></p>
                        <p class="text-[10px] font-mono uppercase tracking-[0.25em] text-cream/50 mt-1">Acceptance</p>
                    </div>
                </div>
            </div>

            {{-- Mini legend --}}
            <div class="mt-8 pt-6 border-t border-white/10 grid grid-cols-3 gap-4 text-xs">
                <div class="flex items-center gap-2">
                    <span class="dot bg-sea-500"></span>
                    <span class="text-cream/50 uppercase tracking-wider font-bold text-[10px]">Diterima</span>
                    <span class="ml-auto font-mono text-cream">{{ $stats['accepted'] }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="dot bg-gold-500"></span>
                    <span class="text-cream/50 uppercase tracking-wider font-bold text-[10px]">Terkirim</span>
                    <span class="ml-auto font-mono text-cream">{{ $stats['submitted'] }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="dot bg-crimson-500"></span>
                    <span class="text-cream/50 uppercase tracking-wider font-bold text-[10px]">Ditolak/Error</span>
                    <span class="ml-auto font-mono text-cream">{{ $stats['rejected'] }}</span>
                </div>
            </div>
        </div>

        {{-- Side stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-5">
            <div class="stat shimmer-on-hover">
                <p class="eyebrow text-sea-700">Terkirim ke DJBC</p>
                <p class="num-display text-5xl text-ink-900 mt-3">{{ number_format($stats['submitted']) }}</p>
                <div class="mt-4 flex items-center justify-between">
                    <span class="pill-gold"><span class="dot bg-gold-500"></span>Menunggu Respon</span>
                    <span class="text-xs font-mono text-ink-400">{{ $pctSubmitted }}%</span>
                </div>
                <div class="mt-3 h-1 bg-cream-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-gold-400 to-gold-600" style="width: {{ $pctSubmitted }}%"></div>
                </div>
            </div>
            <div class="stat shimmer-on-hover">
                <p class="eyebrow text-crimson-700">Perlu perhatian</p>
                <p class="num-display text-5xl text-ink-900 mt-3">{{ number_format($stats['rejected']) }}</p>
                <div class="mt-4 flex items-center justify-between">
                    <span class="pill-crimson"><span class="dot bg-crimson-500"></span>Ditolak / Error</span>
                    <span class="text-xs font-mono text-ink-400">{{ $pctRejected }}%</span>
                </div>
                <div class="mt-3 h-1 bg-cream-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-crimson-500 to-crimson-700" style="width: {{ $pctRejected }}%"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Quick actions ─────────────────────────────────── --}}
    <section class="mb-8">
        <div class="flex items-baseline justify-between mb-4">
            <h2 class="font-display text-xl font-semibold text-ink-900">Aksi cepat</h2>
            <p class="text-[10px] font-mono uppercase tracking-widest text-ink-400">Pintasan workspace</p>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @php
            $actions = [
                [route('documents.create'),         '+',  'Buat Dokumen',       'PIB · PEB · TPB · Rush', 'gold',   'bg-ink-900 text-gold-400'],
                [route('documents.lookup'),         '⌕', 'Cek Status',         'Query CEISA real-time',  'sea',    'bg-sea-50 text-sea-700'],
                [route('documents.archive.create'),'⎘', 'Arsip Manual',       'Catat dokumen lama',     'amber',  'bg-amber-50 text-amber-600'],
                [route('settings.ceisa.edit'),      '⚙', 'Pengaturan CEISA',   'Kredensial H2H',         'ink',    'bg-cream-200 text-ink-700'],
            ];
            @endphp
            @foreach ($actions as [$href, $icon, $title, $sub, $tone, $iconCls])
                <a href="{{ $href }}" class="group relative card p-5 hover:-translate-y-0.5 hover:shadow-card transition-all shimmer-on-hover">
                    <div class="flex items-start gap-3.5">
                        <span class="h-11 w-11 rounded-xl {{ $iconCls }} flex items-center justify-center text-xl font-display font-bold shrink-0">{{ $icon }}</span>
                        <div class="min-w-0">
                            <p class="font-bold text-ink-900 leading-tight">{{ $title }}</p>
                            <p class="text-[11px] text-ink-400 mt-1 truncate">{{ $sub }}</p>
                        </div>
                    </div>
                    <svg class="absolute top-4 right-4 h-3.5 w-3.5 text-ink-300 group-hover:text-gold-600 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ── Recent Documents Table ─────────────────────────────────── --}}
    <section>
        <div class="flex items-baseline justify-between mb-4">
            <div>
                <h2 class="font-display text-xl font-semibold text-ink-900">Dokumen terbaru</h2>
                <p class="text-[11px] text-ink-400 mt-0.5">Aktivitas H2H workspace Anda</p>
            </div>
            <a href="{{ route('documents.index') }}" class="text-sm font-bold text-ink-700 hover:text-gold-700 link-gold inline-flex items-center gap-1">
                Semua dokumen <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>

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
                                <td class="px-5 py-4">
                                    @php
                                        $typeMap = [
                                            'BC30' => ['BC 3.0', 'Ekspor',     'pill-gold'],
                                            'BC20' => ['BC 2.0', 'Impor',      'pill-sea'],
                                            'BC24' => ['BC 2.4', 'Impor TPB',  'pill-ink'],
                                            'TPB'  => ['TPB',    'Penimbunan', 'pill-ink'],
                                            'RUSH' => ['Rush',   'Handling',   'pill-crimson'],
                                        ];
                                        $info = $typeMap[$doc->doc_type] ?? [$doc->doc_type, '', 'pill-ink'];
                                    @endphp
                                    <span class="{{ $info[2] }}">{{ $info[0] }}</span>
                                    @if ($info[1])
                                        <span class="block text-[10px] text-ink-400 mt-1 uppercase tracking-wider font-bold">{{ $info[1] }}</span>
                                    @endif
                                </td>
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
                                            <svg class="h-6 w-6 text-ink-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                        </span>
                                        <p class="font-semibold text-ink-700">Belum ada dokumen</p>
                                        <a href="{{ route('documents.create') }}" class="btn-primary !py-2 !px-4 mt-1">
                                            + Buat dokumen pertama
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5">{{ $documents->links() }}</div>
    </section>
</x-app-layout>
