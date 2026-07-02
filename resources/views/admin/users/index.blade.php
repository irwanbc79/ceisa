<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-mono uppercase tracking-[0.28em] text-gold-600">Administrasi · Akses</p>
                <h2 class="font-display text-2xl font-semibold text-ink-900">Manajemen Pengguna</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <x-flash />

            {{-- Kredensial baru: tampil SEKALI setelah buat akun / reset password --}}
            @if (session('generated_credentials'))
                @php($cred = session('generated_credentials'))
                <div class="rounded-xl bg-gold-50 border border-gold-200 px-4 py-4 text-sm text-ink-800">
                    <p class="font-semibold mb-2">Salin kredensial ini sekarang — password tidak akan ditampilkan lagi:</p>
                    <div class="font-mono text-[13px] bg-white rounded-lg border border-gold-200 px-3 py-2 inline-block select-all">
                        {{ $cred['email'] }} &nbsp;·&nbsp; {{ $cred['password'] }}
                    </div>
                </div>
            @endif

            {{-- Form tambah pengguna --}}
            <div class="card p-4 sm:p-6">
                <h3 class="font-display text-lg font-semibold text-ink-900">Tambah Pengguna</h3>
                <p class="mt-1 text-sm text-ink-500">
                    Akun langsung aktif dan terverifikasi. Kosongkan password untuk digenerate otomatis.
                </p>

                <form method="POST" action="{{ route('users.store') }}" class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                    @csrf
                    <div class="lg:col-span-1">
                        <label class="block text-xs font-semibold text-ink-600 mb-1">Nama</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-lg border-cream-300 text-sm focus:ring-ink-700/20 focus:border-ink-700">
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-xs font-semibold text-ink-600 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full rounded-lg border-cream-300 text-sm focus:ring-ink-700/20 focus:border-ink-700">
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-ink-600 mb-1">Peran</label>
                        <select name="role" class="w-full rounded-lg border-cream-300 text-sm focus:ring-ink-700/20 focus:border-ink-700">
                            <option value="operator" @selected(old('role', 'operator') === 'operator')>Operator</option>
                            <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-ink-600 mb-1">Password <span class="font-normal text-ink-400">(opsional)</span></label>
                        <input type="text" name="password" autocomplete="new-password" placeholder="Otomatis jika kosong"
                               class="w-full rounded-lg border-cream-300 text-sm focus:ring-ink-700/20 focus:border-ink-700">
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>
                    <div>
                        <button type="submit" class="btn-primary !py-2 w-full">Buat Akun</button>
                    </div>
                </form>
            </div>

            {{-- Daftar pengguna --}}
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-mono uppercase tracking-wider text-ink-500 border-b border-cream-200">
                                <th class="px-4 sm:px-6 py-3">Pengguna</th>
                                <th class="px-4 py-3">Peran</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Dokumen</th>
                                <th class="px-4 py-3">Terdaftar</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cream-100">
                            @foreach ($users as $user)
                                <tr class="{{ $user->is_active ? '' : 'opacity-60' }}">
                                    <td class="px-4 sm:px-6 py-3">
                                        <div class="font-semibold text-ink-900">
                                            {{ $user->name }}
                                            @if ($user->is(auth()->user()))
                                                <span class="text-[10px] font-mono text-gold-600 ms-1">(Anda)</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-ink-500">{{ $user->email }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($user->isAdmin())
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gold-50 text-gold-700 border border-gold-200">Admin</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-cream-100 text-ink-600 border border-cream-200">Operator</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($user->is_active)
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-sea-50 text-sea-700 border border-sea-200">Aktif</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-crimson-50 text-crimson-700 border border-crimson-200">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-ink-600">{{ $user->documents_count }}</td>
                                    <td class="px-4 py-3 text-ink-500 text-xs">{{ $user->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-1.5 flex-wrap">
                                            <form method="POST" action="{{ route('users.reset-password', $user) }}"
                                                  onsubmit="return confirm('Reset password {{ $user->name }}? Password baru akan ditampilkan sekali.')">
                                                @csrf
                                                <button type="submit" class="btn-ghost !py-1 !px-2.5 !text-xs">Reset Password</button>
                                            </form>

                                            @unless ($user->is(auth()->user()))
                                                <form method="POST" action="{{ route('users.update', $user) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="role" value="{{ $user->isAdmin() ? 'operator' : 'admin' }}">
                                                    <button type="submit" class="btn-ghost !py-1 !px-2.5 !text-xs">
                                                        {{ $user->isAdmin() ? 'Jadikan Operator' : 'Jadikan Admin' }}
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('users.toggle-active', $user) }}"
                                                      @if ($user->is_active) onsubmit="return confirm('Nonaktifkan {{ $user->name }}? User akan langsung logout dan tidak bisa login.')" @endif>
                                                    @csrf
                                                    <button type="submit" class="btn-ghost !py-1 !px-2.5 !text-xs {{ $user->is_active ? '!text-crimson-600' : '!text-sea-600' }}">
                                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
