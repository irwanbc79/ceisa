<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col">
            <p class="text-[10px] font-mono uppercase tracking-[0.3em] text-ink-400">System · Account</p>
            <h1 class="font-display text-2xl sm:text-3xl font-semibold text-ink-900 tracking-tightest leading-none mt-1">Akun Saya</h1>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="card p-6 lg:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card p-6 lg:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card p-6 lg:p-8 border-crimson-200">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
