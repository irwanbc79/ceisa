<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#F4F7FB">
        <meta name="description" content="Sistem Host-to-Host (H2H) CEISA 4.0 Bea Cukai — pengelolaan dokumen kepabeanan impor & ekspor PT Mora Multi Berkah.">

        <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name', 'CEISA H2H') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|fraunces:300,400,600,700|jetbrains-mono:400,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        {{-- Render guard harus tersedia sebelum stylesheet eksternal selesai diproses. --}}
        <style>[x-cloak] { display: none !important; }</style>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased relative min-h-screen overflow-x-hidden">
        <div class="min-h-screen text-slate-700 antialiased relative z-10">
            <!-- Cahaya peta operasi: memberi kedalaman tanpa mengganggu data. -->
            <div class="fixed -top-48 left-1/3 -translate-x-1/2 w-[900px] h-[420px] rounded-full bg-indigo-200/[0.28] blur-[120px] pointer-events-none z-0" aria-hidden="true"></div>
            <div class="fixed -bottom-56 -right-40 w-[700px] h-[500px] rounded-full bg-sea-100/[0.38] blur-[140px] pointer-events-none z-0" aria-hidden="true"></div>
            <div class="fixed top-1/3 -left-56 w-[600px] h-[600px] rounded-full bg-gold-100/[0.24] blur-[150px] pointer-events-none z-0" aria-hidden="true"></div>
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="customs-page-header bg-white/80 backdrop-blur-xl border-b border-slate-200/80 shadow-soft relative z-20">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8 relative z-10">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        {{-- Global Copy-to-Clipboard Toast & Command Palette (⌘K / Ctrl+K) --}}
        <div x-data="ceisaCommandPalette()" @keydown.window="handleKeydown($event)" @open-command-palette.window="openPalette()">
            {{-- Toast --}}
            <div x-show="toastVisible" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-2"
                 class="fixed bottom-5 right-5 z-[70] inline-flex items-center gap-2 rounded-xl bg-slate-900 text-white px-3.5 py-2 text-xs font-semibold shadow-lg border border-slate-700/80">
                <svg class="h-4 w-4 text-sea-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                <span x-text="toastMessage"></span>
            </div>

            {{-- Command Palette Modal --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-[75] overflow-y-auto p-4 sm:p-6 md:p-20" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-slate-900/45 backdrop-blur-sm transition-opacity" @click="open = false"></div>
                <div class="relative mx-auto max-w-xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 transition-all">
                    <form action="{{ route('documents.index') }}" method="GET" class="relative flex items-center border-b border-slate-100">
                        <svg class="pointer-events-none absolute left-4 h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607.197a7.5 7.5 0 0 1-10.607 0" /></svg>
                        <input x-ref="searchInput" x-model="query" name="q" type="text"
                               placeholder="Cari Nomor Aju / Nomor Daftar, atau pilih menu..."
                               class="h-12 w-full border-0 bg-transparent pl-11 pr-20 text-sm text-slate-800 placeholder:text-slate-400 focus:ring-0" />
                        <span class="mr-3 rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[10px] font-semibold text-slate-500 border border-slate-200">ESC</span>
                    </form>

                    <div class="max-h-80 overflow-y-auto p-2 space-y-1">
                        <template x-if="query.trim().length > 0">
                            <a :href="`{{ route('documents.index') }}?q=${encodeURIComponent(query.trim())}`"
                               class="flex items-center justify-between rounded-xl px-3 py-2.5 text-xs font-semibold text-indigo-700 bg-indigo-50/70 hover:bg-indigo-100/80 transition-colors">
                                <span class="flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607.197a7.5 7.5 0 0 1-10.607 0" /></svg>
                                    Cari dokumen "<span x-text="query.trim()"></span>" di Daftar Dokumen
                                </span>
                                <span class="font-mono text-[10px] text-indigo-500">Enter ↵</span>
                            </a>
                        </template>

                        <div class="px-3 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Navigasi Cepat</div>
                        <template x-for="item in filteredItems()" :key="item.href">
                            <a :href="item.href" class="flex items-center justify-between rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100/80 hover:text-slate-900 transition-colors">
                                <span x-text="item.label"></span>
                                <span class="text-[10px] font-mono text-slate-400" x-text="item.hint"></span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <script>
            window.copyCeisaText = function (text, label = 'Tersalin ke clipboard') {
                if (!text || !navigator.clipboard) return;
                navigator.clipboard.writeText(text).then(() => {
                    window.dispatchEvent(new CustomEvent('ceisa-toast', { detail: label }));
                });
            };

            function ceisaCommandPalette() {
                return {
                    open: false,
                    query: '',
                    toastVisible: false,
                    toastMessage: 'Tersalin ke clipboard',
                    toastTimer: null,
                    items: [
                        { label: 'Dashboard Utama', hint: 'Ringkasan', href: @json(route('dashboard')) },
                        { label: 'Buat Dokumen Baru (BC 3.0 / BC 2.0)', hint: 'Wizard', href: @json(route('documents.create')) },
                        { label: 'Daftar Dokumen Lengkap', hint: 'Filter & Export', href: @json(route('documents.index')) },
                        { label: 'Cek Status CEISA (Lookup)', hint: 'Nomor Aju', href: @json(route('documents.lookup')) },
                        { label: 'Rekam Arsip Dokumen Lama', hint: 'Arsip', href: @json(route('documents.archive.create')) },
                        { label: 'Monitoring Manifes (BC 1.1)', hint: 'Inward/Outward', href: @json(route('manifests.index')) },
                        { label: 'Pusat Notifikasi DJBC', hint: 'Respon/Formulir', href: @json(route('notifications.index')) },
                        { label: 'Pengaturan Kredensial CEISA', hint: 'H2H Gateway', href: @json(route('settings.ceisa.edit')) },
                    ],
                    init() {
                        window.addEventListener('ceisa-toast', (e) => {
                            this.toastMessage = e.detail || 'Tersalin ke clipboard';
                            this.toastVisible = true;
                            clearTimeout(this.toastTimer);
                            this.toastTimer = setTimeout(() => { this.toastVisible = false; }, 2200);
                        });
                    },
                    openPalette() {
                        this.open = true;
                        this.$nextTick(() => this.$refs.searchInput?.focus());
                    },
                    handleKeydown(e) {
                        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                            e.preventDefault();
                            this.open ? (this.open = false) : this.openPalette();
                        } else if (e.key === 'Escape' && this.open) {
                            this.open = false;
                        }
                    },
                    filteredItems() {
                        const q = this.query.trim().toLowerCase();
                        if (!q) return this.items;
                        return this.items.filter(i => i.label.toLowerCase().includes(q) || i.hint.toLowerCase().includes(q));
                    },
                };
            }
        </script>

        @stack('scripts')
    </body>
</html>
