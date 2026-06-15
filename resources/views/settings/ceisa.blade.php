<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pengaturan CEISA') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-flash />

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">Kredensial Host-to-Host</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Masukkan <strong>App ID</strong> dan <strong>API Key</strong> yang diberikan Bea Cukai saat onboarding H2H.
                    API Key disimpan terenkripsi.
                </p>

                <form method="POST" action="{{ route('settings.ceisa.update') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="app_id" value="App ID" />
                        <x-text-input id="app_id" name="app_id" type="text" class="mt-1 block w-full"
                                      :value="old('app_id', $credential?->app_id)" required autofocus />
                        <x-input-error :messages="$errors->get('app_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="api_key" value="API Key" />
                        <x-text-input id="api_key" name="api_key" type="password" class="mt-1 block w-full"
                                      placeholder="{{ $credential ? '•••••••• (biarkan kosong untuk mempertahankan)' : '' }}" />
                        <x-input-error :messages="$errors->get('api_key')" class="mt-2" />
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
</x-app-layout>
