<nav x-data="{ open: false }" class="bg-panel/85 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-50 shadow-sm transition-all duration-300">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-3 h-16">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <!-- Logo & Header Title -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 group">
                        <span class="h-9 px-1.5 inline-flex items-center justify-center rounded-xl bg-white border border-slate-200/80 shadow-xs group-hover:border-indigo-300 transition-colors">
                            <x-application-logo class="block h-6 w-auto" />
                        </span>
                        <div class="hidden 2xl:block leading-tight whitespace-nowrap">
                            <span class="block text-sm font-extrabold tracking-tight text-slate-900">PT. Mora Multi Berkah</span>
                            <span class="block text-[9px] font-mono font-semibold uppercase tracking-[0.2em] text-amber-600">CEISA 4.0 · H2H</span>
                        </div>
                    </a>
                </div>

                <div class="hidden xl:block h-5 w-px bg-slate-200/80 shrink-0" aria-hidden="true"></div>

                <!-- Navigation Links -->
                <div class="hidden xl:flex xl:items-center xl:gap-0.5 min-w-0 overflow-x-auto scrollbar-none">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="whitespace-nowrap">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('documents.index')" :active="request()->routeIs('documents.index')" class="whitespace-nowrap">
                        {{ __('Dokumen') }}
                    </x-nav-link>
                    <x-nav-link :href="route('documents.create')" :active="request()->routeIs('documents.create')" class="whitespace-nowrap">
                        {{ __('Buat Baru') }}
                    </x-nav-link>
                    <x-nav-link :href="route('manifests.index')" :active="request()->routeIs('manifests.*')" class="whitespace-nowrap">
                        {{ __('Manifes') }}
                    </x-nav-link>
                    <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.index')" class="whitespace-nowrap">
                        {{ __('Notifikasi') }}
                    </x-nav-link>
                    <x-nav-link :href="route('settings.ceisa.edit')" :active="request()->routeIs('settings.ceisa.*')" class="whitespace-nowrap">
                        {{ __('Pengaturan') }}
                    </x-nav-link>
                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" class="whitespace-nowrap">
                            {{ __('Pengguna') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown + Quick Search + CEISA Env Pill -->
            @php
                $navCredential = \App\Models\CeisaCredential::shared();
                $navEnv = match (true) {
                    ! $navCredential => ['label' => 'CEISA BELUM DIATUR', 'cls' => 'bg-amber-50 text-amber-700 border-amber-200'],
                    str_contains((string) $navCredential->base_url, 'apisdev-gw') => ['label' => 'SANDBOX', 'cls' => 'bg-amber-50 text-amber-700 border-amber-200'],
                    str_contains((string) $navCredential->base_url, 'apis-gw') => ['label' => 'PROD', 'cls' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                    default => ['label' => 'CUSTOM', 'cls' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
                };
            @endphp
            <div class="hidden xl:flex xl:items-center xl:gap-2 shrink-0">
                <button type="button"
                        @click="$dispatch('open-command-palette')"
                        title="Cari cepat dokumen & menu (⌘K / Ctrl+K)"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200/80 bg-slate-50/80 hover:bg-slate-100 text-xs text-slate-500 hover:text-slate-800 transition-colors shrink-0">
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607.197a7.5 7.5 0 0 1-10.607 0" /></svg>
                    <kbd class="rounded bg-white px-1.5 py-0.5 font-mono text-[10px] font-semibold text-slate-400 border border-slate-200">⌘K</kbd>
                </button>

                <a href="{{ route('settings.ceisa.edit') }}"
                   title="Environment Gateway CEISA Aktif"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[10px] font-mono font-bold tracking-wider whitespace-nowrap shrink-0 {{ $navEnv['cls'] }}">
                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                    {{ $navEnv['label'] }}
                </a>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-3 py-1.5 border border-slate-200/60 text-sm leading-4 font-semibold rounded-lg text-slate-700 bg-slate-50 hover:bg-slate-100/80 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition ease-in-out duration-150 shadow-sm whitespace-nowrap">
                            <div class="h-6 w-6 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[10px] uppercase shadow-inner shrink-0">
                                {{ substr(Auth::user()->name, 0, 2) }}
                            </div>
                            <span class="whitespace-nowrap">{{ Auth::user()->name }}</span>

                            <div class="ms-0.5">
                                <svg class="fill-current h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center xl:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden xl:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('documents.index')" :active="request()->routeIs('documents.index')">
                {{ __('Daftar Dokumen') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('documents.create')" :active="request()->routeIs('documents.create')">
                {{ __('Buat Dokumen') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('manifests.index')" :active="request()->routeIs('manifests.*')">
                {{ __('Monitoring Manifes') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.index')">
                {{ __('Notifikasi') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('settings.ceisa.edit')" :active="request()->routeIs('settings.ceisa.*')">
                {{ __('Pengaturan CEISA') }}
            </x-responsive-nav-link>
            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    {{ __('Pengguna') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
