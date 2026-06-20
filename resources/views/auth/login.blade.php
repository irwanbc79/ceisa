<x-guest-layout>
    <p class="eyebrow text-gold-700">Welcome back</p>
    <h1 class="font-display text-4xl lg:text-5xl font-light tracking-tightest leading-tight mt-5 text-ink-900">
        Masuk ke <em class="font-semibold not-italic">gateway H2H</em><br>Anda.
    </h1>
    <p class="mt-3 text-ink-500 leading-relaxed">Workspace M2B Customs · CEISA 4.0</p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-10 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="anda@perusahaan.co.id" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••••" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded border-cream-400 text-ink-900 focus:ring-ink-700/20" name="remember">
                <span class="ms-2 text-sm text-ink-600">Ingat saya 30 hari</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-ink-700 hover:text-gold-700 link-gold" href="{{ route('password.request') }}">
                    Lupa password?
                </a>
            @endif
        </div>

        <button type="submit" class="btn-primary w-full !py-3 !text-base mt-2">
            Masuk ke Dashboard
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
        </button>

        <div class="divider-gold pt-3"><span class="text-[10px] font-mono uppercase tracking-widest">atau</span></div>

        <p class="text-center text-sm text-ink-500">
            Belum punya akun H2H? <a href="{{ route('register') }}" class="font-bold text-ink-900 link-gold">Daftar gratis</a>
        </p>
    </form>
</x-guest-layout>
