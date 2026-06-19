<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'M2B Customs') }} · Host-to-Host CEISA 4.0 Gateway</title>
    <meta name="description" content="Portal Host-to-Host CEISA 4.0 untuk PIB, PEB, TPB & Rush Handling. Dibangun oleh M2B & morabangun.com untuk efisiensi kepabeanan Indonesia.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&family=fraunces:300,400,500,600,700,900&family=jetbrains-mono:400,500,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-cream text-ink-900 overflow-x-hidden">

    {{-- ── Navbar ──────────────────────────────── --}}
    <header class="absolute top-0 inset-x-0 z-30">
        <div class="max-w-7xl mx-auto px-6 lg:px-10 h-20 flex items-center justify-between">
            <a href="/" class="inline-flex items-center gap-3 group">
                <span class="h-10 w-10 inline-flex items-center justify-center rounded-xl bg-cream shadow-gold-glow ring-1 ring-gold-300/40">
                    <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B" class="h-8 w-8 object-contain">
                </span>
                <div class="leading-tight">
                    <span class="block font-display text-lg font-semibold tracking-tighter text-cream">M2B<span class="text-gold-400">·</span>Customs</span>
                    <span class="block text-[9px] font-mono uppercase tracking-[0.3em] text-gold-300/85">Ceisa H2H</span>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-cream/75">
                <a href="#produk"   class="link-gold hover:text-cream transition">Produk</a>
                <a href="#alur"     class="link-gold hover:text-cream transition">Alur H2H</a>
                <a href="#layanan"  class="link-gold hover:text-cream transition">Layanan</a>
                <a href="#kontak"   class="link-gold hover:text-cream transition">Kontak</a>
            </nav>

            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn-gold">
                        Masuk Dashboard
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex btn-ghost-dark">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-gold">Daftar Akun H2H</a>
                    @endif
                @endauth
            </div>
        </div>
    </header>

    {{-- ── Hero ────────────────────────────────────────────────── --}}
    <section class="ink-hero pt-32 pb-24 lg:pt-44 lg:pb-32">
        <div class="relative max-w-7xl mx-auto px-6 lg:px-10 grid lg:grid-cols-12 gap-12 items-end">

            <div class="lg:col-span-7 stagger">
                <span class="eyebrow text-gold-300">Direktorat Jenderal Bea dan Cukai · CEISA 4.0</span>

                <h1 class="mt-6 font-display text-5xl sm:text-6xl lg:text-7xl font-light leading-[0.95] tracking-tightest text-cream text-balance">
                    Kepabeanan, <br class="hidden sm:block">
                    <em class="text-gold-400 not-italic font-semibold">tanpa antri</em>.
                    <br>
                    <span class="italic font-medium">Langsung dari sistem Anda</span>
                    <span class="block text-3xl sm:text-4xl mt-3 font-mono tracking-tight text-cream/40 normal-case">→ portal.beacukai.go.id</span>
                </h1>

                <p class="mt-8 text-cream/75 leading-relaxed max-w-xl text-lg">
                    <span class="font-bold text-cream">CEISA H2H Gateway</span> oleh M2B Customs — solusi
                    <span class="font-mono text-gold-300">Host-to-Host</span> resmi untuk PIB, PEB,
                    TPB &amp; Rush Handling. Otomatis, terenkripsi, dan terintegrasi penuh dengan portal
                    Bea Cukai Indonesia.
                </p>

                <div class="mt-10 flex flex-wrap items-center gap-4">
                    <a href="{{ Route::has('register') ? route('register') : '#' }}" class="btn-gold !px-7 !py-3.5 !text-base">
                        Buka Akun Gateway
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </a>
                    <a href="#alur" class="btn-ghost-dark !px-6 !py-3.5 !text-base">Lihat alur H2H</a>
                </div>

                <div class="mt-12 flex items-center gap-6 text-[11px] font-mono uppercase tracking-[0.18em] text-cream/40">
                    <span class="flex items-center gap-2">
                        <span class="relative flex h-2 w-2"><span class="absolute h-full w-full rounded-full bg-sea-500 opacity-60 animate-ping"></span><span class="relative h-2 w-2 rounded-full bg-sea-500"></span></span>
                        Gateway online
                    </span>
                    <span>·</span>
                    <span>TLS 1.3</span>
                    <span>·</span>
                    <span>ISO 27001 ready</span>
                </div>
            </div>

            {{-- Right: Floating "ticket" card --}}
            <div class="lg:col-span-5 relative">
                <div class="relative max-w-md mx-auto lg:ml-auto animate-fade-in-up">
                    {{-- Gold glow --}}
                    <div class="absolute -inset-6 bg-gold-500/[.18] blur-3xl rounded-full"></div>

                    <article class="relative bg-cream rounded-2xl shadow-[0_30px_80px_-20px_rgba(0,0,0,.4)] overflow-hidden">
                        {{-- Top ribbon --}}
                        <div class="bg-ink-900 text-cream px-5 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-sea-400"></span>
                                <span class="text-[10px] font-mono uppercase tracking-[0.25em] text-cream/75">CEISA · BC 2.0 · Impor</span>
                            </div>
                            <span class="text-[10px] font-mono text-gold-300">#000020MOT</span>
                        </div>

                        {{-- Content --}}
                        <div class="px-6 py-7 space-y-5">
                            <div>
                                <p class="eyebrow text-ink-400">Pemberitahuan Impor Barang</p>
                                <h3 class="font-display text-2xl font-semibold mt-2 text-ink-900 leading-tight">
                                    Spareparts Industrial — Container 40HC
                                </h3>
                            </div>

                            <div class="grid grid-cols-3 gap-4 text-xs">
                                <div>
                                    <p class="text-ink-400 uppercase tracking-widest font-bold text-[9px]">Importir</p>
                                    <p class="mt-1 font-semibold text-ink-900">PT. Mora<br>Multi Berkah</p>
                                </div>
                                <div>
                                    <p class="text-ink-400 uppercase tracking-widest font-bold text-[9px]">Kantor</p>
                                    <p class="mt-1 font-semibold text-ink-900">KPPBC<br>Tanjung Priok</p>
                                </div>
                                <div>
                                    <p class="text-ink-400 uppercase tracking-widest font-bold text-[9px]">Nilai CIF</p>
                                    <p class="mt-1 font-bold text-ink-900 font-mono">$ 48,720</p>
                                </div>
                            </div>

                            {{-- Progress --}}
                            <div class="space-y-3 pt-2">
                                <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider">
                                    <span class="text-ink-400">Status Pengajuan</span>
                                    <span class="text-sea-700">DITERIMA · SPPB Terbit</span>
                                </div>
                                <div class="h-1.5 bg-cream-300 rounded-full overflow-hidden">
                                    <div class="h-full w-full bg-gradient-to-r from-gold-400 via-sea-500 to-sea-600"></div>
                                </div>
                                <div class="grid grid-cols-4 gap-1 text-[9px] uppercase tracking-wider font-bold text-ink-300">
                                    <span class="text-ink-900">Draft</span>
                                    <span class="text-ink-900">Validasi</span>
                                    <span class="text-ink-900">Terkirim</span>
                                    <span class="text-sea-700">Respon</span>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-cream-300 flex items-center justify-between">
                                <span class="pill-sea"><span class="dot bg-sea-500"></span>Jalur Hijau</span>
                                <span class="text-[10px] font-mono text-ink-400">Respon · 24 dtk</span>
                            </div>
                        </div>
                    </article>

                    {{-- Floating mini card --}}
                    <div class="absolute -bottom-8 -left-10 hidden md:block bg-ink-900 text-cream rounded-xl shadow-ink-glow border border-ink-700 px-4 py-3 max-w-[200px]">
                        <p class="text-[10px] font-mono uppercase tracking-widest text-gold-300">Auto-validate</p>
                        <p class="text-xs mt-1 leading-snug">HS Code · NPWP · Netto · Kurs — diperiksa AI sebelum kirim.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Trust logos ticker --}}
        <div class="relative mt-20 max-w-7xl mx-auto px-6 lg:px-10">
            <p class="text-center text-[10px] font-mono uppercase tracking-[0.35em] text-cream/40 mb-6">Mendukung modul resmi CEISA 4.0</p>
            <div class="overflow-hidden mask-fade-x">
                <div class="ticker text-cream/30 font-display text-2xl font-light italic whitespace-nowrap">
                    @for ($i = 0; $i < 2; $i++)
                        <span class="px-6">BC 2.0 · Impor</span>
                        <span class="px-6 text-gold-400/60">◆</span>
                        <span class="px-6">BC 2.4 · TPB Impor</span>
                        <span class="px-6 text-gold-400/60">◆</span>
                        <span class="px-6">BC 3.0 · Ekspor</span>
                        <span class="px-6 text-gold-400/60">◆</span>
                        <span class="px-6">Portal TPB</span>
                        <span class="px-6 text-gold-400/60">◆</span>
                        <span class="px-6">Rush Handling</span>
                        <span class="px-6 text-gold-400/60">◆</span>
                        <span class="px-6">SPPB · NPE · NPP</span>
                        <span class="px-6 text-gold-400/60">◆</span>
                    @endfor
                </div>
            </div>
        </div>
    </section>

    {{-- ── Section: Produk / Modul ─────────────────────────────── --}}
    <section id="produk" class="relative py-24 lg:py-32 topo-bg">
        <div class="max-w-7xl mx-auto px-6 lg:px-10">

            <div class="grid lg:grid-cols-12 gap-10 mb-16">
                <div class="lg:col-span-5">
                    <p class="eyebrow text-gold-700">Modul CEISA 4.0</p>
                    <h2 class="font-display text-4xl lg:text-5xl font-light leading-tight tracking-tightest mt-5 text-balance text-ink-900">
                        Lima pintu dokumen,<br>
                        <em class="font-semibold not-italic">satu gateway terenkripsi.</em>
                    </h2>
                </div>
                <div class="lg:col-span-6 lg:col-start-7">
                    <p class="text-ink-600 leading-relaxed text-lg">
                        Dari impor barang industri sampai pengeluaran segera (rush handling) untuk vaksin atau organ tubuh —
                        seluruh alur dokumen kepabeanan dapat dikirim &amp; dilacak dari satu portal M2B yang terhubung
                        langsung ke <span class="font-mono text-ink-900">apis-gw.beacukai.go.id</span>.
                    </p>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 stagger">
                @php
                $modules = [
                    ['BC 2.0', 'Pemberitahuan Impor Barang', 'Modul utama importir — rincian barang, nilai pabean, dokumen pendukung secara elektronik.', 'sea',     '📦'],
                    ['BC 3.0', 'Pemberitahuan Ekspor Barang', 'Eksportir melaporkan barang yang dikirim ke luar negeri — header klasifikasi, FOB, hingga NPE.', 'gold',  '🚢'],
                    ['BC 2.4', 'Impor untuk TPB',             'Pemberitahuan impor barang yang ditimbun di Tempat Penimbunan Berikat (TPB).', 'ink',           '🏭'],
                    ['TPB',    'Portal Tempat Penimbunan',    'Administrasi fasilitas TPB — jenis, tujuan pengiriman, dokumen referensi/kontrak.', 'sea',          '🗂️'],
                    ['Rush',   'Rush Handling',               'Pengeluaran segera barang khusus: organ tubuh, vaksin, hewan/tumbuhan hidup, berita aktual.', 'crimson', '⚡'],
                    ['Lookup', 'Cek Status &amp; Arsip',         'Query status real-time dokumen historis berdasarkan Nomor Aju langsung dari API DJBC.', 'ink',     '🔍'],
                ];
                @endphp

                @foreach ($modules as $i => [$code, $name, $desc, $tone, $icon])
                    @php
                        $border = ['sea'=>'border-sea-200','gold'=>'border-gold-200','ink'=>'border-cream-300','crimson'=>'border-crimson-200'][$tone];
                        $bg     = ['sea'=>'bg-sea-50/40','gold'=>'bg-gold-50/40','ink'=>'bg-white','crimson'=>'bg-crimson-50/40'][$tone];
                        $codebg = ['sea'=>'bg-sea-500','gold'=>'bg-gold-500','ink'=>'bg-ink-900','crimson'=>'bg-crimson-500'][$tone];
                        $codetext = ['sea'=>'text-white','gold'=>'text-ink-900','ink'=>'text-cream','crimson'=>'text-white'][$tone];
                    @endphp
                    <article class="group relative {{ $bg }} border {{ $border }} rounded-2xl p-6 hover:shadow-card transition-all duration-300 shimmer-on-hover">
                        <div class="flex items-start justify-between mb-5">
                            <span class="text-3xl">{!! $icon !!}</span>
                            <span class="text-[10px] font-mono uppercase tracking-widest font-bold px-2.5 py-1 rounded-md {{ $codebg }} {{ $codetext }}">{{ $code }}</span>
                        </div>
                        <h3 class="font-display text-xl font-semibold text-ink-900 leading-tight">{{ $name }}</h3>
                        <p class="text-sm text-ink-500 mt-2 leading-relaxed">{!! $desc !!}</p>
                        <div class="mt-5 inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-ink-700 group-hover:text-gold-700 transition-colors">
                            <span class="link-gold">Pelajari modul</span>
                            <svg class="h-3 w-3 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Section: Alur H2H ─────────────────────────────── --}}
    <section id="alur" class="relative py-24 lg:py-32 bg-ink-950 text-cream overflow-hidden">
        {{-- Grid bg --}}
        <div class="absolute inset-0 opacity-[.04]" style="background-image: linear-gradient(rgba(255,255,255,1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,1) 1px, transparent 1px); background-size: 56px 56px;"></div>

        <div class="relative max-w-7xl mx-auto px-6 lg:px-10">

            <div class="max-w-3xl">
                <p class="eyebrow text-gold-300">Bagaimana Host-to-Host bekerja</p>
                <h2 class="font-display text-4xl lg:text-5xl font-light leading-tight tracking-tightest mt-5 text-balance">
                    Empat detik dari klik <span class="font-mono text-base text-gold-400 align-middle">[ submit ]</span><br>
                    sampai <em class="text-gold-400 not-italic font-semibold">SPPB</em> terbit.
                </h2>
                <p class="mt-5 text-cream/65 text-lg leading-relaxed">
                    Tidak lagi salin-tempel data antar sistem. Setelah Anda hubungkan API perusahaan ke M2B,
                    setiap dokumen mengalir end-to-end, ditandatangani digital, dan direspon Bea Cukai secara real-time.
                </p>
            </div>

            <div class="mt-16 grid lg:grid-cols-4 gap-px bg-ink-700/50 rounded-2xl overflow-hidden border border-ink-700">
                @php
                $steps = [
                    ['01','Connect','Hubungkan ERP / WMS Anda ke endpoint API M2B. Token enkripsi tergenerate otomatis.','API · TLS 1.3'],
                    ['02','Compose','Sistem M2B menyusun payload CEISA 4.0 valid (header, entitas, logistik, pos barang).','BC2.0 / BC3.0 / TPB'],
                    ['03','Validate','AI Hybrid (Claude · Gemini · DeepSeek) + rule engine memeriksa HS code, NPWP, netto, kurs.','Pre-submit checklist'],
                    ['04','Receive','Bea Cukai merespon SPPB / NPE / NPP via webhook. Status dokumen Anda diupdate seketika.','Jalur H · K · M'],
                ];
                @endphp

                @foreach ($steps as $idx => [$num, $title, $desc, $tag])
                    <div class="relative bg-ink-900 p-8 group hover:bg-ink-800 transition-colors">
                        <div class="flex items-baseline gap-3">
                            <span class="num-display text-5xl text-gold-400">{{ $num }}</span>
                            <span class="h-px flex-1 bg-gold-400/30"></span>
                        </div>
                        <h3 class="font-display text-2xl font-semibold mt-6 text-cream">{{ $title }}</h3>
                        <p class="mt-3 text-sm text-cream/55 leading-relaxed">{{ $desc }}</p>
                        <p class="mt-6 text-[10px] font-mono uppercase tracking-[0.25em] text-gold-300/80">{{ $tag }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Code block --}}
            <div class="mt-14 grid lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-5">
                    <p class="eyebrow text-gold-300">Untuk developer</p>
                    <h3 class="font-display text-3xl font-light leading-tight mt-4">
                        Satu request → seluruh dokumen <em class="text-gold-400 not-italic font-semibold">tervalidasi</em> &amp; terkirim.
                    </h3>
                    <p class="mt-4 text-cream/60 leading-relaxed">
                        Endpoint REST minimalis. Cukup kirim payload — M2B yang mengurus orkestrasi token,
                        retry, signature, webhook listener, dan log audit.
                    </p>
                    <a href="#kontak" class="mt-6 inline-flex items-center gap-2 text-gold-400 hover:text-gold-300 font-semibold text-sm link-gold">
                        Minta dokumentasi API <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </a>
                </div>
                <div class="lg:col-span-7">
                    <div class="rounded-2xl bg-black/40 border border-ink-700 overflow-hidden shadow-ink-glow">
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-ink-700 bg-black/30">
                            <span class="h-2.5 w-2.5 rounded-full bg-crimson-500/70"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-400/70"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-sea-500/70"></span>
                            <span class="ml-3 text-[11px] font-mono text-cream/50">POST · /api/h2h/submit · BC 2.0</span>
                        </div>
                        <pre class="px-5 py-5 text-[12px] leading-relaxed font-mono text-cream/85 overflow-x-auto"><span class="text-gold-300">curl</span> -X POST https://ceisa.m2b.co.id/api/h2h/submit \
  -H <span class="text-sea-300">"Authorization: Bearer $M2B_TOKEN"</span> \
  -H <span class="text-sea-300">"Content-Type: application/json"</span> \
  -d <span class="text-cream/60">'{</span>
    <span class="text-gold-300">"doc_type"</span>: <span class="text-sea-300">"BC20"</span>,
    <span class="text-gold-300">"importir"</span>: { <span class="text-gold-300">"npwp"</span>: <span class="text-sea-300">"012345678901000"</span> },
    <span class="text-gold-300">"barang"</span>: [
      { <span class="text-gold-300">"hs_code"</span>: <span class="text-sea-300">"84799090"</span>,
        <span class="text-gold-300">"netto"</span>: 248.5, <span class="text-gold-300">"nilai_cif"</span>: 48720 }
    ]
  <span class="text-cream/60">}'</span>

<span class="text-cream/40">// → 200 OK · nomor_aju · status: submitted</span></pre>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Section: Layanan & Manfaat ─────────────────────────────── --}}
    <section id="layanan" class="py-24 lg:py-32 bg-cream">
        <div class="max-w-7xl mx-auto px-6 lg:px-10">

            <div class="grid lg:grid-cols-12 gap-10 mb-14">
                <div class="lg:col-span-6">
                    <p class="eyebrow text-gold-700">Mengapa M2B Customs</p>
                    <h2 class="font-display text-4xl lg:text-5xl font-light leading-tight tracking-tightest mt-5 text-balance text-ink-900">
                        Bukan sekadar konektor — <br>
                        <em class="font-semibold not-italic">co-pilot kepabeanan</em> Anda.
                    </h2>
                </div>
                <div class="lg:col-span-5 lg:col-start-8">
                    <p class="text-ink-500 leading-relaxed">
                        Dibangun sebagai pilot project Mora Bangun, M2B Customs dirancang untuk perusahaan
                        importir, eksportir, dan pengusaha TPB yang menginginkan operasional 24/7 tanpa
                        bergantung pada operator manual.
                    </p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-5">
                @php
                $benefits = [
                    ['Pre-submit AI Validator',  'Cek HS-Code, NPWP, netto, kurs, dan kelengkapan dokumen — sebelum sampai ke DJBC. Mengurangi NPP &amp; revisi.','sparkles'],
                    ['Real-time Webhook',         'Setiap respons CEISA (SPPB, NPE, NPP, perubahan jalur) langsung mendorong update status ke ERP Anda.','bolt'],
                    ['Audit-Ready Log',           'Setiap payload, signature, dan respons tersimpan terenkripsi — siap untuk audit internal &amp; eksternal.','shield'],
                    ['Sandbox & Production',      'Sandbox DJBC untuk testing tanpa risiko, switch ke production hanya dengan satu pilihan environment.','beaker'],
                    ['Multi-tenant Ready',        'Kelola lebih dari satu NPWP / App ID dalam satu workspace — cocok untuk holding &amp; group company.','users'],
                    ['Manual Archive Importer',  'Sudah punya dokumen lama di portal DJBC? Impor &amp; arsipkan ke M2B untuk pelaporan menyeluruh.','archive'],
                ];
                @endphp
                @foreach ($benefits as $idx => [$title, $desc, $iconKey])
                    <article class="card p-6 lg:p-7 hover:-translate-y-0.5 hover:shadow-card transition-all">
                        <div class="flex items-start gap-5">
                            <div class="shrink-0 h-12 w-12 rounded-xl bg-ink-900 text-gold-400 flex items-center justify-center">
                                @switch($iconKey)
                                    @case('sparkles') <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg> @break
                                    @case('bolt') <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 13.5 3 12 12h7.5L9 21l1.5-7.5H3.75Z"/></svg> @break
                                    @case('shield') <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9c1.27 0 2.48.26 3.59.74"/></svg> @break
                                    @case('beaker') <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23-.693L5 14.5"/></svg> @break
                                    @case('users') <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg> @break
                                    @case('archive') <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v9a2.25 2.25 0 0 1-2.25 2.25h-12a2.25 2.25 0 0 1-2.25-2.25v-9m16.5 0a2.25 2.25 0 0 0-2.25-2.25h-12a2.25 2.25 0 0 0-2.25 2.25"/></svg> @break
                                @endswitch
                            </div>
                            <div>
                                <h3 class="font-display text-xl font-semibold text-ink-900">{{ $title }}</h3>
                                <p class="text-sm text-ink-500 mt-2 leading-relaxed">{!! $desc !!}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Big numbers strip --}}
            <div class="mt-20 grid grid-cols-2 lg:grid-cols-4 gap-px bg-cream-300 rounded-2xl overflow-hidden border border-cream-300">
                @php
                $kpis = [
                    ['99.9%', 'Uptime Gateway', 'Production'],
                    ['<30s',  'Median Respon SPPB', 'Real-time'],
                    ['100%',  'Payload terenkripsi', 'AES-256 + TLS 1.3'],
                    ['5',     'Modul CEISA aktif', 'BC 2.0 · 2.4 · 3.0 · TPB · Rush'],
                ];
                @endphp
                @foreach ($kpis as [$n, $label, $sub])
                    <div class="bg-white p-7 hover:bg-cream-100 transition-colors">
                        <p class="num-display text-5xl text-ink-900">{{ $n }}</p>
                        <p class="mt-3 text-xs font-bold uppercase tracking-widest text-ink-700">{{ $label }}</p>
                        <p class="text-[11px] text-ink-400 mt-1 font-mono">{{ $sub }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Section: CTA / Kontak ─────────────────────────────── --}}
    <section id="kontak" class="relative py-24 lg:py-32 ink-hero overflow-hidden">
        <div class="relative max-w-5xl mx-auto px-6 lg:px-10 text-center">
            <p class="eyebrow text-gold-300 justify-center">Buka Gateway H2H Anda</p>
            <h2 class="mt-6 font-display text-5xl lg:text-7xl font-light leading-[0.95] tracking-tightest text-cream text-balance">
                Mulai impor &amp; ekspor <br>
                <em class="text-gold-400 not-italic font-semibold">tanpa antrian portal.</em>
            </h2>
            <p class="mt-7 max-w-2xl mx-auto text-cream/65 text-lg leading-relaxed">
                Buka akun M2B Customs hari ini, hubungkan kredensial CEISA Anda,
                dan kirim dokumen pertama dalam hitungan menit.
            </p>

            <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ Route::has('register') ? route('register') : '#' }}" class="btn-gold !px-8 !py-4 !text-base">
                    Daftar Akun H2H
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </a>
                <a href="mailto:halo@morabangun.com" class="btn-ghost-dark !px-7 !py-4 !text-base">
                    halo@morabangun.com
                </a>
            </div>

            <div class="mt-16 inline-flex items-center gap-6 text-[11px] font-mono uppercase tracking-[0.22em] text-cream/40">
                <span>· Jakarta · Indonesia</span>
                <span>· morabangun.com</span>
                <span>· M2B Customs Care</span>
            </div>
        </div>
    </section>

    {{-- ── Footer ─────────────────────────────── --}}
    <footer class="bg-ink-950 text-cream/70 border-t border-ink-700/40">
        <div class="max-w-7xl mx-auto px-6 lg:px-10 py-12 grid md:grid-cols-4 gap-10">
            <div class="md:col-span-2">
                <a href="/" class="inline-flex items-center gap-3">
                    <span class="h-10 w-10 inline-flex items-center justify-center rounded-xl bg-cream">
                        <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B" class="h-8 w-8 object-contain">
                    </span>
                    <span class="font-display text-xl font-semibold tracking-tighter text-cream">M2B<span class="text-gold-400">·</span>Customs</span>
                </a>
                <p class="mt-5 max-w-md text-sm leading-relaxed">
                    Host-to-Host Gateway untuk CEISA 4.0 — dibangun oleh PT Mora Multi Berkah
                    sebagai pilot project morabangun.com untuk mempercepat layanan kepabeanan Indonesia.
                </p>
            </div>
            <div>
                <p class="eyebrow text-gold-300">Produk</p>
                <ul class="mt-5 space-y-2 text-sm">
                    <li><a href="#produk" class="hover:text-cream link-gold">Modul CEISA</a></li>
                    <li><a href="#alur" class="hover:text-cream link-gold">Alur H2H</a></li>
                    <li><a href="#layanan" class="hover:text-cream link-gold">Layanan</a></li>
                    <li><a href="{{ Route::has('register') ? route('register') : '#' }}" class="hover:text-cream link-gold">Daftar</a></li>
                </ul>
            </div>
            <div>
                <p class="eyebrow text-gold-300">Legal</p>
                <ul class="mt-5 space-y-2 text-sm">
                    <li>Syarat &amp; Ketentuan</li>
                    <li>Kebijakan Privasi</li>
                    <li>Compliance &amp; ISO</li>
                    <li><a href="mailto:halo@morabangun.com" class="hover:text-cream link-gold">halo@morabangun.com</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-ink-700/40">
            <div class="max-w-7xl mx-auto px-6 lg:px-10 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] font-mono text-cream/40">
                <p>© {{ date('Y') }} PT Mora Multi Berkah · morabangun.com</p>
                <p>CEISA Care — Direktorat Jenderal Bea dan Cukai · Kementerian Keuangan RI</p>
            </div>
        </div>
    </footer>

    <style>
        .mask-fade-x { mask-image: linear-gradient(90deg, transparent, black 8%, black 92%, transparent); }
    </style>
</body>
</html>
