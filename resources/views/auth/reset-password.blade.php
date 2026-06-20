<x-guest-layout>
    <p class="eyebrow text-gold-700">Password baru</p>
    <h1 class="font-display text-3xl lg:text-4xl font-light tracking-tightest leading-tight mt-5 text-ink-900">
        Atur ulang <em class="font-semibold not-italic">password</em> Anda.
    </h1>

    <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <div>
            <x-input-label for="password" :value="__('Password baru')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>
        <div>
            <x-input-label for="password_confirmation" :value="__('Konfirmasi password')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>
        <button type="submit" class="btn-primary w-full !py-3 !text-base">Simpan password baru</button>
    </form>
</x-guest-layout>
