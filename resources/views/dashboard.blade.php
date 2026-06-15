<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('documents.lookup') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50 shadow-sm transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803m10.607.197a7.5 7.5 0 0 1-10.607 0" /></svg>
                    Cek Status Dokumen
                </a>
                <a href="{{ route('documents.create') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    + Buat Dokumen
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-flash />

            @unless ($hasCredential)
                <div class="mb-4 rounded-md bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-800">
                    Kredensial CEISA belum diatur.
                    <a href="{{ route('settings.ceisa.edit') }}" class="font-semibold underline">Atur sekarang</a>
                    sebelum membuat dokumen.
                </div>
            @endunless

            {{-- Statistik --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                @foreach ([
                    ['Total Dokumen', $stats['total'], 'text-gray-900'],
                    ['Terkirim', $stats['submitted'], 'text-blue-600'],
                    ['Diterima', $stats['accepted'], 'text-green-600'],
                    ['Ditolak/Error', $stats['rejected'], 'text-red-600'],
                ] as [$label, $value, $color])
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <div class="text-sm text-gray-500">{{ $label }}</div>
                        <div class="text-2xl font-bold {{ $color }}">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Tabel dokumen --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-gray-500">
                                <th class="px-4 py-3">No. Aju</th>
                                <th class="px-4 py-3">Jenis</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Dibuat</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($documents as $doc)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-gray-700">{{ $doc->nomor_aju ?? '—' }}</td>
                                    <td class="px-4 py-3 font-semibold">
                                        @if($doc->doc_type === 'BC30')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 shadow-sm">BC 3.0 (Ekspor)</span>
                                        @elseif($doc->doc_type === 'BC20')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 shadow-sm">BC 2.0 (Impor)</span>
                                        @elseif($doc->doc_type === 'BC24')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100 shadow-sm">BC 2.4 (Impor TPB)</span>
                                        @elseif($doc->doc_type === 'TPB')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700 border border-purple-100 shadow-sm">Portal TPB</span>
                                        @elseif($doc->doc_type === 'RUSH')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 border border-rose-100 shadow-sm">Rush Handling</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-50 text-slate-700 border border-slate-100">{{ $doc->doc_type }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3"><x-status-badge :status="$doc->status" /></td>
                                    <td class="px-4 py-3 text-gray-500">{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('documents.show', $doc) }}" class="text-indigo-600 hover:underline">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                        Belum ada dokumen. <a href="{{ route('documents.create') }}" class="text-indigo-600 underline">Buat dokumen pertama</a>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $documents->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
