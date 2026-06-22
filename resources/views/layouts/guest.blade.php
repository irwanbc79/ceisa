<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'M2B Customs') }}</title>
    <meta name="description" content="Portal Host-to-Host CEISA 4.0 Bea Cukai — PT Mora Multi Berkah. Pengelolaan dokumen kepabeanan impor, ekspor & TPB.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&family=fraunces:300,400,500,600,700,900&family=jetbrains-mono:400,500,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen bg-cream text-ink-900">
    <div class="min-h-screen grid lg:grid-cols-[1.05fr_1fr]">

        {{-- ── Left brand panel ─────────────────────────────── --}}
        <aside class="relative ink-hero hidden lg:flex flex-col justify-between p-12 xl:p-16 overflow-hidden">

            {{-- Subtle maritime wave overlay --}}
            <div class="absolute inset-0 opacity-[0.03] pointer-events-none">
                <svg class="absolute bottom-0 left-0 w-full h-64" viewBox="0 0 1440 320" preserveAspectRatio="none">
                    <path fill="currentColor" class="text-gold-400" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,261.3C960,256,1056,224,1152,208C1248,192,1344,192,1392,192L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"/>
                </svg>
            </div>

            {{-- Top: brand --}}
            <div class="relative z-10">
                <a href="/" class="inline-flex items-center gap-3 group">
                    <span class="h-12 w-12 inline-flex items-center justify-center rounded-2xl bg-cream shadow-gold-glow ring-1 ring-gold-300/40 transition-transform duration-300 group-hover:scale-105 group-hover:shadow-[0_0_20px_rgba(201,165,92,0.25)]">
                        <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B" class="h-9 w-9 object-contain">
                    </span>
                    <div class="leading-tight">
                        <span class="block font-display text-2xl font-semibold tracking-tighter text-cream">M2B<span class="text-gold-400">·</span>Customs</span>
                        <span class="block text-[10px] font-mono uppercase tracking-[0.32em] text-gold-300/85">Ceisa H2H · 4.0</span>
                    </div>
                </a>
            </div>

            {{-- Middle: editorial pitch --}}
            <div class="relative z-10 max-w-xl stagger">
                <p class="eyebrow text-gold-300">Direktorat Jenderal Bea dan Cukai · Indonesia</p>

                <h1 class="font-display text-5xl xl:text-7xl font-light text-cream leading-[0.92] tracking-tightest mt-6 text-balance">
                    Kepabeanan <em class="text-gold-400 not-italic font-semibold">tanpa antrian</em>, <br>
                    dari sistem Anda <span class="italic font-medium">langsung</span> ke Bea Cukai.
                </h1>

                <p class="mt-8 text-cream/70 leading-relaxed max-w-md">
                    Portal Host-to-Host CEISA 4.0 untuk PIB, PEB, TPB &amp; Rush Handling.
                    Otomatis, terenkripsi, dan terintegrasi penuh dengan portal
                    <span class="font-mono text-gold-300">portal.beacukai.go.id</span>.
                </p>

                {{-- Stat row --}}
                <div class="mt-12 grid grid-cols-3 gap-6 max-w-md">
                    <div>
                        <p class="num-display text-3xl text-cream">Pilot</p>
                        <p class="text-[11px] uppercase tracking-widest text-cream/50 mt-1">Project resmi</p>
                    </div>
                    <div>
                        <p class="num-display text-3xl text-cream">H2H<span class="text-gold-400 text-xl">·</span>5</p>
                        <p class="text-[11px] uppercase tracking-widest text-cream/50 mt-1">Modul CEISA 4.0</p>
                    </div>
                    <div>
                        <p class="num-display text-3xl text-cream">24<span class="text-gold-400 text-xl">/</span>7</p>
                        <p class="text-[11px] uppercase tracking-widest text-cream/50 mt-1">Submit kapan saja</p>
                    </div>
                </div>
            </div>

            {{-- Bottom: ticker + footer --}}
            <div class="relative z-10 mt-12 space-y-8">
                <div class="overflow-hidden mask-fade">
                    <div class="ticker text-[11px] uppercase tracking-[0.3em] text-cream/40 font-mono">
                        @for ($i = 0; $i < 2; $i++)
                            <span>· BC 2.0 Impor</span><span>· BC 2.4 TPB Impor</span><span>· BC 3.0 Ekspor</span><span>· Portal TPB</span><span>· Rush Handling</span><span>· Validasi AI</span><span>· Real-time webhook</span><span>· Audit-ready log</span>
                        @endfor
                    </div>
                </div>

                <div class="flex items-center justify-between text-[10px] font-mono text-cream/30">
                    <span>© {{ date('Y') }} PT Mora Multi Berkah</span>
                    <span class="hidden xl:inline">morabangun.com</span>
                    <span>v4.0 · TLS 1.3</span>
                </div>
            </div>

            {{-- Decorative compass --}}
            <svg class="absolute -right-32 -bottom-32 w-[520px] h-[520px] text-gold-400/[.07]" viewBox="0 0 200 200" fill="none" stroke="currentColor">
                <circle cx="100" cy="100" r="98" stroke-width=".5"/>
                <circle cx="100" cy="100" r="80" stroke-width=".5"/>
                <circle cx="100" cy="100" r="60" stroke-width=".5"/>
                <circle cx="100" cy="100" r="40" stroke-width=".5"/>
                <g stroke-width=".4">
                    <line x1="100" y1="2" x2="100" y2="198"/>
                    <line x1="2" y1="100" x2="198" y2="100"/>
                    <line x1="30" y1="30" x2="170" y2="170"/>
                    <line x1="170" y1="30" x2="30" y2="170"/>
                </g>
                <polygon points="100,20 105,100 100,180 95,100" fill="currentColor" fill-opacity=".6"/>
            </svg>
        </aside>

        {{-- ── Right form panel ─────────────────────────────── --}}
        <main class="relative flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16 xl:px-24">
            {{-- Mobile brand (hidden lg+) --}}
            <a href="/" class="lg:hidden inline-flex items-center gap-3 mb-10">
                <span class="h-10 w-10 inline-flex items-center justify-center rounded-xl bg-ink-900">
                    <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B" class="h-8 w-8 object-contain">
                </span>
                <div class="leading-tight">
                    <span class="font-display text-xl font-semibold tracking-tighter text-ink-900">M2B<span class="text-gold-500">·</span>Customs</span>
                    <span class="block text-[9px] font-mono uppercase tracking-[0.25em] text-ink-400">CEISA 4.0 H2H</span>
                </div>
            </a>

            <div class="w-full max-w-md mx-auto lg:mx-0">
                {{ $slot }}
            </div>

            <div class="mt-auto pt-12 text-[10px] font-mono text-ink-300 max-w-md mx-auto lg:mx-0 flex items-center justify-between">
                <span>© {{ date('Y') }} morabangun.com · PT Mora Multi Berkah</span>
                <span class="hidden sm:inline">All systems secure</span>
            </div>
        </main>
    </div>

    <style>
        .mask-fade { mask-image: linear-gradient(90deg, transparent, black 10%, black 90%, transparent); }
    </style>
</body>
</html>
