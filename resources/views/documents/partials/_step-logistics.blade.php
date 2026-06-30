@php
/**
 * Step 3 — Pengangkutan & Transaksi (logistik + nilai moneter).
 *
 * Berbages Alpine scope dari root x-data="documentWizard()" di create.blade.php.
 * Variabel Alpine: formData, doc_type, references.{...}.
 * Section per doc_type: BC30, BC20/BC24, TPB, RUSH. Plus Nomor AJU kustom.
 */
@endphp

{{-- Step 3: Logistics & Transactions --}}
<div x-show="step === 4" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 transition-all duration-300">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Data Logistik & Transaksi</h3>
            <p class="text-xs text-slate-500 mt-0.5">Input pelabuhan, sarana angkut, valuta, nilai, dan asuransi</p>
        </div>
        <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-full border border-indigo-100">Tahap <span x-text="step"></span> dari <span x-text="steps.length"></span></span>
    </div>

    {{-- Common Header Options: Nomor AJU Kustom --}}
    <div class="mb-6 p-4 rounded-xl border border-slate-200 bg-slate-50/50">
        <div class="flex items-center gap-2 mb-2">
            <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
            </svg>
            <h4 class="text-xs font-black text-slate-700 uppercase tracking-wider">Nomor AJU Kustom (Opsional)</h4>
        </div>
        <p class="text-xs text-slate-500 leading-normal mb-3">Isi jika Anda ingin menggunakan nomor pengajuan internal tertentu. Biarkan kosong agar sistem otomatis membuat nomor AJU 26-digit resmi pada saat submit.</p>
        <div class="max-w-md">
            <input type="text" id="nomor_aju" name="nomor_aju" x-model="formData.nomor_aju" maxlength="26" minlength="26"
                   class="block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs font-mono tracking-wider shadow-sm placeholder-slate-400"
                   placeholder="Contoh: 04010020260617012345000001" />
            <p class="text-[10px] text-slate-400 mt-1" :class="formData.nomor_aju && formData.nomor_aju.length !== 26 ? 'text-rose-500 font-semibold' : ''">
                Panjang karakter: <span x-text="formData.nomor_aju ? formData.nomor_aju.length : 0"></span> dari 26 karakter (harus alfanumerik saja).
            </p>
        </div>
    </div>

    {{-- BC 3.0 — Pengangkut & Transaksi (CEISA 4.0) --}}
    <div x-show="doc_type === 'BC30'" class="space-y-6">
        <div class="border-b border-slate-100 pb-4">
            <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Data Pengangkut</h4>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-3">
                <div>
                    <x-input-label for="cara_angkut" value="Cara Pengangkutan" />
                    <x-searchable-select id="cara_angkut" name="cara_angkut" model="formData.cara_angkut" options="references.caraAngkut" placeholder="-- Pilih Cara Pengangkutan --" />
                </div>
                <div>
                    <x-input-label for="nama_sarana" value="Nama Sarana Pengangkut" />
                    <input type="text" id="nama_sarana" name="nama_sarana" x-model="formData.nama_sarana" placeholder="mis. MV Sinar Jaya" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                </div>
                <div>
                    <x-input-label for="voy_flight" value="No. Voyage / Flight" />
                    <input type="text" id="voy_flight" name="voy_flight" x-model="formData.voy_flight" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                </div>
                <div>
                    <x-input-label for="pelabuhan_muat_bc30" value="Pelabuhan Muat Ekspor" />
                    <x-searchable-select id="pelabuhan_muat_bc30" ::name="doc_type === 'BC30' ? 'pelabuhan_muat' : ''" model="formData.pelabuhan_muat" options="references.ports" placeholder="-- Pilih Pelabuhan Muat --" ::required="doc_type === 'BC30'" />
                </div>
                <div>
                    <x-input-label for="pelabuhan_tujuan" value="Pelabuhan Tujuan" />
                    <x-searchable-select id="pelabuhan_tujuan" name="pelabuhan_tujuan" model="formData.pelabuhan_tujuan" options="references.ports" placeholder="-- Pilih Pelabuhan Tujuan --" ::required="doc_type === 'BC30'" />
                </div>
                <div>
                    <x-input-label for="tanggal_ekspor" value="Tanggal Perkiraan Ekspor" />
                    <input type="date" id="tanggal_ekspor" name="tanggal_ekspor" x-model="formData.tanggal_ekspor" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                </div>
            </div>
        </div>

        <div>
            <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Data Transaksi</h4>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-3">
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
    </div>

    {{-- BC 2.0 / BC 2.4 --}}
    <div x-show="doc_type === 'BC20' || doc_type === 'BC24'" class="grid sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="pelabuhan_muat" value="Pelabuhan Muat (Kode Referensi)" />
            <x-searchable-select id="pelabuhan_muat" ::name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'pelabuhan_muat' : ''" model="formData.pelabuhan_muat" options="references.ports" placeholder="-- Pilih Pelabuhan Muat --" ::required="doc_type === 'BC20' || doc_type === 'BC24'" />
        </div>
        <div>
            <x-input-label for="pelabuhan_bongkar" value="Pelabuhan Bongkar (Kode Referensi)" />
            <x-searchable-select id="pelabuhan_bongkar" name="pelabuhan_bongkar" model="formData.pelabuhan_bongkar" options="references.ports" placeholder="-- Pilih Pelabuhan Bongkar --" ::required="doc_type !== 'BC30'" />
        </div>
        <div>
            <x-input-label for="kode_valuta" value="Mata Uang / Valuta (Referensi)" />
            <x-searchable-select id="kode_valuta" ::name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'kode_valuta' : ''" model="formData.kode_valuta" options="references.currencies" placeholder="-- Pilih Mata Uang --" ::required="doc_type === 'BC20' || doc_type === 'BC24'" />
        </div>

        <div x-show="doc_type === 'BC20' || doc_type === 'BC24'">
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

        {{-- Data Pengangkutan impor --}}
        <div class="sm:col-span-2 mt-1 pt-3 border-t border-slate-100">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Data Pengangkutan</p>
        </div>
        <div>
            <x-input-label for="cara_angkut_imp" value="Cara Pengangkutan" />
            <x-searchable-select id="cara_angkut_imp" ::name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'cara_angkut' : ''" model="formData.cara_angkut" :options="['Laut' => 'Laut', 'Udara' => 'Udara', 'Darat' => 'Darat', 'Kereta Api' => 'Kereta Api', 'Pos' => 'Pos']" placeholder="-- Pilih Cara Pengangkutan --" />
        </div>
        <div>
            <x-input-label for="kode_bendera_imp" value="Bendera Sarana (ISO 2 huruf)" />
            <input type="text" maxlength="2" id="kode_bendera_imp" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'kode_bendera' : ''" x-model="formData.kode_bendera" placeholder="mis. SG" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm uppercase" />
        </div>
        <div>
            <x-input-label for="nama_sarana_imp" value="Nama Sarana Pengangkut" />
            <input type="text" id="nama_sarana_imp" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'nama_sarana' : ''" x-model="formData.nama_sarana" placeholder="mis. MV Ocean Star" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
        </div>
        <div>
            <x-input-label for="voy_flight_imp" value="No. Voyage / Flight" />
            <input type="text" id="voy_flight_imp" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'voy_flight' : ''" x-model="formData.voy_flight" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
        </div>
        <div>
            <x-input-label for="kode_tps_imp" value="Kode TPS (Tempat Penimbunan Sementara)" />
            <input type="text" id="kode_tps_imp" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'kode_tps' : ''" x-model="formData.kode_tps" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
        </div>
        <div>
            <x-input-label for="tanggal_tiba_imp" value="Perkiraan Tanggal Tiba" />
            <input type="date" id="tanggal_tiba_imp" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'tanggal_tiba' : ''" x-model="formData.tanggal_tiba" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
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
    <div x-show="doc_type === 'TPB'" class="space-y-6">
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="kode_kantor_tpb" value="Kantor Bea Cukai (Referensi)" />
                <x-searchable-select id="kode_kantor_tpb" ::name="doc_type === 'TPB' ? 'kode_kantor' : ''" model="formData.kode_kantor" options="references.kantorMuat" placeholder="-- Pilih Kantor Bea Cukai --" ::required="doc_type === 'TPB'" />
            </div>
            <div>
                <x-input-label for="kode_valuta_tpb" value="Mata Uang / Valuta (Referensi)" />
                <x-searchable-select id="kode_valuta_tpb" ::name="doc_type === 'TPB' ? 'kode_valuta' : ''" model="formData.kode_valuta" options="references.currencies" placeholder="-- Pilih Mata Uang --" ::required="doc_type === 'TPB'" />
            </div>
            <div>
                <x-input-label for="nilai_barang" value="Nilai Total Barang TPB" />
                <input type="number" step="0.01" min="0" id="nilai_barang" :name="doc_type === 'TPB' ? 'nilai_barang' : ''" x-model="formData.nilai_barang" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'TPB'" />
            </div>
        </div>
        <div class="border-t border-slate-100 pt-4">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Informasi Pengangkutan</h4>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <x-input-label for="cara_angkut_tpb" value="Cara Pengangkutan" />
                    <x-searchable-select id="cara_angkut_tpb" ::name="doc_type === 'TPB' ? 'cara_angkut' : ''" model="formData.cara_angkut" options="references.caraAngkut" placeholder="-- Pilih Cara Angkut --" />
                </div>
                <div>
                    <x-input-label for="nama_sarana_tpb" value="Nama Sarana Pengangkut" />
                    <input type="text" id="nama_sarana_tpb" :name="doc_type === 'TPB' ? 'nama_sarana' : ''" x-model="formData.nama_sarana" placeholder="mis. MV CONTAINER" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                </div>
                <div>
                    <x-input-label for="voy_flight_tpb" value="Nomor Voy / Flight" />
                    <input type="text" id="voy_flight_tpb" :name="doc_type === 'TPB' ? 'voy_flight' : ''" x-model="formData.voy_flight" placeholder="mis. V-100" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                </div>
                <div>
                    <x-input-label for="kode_bendera_tpb" value="Bendera Pengangkut" />
                    <input type="text" maxlength="2" id="kode_bendera_tpb" :name="doc_type === 'TPB' ? 'kode_bendera' : ''" x-model="formData.kode_bendera" placeholder="mis. US" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm uppercase" />
                </div>
            </div>
        </div>
    </div>

    {{-- RUSH --}}
    <div x-show="doc_type === 'RUSH'" class="space-y-4">
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="kode_kantor_rush" value="Kantor Bea Cukai (Referensi)" />
                <x-searchable-select id="kode_kantor_rush" ::name="doc_type === 'RUSH' ? 'kode_kantor' : ''" model="formData.kode_kantor" options="references.kantorMuat" placeholder="-- Pilih Kantor Bea Cukai --" ::required="doc_type === 'RUSH'" />
            </div>
            <div>
                <x-input-label for="cara_angkut_rush" value="Cara Pengangkutan" />
                <x-searchable-select id="cara_angkut_rush" ::name="doc_type === 'RUSH' ? 'cara_angkut' : ''" model="formData.cara_angkut" options="references.caraAngkut" placeholder="-- Pilih Cara Angkut --" />
            </div>
            <div>
                <x-input-label for="kode_bendera_rush" value="Bendera Pengangkut" />
                <input type="text" maxlength="2" id="kode_bendera_rush" :name="doc_type === 'RUSH' ? 'kode_bendera' : ''" x-model="formData.kode_bendera" placeholder="mis. US" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm uppercase" />
            </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="nama_sarana_pengangkut" value="Sarana Pengangkut (Airlines / Carrier)" />
                <input type="text" id="nama_sarana_pengangkut" :name="doc_type === 'RUSH' ? 'nama_sarana_pengangkut' : ''" x-model="formData.nama_sarana_pengangkut" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'RUSH'" />
            </div>
            <div>
                <x-input-label for="nomor_flight" value="Nomor Flight / Voyage" />
                <input type="text" id="nomor_flight" :name="doc_type === 'RUSH' ? 'nomor_flight' : ''" x-model="formData.nomor_flight" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'RUSH'" />
            </div>
            <div>
                <x-input-label for="nomor_awb_bl" value="Nomor AWB (Air Waybill) / Bill of Lading" />
                <input type="text" id="nomor_awb_bl" :name="doc_type === 'RUSH' ? 'nomor_awb_bl' : ''" x-model="formData.nomor_awb_bl" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'RUSH'" />
            </div>
            <div>
                <x-input-label for="tanggal_awb_bl" value="Tanggal AWB / BL" />
                <input type="date" id="tanggal_awb_bl" :name="doc_type === 'RUSH' ? 'tanggal_awb_bl' : ''" x-model="formData.tanggal_awb_bl" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'RUSH'" />
            </div>
        </div>
        <div class="border-t border-slate-100 pt-4">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Informasi Kemasan</h4>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="jumlah_kemasan" value="Jumlah Kemasan" />
                    <input type="number" min="1" id="jumlah_kemasan" :name="doc_type === 'RUSH' ? 'jumlah_kemasan' : ''" x-model="formData.jumlah_kemasan" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'RUSH'" />
                </div>
                <div>
                    <x-input-label for="jenis_kemasan" value="Jenis Kemasan (Referensi)" />
                    <x-searchable-select id="jenis_kemasan" ::name="doc_type === 'RUSH' ? 'jenis_kemasan' : ''" model="formData.jenis_kemasan" options="references.packages" placeholder="-- Pilih Jenis Kemasan --" ::required="doc_type === 'RUSH'" />
                </div>
            </div>
        </div>
    </div>
</div>
