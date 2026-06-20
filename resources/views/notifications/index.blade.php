<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-900 leading-tight tracking-tight">Pusat Notifikasi DJBC</h2>
                <p class="text-xs text-slate-500 mt-1 font-medium">Respon dokumen, pembaruan formulir, dan pengumuman sistem dari Bea Cukai (CEISA H2H).</p>
            </div>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">&larr; Dashboard</a>
        </div>
    </x-slot>

    @php
        $tabs = [
            'Respon' => ['label' => 'Respon', 'desc' => 'Respon dokumen: SPPB, NPE, penolakan, jalur pemeriksaan.', 'color' => 'indigo'],
            'Formulir' => ['label' => 'Formulir', 'desc' => 'Pembaruan formulir / data dokumen.', 'color' => 'amber'],
            'Informasi' => ['label' => 'Informasi', 'desc' => 'Pengumuman & informasi sistem CEISA.', 'color' => 'slate'],
        ];
    @endphp

    <div class="py-10" x-data="{ tab: 'Respon' }">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash />

            {{-- Tab bar --}}
            <div class="bg-white border border-slate-200/70 rounded-2xl shadow-sm overflow-hidden">
                <div class="flex border-b border-slate-100">
                    @foreach ($tabs as $key => $tab)
                        <button type="button" @click="tab = '{{ $key }}'"
                                class="flex-1 px-4 py-4 text-sm font-bold transition-colors relative focus:outline-none"
                                :class="tab === '{{ $key }}' ? 'text-indigo-700 bg-indigo-50/40' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50'">
                            <span class="inline-flex items-center gap-2 justify-center">
                                {{ $tab['label'] }}
                                <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full text-[10px] font-extrabold
                                    {{ ($counts[$key] ?? 0) > 0 ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-400' }}">
                                    {{ $counts[$key] ?? 0 }}
                                </span>
                            </span>
                            <span class="absolute bottom-0 inset-x-0 h-0.5 bg-indigo-600 transition-opacity"
                                  :class="tab === '{{ $key }}' ? 'opacity-100' : 'opacity-0'"></span>
                        </button>
                    @endforeach
                </div>

                @foreach ($tabs as $key => $tab)
                    <div x-show="tab === '{{ $key }}'" x-cloak>
                        <div class="px-5 py-3 bg-slate-50/60 border-b border-slate-100">
                            <p class="text-[11px] text-slate-500 font-medium">{{ $tab['desc'] }}</p>
                        </div>

                        <ul class="divide-y divide-slate-100">
                            @forelse ($grouped[$key] as $log)
                                <li class="px-5 py-4 hover:bg-slate-50/50 transition-colors">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-bold text-slate-800 text-sm">{{ $log->event ?? 'Notifikasi' }}</span>
                                                @if ($log->document)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">{{ $log->document->doc_type }}</span>
                                                @endif
                                            </div>
                                            @if ($log->nomor_aju)
                                                <p class="text-xs text-slate-500 font-mono mt-1 break-all">No. Aju: {{ $log->nomor_aju }}</p>
                                            @endif
                                            @php $msg = data_get($log->payload, 'message') ?? data_get($log->payload, 'keterangan') ?? data_get($log->payload, 'status'); @endphp
                                            @if ($msg)
                                                <p class="text-xs text-slate-600 mt-1">{{ \Illuminate\Support\Str::limit((string) $msg, 160) }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="text-[11px] text-slate-400 font-medium whitespace-nowrap">{{ optional($log->received_at)->format('d/m/Y H:i') }}</p>
                                            @if ($log->document)
                                                <a href="{{ route('documents.show', $log->document) }}" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline mt-1 inline-block">Lihat dokumen &rarr;</a>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="px-5 py-12 text-center">
                                    <svg class="h-8 w-8 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                    </svg>
                                    <p class="text-xs font-semibold text-slate-400 mt-2">Belum ada notifikasi {{ strtolower($tab['label']) }}.</p>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                @endforeach
            </div>

            <p class="text-[11px] text-slate-400 mt-4 text-center">
                Notifikasi dikirim otomatis oleh DJBC ke webhook H2H yang terdaftar di My Profile CEISA.
            </p>
        </div>
    </div>
</x-app-layout>
