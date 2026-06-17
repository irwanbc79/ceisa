<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-900 leading-tight tracking-tight">Daftar Dokumen</h2>
                <p class="text-xs text-slate-500 mt-1 font-medium">Lihat, cari, saring, dan ekspor daftar rekapitulasi dokumen pabean Bea Cukai Anda.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('documents.export', request()->query()) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 bg-white border border-slate-200/80 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-50 hover:text-slate-900 shadow-sm transition-all duration-200 hover:-translate-y-0.5">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                    Ekspor CSV
                </a>
                <a href="{{ route('documents.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white text-xs font-extrabold rounded-xl hover:from-indigo-700 hover:to-indigo-800 shadow-md shadow-indigo-200 hover:shadow-indigo-300 transition-all duration-200 hover:-translate-y-0.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Buat Dokumen
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6" x-data="{}">
            <x-flash />

            {{-- Rekap (mengikuti filter aktif) --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Terfilter -->
                <div class="bg-white border border-slate-200/60 border-l-4 border-l-slate-600 shadow-sm shadow-slate-100/40 rounded-2xl p-4 relative overflow-hidden group hover:shadow-md transition-all duration-300">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total (Terfilter)</div>
                    <div class="text-2xl font-extrabold text-slate-800 mt-1 tracking-tight">{{ $rekap['total'] }}</div>
                </div>
                <!-- Diterima -->
                <div class="bg-white border border-slate-200/60 border-l-4 border-l-emerald-500 shadow-sm shadow-slate-100/40 rounded-2xl p-4 relative overflow-hidden group hover:shadow-md transition-all duration-300">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Diterima</div>
                    <div class="text-2xl font-extrabold text-emerald-600 mt-1 tracking-tight">{{ $rekap['accepted'] }}</div>
                </div>
                <!-- Ditolak/Error -->
                <div class="bg-white border border-slate-200/60 border-l-4 border-l-rose-500 shadow-sm shadow-slate-100/40 rounded-2xl p-4 relative overflow-hidden group hover:shadow-md transition-all duration-300">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ditolak / Error</div>
                    <div class="text-2xl font-extrabold text-rose-600 mt-1 tracking-tight">{{ $rekap['rejected'] }}</div>
                </div>
                <!-- Jalur Merah -->
                <div class="bg-white border border-slate-200/60 border-l-4 border-l-red-600 shadow-sm shadow-slate-100/40 rounded-2xl p-4 relative overflow-hidden group hover:shadow-md transition-all duration-300">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jalur Merah</div>
                    <div class="text-2xl font-extrabold text-red-600 mt-1 tracking-tight">{{ $rekap['merah'] }}</div>
                </div>
            </div>

            {{-- Filter bar --}}
            <form method="GET" action="{{ route('documents.index') }}"
                  class="bg-white border border-slate-200/70 rounded-2xl shadow-sm p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-4">
                    <div class="lg:col-span-2">
                         <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Cari Nomor Aju / Daftar</label>
                         <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Ketik nomor…"
                                class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Jenis</label>
                        <select name="doc_type" class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm font-medium">
                            <option value="">Semua</option>
                            @foreach ($docTypes as $code => $label)
                                <option value="{{ $code }}" @selected($filters['doc_type'] === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Status</label>
                        <select name="status" class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm font-medium">
                            <option value="">Semua</option>
                            @foreach (['draft' => 'Draft', 'submitting' => 'Mengirim', 'submitted' => 'Terkirim', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', 'error' => 'Error'] as $val => $label)
                                <option value="{{ $val }}" @selected($filters['status'] === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Jalur</label>
                        <select name="jalur" class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm font-medium">
                            <option value="">Semua</option>
                            <option value="H" @selected($filters['jalur'] === 'H')>Hijau</option>
                            <option value="K" @selected($filters['jalur'] === 'K')>Kuning</option>
                            <option value="M" @selected($filters['jalur'] === 'M')>Merah</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Sumber</label>
                        <select name="source" class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm font-medium">
                            <option value="">Semua</option>
                            <option value="h2h" @selected($filters['source'] === 'h2h')>H2H</option>
                            <option value="arsip" @selected($filters['source'] === 'arsip')>Arsip</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold rounded-xl shadow-sm transition-colors cursor-pointer">
                            Terapkan
                        </button>
                        <a href="{{ route('documents.index') }}" class="inline-flex items-center justify-center px-3 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
                            Reset
                        </a>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 border-t border-slate-100 pt-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                        <input type="date" name="from" value="{{ $filters['from'] }}"
                               class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
                        <input type="date" name="to" value="{{ $filters['to'] }}"
                               class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 text-sm shadow-sm" />
                    </div>
                </div>
            </form>

            {{-- Tabel --}}
            <div class="bg-white border border-slate-200/70 rounded-2xl shadow-sm overflow-hidden mb-6">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="font-extrabold text-slate-800 text-sm">Dokumen Terfilter</h3>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Menampilkan <span class="font-bold text-slate-700">{{ $documents->total() }}</span> dokumen hasil penyaringan.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70">
                            <tr class="text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <th class="px-6 py-4">No. Aju</th>
                                <th class="px-6 py-4">Jenis Dokumen</th>
                                <th class="px-6 py-4">Pihak / Entitas</th>
                                <th class="px-6 py-4">Status & Jalur</th>
                                <th class="px-6 py-4">Tanggal Dibuat</th>
                                <th class="px-6 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($documents as $doc)
                                <tr class="hover:bg-slate-50/60 transition-colors duration-150">
                                    <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-600">
                                        <div class="flex items-center gap-1.5">
                                            @if ($doc->nomor_aju)
                                                <span>{{ $doc->nomor_aju }}</span>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                            @if ($doc->isArchived())
                                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-500 border border-slate-200 uppercase tracking-wide">Arsip</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($doc->doc_type === 'BC30')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100/60 shadow-sm">BC 3.0 (Ekspor)</span>
                                        @elseif($doc->doc_type === 'BC20')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100/60 shadow-sm">BC 2.0 (Impor)</span>
                                        @elseif($doc->doc_type === 'BC24')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100/60 shadow-sm">BC 2.4 (Impor TPB)</span>
                                        @elseif($doc->doc_type === 'TPB')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700 border border-purple-100/60 shadow-sm">Portal TPB</span>
                                        @elseif($doc->doc_type === 'RUSH')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 border border-rose-100/60 shadow-sm">Rush Handling</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-50 text-slate-700 border border-slate-150">{{ $doc->doc_type }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-800 leading-snug">{{ $doc->partyName() ?? '—' }}</div>
                                        @if ($doc->partyNpwp())
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5 bg-slate-50 border border-slate-100 px-1.5 py-0.5 rounded-md inline-block">NPWP: {{ $doc->partyNpwp() }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <x-status-badge :status="$doc->status" />
                                            <x-jalur-badge :jalur="$doc->jalur" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-medium text-slate-400 whitespace-nowrap">
                                        {{ $doc->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap text-xs font-semibold">
                                        <div class="flex items-center justify-end gap-3.5">
                                            <button type="button"
                                                    @click="$dispatch('quick-view', @js($doc->quickViewData()))"
                                                    class="text-indigo-600 hover:text-indigo-950 inline-flex items-center gap-1 hover:underline transition-colors cursor-pointer">
                                                Detail
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                            </button>
                                            @if ($doc->isEditable())
                                                <a href="{{ route('documents.edit', $doc) }}"
                                                   class="text-slate-400 hover:text-amber-600 inline-flex items-center gap-1 transition-colors" title="Ubah dokumen">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                                                    Ubah
                                                </a>
                                            @endif
                                            @unless ($doc->isArchived())
                                                <form method="POST" action="{{ route('documents.duplicate', $doc) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-slate-400 hover:text-slate-700 inline-flex items-center gap-1 transition-colors cursor-pointer"
                                                            onclick="return confirm('Duplikasi dokumen ini sebagai draft baru?')">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                                                        </svg>
                                                        Duplikasi
                                                    </button>
                                                </form>
                                            @endunless
                                            @if ($doc->canBeDeleted())
                                                <form method="POST" action="{{ route('documents.destroy', $doc) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-slate-400 hover:text-rose-600 inline-flex items-center gap-1 transition-colors cursor-pointer"
                                                            onclick="return confirm('Hapus dokumen ini secara permanen?')" title="Hapus dokumen">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                        Tidak ada dokumen yang cocok dengan saringan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $documents->links() }}</div>

            <x-doc-quick-view-modal />
        </div>
    </div>
</x-app-layout>
