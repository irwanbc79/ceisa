<x-guest-layout>
    <p class="eyebrow text-gold-700">Reset password</p>
    <h1 class="font-display text-3xl lg:text-4xl font-light tracking-tightest leading-tight mt-5 text-ink-900">
        Lupa password? <em class="font-semibold not-italic">Tenang.</em>
    </h1>
    <p class="mt-4 text-sm text-ink-500 leading-relaxed">
        Masukkan email Anda — kami akan mengirim tautan untuk mengatur ulang password.
    </p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus placeholder="anda@perusahaan.co.id" />
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <button type="submit" class="btn-primary w-full !py-3 !text-base">
            Kirim Tautan Reset
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
        </button>
        <p class="text-center text-sm text-ink-500">
            Ingat password lagi? <a href="{{ route('login') }}" class="font-bold text-ink-900 link-gold">Masuk</a>
        </p>
    </form>
</x-guest-layout>
