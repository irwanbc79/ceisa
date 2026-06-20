@props(['document'])

@php
    $timeline = $document->statusTimeline();
    $responses = $document->responseHistory();
    $petugas = $document->petugasHistory();
@endphp

{{-- Pelacakan dokumen ala Portal CEISA 4.0: Riwayat Status / Respon / Petugas --}}
<div x-data="{ tab: 'status' }" class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100/90 transition-all duration-300 hover:shadow-md">

    <div class="flex items-center gap-2 pb-4 mb-4 border-b border-slate-100">
        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
        </svg>
        <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider">Pelacakan Dokumen (CCTV)</h3>
    </div>

    {{-- Tab headers --}}
    <div class="flex flex-wrap items-center gap-1.5 mb-5">
        <button type="button" @click="tab='status'"
                :class="tab==='status' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-700'"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-[11px] font-extrabold uppercase tracking-wide transition-all cursor-pointer">
            Riwayat Status
            <span :class="tab==='status' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-500'" class="px-1.5 py-0.5 rounded-md text-[9px]">{{ count($timeline) }}</span>
        </button>
        <button type="button" @click="tab='respon'"
                :class="tab==='respon' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-700'"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-[11px] font-extrabold uppercase tracking-wide transition-all cursor-pointer">
            Riwayat Respon
            <span :class="tab==='respon' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-500'" class="px-1.5 py-0.5 rounded-md text-[9px]">{{ count($responses) }}</span>
        </button>
        <button type="button" @click="tab='petugas'"
                :class="tab==='petugas' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-700'"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-[11px] font-extrabold uppercase tracking-wide transition-all cursor-pointer">
            Riwayat Petugas
            <span :class="tab==='petugas' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-500'" class="px-1.5 py-0.5 rounded-md text-[9px]">{{ count($petugas) }}</span>
        </button>
    </div>

    {{-- ====== Riwayat Status (timeline tahapan pabean) ====== --}}
    <div x-show="tab==='status'" x-cloak>
        @if (count($timeline) > 0)
            <div class="flow-root">
                <ul role="list" class="-mb-6">
                    @foreach ($timeline as $idx => $stage)
                        <li>
                            <div class="relative pb-6">
                                @if ($idx < count($timeline) - 1)
                                    <span class="absolute left-3.5 top-4 -ml-px h-full w-0.5 bg-gradient-to-b from-indigo-200 to-slate-100" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex items-start gap-3">
                                    <span class="h-7 w-7 rounded-full bg-indigo-50 border-2 border-indigo-500 flex items-center justify-center ring-4 ring-white shrink-0">
                                        <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                    </span>
                                    <div class="flex min-w-0 flex-1 justify-between gap-3 pt-0.5">
                                        <div class="min-w-0">
                                            <p class="text-[13px] font-bold text-slate-800 leading-snug">{{ $stage['label'] }}</p>
                                            <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide mt-0.5">
                                                Diperbarui oleh:
                                                <span class="text-slate-500">{{ $stage['actor'] }}</span>
                                            </p>
                                        </div>
                                        <div class="whitespace-nowrap text-right text-[10px] text-slate-400 font-bold">
                                            @if ($stage['time'])
                                                <time>{{ $stage['time']->translatedFormat('d M Y') }}</time>
                                                <div class="text-slate-300">{{ $stage['time']->format('H:i') }}</div>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <x-tracking-empty pesan="Belum ada riwayat status untuk dokumen ini." />
        @endif
    </div>

    {{-- ====== Riwayat Respon (SPPB / BILLING / NPE) ====== --}}
    <div x-show="tab==='respon'" x-cloak>
        @if (count($responses) > 0)
            <div class="space-y-2.5">
                @foreach ($responses as $resp)
                    @php
                        $isSppb = $resp['nama'] === 'SPPB';
                        $isTolak = str_contains($resp['nama'], 'TOLAK') || str_contains($resp['nama'], 'NPP') || $resp['nama'] === 'PENOLAKAN';
                        // Class statik penuh (jangan interpolasi — Tailwind v4 tak meng-scan class dinamis)
                        $iconWrap = $isSppb ? 'bg-emerald-50 border-emerald-100' : ($isTolak ? 'bg-rose-50 border-rose-100' : 'bg-sky-50 border-sky-100');
                        $iconColor = $isSppb ? 'text-emerald-600' : ($isTolak ? 'text-rose-600' : 'text-sky-600');
                    @endphp
                    <div class="flex items-center justify-between gap-3 p-3.5 rounded-2xl border border-slate-100 bg-slate-50/60 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="h-9 w-9 rounded-xl border flex items-center justify-center shrink-0 {{ $iconWrap }}">
                                <svg class="h-4 w-4 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[13px] font-extrabold text-slate-800">{{ $resp['nama'] }}</p>
                                @if ($resp['no_surat'])
                                    <p class="text-[10px] text-slate-400 font-mono truncate">No. Surat: {{ $resp['no_surat'] }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right text-[10px] text-slate-400 font-bold whitespace-nowrap">
                            @if ($resp['tanggal'])
                                <time>{{ $resp['tanggal']->translatedFormat('d M Y') }}</time>
                                <div class="text-slate-300">{{ $resp['tanggal']->format('H:i') }}</div>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <x-tracking-empty pesan="Belum ada respon DJBC (SPPB/BILLING) yang diterima." />
        @endif
    </div>

    {{-- ====== Riwayat Petugas ====== --}}
    <div x-show="tab==='petugas'" x-cloak>
        @if (count($petugas) > 0)
            <div class="overflow-hidden rounded-2xl border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead class="bg-slate-50/70 text-left text-[10px] font-black text-slate-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">ID Petugas</th>
                            <th class="px-4 py-3">Kegiatan</th>
                            <th class="px-4 py-3 text-right">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($petugas as $p)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3 font-mono font-bold text-slate-700">{{ $p['petugas'] }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $p['kegiatan'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-400 font-semibold whitespace-nowrap">
                                    {{ $p['waktu']?->translatedFormat('d M Y H:i') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-tracking-empty pesan="Belum ada penanganan oleh petugas Bea Cukai (otomatis SYSTEM)." />
        @endif
    </div>
</div>
