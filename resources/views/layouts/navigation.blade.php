{{-- ── Sidebar Navigation (Maritime Indigo) ────────────────── --}}
<aside
    class="fixed inset-y-0 left-0 z-40 w-[260px] transform transition-transform duration-300 ease-out lg:translate-x-0"
    :class="sideOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    <div class="relative h-full ink-hero border-r border-ink-700/50 flex flex-col">

        {{-- Brand --}}
        <div class="relative px-5 pt-7 pb-5 border-b border-white/10">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                <span class="relative inline-flex h-11 w-11 items-center justify-center rounded-xl bg-cream shadow-gold-glow ring-1 ring-gold-300/40 overflow-hidden">
                    <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B" class="h-9 w-9 object-contain">
                </span>
                <div class="flex flex-col leading-tight">
                    <span class="font-display text-[20px] font-semibold tracking-tighter text-cream">
                        M2B<span class="text-gold-400">·</span>Customs
                    </span>
                    <span class="text-[10px] font-mono uppercase tracking-[0.3em] text-gold-300/80">CEISA H2H · 4.0</span>
                </div>
            </a>
        </div>

        {{-- Nav links --}}
        <nav class="relative flex-1 px-4 py-6 overflow-y-auto">
            <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-[0.22em] text-cream/40">Workspace</p>
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('dashboard') }}" data-active="{{ request()->routeIs('dashboard') ? 'true' : 'false' }}" class="nav-side">
                        <svg class="h-4 w-4 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 12 2.25 21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
                        <span>Dashboard</span>
                        <span class="ml-auto text-[10px] font-mono opacity-50">⌘D</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('documents.index') }}" data-active="{{ request()->routeIs('documents.index') ? 'true' : 'false' }}" class="nav-side">
                        <svg class="h-4 w-4 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        <span>Daftar Dokumen</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('documents.create') }}" data-active="{{ request()->routeIs('documents.create') ? 'true' : 'false' }}" class="nav-side">
                        <svg class="h-4 w-4 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        <span>Buat Dokumen</span>
                        <span class="ml-auto pill-gold !py-0.5 !px-1.5 !text-[9px] !tracking-wider">H2H</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('documents.lookup') }}" data-active="{{ request()->routeIs('documents.lookup') ? 'true' : 'false' }}" class="nav-side">
                        <svg class="h-4 w-4 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607.197a7.5 7.5 0 0 1-10.607 0"/></svg>
                        <span>Cek Status</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('documents.archive.create') }}" data-active="{{ request()->routeIs('documents.archive.*') ? 'true' : 'false' }}" class="nav-side">
                        <svg class="h-4 w-4 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v9a2.25 2.25 0 0 1-2.25 2.25h-12a2.25 2.25 0 0 1-2.25-2.25v-9m16.5 0a2.25 2.25 0 0 0-2.25-2.25h-12a2.25 2.25 0 0 0-2.25 2.25m16.5 0V7.5m-9 6h.008v.008H11.25v-.008Z"/></svg>
                        <span>Arsip Dokumen</span>
                    </a>
                </li>
            </ul>

            <p class="px-3 mt-7 mb-2 text-[10px] font-bold uppercase tracking-[0.22em] text-cream/40">System</p>
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('settings.ceisa.edit') }}" data-active="{{ request()->routeIs('settings.ceisa.*') ? 'true' : 'false' }}" class="nav-side">
                        <svg class="h-4 w-4 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        <span>Kredensial CEISA</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('profile.edit') }}" data-active="{{ request()->routeIs('profile.*') ? 'true' : 'false' }}" class="nav-side">
                        <svg class="h-4 w-4 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                        <span>Akun Saya</span>
                    </a>
                </li>
            </ul>
        </nav>

        {{-- Footer brand block --}}
        <div class="relative px-5 py-5 border-t border-white/10">
            <div class="relative rounded-xl border border-gold-500/30 bg-gradient-to-br from-gold-500/[.12] to-transparent p-4 overflow-hidden">
                <div class="absolute -right-6 -top-6 h-16 w-16 rounded-full bg-gold-400/20 blur-2xl"></div>
                <p class="text-[10px] font-mono uppercase tracking-[0.25em] text-gold-300/90">Powered by</p>
                <p class="font-display text-lg font-semibold text-cream leading-tight mt-1">Mora<span class="text-gold-400">·</span>Bangun</p>
                <p class="text-[10px] text-cream/50 mt-1">Pilot project H2H · 2026</p>
            </div>
        </div>
    </div>
</aside>
