<x-app-layout>
    @php
        $currentBaseUrl = $credential?->base_url;
        $env = 'production';
        if ($currentBaseUrl) {
            if ($currentBaseUrl === 'https://apisdev-gw.beacukai.go.id') { $env = 'sandbox'; }
            elseif ($currentBaseUrl === 'https://apis-gw.beacukai.go.id') { $env = 'production'; }
            else { $env = 'custom'; }
        }
    @endphp
    <x-slot name="header">
        <div class="flex flex-col">
            <p class="text-[10px] font-mono uppercase tracking-[0.3em] text-ink-400">System · Credentials</p>
            <h1 class="font-display text-2xl sm:text-3xl font-semibold text-ink-900 tracking-tightest leading-none mt-1">Pengaturan CEISA H2H</h1>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <x-flash />

        <div class="card p-6 lg:p-8">
            <div class="flex items-start gap-5 border-b border-cream-300 pb-6 mb-6">
                <span class="h-12 w-12 rounded-xl bg-ink-900 text-gold-400 flex items-center justify-center shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-3.75 11.25h16.5a1.5 1.5 0 0 0 1.5-1.5v-9a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v9a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                </span>
                <div>
                    <h3 class="font-display text-xl font-semibold text-ink-900">Kredensial Host-to-Host (CEISA 4.0)</h3>
                    <p class="mt-2 text-sm text-ink-500 leading-relaxed">
                        Login H2H memakai <strong>Username</strong> + <strong>Password</strong> akun Portal CEISA
                        (<span class="font-mono text-ink-700">portal.beacukai.go.id</span>) beserta
                        <strong>Beacukai API Key</strong> (header <code class="font-mono text-xs text-gold-700">beacukai-api-key</code>).
                        Semua kredensial disimpan terenkripsi.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.ceisa.update') }}" class="space-y-5">
                @csrf

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="username" value="Username" />
                        <x-text-input id="username" name="username" type="text" :value="old('username', $credential?->username)" required autofocus autocomplete="off" />
                        <x-input-error :messages="$errors->get('username')" />
                    </div>
                    <div>
                        <x-input-label for="npwp" value="NPWP Perusahaan (Opsional)" />
                        <x-text-input id="npwp" name="npwp" type="text" :value="old('npwp', $credential?->npwp)" autocomplete="off" placeholder="15/16 digit" />
                        <x-input-error :messages="$errors->get('npwp')" />
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="password" value="Password" />
                        <x-text-input id="password" name="password" type="password" autocomplete="new-password" placeholder="{{ $credential ? '••••••• (kosongkan untuk pertahankan)' : '' }}" />
                        <x-input-error :messages="$errors->get('password')" />
                    </div>
                    <div>
                        <x-input-label for="api_key" value="Beacukai API Key" />
                        <x-text-input id="api_key" name="api_key" type="password" autocomplete="off" placeholder="{{ $credential ? '••••••• (kosongkan untuk pertahankan)' : '' }}" />
                        <x-input-error :messages="$errors->get('api_key')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="id_platform" value="ID Platform" />
                    <x-text-input id="id_platform" name="id_platform" type="text" :value="old('id_platform', $credential?->id_platform)" autocomplete="off" placeholder="ID Platform client dari Portal CEISA" />
                    <x-input-error :messages="$errors->get('id_platform')" />
                </div>

                <div>
                    <x-input-label for="environment" value="Environment / Gateway URL" />
                    <select id="environment" name="environment" class="field" onchange="toggleCustomUrl(this.value)">
                        <option value="production" {{ old('environment', $env) === 'production' ? 'selected' : '' }}>Production · apis-gw.beacukai.go.id</option>
                        <option value="sandbox"    {{ old('environment', $env) === 'sandbox'    ? 'selected' : '' }}>Sandbox / Dev · apisdev-gw.beacukai.go.id</option>
                        <option value="custom"     {{ old('environment', $env) === 'custom'     ? 'selected' : '' }}>Custom Gateway URL</option>
                    </select>
                    <x-input-error :messages="$errors->get('environment')" />
                </div>

                <div id="custom-url-container" style="display: {{ old('environment', $env) === 'custom' ? 'block' : 'none' }}">
                    <x-input-label for="custom_base_url" value="Custom Gateway URL" />
                    <x-text-input id="custom_base_url" name="custom_base_url" type="text" :value="old('custom_base_url', $credential?->base_url)" placeholder="https://..." />
                    <x-input-error :messages="$errors->get('custom_base_url')" />
                </div>

                <div>
                    <x-input-label for="app_id" value="App ID (Opsional)" />
                    <x-text-input id="app_id" name="app_id" type="text" :value="old('app_id', $credential?->app_id)" />
                    <x-input-error :messages="$errors->get('app_id')" />
                </div>

                @if ($credential)
                    <div class="rounded-xl bg-cream-100 border border-cream-300 px-4 py-3 flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-widest text-ink-400">Status Token</span>
                        @if ($credential->hasValidToken())
                            <span class="pill-sea"><span class="dot bg-sea-500"></span>Valid · {{ $credential->token_expires_at->diffForHumans() }}</span>
                        @else
                            <span class="pill-ink"><span class="dot bg-ink-300"></span>Belum ada / kadaluarsa</span>
                        @endif
                    </div>
                @endif

                <div class="flex items-center gap-3 pt-2 border-t border-cream-300">
                    <x-primary-button>Simpan Kredensial</x-primary-button>
                </div>
            </form>

            @if ($credential)
                <form method="POST" action="{{ route('settings.ceisa.test') }}" class="mt-5 pt-5 border-t border-cream-300">
                    @csrf
                    <div class="flex items-center gap-3">
                        <x-secondary-button type="submit">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                            Uji Koneksi (ambil token)
                        </x-secondary-button>
                        <span class="text-xs text-ink-400 font-mono">→ Request token ke CEISA</span>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <script>
        function toggleCustomUrl(value) {
            document.getElementById('custom-url-container').style.display = value === 'custom' ? 'block' : 'none';
        }
    </script>
</x-app-layout>
