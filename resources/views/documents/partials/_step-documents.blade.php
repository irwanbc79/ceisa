@php
/**
 * Step 3 — Dokumen Pelengkap Kepabeanan.
 */
@endphp

<div x-show="step === 3" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 transition-all duration-300">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Dokumen Pelengkap Kepabeanan</h3>
            <p class="text-xs text-slate-500 mt-0.5">Sebutkan invoice, packing list, B/L, AWB, atau dokumen perizinan pendukung lainnya</p>
        </div>
        <button type="button" @click="addDocument()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-colors shadow-md shadow-indigo-100">
            + Tambah Dokumen
        </button>
    </div>

    <div class="space-y-4">
        <template x-for="(doc, index) in formData.dokumen" :key="index">
            <div class="border border-slate-100 rounded-xl p-4 bg-slate-50/50 hover:border-slate-300 transition-all relative group">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                    <span class="text-xs font-extrabold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded uppercase tracking-wider">Dokumen #<span x-text="index + 1"></span></span>
                    <button type="button" @click="removeDocument(index)" x-show="formData.dokumen.length > 1"
                            class="text-xs text-rose-600 hover:underline">Hapus Dokumen</button>
                </div>

                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Jenis Dokumen (Referensi)</label>
                        <x-searchable-select ::name="'dokumen[' + index + '][kode_dokumen]'" model="doc.kode_dokumen" options="references.documentTypes" placeholder="-- Pilih Jenis --" required />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Nomor Dokumen</label>
                        <input type="text" :name="`dokumen[${index}][nomor_dokumen]`" x-model="doc.nomor_dokumen"
                               class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" placeholder="mis. INV-99201A" required />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Tanggal Dokumen</label>
                        <input type="date" :name="`dokumen[${index}][tanggal_dokumen]`" x-model="doc.tanggal_dokumen"
                               class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm" required />
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
