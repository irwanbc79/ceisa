<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-mono uppercase tracking-[0.28em] text-gold-600">Modul Manifes · BC 1.1</p>
                <h2 class="font-display text-2xl font-semibold text-ink-900">Monitoring Manifes</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-xl bg-sea-50 border border-sea-200 px-4 py-3 text-sm text-sea-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl bg-crimson-50 border border-crimson-200 px-4 py-3 text-sm text-crimson-700">{{ session('error') }}</div>
            @endif

            {{-- Filter & aksi --}}
            <form method="GET" class="card p-4 sm:p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-ink-600 mb-1">Cari (Sarana / Voyage / IMO / No. Daftar)</label>
                        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Ketik kata kunci…"
                               class="w-full rounded-lg border-cream-300 text-sm focus:ring-ink-700/20 focus:border-ink-700">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-ink-600 mb-1">Jenis</label>
                        <select name="jenis" class="w-full rounded-lg border-cream-300 text-sm focus:ring-ink-700/20 focus:border-ink-700">
                            <option value="">Semua</option>
                            <option value="inward" @selected($filters['jenis'] === 'inward')>Kedatangan</option>
                            <option value="outward" @selected($filters['jenis'] === 'outward')>Keberangkatan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-ink-600 mb-1">Dari Tgl</label>
                        <input type="date" name="from" value="{{ $filters['from'] }}" class="w-full rounded-lg border-cream-300 text-sm focus:ring-ink-700/20 focus:border-ink-700">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-ink-600 mb-1">s.d Tgl</label>
                        <input type="date" name="to" value="{{ $filters['to'] }}" class="w-full rounded-lg border-cream-300 text-sm focus:ring-ink-700/20 focus:border-ink-700">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary !py-2 flex-1">Filter</button>
                        <a href="{{ route('manifests.index') }}" class="btn-ghost !py-2">Reset</a>
                    </div>
                </div>
            </form>

            {{-- Tabel monitoring --}}
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b border-cream-200">
                    <p class="text-sm text-ink-500">Total <span class="font-semibold text-ink-900">{{ $manifests->total() }}</span> manifes</p>
                    <form method="POST" action="{{ route('manifests.sync') }}">
                        @csrf
                        <input type="hidden" name="jenis" value="{{ $filters['jenis'] ?: 'inward' }}">
                        <button type="submit" class="btn-ghost !py-1.5 !text-xs">↻ Tarik dari CEISA</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-cream-50 text-[11px] uppercase tracking-wider text-ink-500">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Nama Sarana Pengangkut</th>
                                <th class="px-4 py-3 text-left font-semibold">Tgl Tiba / Berangkat</th>
                                <th class="px-4 py-3 text-left font-semibold">Kantor Pabean</th>
                                <th class="px-4 py-3 text-left font-semibold">No. Voyage</th>
                                <th class="px-4 py-3 text-left font-semibold">No. IMO</th>
                                <th class="px-4 py-3 text-left font-semibold">No. Daftar</th>
                                <th class="px-4 py-3 text-left font-semibold">Jenis</th>
                                <th class="px-4 py-3 text-left font-semibold">Tgl Daftar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cream-100">
                            @forelse ($manifests as $m)
                                <tr class="hover:bg-cream-50/60">
                                    <td class="px-4 py-3 font-medium text-ink-900">{{ $m->nama_sarana ?: '—' }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ optional($m->tanggal_sarana)->format('d-m-Y') ?: '—' }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $m->kantorPabeanLabel() ?: '—' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-ink-700">{{ $m->nomor_voyage ?: '—' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-ink-700">{{ $m->nomor_imo ?: '—' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-ink-700">{{ $m->nomor_daftar ?: '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $m->jenis_manifes === 'outward' ? 'bg-gold-100 text-gold-800' : 'bg-sea-100 text-sea-800' }}">
                                            {{ $m->jenisLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-ink-600">{{ optional($m->tanggal_daftar)->format('d-m-Y') ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-16 text-center">
                                        <p class="font-display text-lg text-ink-700">Belum ada data manifes</p>
                                        <p class="mt-1 text-sm text-ink-400">Klik “Tarik dari CEISA” untuk mengambil data manifes kedatangan/keberangkatan.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($manifests->hasPages())
                    <div class="px-5 py-3 border-t border-cream-200">{{ $manifests->links() }}</div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
