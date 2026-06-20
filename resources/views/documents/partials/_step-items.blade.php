@php
/**
 * Step 4 — Detail Pos Barang (dynamic grid).
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

{{-- Step 4: Items (Dynamic Grid) --}}
<div x-show="step === 4" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 transition-all duration-300">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Detail Pos Barang</h3>
            <p class="text-xs text-slate-500 mt-0.5">Input pos-pos komoditas barang ekspor / impor secara terperinci</p>
        </div>
        <button type="button" @click="addItem()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-colors shadow-md shadow-indigo-100">
            + Tambah Barang
        </button>
    </div>

    <div class="space-y-4">
        <template x-for="(item, index) in formData.barang" :key="index">
            <div class="border border-slate-100 rounded-xl p-4 bg-slate-50/50 hover:border-slate-300 transition-all relative group">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                    <span class="text-xs font-extrabold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded uppercase tracking-wider">Barang #<span x-text="index + 1"></span></span>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="copyItem(index)"
                                class="text-xs text-indigo-600 hover:underline">Salin Pos</button>
                        <button type="button" @click="removeItem(index)" x-show="formData.barang.length > 1"
                                class="text-xs text-rose-600 hover:underline">Hapus Pos</button>
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
                        <select :name="`barang[${index}][kode_satuan]`" x-model="item.kode_satuan"
                                class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" required>
                            <option value="">-- Pilih Satuan --</option>
                            <template x-for="u in references.units" :key="u.code">
                                <option :value="u.code" x-text="u.label"></option>
                            </template>
                        </select>
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
                                <label class="block text-[11px] font-bold text-slate-500 uppercase">Merk (Opsional)</label>
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
                                <select :name="`barang[${index}][negara_asal]`" x-model="item.negara_asal" class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm">
                                    <option value="">-- Pilih --</option>
                                    <template x-for="c in references.countries" :key="c.code">
                                        <option :value="c.code" x-text="c.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase">Daerah Asal (Opsional)</label>
                                <input type="text" :name="`barang[${index}][daerah_asal]`" x-model="item.daerah_asal" placeholder="mis. Jawa Barat" class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" />
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
                                <select :name="`barang[${index}][kode_kemasan]`" x-model="item.kode_kemasan" class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm">
                                    <option value="">-- Pilih --</option>
                                    <template x-for="p in references.packages" :key="p.code">
                                        <option :value="p.code" x-text="p.label"></option>
                                    </template>
                                </select>
                            </div>
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
            <span class="font-bold text-indigo-700 ml-1" x-text="calculateTotalValue() + ' ' + (formData.kode_valuta || 'USD')"></span>
        </div>
    </div>
</div>
