<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 tracking-tight">
                    Rincian Dokumen <span class="text-indigo-600 font-extrabold" x-text="'{{ $document->doc_type }}'"></span>
                    <span class="text-slate-400 font-normal text-sm ml-2">ID #{{ $document->id }}</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">Status pengiriman ke Bea Cukai Indonesia</p>
            </div>
            <div class="flex items-center gap-3">
                <x-status-badge :status="$document->status" />
                <x-jalur-badge :jalur="$document->jalur" class="px-2.5 py-1 text-xs" />
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            {{-- 1. Status Ringkasan & Aksi --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col md:flex-row justify-between gap-6">
                <dl class="grid grid-cols-2 md:grid-cols-5 gap-6 text-sm grow">
                    <div>
                        <dt class="text-slate-400 font-semibold uppercase text-[10px]">Pihak / Entitas Utama</dt>
                        <dd class="text-slate-800 font-bold mt-1 text-sm leading-tight">
                            {{ $document->partyName() ?? '—' }}
                            @if ($document->partyNpwp())
                                <span class="block text-[11px] text-slate-400 font-mono font-normal mt-0.5">NPWP: {{ $document->partyNpwp() }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-semibold uppercase text-[10px]">Nomor Aju</dt>
                        <dd class="font-mono text-slate-800 font-bold mt-1 text-sm">{{ $document->nomor_aju ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-semibold uppercase text-[10px]">Nomor Pendaftaran</dt>
                        <dd class="font-mono text-slate-800 font-bold mt-1 text-sm">{{ $document->nomor_daftar ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-semibold uppercase text-[10px]">Disubmit Pada</dt>
                        <dd class="text-slate-600 font-medium mt-1">{{ $document->submitted_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-semibold uppercase text-[10px]">Terakhir Diupdate</dt>
                        <dd class="text-slate-600 font-medium mt-1">{{ $document->response_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>

                <div class="flex items-center justify-end shrink-0">
                    @if ($document->error_message)
                        <div class="rounded-xl bg-rose-50 border border-rose-100 p-3 text-xs text-rose-800 mr-4 max-w-xs">
                            <span class="font-bold">Error:</span> {{ $document->error_message }}
                        </div>
                    @endif

                    @if (in_array($document->status, ['draft', 'error']))
                        <form method="POST" action="{{ route('documents.submit', $document) }}">
                            @csrf
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-100 transition-all">
                                {{ $document->status === 'draft' ? 'Kirim ke CEISA' : 'Kirim Ulang ke CEISA' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- 2. Structured Document Content --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800 text-sm">Informasi Dokumen Terstruktur</h3>
                </div>
                
                <div class="p-6 space-y-8">
                    @if ($document->isArchived())
                        {{-- Panel data arsip (rekam manual dokumen lama DJBC) --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-600 uppercase tracking-wide">Arsip Manual</span>
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Data Dokumen Lama</h4>
                            </div>
                            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                                <div><dt class="text-slate-400 text-[10px] uppercase font-bold">Perusahaan</dt><dd class="font-bold text-slate-800">{{ data_get($document->payload, 'nama_perusahaan') ?? '—' }}</dd></div>
                                <div><dt class="text-slate-400 text-[10px] uppercase font-bold">NPWP</dt><dd class="font-mono text-slate-700">{{ data_get($document->payload, 'npwp') ?? '—' }}</dd></div>
                                <div><dt class="text-slate-400 text-[10px] uppercase font-bold">Kantor Pabean</dt><dd class="font-semibold text-slate-700">{{ data_get($document->payload, 'kantor_pabean') ?? '—' }}</dd></div>
                                <div><dt class="text-slate-400 text-[10px] uppercase font-bold">Tanggal Dokumen</dt><dd class="font-semibold text-slate-700">{{ data_get($document->payload, 'tanggal_dokumen') ?? '—' }}</dd></div>
                                <div><dt class="text-slate-400 text-[10px] uppercase font-bold">Nilai</dt><dd class="font-bold text-indigo-700">{{ data_get($document->payload, 'nilai') !== null ? number_format(data_get($document->payload, 'nilai'), 2, ',', '.').' '.(data_get($document->payload, 'valuta') ?? '') : '—' }}</dd></div>
                                <div><dt class="text-slate-400 text-[10px] uppercase font-bold">Uraian</dt><dd class="text-slate-700">{{ data_get($document->payload, 'uraian') ?? '—' }}</dd></div>
                                @if (data_get($document->payload, 'keterangan'))
                                    <div class="sm:col-span-2"><dt class="text-slate-400 text-[10px] uppercase font-bold">Keterangan</dt><dd class="text-slate-700">{{ data_get($document->payload, 'keterangan') }}</dd></div>
                                @endif
                            </dl>
                        </div>
                    @endif

                    {{-- Header Row: Parties/Entities & Transport/Values --}}
                    <div class="grid md:grid-cols-2 gap-8" @if ($document->isArchived()) style="display:none" @endif>

                        {{-- Left Column: Entities (Importir/Eksportir dll) --}}
                        <div class="space-y-4">
                            @if ($document->doc_type === 'BC30')
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Eksportir</h4>
                                    <div class="text-sm bg-slate-50 p-4 rounded-xl space-y-1">
                                        <p class="font-bold text-slate-800">{{ data_get($document->payload, 'header.eksportir.nama') }}</p>
                                        <p class="text-xs text-slate-500 font-mono">NPWP: {{ data_get($document->payload, 'header.eksportir.npwp') }}</p>
                                        <p class="text-xs text-slate-600 mt-1">{{ data_get($document->payload, 'header.eksportir.alamat') }}</p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Penerima (Consignee)</h4>
                                    <div class="text-sm bg-slate-50 p-4 rounded-xl">
                                        <p class="font-bold text-slate-800">{{ data_get($document->payload, 'header.penerima.nama') }}</p>
                                        <p class="text-xs text-slate-500 mt-1">Negara Tujuan: <span class="font-bold text-slate-700">{{ data_get($document->payload, 'header.penerima.negara') }}</span></p>
                                    </div>
                                </div>
                            @elseif ($document->doc_type === 'BC20' || $document->doc_type === 'BC24')
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Importir</h4>
                                    <div class="text-sm bg-slate-50 p-4 rounded-xl space-y-1">
                                        <p class="font-bold text-slate-800">{{ data_get($document->payload, 'header.importir.nama') }}</p>
                                        <p class="text-xs text-slate-500 font-mono">NPWP: {{ data_get($document->payload, 'header.importir.npwp') }}</p>
                                        <p class="text-xs text-slate-600 mt-1">{{ data_get($document->payload, 'header.importir.alamat') }}</p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pemasok (Supplier)</h4>
                                    <div class="text-sm bg-slate-50 p-4 rounded-xl">
                                        <p class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pemasok.nama') }}</p>
                                        <p class="text-xs text-slate-500 mt-1">Negara Pemasok: <span class="font-bold text-slate-700">{{ data_get($document->payload, 'header.pemasok.negara') }}</span></p>
                                    </div>
                                </div>
                            @elseif ($document->doc_type === 'TPB')
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pengusaha TPB</h4>
                                    <div class="text-sm bg-slate-50 p-4 rounded-xl space-y-1">
                                        <p class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pengusaha_tpb.nama') }}</p>
                                        <p class="text-xs text-slate-500 font-mono">NPWP: {{ data_get($document->payload, 'header.pengusaha_tpb.npwp') }}</p>
                                        <p class="text-xs text-slate-600 mt-1">{{ data_get($document->payload, 'header.pengusaha_tpb.alamat') }}</p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Fasilitas TPB</h4>
                                    <div class="text-sm bg-slate-50 p-4 rounded-xl space-y-1">
                                        <p class="text-xs text-slate-600">Jenis: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.jenis_tpb') }}</span></p>
                                        <p class="text-xs text-slate-600">Tujuan: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.tujuan_pengiriman') }}</span></p>
                                        <p class="text-xs text-slate-600">No. Kontrak: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.dokumen_referensi') }}</span></p>
                                    </div>
                                </div>
                            @elseif ($document->doc_type === 'RUSH')
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pemohon Rush Handling</h4>
                                    <div class="text-sm bg-slate-50 p-4 rounded-xl space-y-1">
                                        <p class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pemohon.nama') }}</p>
                                        <p class="text-xs text-slate-500 font-mono">NPWP: {{ data_get($document->payload, 'header.pemohon.npwp') }}</p>
                                        <p class="text-xs text-slate-600 mt-1">{{ data_get($document->payload, 'header.pemohon.alamat') }}</p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Alasan Pengeluaran Segera</h4>
                                    <div class="text-sm bg-rose-50 border border-rose-100 p-4 rounded-xl text-rose-900">
                                        <p class="font-bold text-xs">Kebutuhan Mendadak:</p>
                                        <p class="mt-1 font-semibold text-sm">{{ data_get($document->payload, 'header.alasan_rush_handling') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Right Column: Logistics & Transaction --}}
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Logistik &amp; Pengangkutan</h4>
                                <div class="text-sm bg-slate-50 p-4 rounded-xl space-y-2">
                                    @if ($document->doc_type === 'RUSH')
                                        <p class="text-xs text-slate-600">Sarana Pengangkut: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pengangkutan.sarana') }} ({{ data_get($document->payload, 'header.pengangkutan.flight_no') }})</span></p>
                                        <p class="text-xs text-slate-600">No. AWB / BL: <span class="font-mono font-bold text-slate-800">{{ data_get($document->payload, 'header.dokumen_pengangkutan.awb_bl') }}</span></p>
                                        <p class="text-xs text-slate-600">Tanggal AWB: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.dokumen_pengangkutan.tanggal') }}</span></p>
                                        <p class="text-xs text-slate-600">Kemasan: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.kemasan.jumlah') }} {{ data_get($document->payload, 'header.kemasan.jenis') }}</span></p>
                                    @elseif ($document->doc_type === 'BC30')
                                        <p class="text-xs text-slate-600">Pelabuhan Muat: <span class="font-bold text-slate-800 font-mono">{{ data_get($document->payload, 'header.pengangkutan.pelabuhan_muat') }}</span></p>
                                        <p class="text-xs text-slate-600">Pelabuhan Tujuan: <span class="font-bold text-slate-800 font-mono">{{ data_get($document->payload, 'header.pengangkutan.pelabuhan_tujuan') ?? '—' }}</span></p>
                                        <p class="text-xs text-slate-600">Cara Angkut: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pengangkutan.cara_angkut') ?? '—' }}</span></p>
                                        <p class="text-xs text-slate-600">Sarana / Voy: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pengangkutan.sarana_angkut') ?? '—' }} {{ data_get($document->payload, 'header.pengangkutan.voy_flight') ? '· '.data_get($document->payload, 'header.pengangkutan.voy_flight') : '' }}</span></p>
                                        <p class="text-xs text-slate-600">Perkiraan Ekspor: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pengangkutan.tanggal_ekspor') ?? '—' }}</span></p>
                                    @else
                                        <p class="text-xs text-slate-600">Pelabuhan Muat: <span class="font-bold text-slate-800 font-mono">{{ data_get($document->payload, 'header.pengangkutan.pelabuhan_muat') }}</span></p>
                                        <p class="text-xs text-slate-600">Pelabuhan Bongkar: <span class="font-bold text-slate-800 font-mono">{{ data_get($document->payload, 'header.pengangkutan.pelabuhan_bongkar') ?? '—' }}</span></p>
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Mata Uang &amp; Nilai</h4>
                                <div class="text-sm bg-indigo-50/50 border border-indigo-100 p-4 rounded-xl space-y-1">
                                    <p class="text-xs text-slate-500">Mata Uang Transaksi: <span class="font-bold text-slate-700">{{ data_get($document->payload, 'header.valuta') ?? 'USD' }}</span></p>
                                    <div class="mt-2">
                                        @if ($document->doc_type === 'BC30')
                                            <span class="text-xs text-slate-500 block">Nilai FOB Total:</span>
                                            <span class="text-lg font-bold text-indigo-700">{{ number_format(data_get($document->payload, 'header.nilai_fob', 0), 2, ',', '.') }} {{ data_get($document->payload, 'header.valuta') ?? 'USD' }}</span>
                                        @elseif ($document->doc_type === 'BC20' || $document->doc_type === 'BC24')
                                            <span class="text-xs text-slate-500 block">Nilai CIF Total:</span>
                                            <span class="text-lg font-bold text-indigo-700">{{ number_format(data_get($document->payload, 'header.nilai_cif', 0), 2, ',', '.') }} {{ data_get($document->payload, 'header.valuta') ?? 'USD' }}</span>
                                        @else
                                            <span class="text-xs text-slate-500 block">Nilai Total Barang:</span>
                                            <span class="text-lg font-bold text-indigo-700">{{ number_format(data_get($document->payload, 'header.nilai_barang', 0), 2, ',', '.') }} {{ data_get($document->payload, 'header.valuta') ?? 'USD' }}</span>
                                        @endif
                                    </div>
                                    @if (data_get($document->payload, 'header.cara_pembayaran'))
                                        <p class="text-[11px] text-slate-500 mt-2 border-t border-indigo-100/50 pt-1">Metode Pembayaran: <span class="font-semibold text-slate-700">{{ data_get($document->payload, 'header.cara_pembayaran') }}</span></p>
                                    @endif
                                    @if ($document->doc_type === 'BC30')
                                        <div class="mt-2 border-t border-indigo-100/50 pt-2 grid grid-cols-3 gap-2 text-[11px]">
                                            <p class="text-slate-500">Incoterm: <span class="font-bold text-slate-700">{{ data_get($document->payload, 'header.incoterm') ?? '—' }}</span></p>
                                            <p class="text-slate-500">NDPBM: <span class="font-bold text-slate-700">{{ number_format(data_get($document->payload, 'header.ndpbm', 0), 0, ',', '.') }}</span></p>
                                            <p class="text-slate-500">Bruto: <span class="font-bold text-slate-700">{{ number_format(data_get($document->payload, 'header.bruto', 0), 2, ',', '.') }} kg</span></p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if ($document->doc_type === 'BC30')
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Klasifikasi &amp; Pernyataan</h4>
                                    <div class="text-sm bg-slate-50 p-4 rounded-xl space-y-1 text-xs text-slate-600">
                                        <p>Jenis Ekspor: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.jenis_ekspor') ?? '—' }}</span> · Kategori: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.kategori_ekspor') ?? '—' }}</span></p>
                                        <p>Komoditi: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.komoditi') === 'MIGAS' ? 'Migas' : 'Non Migas' }}</span> · {{ data_get($document->payload, 'header.curah') === 'CURAH' ? 'Curah' : 'Non Curah' }}</p>
                                        @if (data_get($document->payload, 'header.bank_devisa'))
                                            <p>Bank Devisa: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.bank_devisa') }}</span></p>
                                        @endif
                                        <p class="border-t border-slate-200 pt-1 mt-1">Penanggung Jawab: <span class="font-bold text-slate-800">{{ data_get($document->payload, 'header.pernyataan.nama') ?? '—' }}</span>{{ data_get($document->payload, 'header.pernyataan.jabatan') ? ' ('.data_get($document->payload, 'header.pernyataan.jabatan').')' : '' }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                    </div>

                    {{-- Items Table --}}
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Detail Pos Komoditas Barang</h4>
                        <div class="border border-slate-100 rounded-xl overflow-hidden shadow-sm">
                            <table class="min-w-full divide-y divide-slate-100 text-xs text-left">
                                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="px-4 py-3 text-center">Seri</th>
                                        <th class="px-4 py-3">Kode HS</th>
                                        <th class="px-4 py-3">Uraian</th>
                                        <th class="px-4 py-3 text-right">Jumlah</th>
                                        <th class="px-4 py-3 text-center">Satuan</th>
                                        <th class="px-4 py-3 text-right">Netto</th>
                                        <th class="px-4 py-3 text-right">Nilai Barang</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach (data_get($document->payload, 'barang', []) as $item)
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="px-4 py-3 font-bold text-slate-400 text-center">{{ data_get($item, 'seri') }}</td>
                                            <td class="px-4 py-3 font-mono font-semibold text-slate-700">{{ data_get($item, 'hs_code') }}</td>
                                            <td class="px-4 py-3 text-slate-600 font-medium">{{ data_get($item, 'uraian') }}</td>
                                            <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ number_format(data_get($item, 'jumlah_satuan'), 2, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-center text-slate-500 font-bold">{{ data_get($item, 'kode_satuan') }}</td>
                                            <td class="px-4 py-3 text-right text-slate-600 font-mono">{{ number_format(data_get($item, 'netto'), 2, ',', '.') }} kg</td>
                                            <td class="px-4 py-3 text-right font-bold text-indigo-600">
                                                @if ($document->doc_type === 'BC30')
                                                    {{ number_format(data_get($item, 'nilai_fob', 0), 2, ',', '.') }}
                                                @elseif ($document->doc_type === 'BC20' || $document->doc_type === 'BC24')
                                                    {{ number_format(data_get($item, 'nilai_cif', 0), 2, ',', '.') }}
                                                @else
                                                    {{ number_format(data_get($item, 'nilai_barang', 0), 2, ',', '.') }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. JSON tabs (Payload & Response) --}}
            <div class="grid md:grid-cols-2 gap-6" x-data="{ activeTab: 'payload' }">
                
                {{-- Left: JSON Viewer --}}
                <div class="bg-slate-900 text-slate-300 rounded-2xl shadow-sm border border-slate-800 overflow-hidden">
                    <div class="bg-slate-950 px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                        <div class="flex gap-2">
                            <button type="button" @click="activeTab = 'payload'"
                                    class="text-xs font-bold uppercase tracking-wider px-2 py-1 rounded transition-colors"
                                    :class="activeTab === 'payload' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200'">
                                Payload Kirim
                            </button>
                            @if ($document->ceisa_response)
                                <button type="button" @click="activeTab = 'response'"
                                        class="text-xs font-bold uppercase tracking-wider px-2 py-1 rounded transition-colors"
                                        :class="activeTab === 'response' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200'">
                                    Response CEISA
                                </button>
                            @endif
                        </div>
                        <span class="text-[10px] text-slate-500 font-mono">Format JSON</span>
                    </div>
                    
                    <div class="p-4">
                        <div x-show="activeTab === 'payload'">
                            <pre class="text-[11px] font-mono leading-relaxed overflow-x-auto text-emerald-400 max-h-[300px]">{{ json_encode($document->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                        @if ($document->ceisa_response)
                            <div x-show="activeTab === 'response'">
                                <pre class="text-[11px] font-mono leading-relaxed overflow-x-auto text-cyan-400 max-h-[300px]">{{ json_encode($document->ceisa_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Right: Webhook Logs History --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                    <h3 class="text-sm font-bold text-slate-800 mb-4 uppercase tracking-wider">Riwayat Webhook Real-time</h3>
                    @if ($document->webhookLogs->isNotEmpty())
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                @foreach ($document->webhookLogs->sortByDesc('received_at') as $idx => $log)
                                    <li>
                                        <div class="relative pb-8">
                                            @if ($idx < $document->webhookLogs->count() - 1)
                                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-100" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-emerald-50 border-2 border-emerald-500 flex items-center justify-center ring-8 ring-white">
                                                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                    <div>
                                                        <p class="text-xs text-slate-700 font-bold">Status: <span class="text-indigo-600">{{ $log->event ?? 'DITERIMA' }}</span></p>
                                                    </div>
                                                    <div class="whitespace-nowrap text-right text-xs text-slate-400 font-medium">
                                                        <time>{{ $log->received_at?->format('d/m/Y H:i:s') }}</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="text-center py-10 text-slate-400">
                            <svg class="h-10 w-10 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <p class="text-xs">Menunggu update status otomatis (webhook) dari CEISA DJBC...</p>
                        </div>
                    @endif
                </div>

            </div>

            <div class="flex items-center justify-start pt-4">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-slate-800 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
