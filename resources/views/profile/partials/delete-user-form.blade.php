<section class="space-y-6">
    <header>
        <p class="eyebrow text-crimson-700">Danger zone</p>
        <h2 class="font-display text-2xl font-semibold text-ink-900 mt-3">Hapus Akun</h2>
        <p class="mt-2 text-sm text-ink-500">
            Setelah akun dihapus, semua data &amp; dokumen akan terhapus permanen. Mohon unduh data penting sebelum menghapus akun.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hapus Akun Saya</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="font-display text-xl font-semibold text-ink-900">Apakah Anda yakin ingin menghapus akun?</h2>
            <p class="mt-2 text-sm text-ink-500">
                Setelah akun dihapus, semua data &amp; dokumen akan terhapus permanen. Ketik password Anda untuk konfirmasi.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />
                <x-text-input id="password" name="password" type="password" class="block w-3/4" placeholder="{{ __('Password') }}" />
                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-danger-button>Hapus Akun</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
