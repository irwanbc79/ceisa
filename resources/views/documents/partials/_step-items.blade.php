@php
/**
 * Step 8 — Detail Pos Barang (dynamic grid).
 *
 * Berbages Alpine scope dari root x-data="documentWizard()" di create.blade.php,
 * jadi variabel formData / doc_type / references langsung tersedia.
 *
 * Variabel yang dipakai:
 *   - formData.barang   : array item barang (Alpine reactive)
 *   - doc_type          : 'BC30' | 'BC20' | 'BC24' | 'TPB' | 'RUSH'
 *   - references        : { units, packages, countries }
 */
@endphp

{{-- Step 4: Items (Dynamic Grid + CEISA 4.0 Inline Pager) --}}
<div x-show="step === 8" x-cloak
     x-data="{ activeItemIdx: 0, pagerMode: false }"
     class="bg-white/90 backdrop-blur-xl border border-slate-200 shadow-card rounded-2xl p-6 transition-all duration-300">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Detail Pos Barang</h3>
            <p class="text-xs text-slate-500 mt-0.5">Input pos-pos komoditas barang ekspor / impor secara terperinci</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            {{-- Inline Pager ala Portal CEISA 4.0 (< > 1/N) --}}
            <div class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 p-0.5 text-xs">
                <button type="button" @click="pagerMode = !pagerMode; activeItemIdx = Math.min(activeItemIdx, Math.max(0, formData.barang.length - 1));"
                        class="px-2.5 py-1 rounded-md font-semibold transition cursor-pointer"
                        :class="pagerMode ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        x-text="pagerMode ? 'Mode Pager (1/' + formData.barang.length + ')' : 'Tampilkan Semua (' + formData.barang.length + ')'">
                </button>
                <template x-if="pagerMode">
                    <div class="inline-flex items-center border-l border-slate-200 pl-1 ml-1 gap-0.5">
                        <button type="button" @click="activeItemIdx = Math.max(0, activeItemIdx - 1)" :disabled="activeItemIdx === 0"
                                class="px-2 py-1 rounded hover:bg-white disabled:opacity-40 font-bold cursor-pointer">&lt;</button>
                        <span class="px-1.5 font-mono text-[11px] font-bold text-slate-700" x-text="(activeItemIdx + 1) + '/' + formData.barang.length"></span>
                        <button type="button" @click="activeItemIdx = Math.min(formData.barang.length - 1, activeItemIdx + 1)" :disabled="activeItemIdx >= formData.barang.length - 1"
                                class="px-2 py-1 rounded hover:bg-white disabled:opacity-40 font-bold cursor-pointer">&gt;</button>
                    </div>
                </template>
            </div>
            <button type="button" @click="addItem(); activeItemIdx = formData.barang.length - 1;"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-colors shadow-md shadow-slate-900/10 cursor-pointer">
                + Tambah Barang
            </button>
        </div>
    </div>

    <div class="space-y-4">
        <template x-for="(item, index) in formData.barang" :key="index">
            <div x-show="!pagerMode || activeItemIdx === index"
                 class="border border-slate-100 rounded-xl p-4 bg-slate-50/50 hover:border-slate-300 transition-all relative group">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                    <span class="text-xs font-extrabold text-indigo-300 bg-indigo-50 px-2 py-0.5 rounded uppercase tracking-wider">Barang #<span x-text="index + 1"></span></span>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="copyItem(index)"
                                class="text-xs text-indigo-300 hover:underline">Salin Pos</button>
                        <button type="button" @click="removeItem(index)" x-show="formData.barang.length > 1"
                                class="text-xs text-rose-300 hover:underline">Hapus Pos</button>
                    </div>
                </div>

                <div class="grid sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-1">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Kode HS (Harmonized System)</label>
                        <input type="text" :name="`barang[${index}][hs_code]`" x-model="item.hs_code"
                               class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" placeholder="6109100000" required />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Uraian Barang Lengkap</label>
                        <input type="text" :name="`barang[${index}][uraian]`" x-model="item.uraian"
                               class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" required />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Jumlah Satuan</label>
                        <input type="number" step="0.01" min="0" :name="`barang[${index}][jumlah_satuan]`" x-model="item.jumlah_satuan"
                               class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" required />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Kode Satuan (Referensi)</label>
                        <x-searchable-select ::name="'barang[' + index + '][kode_satuan]'" model="item.kode_satuan" options="references.units" placeholder="-- Pilih Satuan --" required />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Netto (KGM / Kilogram)</label>
                        <input type="number" step="0.01" min="0" :name="`barang[${index}][netto]`" x-model="item.netto"
                               class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" required />
                    </div>

                    {{-- Field tambahan khusus BC 3.0 ekspor --}}
                    <template x-if="doc_type === 'BC30'">
                        <div class="sm:col-span-3 grid sm:grid-cols-3 gap-3 border-t border-dashed border-slate-200 pt-3 mt-1">
                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="block text-[11px] font-bold text-slate-500 uppercase">Merk (Opsional)</label>
                                    <label class="inline-flex items-center gap-1 text-[10px] font-semibold text-slate-500 cursor-pointer">
                                        <input type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                               :checked="item.merk === 'TANPA MEREK'"
                                               @change="item.merk = $event.target.checked ? 'TANPA MEREK' : ''">
                                        Tanpa Merek
                                    </label>
                                </div>
                                <input type="text" :name="`barang[${index}][merk]`" x-model="item.merk" class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase">Tipe (Opsional)</label>
                                <input type="text" :name="`barang[${index}][tipe]`" x-model="item.tipe" class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase">Ukuran (Opsional)</label>
                                <input type="text" :name="`barang[${index}][ukuran]`" x-model="item.ukuran" class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase">Negara Asal Barang</label>
                                <x-searchable-select ::name="'barang[' + index + '][negara_asal]'" model="item.negara_asal" options="references.countries" placeholder="-- Pilih Negara --" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase">Daerah Asal (Opsional)</label>
                                <x-searchable-select ::name="'barang[' + index + '][daerah_asal]'" model="item.daerah_asal" options="references.daerahAsal" placeholder="-- Pilih Daerah Asal --" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase">Volume (m³, Opsional)</label>
                                <input type="number" step="0.0001" min="0" :name="`barang[${index}][volume]`" x-model="item.volume" class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase">Jumlah Kemasan</label>
                                <input type="number" step="0.01" min="0" :name="`barang[${index}][jumlah_kemasan]`" x-model="item.jumlah_kemasan" class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase">Jenis Kemasan</label>
                                <x-searchable-select ::name="'barang[' + index + '][kode_kemasan]'" model="item.kode_kemasan" options="references.packages" placeholder="-- Pilih Kemasan --" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase">Merek Kemasan</label>
                                <input type="text" :name="`barang[${index}][merk_kemasan]`" x-model="item.merk_kemasan" placeholder="mis. M2B / Tanpa Merek" class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" required />
                            </div>
                        </div>
                    </template>

                    {{-- Tarif impor harus berasal dari hasil klasifikasi/operator, bukan default aplikasi. --}}
                    <template x-if="doc_type === 'BC20' || doc_type === 'BC24'">
                        <div class="sm:col-span-3 grid sm:grid-cols-3 gap-3 border-t border-dashed border-slate-200 pt-3 mt-1">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase">Tarif BM (%)</label>
                                <input type="number" step="0.01" min="0" max="100" :name="`barang[${index}][tarif_bm]`" x-model="item.tarif_bm" class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" required />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase">Tarif PPN (%)</label>
                                <input type="number" step="0.01" min="0" max="100" :name="`barang[${index}][tarif_ppn]`" x-model="item.tarif_ppn" class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" required />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase">Tarif PPh (%)</label>
                                <input type="number" step="0.01" min="0" max="100" :name="`barang[${index}][tarif_pph]`" x-model="item.tarif_pph" class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" required />
                            </div>
                            <p class="sm:col-span-3 text-[10px] text-amber-700">Gunakan tarif hasil klasifikasi HS dan fasilitas importir yang telah diverifikasi. Sistem tidak lagi mengisi tarif pajak asumsi.</p>
                        </div>
                    </template>

                    {{-- Dynamic Monetary Value for Item --}}
                    <div class="sm:col-span-3">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase"
                               x-text="doc_type === 'BC30' ? 'Nilai FOB Barang' : (doc_type === 'BC20' || doc_type === 'BC24' ? 'Nilai CIF Barang' : 'Nilai Barang')"></label>

                        <input type="number" step="0.01" min="0"
                               :name="doc_type === 'BC30' ? `barang[${index}][nilai_fob]` : (doc_type === 'BC20' || doc_type === 'BC24' ? `barang[${index}][nilai_cif]` : `barang[${index}][nilai_barang]`)"
                               x-model="item[doc_type === 'BC30' ? 'nilai_fob' : (doc_type === 'BC20' || doc_type === 'BC24' ? 'nilai_cif' : 'nilai_barang')]"
                               class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" required />
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Live Summary Totals --}}
    <div class="mt-6 border-t border-slate-100 pt-4 bg-indigo-50/30 rounded-xl p-4 flex flex-wrap gap-6 text-sm">
        <div>
            <span class="text-slate-500">Total Pos Barang:</span>
            <span class="font-bold text-slate-800 ml-1" x-text="formData.barang.length"></span>
        </div>
        <div>
            <span class="text-slate-500">Total Netto:</span>
            <span class="font-bold text-slate-800 ml-1" x-text="calculateTotalNetto() + ' kg'"></span>
        </div>
        <div>
            <span class="text-slate-500">Total Nilai Barang:</span>
            <span class="font-bold text-indigo-300 ml-1" x-text="calculateTotalValue() + ' ' + (formData.kode_valuta || 'USD')"></span>
        </div>
    </div>
</div>
