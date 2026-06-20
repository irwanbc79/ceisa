    <div x-show="showDraftModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm"
         @keydown.escape.window="showDraftModal = false">

        <div x-show="showDraftModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="bg-white w-full max-w-3xl max-h-[92vh] overflow-y-auto rounded-2xl shadow-2xl border border-slate-100"
             @click.stop>

            {{-- Modal Header --}}
            <div class="sticky top-0 z-10 bg-white border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-base tracking-tight">Preview Dokumen Draft</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Periksa kembali sebelum disimpan ke sistem M2B</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full border border-amber-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        DRAFT — Belum Dikirim ke CEISA
                    </span>
                    <button type="button" @click="showDraftModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-50 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            {{-- Document Preview Body — Bergaya Resmi Bea Cukai --}}
            <div class="p-6">
                {{-- Official Header --}}
                <div class="border border-slate-300 rounded-xl overflow-hidden font-mono text-xs">

                    {{-- Government Header --}}
                    <div class="bg-slate-50 border-b border-slate-200 p-4 flex items-start justify-between gap-4">
                        <div class="text-[10px] leading-relaxed text-slate-600">
                            <p class="font-bold text-slate-800 uppercase tracking-wide">KEMENTERIAN KEUANGAN REPUBLIK INDONESIA</p>
                            <p class="font-semibold uppercase">Direktorat Jenderal Bea dan Cukai</p>
                            <p class="mt-2 font-bold text-base text-slate-900 uppercase tracking-wide" x-text="getDocTypeLabel()"></p>
                        </div>
                        {{-- QR Placeholder --}}
                        <div class="shrink-0 h-16 w-16 bg-slate-200 rounded border border-slate-300 flex items-center justify-center">
                            <svg class="h-8 w-8 text-slate-400" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h7v7H3V3zm0 11h7v7H3v-7zm11-11h7v7h-7V3zm0 11h7v7h-7v-7z"/><path d="M5 5h3v3H5V5zm0 11h3v3H5v-3zm11-11h3v3h-3V5zm0 11h3v3h-3v-3z"/></svg>
                        </div>
                    </div>

                    {{-- Document Info Grid --}}
                    <div class="p-4 border-b border-slate-200">
                        <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-[11px]">
                            <div>
                                <span class="text-slate-500">Nomor Pengajuan</span>
                                <span class="ml-2 font-bold text-amber-700">: [DRAFT — Belum Digenerate]</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Tanggal Input</span>
                                <span class="ml-2 font-semibold" x-text="': ' + todayFormatted()"></span>
                            </div>
                            <div>
                                <span class="text-slate-500">Jenis Dokumen</span>
                                <span class="ml-2 font-bold text-indigo-700" x-text="': ' + doc_type"></span>
                            </div>
                            <div>
                                <span class="text-slate-500">Status</span>
                                <span class="ml-2 font-bold text-amber-600">: DRAFT LOKAL</span>
                            </div>
                        </div>
                    </div>

                    {{-- Parties Section --}}
                    <div class="p-4 border-b border-slate-200 bg-slate-50/50">
                        <div class="grid grid-cols-2 gap-6">
                            {{-- Main Party --}}
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5"
                                   x-text="doc_type === 'BC30' ? 'EKSPORTIR / PPJK' : (doc_type === 'RUSH' ? 'PEMOHON / PPJK' : 'IMPORTIR / PPJK')"></p>
                                <div class="space-y-1 text-[11px]">
                                    <div class="flex gap-2"><span class="text-slate-500 w-16 shrink-0">Nama</span><span class="font-bold text-slate-800" x-text="': ' + getPartyName()"></span></div>
                                    <div class="flex gap-2"><span class="text-slate-500 w-16 shrink-0">NPWP</span><span class="font-mono font-semibold" x-text="': ' + getPartyNPWP()"></span></div>
                                    <div class="flex gap-2"><span class="text-slate-500 w-16 shrink-0">PPJK</span><span class="font-bold text-indigo-700">: PT. MORA MULTI BERKAH</span></div>
                                </div>
                            </div>
                            {{-- Counter Party --}}
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5"
                                   x-text="doc_type === 'BC30' ? 'PENERIMA / CONSIGNEE' : 'PEMASOK / SUPPLIER'"></p>
                                <div class="space-y-1 text-[11px]">
                                    <div class="flex gap-2"><span class="text-slate-500 w-16 shrink-0">Nama</span><span class="font-bold text-slate-800" x-text="': ' + getCounterPartyName()"></span></div>
                                    <template x-if="doc_type === 'BC30' || doc_type === 'BC20' || doc_type === 'BC24'">
                                        <div class="flex gap-2"><span class="text-slate-500 w-16 shrink-0">Negara</span>
                                            <span class="font-semibold" x-text="': ' + (doc_type === 'BC30' ? (formData.negara_tujuan || '—') : (formData.negara_pemasok || '—'))"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Logistics & Value --}}
                    <div class="p-4 border-b border-slate-200">
                        <div class="grid grid-cols-3 gap-4 text-[11px]">
                            <div>
                                <span class="text-slate-500 block">Pelabuhan Muat</span>
                                <span class="font-bold text-slate-800" x-text="formData.pelabuhan_muat || (formData.nama_sarana_pengangkut || '—')"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block" x-text="doc_type === 'RUSH' ? 'No. Flight/AWB' : 'Pelabuhan Bongkar'"></span>
                                <span class="font-bold text-slate-800" x-text="doc_type === 'RUSH' ? (formData.nomor_flight || '—') : (formData.pelabuhan_bongkar || '—')"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Mata Uang (Valuta)</span>
                                <span class="font-bold text-slate-800" x-text="formData.kode_valuta || 'USD'"></span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-slate-500 block" x-text="getValueLabel()"></span>
                                <span class="font-bold text-lg text-indigo-700" x-text="getTotalValue() + ' ' + (formData.kode_valuta || 'USD')"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Cara Pembayaran</span>
                                <span class="font-semibold text-slate-700" x-text="formData.cara_pembayaran || '—'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Items Table --}}
                    <div class="p-4">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">DAFTAR POS BARANG</p>
                        <table class="w-full text-[10px] border-collapse border border-slate-300">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="border border-slate-300 px-2 py-1.5 text-left">No</th>
                                    <th class="border border-slate-300 px-2 py-1.5 text-left">Kode HS</th>
                                    <th class="border border-slate-300 px-2 py-1.5 text-left">Uraian Barang</th>
                                    <th class="border border-slate-300 px-2 py-1.5 text-right">Jml Satuan</th>
                                    <th class="border border-slate-300 px-2 py-1.5 text-center">Sat</th>
                                    <th class="border border-slate-300 px-2 py-1.5 text-right">Netto (KG)</th>
                                    <th class="border border-slate-300 px-2 py-1.5 text-right">Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, idx) in formData.barang" :key="idx">
                                    <tr class="even:bg-slate-50/50">
                                        <td class="border border-slate-300 px-2 py-1.5 text-center font-semibold" x-text="idx + 1"></td>
                                        <td class="border border-slate-300 px-2 py-1.5 font-mono font-bold text-indigo-700" x-text="item.hs_code || '—'"></td>
                                        <td class="border border-slate-300 px-2 py-1.5" x-text="item.uraian || '—'"></td>
                                        <td class="border border-slate-300 px-2 py-1.5 text-right" x-text="item.jumlah_satuan || 0"></td>
                                        <td class="border border-slate-300 px-2 py-1.5 text-center font-bold" x-text="item.kode_satuan || '—'"></td>
                                        <td class="border border-slate-300 px-2 py-1.5 text-right" x-text="item.netto || 0"></td>
                                        <td class="border border-slate-300 px-2 py-1.5 text-right font-semibold"
                                            x-text="(parseFloat(item[doc_type === 'BC30' ? 'nilai_fob' : (doc_type === 'BC20' || doc_type === 'BC24' ? 'nilai_cif' : 'nilai_barang')] || 0)).toLocaleString('id-ID')"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        {{-- Footer Note --}}
                        <div class="mt-4 pt-4 border-t border-slate-200 text-[10px] text-slate-400 leading-relaxed">
                            <p class="font-semibold text-slate-500">Catatan:</p>
                            <p>Dokumen ini merupakan pratinjau <strong class="text-amber-600">DRAFT LOKAL</strong> yang belum disubmit ke portal CEISA 4.0 Bea Cukai (DJBC). Nomor Aju akan digenerate setelah dokumen dikirim secara resmi via gateway H2H.</p>
                            <p class="mt-1">PPJK: PT. MORA MULTI BERKAH — Sistem H2H CEISA 4.0</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="sticky bottom-0 bg-white border-t border-slate-100 px-6 py-4 flex items-center justify-between gap-3">
                <button type="button" @click="showDraftModal = false"
                        class="inline-flex items-center gap-2 px-5 py-2.5 border border-slate-200 text-slate-700 text-sm font-bold rounded-xl bg-white hover:bg-slate-50 transition-colors shadow-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    Edit Kembali
                </button>
                <div class="flex items-center gap-3">
                    <button type="button" @click="submitForm('submit')"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-all shadow-md shadow-indigo-100">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                        Kirim ke CEISA Sekarang
                    </button>
                    <button type="button" @click="confirmSaveDraft()"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" /></svg>
                        Konfirmasi Simpan Draft
                    </button>
                </div>
            </div>
        </div>
    </div>
