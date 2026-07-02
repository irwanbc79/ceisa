<x-app-layout>
    @php
        $currentBaseUrl = $credential?->base_url;
        $env = 'production'; // default
        if ($currentBaseUrl) {
            if ($currentBaseUrl === 'https://apisdev-gw.beacukai.go.id') {
                $env = 'sandbox';
            } elseif ($currentBaseUrl === 'https://apis-gw.beacukai.go.id') {
                $env = 'production';
            } else {
                $env = 'custom';
            }
        }
    @endphp
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pengaturan CEISA') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-flash />

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">Kredensial Host-to-Host (CEISA 4.0) — Perusahaan</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Kredensial berlaku untuk <strong>seluruh staf</strong> (satu akun H2H perusahaan).
                    Login H2H memakai <strong>Username</strong> + <strong>Password</strong> akun Portal CEISA
                    (portal.beacukai.go.id) beserta <strong>Beacukai API Key</strong> (header <code class="text-xs">beacukai-api-key</code>).
                    Semua kredensial disimpan terenkripsi.
                </p>

                @if (! auth()->user()->isAdmin())
                    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50/60 p-4 text-sm text-slate-600 space-y-1.5">
                        <p class="font-semibold text-slate-800">Kredensial dikelola oleh admin.</p>
                        @if ($credential)
                            <p>Status: <span class="font-semibold text-emerald-600">terpasang</span>
                                @if ($credential->npwp) · NPWP {{ $credential->npwp }} @endif
                                @if ($credential->user) · diatur oleh {{ $credential->user->name }} @endif
                            </p>
                            <p class="text-xs text-slate-400">Anda dapat langsung membuat & mengirim dokumen — sistem otomatis memakai kredensial perusahaan ini.</p>
                        @else
                            <p>Status: <span class="font-semibold text-crimson-600">belum diisi</span> — hubungi admin untuk mengisi kredensial CEISA sebelum mengirim dokumen.</p>
                        @endif
                    </div>
                @endif

                @if (auth()->user()->isAdmin())
                <form method="POST" action="{{ route('settings.ceisa.update') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="username" value="Username" />
                        <x-text-input id="username" name="username" type="text" class="mt-1 block w-full"
                                      :value="old('username', $credential?->username)" required autofocus autocomplete="off" />
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="npwp" value="NPWP Perusahaan (opsional)" />
                        <x-text-input id="npwp" name="npwp" type="text" class="mt-1 block w-full"
                                      :value="old('npwp', $credential?->npwp)" autocomplete="off"
                                      placeholder="15/16 digit — dipakai untuk query status per perusahaan" />
                        <x-input-error :messages="$errors->get('npwp')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Password" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password"
                                      placeholder="{{ $credential ? '•••••••• (biarkan kosong untuk mempertahankan)' : '' }}" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="api_key" value="Beacukai API Key" />
                        <x-text-input id="api_key" name="api_key" type="password" class="mt-1 block w-full" autocomplete="off"
                                      placeholder="{{ $credential ? '•••••••• (biarkan kosong untuk mempertahankan)' : '' }}" />
                        <x-input-error :messages="$errors->get('api_key')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="id_platform" value="ID Platform" />
                        <x-text-input id="id_platform" name="id_platform" type="text" class="mt-1 block w-full"
                                      :value="old('id_platform', $credential?->id_platform)" autocomplete="off"
                                      placeholder="ID Platform client dari Portal CEISA" />
                        <x-input-error :messages="$errors->get('id_platform')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="environment" value="Environment / Gateway URL" />
                        <select id="environment" name="environment" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" onchange="toggleCustomUrl(this.value)">
                            <option value="production" {{ old('environment', $env) === 'production' ? 'selected' : '' }}>Production (https://apis-gw.beacukai.go.id)</option>
                            <option value="sandbox" {{ old('environment', $env) === 'sandbox' ? 'selected' : '' }}>Sandbox / Development (https://apisdev-gw.beacukai.go.id)</option>
                            <option value="custom" {{ old('environment', $env) === 'custom' ? 'selected' : '' }}>Custom Gateway URL</option>
                        </select>
                        <x-input-error :messages="$errors->get('environment')" class="mt-2" />
                    </div>

                    <div id="custom-url-container" style="display: {{ old('environment', $env) === 'custom' ? 'block' : 'none' }}">
                        <x-input-label for="custom_base_url" value="Custom Gateway URL" />
                        <x-text-input id="custom_base_url" name="custom_base_url" type="text" class="mt-1 block w-full"
                                      :value="old('custom_base_url', $credential?->base_url)" placeholder="https://..." />
                        <x-input-error :messages="$errors->get('custom_base_url')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="app_id" value="App ID (opsional)" />
                        <x-text-input id="app_id" name="app_id" type="text" class="mt-1 block w-full"
                                      :value="old('app_id', $credential?->app_id)" />
                        <x-input-error :messages="$errors->get('app_id')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>Simpan</x-primary-button>
                    </div>
                </form>
                @endif

                @if ($credential)
                    @php
                        $expISO = $credential->token_expires_at?->toIso8601String();
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3.5"
                         x-data="ceisaTokenCountdown(@js($expISO))" x-init="start()">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-slate-500">Status token akses:</span>
                                <template x-if="remaining > 0">
                                    <span class="inline-flex items-center gap-1.5 font-bold text-emerald-600">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                        </span>
                                        Aktif
                                    </span>
                                </template>
                                <template x-if="remaining <= 0">
                                    <span class="inline-flex items-center gap-1.5 font-bold text-slate-400">
                                        <span class="h-2 w-2 rounded-full bg-slate-300"></span>
                                        Kedaluwarsa
                                    </span>
                                </template>
                            </div>
                            <div class="text-right">
                                <template x-if="remaining > 0">
                                    <span class="font-mono font-bold tabular-nums text-lg"
                                          :class="remaining <= 60 ? 'text-amber-600' : 'text-slate-700'"
                                          x-text="formatted"></span>
                                </template>
                                <template x-if="remaining <= 0">
                                    <span class="text-xs text-slate-400 font-medium">diperbarui otomatis saat aksi berikutnya</span>
                                </template>
                            </div>
                        </div>
                        {{-- Progress bar sisa umur token (asumsi TTL {{ (int) config('ceisa.token_ttl_fallback', 300) }} detik) --}}
                        <div class="mt-2.5 h-1.5 w-full rounded-full bg-slate-200 overflow-hidden" x-show="remaining > 0">
                            <div class="h-full rounded-full transition-all duration-1000 ease-linear"
                                 :class="remaining <= 60 ? 'bg-amber-500' : 'bg-emerald-500'"
                                 :style="`width: ${Math.min(100, Math.max(0, (remaining / {{ (int) config('ceisa.token_ttl_fallback', 300) }}) * 100))}%`"></div>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-2">Access token CEISA 4.0 berumur ±5 menit; sistem me-refresh otomatis (refresh token) saat dibutuhkan.</p>
                    </div>
                @endif

                @if ($credential)
                    <form method="POST" action="{{ route('settings.ceisa.test') }}" class="mt-4 pt-4 border-t border-gray-100">
                        @csrf
                        <x-secondary-button type="submit">Uji Koneksi (ambil token)</x-secondary-button>
                        <span class="text-xs text-gray-400 ml-2">Mengirim request token ke CEISA.</span>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <script>
        function toggleCustomUrl(value) {
            var container = document.getElementById('custom-url-container');
            if (value === 'custom') {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        // Countdown live umur access token CEISA (±5 menit).
        function ceisaTokenCountdown(expiresIso) {
            return {
                remaining: 0,
                formatted: '00:00',
                timer: null,
                tick() {
                    if (!expiresIso) { this.remaining = 0; this.formatted = '00:00'; return; }
                    const diff = Math.floor((new Date(expiresIso).getTime() - Date.now()) / 1000);
                    this.remaining = diff > 0 ? diff : 0;
                    const m = Math.floor(this.remaining / 60);
                    const s = this.remaining % 60;
                    this.formatted = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                    if (this.remaining <= 0 && this.timer) { clearInterval(this.timer); this.timer = null; }
                },
                start() {
                    this.tick();
                    this.timer = setInterval(() => this.tick(), 1000);
                },
            };
        }
    </script>
</x-app-layout>
