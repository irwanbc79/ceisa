@php
/**
 * Step 5 — Data Pengangkut.
 *
 * Selaras urutan Portal CEISA 4.0 resmi: Data Pengangkut adalah tahap
 * tersendiri setelah Dokumen Pelengkap (sebelum Kemasan & Transaksi).
 * Berbagi Alpine scope dari root x-data="documentWizard()".
 */
@endphp

{{-- Step 5: Data Pengangkut --}}
<div x-show="step === 5" x-cloak class="bg-white/90 backdrop-blur-xl border border-slate-200 shadow-card rounded-2xl p-6 transition-all duration-300">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Data Pengangkut</h3>
            <p class="text-xs text-slate-500 mt-0.5">Sarana pengangkut, pelabuhan, dan jadwal pengangkutan</p>
        </div>
        <span class="px-3 py-1 bg-indigo-50 text-indigo-300 text-xs font-bold rounded-full border border-indigo-100">Tahap <span x-text="step"></span> dari <span x-text="steps.length"></span></span>
    </div>

    {{-- BC 3.0 --}}
    <div x-show="doc_type === 'BC30'">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
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
    </div>

    {{-- TPB --}}
    <div x-show="doc_type === 'TPB'">
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

    {{-- RUSH --}}
    <div x-show="doc_type === 'RUSH'" class="space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="cara_angkut_rush" value="Cara Pengangkutan" />
                <x-searchable-select id="cara_angkut_rush" ::name="doc_type === 'RUSH' ? 'cara_angkut' : ''" model="formData.cara_angkut" options="references.caraAngkut" placeholder="-- Pilih Cara Angkut --" />
            </div>
            <div>
                <x-input-label for="kode_bendera_rush" value="Bendera Pengangkut" />
                <input type="text" maxlength="2" id="kode_bendera_rush" :name="doc_type === 'RUSH' ? 'kode_bendera' : ''" x-model="formData.kode_bendera" placeholder="mis. US" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm uppercase" />
            </div>
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
    </div>
</div>
