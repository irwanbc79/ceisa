<x-guest-layout>
    <p class="eyebrow text-gold-700">Mulai gratis</p>
    <h1 class="font-display text-4xl lg:text-5xl font-light tracking-tightest leading-tight mt-5 text-ink-900">
        Buka <em class="font-semibold not-italic">workspace H2H</em>,<br>kirim PIB pertama Anda.
    </h1>
    <p class="mt-3 text-ink-500 leading-relaxed">Tanpa kartu kredit · Sandbox CEISA siap pakai · Setup &lt; 5 menit</p>

    <form method="POST" action="{{ route('register') }}" class="mt-10 space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Nama lengkap')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Budi Santoso" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email kerja')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="anda@perusahaan.co.id" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Konfirmasi password')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ketik ulang password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <button type="submit" class="btn-primary w-full !py-3 !text-base mt-2">
            Daftar &amp; Mulai
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
        </button>

        <p class="text-center text-sm text-ink-500 pt-2">
            Sudah punya akun? <a href="{{ route('login') }}" class="font-bold text-ink-900 link-gold">Masuk di sini</a>
        </p>
    </form>
</x-guest-layout>
