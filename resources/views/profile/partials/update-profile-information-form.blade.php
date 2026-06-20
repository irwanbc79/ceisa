<section>
    <header>
        <p class="eyebrow text-gold-700">Profile</p>
        <h2 class="font-display text-2xl font-semibold text-ink-900 mt-3">Informasi Profil</h2>
        <p class="mt-2 text-sm text-ink-500">Update nama dan alamat email akun Anda.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Nama')" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 text-xs text-ink-500">
                    Email belum diverifikasi.
                    <button form="send-verification" class="font-bold text-ink-900 link-gold">Kirim ulang tautan verifikasi.</button>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sea-700 font-medium">Tautan verifikasi baru telah dikirim ke email Anda.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Simpan</x-primary-button>
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-xs text-sea-700 font-bold uppercase tracking-widest">Tersimpan ✓</p>
            @endif
        </div>
    </form>
</section>
