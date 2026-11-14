<nav x-data="{ open: false }" class="bg-panel/85 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-50 shadow-sm transition-all duration-300">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-7 xl:-my-px xl:ms-8 xl:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('documents.index')" :active="request()->routeIs('documents.index')">
                        {{ __('Daftar Dokumen') }}
                    </x-nav-link>
                    <x-nav-link :href="route('documents.create')" :active="request()->routeIs('documents.create')">
                        {{ __('Buat Dokumen') }}
                    </x-nav-link>
                    <x-nav-link :href="route('manifests.index')" :active="request()->routeIs('manifests.*')">
                        {{ __('Monitoring Manifes') }}
                    </x-nav-link>
                    <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.index')">
                        {{ __('Notifikasi') }}
                    </x-nav-link>
                    <x-nav-link :href="route('settings.ceisa.edit')" :active="request()->routeIs('settings.ceisa.*')">
                        {{ __('Pengaturan CEISA') }}
                    </x-nav-link>
                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            {{ __('Pengguna') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown + Quick Search + CEISA Env Pill -->
            @php
                $navCredential = \App\Models\CeisaCredential::shared();
                $navEnv = match (true) {
                    ! $navCredential => ['label' => 'CEISA BELUM DIATUR', 'cls' => 'bg-amber-50 text-amber-300 border-amber-200'],
                    str_contains((string) $navCredential->base_url, 'apisdev-gw') => ['label' => 'SANDBOX', 'cls' => 'bg-amber-50 text-amber-300 border-amber-200'],
                    str_contains((string) $navCredential->base_url, 'apis-gw') => ['label' => 'PROD', 'cls' => 'bg-emerald-50 text-emerald-300 border-emerald-200'],
                    default => ['label' => 'CUSTOM', 'cls' => 'bg-indigo-50 text-indigo-300 border-indigo-200'],
                };
            @endphp
            <div class="hidden xl:flex xl:items-center xl:gap-2.5 xl:ms-5">
                <button type="button"
                        @click="$dispatch('open-command-palette')"
                        title="Cari cepat dokumen & menu (⌘K / Ctrl+K)"
                        class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg border border-slate-200/80 bg-slate-50/80 hover:bg-slate-100 text-xs text-slate-500 hover:text-slate-800 transition-colors">
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607.197a7.5 7.5 0 0 1-10.607 0" /></svg>
                    <span class="hidden 2xl:inline font-medium">Cari...</span>
                    <kbd class="rounded bg-white px-1.5 py-0.5 font-mono text-[10px] font-semibold text-slate-400 border border-slate-200">⌘K</kbd>
                </button>

                <a href="{{ route('settings.ceisa.edit') }}"
                   title="Environment Gateway CEISA Aktif"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[10px] font-mono font-bold tracking-wider {{ $navEnv['cls'] }}">
                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                    {{ $navEnv['label'] }}
                </a>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-3 py-1.5 border border-slate-200/60 text-sm leading-4 font-semibold rounded-lg text-slate-700 bg-slate-50 hover:bg-slate-100/80 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition ease-in-out duration-150 shadow-sm">
                            <div class="h-6 w-6 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[10px] uppercase shadow-inner">
                                {{ substr(Auth::user()->name, 0, 2) }}
                            </div>
                            <div>{{ Auth::user()->name }}</div>

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
