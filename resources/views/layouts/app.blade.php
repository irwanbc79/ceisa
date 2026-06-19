<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'M2B Customs') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&family=fraunces:300,400,500,600,700,900&family=jetbrains-mono:400,500,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-cream text-ink-900 min-h-screen">
    <div class="min-h-screen flex" x-data="{ sideOpen: false }">

        {{-- ── Sidebar (Desktop) ─────────────────────────── --}}
        @include('layouts.navigation')

        {{-- ── Mobile overlay ─────────────────────────── --}}
        <div x-show="sideOpen" x-transition.opacity
             @click="sideOpen = false"
             class="fixed inset-0 bg-ink-950/60 backdrop-blur-sm z-30 lg:hidden"
             style="display:none"></div>

        {{-- ── Main content ─────────────────────────── --}}
        <div class="flex-1 min-w-0 lg:pl-[260px]">

            {{-- Top bar --}}
            <header class="sticky top-0 z-20 bg-cream/85 backdrop-blur-md border-b border-cream-300">
                <div class="px-4 sm:px-6 lg:px-10 h-16 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <button @click="sideOpen = !sideOpen" class="lg:hidden btn-icon" aria-label="Menu">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        </button>
                        @isset($header)
                            <div class="min-w-0">{{ $header }}</div>
                        @endisset
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        {{-- Live status dot --}}
                        <span class="hidden md:inline-flex items-center gap-2 text-[11px] font-semibold text-ink-500 px-3 py-1.5 rounded-full bg-cream-200 border border-cream-300">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full rounded-full bg-sea-500 opacity-60 animate-ping"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-sea-500"></span>
                            </span>
                            <span class="font-mono">CEISA Gateway · Online</span>
                        </span>

                        {{-- Profile dropdown --}}
                        <x-dropdown align="right" width="56">
                            <x-slot name="trigger">
                                <button class="group inline-flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-full bg-white border border-cream-300 hover:border-ink-200 transition-colors">
                                    <span class="h-7 w-7 rounded-full bg-gradient-to-br from-ink-700 to-ink-900 text-cream text-xs font-bold flex items-center justify-center uppercase tracking-tight ring-2 ring-gold-200">
                                        {{ \Illuminate\Support\Str::of(Auth::user()->name)->substr(0,1) }}
                                    </span>
                                    <span class="hidden sm:flex flex-col leading-tight text-left">
                                        <span class="text-[12px] font-bold text-ink-900">{{ Auth::user()->name }}</span>
                                        <span class="text-[10px] text-ink-400 font-mono">{{ Auth::user()->email }}</span>
                                    </span>
                                    <svg class="h-3.5 w-3.5 text-ink-400 group-hover:text-ink-700 transition" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="px-4 py-3 border-b border-cream-200">
                                    <p class="text-[10px] uppercase tracking-widest text-ink-400 font-bold">Akun aktif</p>
                                    <p class="text-sm font-bold text-ink-900 mt-1 truncate">{{ Auth::user()->name }}</p>
                                </div>
                                <x-dropdown-link :href="route('profile.edit')">
                                    <span class="inline-flex items-center gap-2">
                                        <svg class="h-4 w-4 text-ink-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                        Profile
                                    </span>
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('settings.ceisa.edit')">
                                    <span class="inline-flex items-center gap-2">
                                        <svg class="h-4 w-4 text-ink-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.094c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.166-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.764-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                        Pengaturan CEISA
                                    </span>
                                </x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();this.closest('form').submit();">
                                        <span class="inline-flex items-center gap-2 text-crimson-700">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/></svg>
                                            Log out
                                        </span>
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </header>

            <main class="px-4 sm:px-6 lg:px-10 py-6 sm:py-8">
                {{ $slot }}
            </main>

            <footer class="px-4 sm:px-6 lg:px-10 py-6 border-t border-cream-300 mt-12">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-[11px] text-ink-400 font-mono">
                    <p>© {{ date('Y') }} <span class="font-bold text-ink-700">M2B Customs</span> · Host-to-Host Gateway · Powered by morabangun.com</p>
                    <p>Compliance · CEISA 4.0 · Direktorat Jenderal Bea dan Cukai</p>
                </div>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
