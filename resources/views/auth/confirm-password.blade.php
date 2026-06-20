<x-guest-layout>
    <p class="eyebrow text-gold-700">Area aman</p>
    <h1 class="font-display text-3xl lg:text-4xl font-light tracking-tightest leading-tight mt-5 text-ink-900">
        Konfirmasi <em class="font-semibold not-italic">password</em> Anda.
    </h1>
    <p class="mt-4 text-sm text-ink-500 leading-relaxed">
        Ini adalah area aman aplikasi. Mohon ketik ulang password Anda sebelum melanjutkan.
    </p>
    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>
        <button type="submit" class="btn-primary w-full !py-3 !text-base">Konfirmasi</button>
    </form>
</x-guest-layout>
