<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Dokumen</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('documents.export', request()->query()) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50 shadow-sm transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                    Export CSV
                </a>
                <a href="{{ route('documents.create') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    + Buat Dokumen
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">
            <x-flash />

            {{-- Rekap (mengikuti filter aktif) --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach ([
                    ['Total (terfilter)', $rekap['total'], 'text-slate-900'],
                    ['Diterima', $rekap['accepted'], 'text-emerald-600'],
                    ['Ditolak/Error', $rekap['rejected'], 'text-rose-600'],
                    ['Jalur Merah', $rekap['merah'], 'text-rose-600'],
                ] as [$label, $value, $color])
                    <div class="bg-white shadow-sm rounded-xl p-4 border border-slate-100">
                        <div class="text-xs text-slate-500">{{ $label }}</div>
                        <div class="text-2xl font-bold {{ $color }}">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Filter bar --}}
            <form method="GET" action="{{ route('documents.index') }}"
                  class="bg-white shadow-sm rounded-xl p-4 border border-slate-100">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Cari No. Aju / Pendaftaran</label>
                        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Ketik nomor…"
                               class="block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Jenis</label>
                        <select name="doc_type" class="block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                            <option value="">Semua</option>
                            @foreach ($docTypes as $code => $label)
                                <option value="{{ $code }}" @selected($filters['doc_type'] === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Status</label>
                        <select name="status" class="block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                            <option value="">Semua</option>
                            @foreach (['draft' => 'Draft', 'submitting' => 'Mengirim', 'submitted' => 'Terkirim', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', 'error' => 'Error'] as $val => $label)
                                <option value="{{ $val }}" @selected($filters['status'] === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Jalur</label>
                        <select name="jalur" class="block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                            <option value="">Semua</option>
                            <option value="H" @selected($filters['jalur'] === 'H')>Hijau</option>
                            <option value="K" @selected($filters['jalur'] === 'K')>Kuning</option>
                            <option value="M" @selected($filters['jalur'] === 'M')>Merah</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Sumber</label>
                        <select name="source" class="block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                            <option value="">Semua</option>
                            <option value="h2h" @selected($filters['source'] === 'h2h')>H2H</option>
                            <option value="arsip" @selected($filters['source'] === 'arsip')>Arsip</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Dari Tanggal</label>
                        <input type="date" name="from" value="{{ $filters['from'] }}"
                               class="block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Sampai Tanggal</label>
                        <input type="date" name="to" value="{{ $filters['to'] }}"
                               class="block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                    </div>
                    <div class="flex items-end gap-2 lg:col-span-2">
                        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                            Terapkan
                        </button>
                        <a href="{{ route('documents.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-semibold text-slate-500 hover:text-slate-800">
                            Reset
                        </a>
                    </div>
                </div>
            </form>

            {{-- Tabel --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-100">
                <div class="px-4 py-3 border-b border-slate-100 text-xs text-slate-500">
                    Menampilkan <span class="font-bold text-slate-700">{{ $documents->total() }}</span> dokumen
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-gray-500">
                                <th class="px-4 py-3">No. Aju</th>
                                <th class="px-4 py-3">Jenis</th>
                                <th class="px-4 py-3">Pihak / Entitas</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Dibuat</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($documents as $doc)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-gray-700">
                                        {{ $doc->nomor_aju ?? '—' }}
                                        @if ($doc->isArchived())
                                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-500 border border-slate-200 uppercase tracking-wide">Arsip</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-700">{{ $doc->doc_type }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-900 leading-snug">{{ $doc->partyName() ?? '—' }}</div>
                                        @if ($doc->partyNpwp())
                                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">NPWP: {{ $doc->partyNpwp() }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <x-status-badge :status="$doc->status" />
                                            <x-jalur-badge :jalur="$doc->jalur" />
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('documents.show', $doc) }}" class="text-indigo-600 hover:underline font-medium">Detail</a>
                                        @unless ($doc->isArchived())
                                            <form method="POST" action="{{ route('documents.duplicate', $doc) }}" class="inline ml-3">
                                                @csrf
                                                <button type="submit" class="text-slate-500 hover:text-slate-800 font-medium"
                                                        onclick="return confirm('Duplikasi dokumen ini sebagai draft baru?')">Duplikasi</button>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                                        Tidak ada dokumen yang cocok dengan filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $documents->links() }}</div>
        </div>
    </div>
</x-app-layout>
