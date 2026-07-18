@php
/**
 * Step 7 — Data Transaksi.
 *
 * Selaras urutan Portal CEISA 4.0 resmi: Data Transaksi setelah
 * Kemasan & Peti Kemas, sebelum Data Barang.
 * Berbagi Alpine scope dari root x-data="documentWizard()".
 */
@endphp

{{-- Step 7: Data Transaksi --}}
<div x-show="step === 7" x-cloak class="bg-white/90 backdrop-blur-xl border border-slate-200 shadow-card rounded-2xl p-6 transition-all duration-300">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Data Transaksi</h3>
            <p class="text-xs text-slate-500 mt-0.5">Valuta, NDPBM, nilai transaksi, freight, dan asuransi</p>
        </div>
        <span class="px-3 py-1 bg-indigo-50 text-indigo-300 text-xs font-bold rounded-full border border-indigo-100">Tahap <span x-text="step"></span> dari <span x-text="steps.length"></span></span>
    </div>

    {{-- BC 3.0 --}}
    <div x-show="doc_type === 'BC30'">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <x-input-label for="kode_valuta_bc30" value="Valuta" />
                <x-searchable-select id="kode_valuta_bc30" ::name="doc_type === 'BC30' ? 'kode_valuta' : ''" model="formData.kode_valuta" options="references.currencies" placeholder="-- Pilih Mata Uang --" ::required="doc_type === 'BC30'" />
            </div>
            <div>
                <x-input-label for="ndpbm" value="NDPBM / Kurs (ke IDR)" />
                <input type="number" step="0.0001" min="0" id="ndpbm" name="ndpbm" x-model="formData.ndpbm" placeholder="mis. 15800" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC30'" />
            </div>
            <div>
                <x-input-label for="incoterm" value="Cara Penyerahan (Incoterm)" />
                <x-searchable-select id="incoterm" name="incoterm" model="formData.incoterm" options="references.incoterms" placeholder="-- Pilih Incoterm --" ::required="doc_type === 'BC30'" />
            </div>
            <div>
                <x-input-label for="nilai_fob" value="Nilai FOB Total" />
                <input type="number" step="0.01" min="0" id="nilai_fob" name="nilai_fob" x-model="formData.nilai_fob" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC30'" />
                <p class="text-[10px] text-slate-400 mt-1">Auto-terisi dari total Pos Barang bila dikosongkan.</p>
            </div>
            <div>
                <x-input-label for="freight" value="Freight (Opsional)" />
                <input type="number" step="0.01" min="0" id="freight" name="freight" x-model="formData.freight" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
            </div>
            <div>
                <x-input-label for="bruto" value="Berat Kotor / Bruto (KGM)" />
                <input type="number" step="0.01" min="0" id="bruto" name="bruto" x-model="formData.bruto" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC30'" />
            </div>
            <div>
                <x-input-label for="asuransi_jenis" value="Asuransi" />
                <x-searchable-select id="asuransi_jenis" name="asuransi_jenis" model="formData.asuransi_jenis" :options="['DN' => 'Dalam Negeri', 'LN' => 'Luar Negeri']" placeholder="-- Pilih Asuransi --" />
            </div>
            <div>
                <x-input-label for="nilai_asuransi" value="Nilai Asuransi (Opsional)" />
                <input type="number" step="0.01" min="0" id="nilai_asuransi" name="nilai_asuransi" x-model="formData.nilai_asuransi" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
            </div>
            <div>
                <x-input-label for="bank_devisa" value="Bank Devisa (Opsional)" />
                <input type="text" id="bank_devisa" name="bank_devisa" x-model="formData.bank_devisa" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <x-input-label for="cara_pembayaran_bc30" value="Cara Pembayaran (Opsional)" />
                <x-searchable-select id="cara_pembayaran_bc30" ::name="doc_type === 'BC30' ? 'cara_pembayaran' : ''" model="formData.cara_pembayaran" options="references.paymentMethods" placeholder="-- Pilih Cara Pembayaran --" />
            </div>
        </div>
    </div>

    {{-- BC 2.0 / BC 2.4 --}}
    <div x-show="doc_type === 'BC20' || doc_type === 'BC24'" class="grid sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="kode_valuta" value="Mata Uang / Valuta (Referensi)" />
            <x-searchable-select id="kode_valuta" ::name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'kode_valuta' : ''" model="formData.kode_valuta" options="references.currencies" placeholder="-- Pilih Mata Uang --" ::required="doc_type === 'BC20' || doc_type === 'BC24'" />
        </div>
        <div>
            <x-input-label for="nilai_cif" value="Nilai CIF Total (Cost, Insurance, Freight)" />
            <input type="number" step="0.01" min="0" id="nilai_cif" name="nilai_cif" x-model="formData.nilai_cif" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC20' || doc_type === 'BC24'" />
        </div>
        <div>
            <x-input-label for="ndpbm_imp" value="NDPBM / Kurs Pajak" />
            <input type="number" step="0.0001" min="0" id="ndpbm_imp" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'ndpbm' : ''" x-model="formData.ndpbm" placeholder="mis. 15800" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
        </div>
        <div>
            <x-input-label for="incoterm_imp" value="Cara Penyerahan (Incoterm)" />
            <x-searchable-select id="incoterm_imp" ::name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'incoterm' : ''" model="formData.incoterm" options="references.incoterms" placeholder="-- Pilih Incoterm --" />
        </div>
        <div>
            <x-input-label for="freight_imp" value="Freight (opsional)" />
            <input type="number" step="0.01" min="0" id="freight_imp" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'freight' : ''" x-model="formData.freight" placeholder="kosongkan = estimasi dari CIF" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
        </div>
        <div>
            <x-input-label for="nilai_asuransi_imp" value="Nilai Asuransi (opsional)" />
            <input type="number" step="0.01" min="0" id="nilai_asuransi_imp" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'nilai_asuransi' : ''" x-model="formData.nilai_asuransi" placeholder="kosongkan = estimasi dari CIF" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
        </div>
        <div>
            <x-input-label for="bruto_imp" value="Berat Kotor / Bruto (Kg, opsional)" />
            <input type="number" step="0.0001" min="0" id="bruto_imp" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'bruto' : ''" x-model="formData.bruto" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
        </div>
        <div>
            <x-input-label for="nib_importir_imp" value="NIB Importir (opsional)" />
            <input type="text" id="nib_importir_imp" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'nib_importir' : ''" x-model="formData.nib_importir" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
        </div>
        <div>
            <x-input-label for="jenis_api_imp" value="Jenis API (mis. 01)" />
            <input type="text" maxlength="5" id="jenis_api_imp" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'jenis_api' : ''" x-model="formData.jenis_api" placeholder="01" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="cara_pembayaran" value="Cara Pembayaran (Referensi)" />
            <x-searchable-select id="cara_pembayaran" ::name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'cara_pembayaran' : ''" model="formData.cara_pembayaran" options="references.paymentMethods" placeholder="-- Pilih Cara Pembayaran --" />
        </div>
    </div>

    {{-- TPB --}}
    <div x-show="doc_type === 'TPB'">
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="kode_valuta_tpb" value="Mata Uang / Valuta (Referensi)" />
                <x-searchable-select id="kode_valuta_tpb" ::name="doc_type === 'TPB' ? 'kode_valuta' : ''" model="formData.kode_valuta" options="references.currencies" placeholder="-- Pilih Mata Uang --" ::required="doc_type === 'TPB'" />
            </div>
            <div>
                <x-input-label for="nilai_barang" value="Nilai Total Barang TPB" />
                <input type="number" step="0.01" min="0" id="nilai_barang" :name="doc_type === 'TPB' ? 'nilai_barang' : ''" x-model="formData.nilai_barang" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'TPB'" />
            </div>
        </div>
    </div>

    {{-- RUSH: tidak ada data transaksi khusus --}}
    <div x-show="doc_type === 'RUSH'" class="rounded-xl border border-dashed border-slate-200 p-6 text-center bg-slate-50/50">
        <p class="text-xs text-slate-500">Rush Handling tidak memerlukan data transaksi — nilai barang diisi pada tahap <strong>Pos Barang</strong>. Klik <strong>Lanjut</strong>.</p>
    </div>
</div>
