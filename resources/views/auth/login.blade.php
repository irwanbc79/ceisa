<x-guest-layout>

    {{-- ── Status badge ──────────────────────────────────── --}}
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-sea-50 border border-sea-200 mb-6">
        <span class="relative flex h-2 w-2">
            <span class="absolute h-full w-full rounded-full bg-sea-400 opacity-60 animate-ping"></span>
            <span class="relative h-2 w-2 rounded-full bg-sea-500"></span>
        </span>
        <span class="text-[11px] font-mono font-semibold uppercase tracking-widest text-sea-700">System Online</span>
    </div>

    <h1 class="font-display text-4xl lg:text-5xl font-light tracking-tightest leading-tight text-ink-900">
        Masuk ke <em class="font-semibold not-italic">Workspace H2H</em>
    </h1>
    <p class="mt-2 text-ink-500 leading-relaxed text-sm">
        PT Mora Multi Berkah · CEISA 4.0 Gateway
    </p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
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

        <button type="submit" class="btn-primary w-full !py-3.5 !text-base mt-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3 3m0 0 3-3m-3 3V9"/></svg>
            Masuk ke Dashboard
        </button>
    </form>

    {{-- ── Access info ──────────────────────────────────── --}}
    <div class="mt-8 pt-6 border-t border-cream-200/80">
        <p class="text-[11px] font-mono text-ink-400 leading-relaxed">
            <span class="inline-flex items-center gap-1.5">
                <svg class="h-3 w-3 text-ink-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                Akses dibatasi · Akun dibuat oleh administrator
            </span>
        </p>
        <a href="mailto:halo@morabangun.com" class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-semibold text-gold-700 hover:text-ink-700 link-gold">
            Butuh akses? Hubungi halo@morabangun.com
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
        </a>
    </div>

</x-guest-layout>
