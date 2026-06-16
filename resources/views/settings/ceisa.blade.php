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
                <h3 class="text-lg font-medium text-gray-900">Kredensial Host-to-Host (CEISA 4.0)</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Login H2H memakai <strong>Username</strong> + <strong>Password</strong> akun Portal CEISA
                    (portal.beacukai.go.id) beserta <strong>Beacukai API Key</strong> (header <code class="text-xs">beacukai-api-key</code>).
                    Semua kredensial disimpan terenkripsi.
                </p>

                <form method="POST" action="{{ route('settings.ceisa.update') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="username" value="Username" />
                        <x-text-input id="username" name="username" type="text" class="mt-1 block w-full"
                                      :value="old('username', $credential?->username)" required autofocus autocomplete="off" />
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
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

                    @if ($credential)
                        <div class="text-sm text-gray-500">
                            Status token:
                            @if ($credential->hasValidToken())
                                <span class="text-green-600 font-medium">Valid</span>
                                (kadaluarsa {{ $credential->token_expires_at->diffForHumans() }})
                            @else
                                <span class="text-gray-500">Belum ada / kadaluarsa</span>
                            @endif
                        </div>
                    @endif

                    <div class="flex items-center gap-3">
                        <x-primary-button>Simpan</x-primary-button>
                    </div>
                </form>

                @if ($credential)
                    <form method="POST" action="{{ route('settings.ceisa.test') }}" class="mt-4 pt-4 border-t border-gray-100">
                        @csrf
                        <x-secondary-button>Uji Koneksi (ambil token)</x-secondary-button>
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
    </script>
</x-app-layout>
