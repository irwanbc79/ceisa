@php
/**
 * Step 2 — Data Header dokumen pabean.
 *
 * Selaras urutan Portal CEISA 4.0 resmi (usermanualceisa40.gitbook.io):
 * Data Header adalah tahap pertama perekaman setelah memilih layanan portal.
 * Berbagi Alpine scope dari root x-data="documentWizard()" di create.blade.php.
 */
@endphp

{{-- Step 2: Data Header --}}
<div x-show="step === 2" class="bg-white/70 backdrop-blur-xl border border-white/60 shadow-xl shadow-slate-100/30 rounded-2xl p-6 transition-all duration-300">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Data Header</h3>
            <p class="text-xs text-slate-500 mt-0.5">Data dasar dokumen: kantor pabean, klasifikasi, dan nomor pengajuan</p>
        </div>
        <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-full border border-indigo-100">Tahap <span x-text="step"></span> dari <span x-text="steps.length"></span></span>
    </div>

    {{-- Common: Nomor AJU Kustom --}}
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

    {{-- BC 3.0 — Header Klasifikasi Ekspor (sesuai Data Header Portal Ekspor) --}}
    <div x-show="doc_type === 'BC30'">
        <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Klasifikasi Ekspor</h4>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-3">
            <div>
                <x-input-label for="kantor_muat" value="Kantor Muat" />
                <x-searchable-select id="kantor_muat" name="kantor_muat" model="formData.kantor_muat" options="references.kantorMuat" placeholder="-- Pilih Kantor Muat --" />
            </div>
            <div>
                <x-input-label for="jenis_ekspor" value="Jenis Ekspor" />
                <x-searchable-select id="jenis_ekspor" name="jenis_ekspor" model="formData.jenis_ekspor" options="references.jenisEkspor" placeholder="-- Pilih Jenis Ekspor --" />
            </div>
            <div>
                <x-input-label for="kategori_ekspor" value="Kategori Ekspor" />
                <x-searchable-select id="kategori_ekspor" name="kategori_ekspor" model="formData.kategori_ekspor" options="references.kategoriEkspor" placeholder="-- Pilih Kategori Ekspor --" />
            </div>
            <div>
                <x-input-label for="cara_dagang" value="Cara Dagang" />
                <x-searchable-select id="cara_dagang" name="cara_dagang" model="formData.cara_dagang" options="references.caraDagang" placeholder="-- Pilih Cara Dagang --" />
            </div>
            <div>
                <x-input-label for="cara_bayar" value="Cara Bayar" />
                <x-searchable-select id="cara_bayar" name="cara_bayar" model="formData.cara_bayar" options="references.caraBayar" placeholder="-- Pilih Cara Bayar --" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-input-label for="komoditi" value="Komoditi" />
                    <x-searchable-select id="komoditi" name="komoditi" model="formData.komoditi" :options="['NON_MIGAS' => 'Non Migas', 'MIGAS' => 'Migas']" placeholder="-- Pilih Komoditi --" />
                </div>
                <div>
                    <x-input-label for="curah" value="Curah" />
                    <x-searchable-select id="curah" name="curah" model="formData.curah" :options="['NON_CURAH' => 'Non Curah', 'CURAH' => 'Curah']" placeholder="-- Pilih Curah --" />
                </div>
            </div>
        </div>
    </div>

    {{-- BC 2.0 / BC 2.4 — Header Impor --}}
    <div x-show="doc_type === 'BC20' || doc_type === 'BC24'">
        <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Header Impor</h4>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-3">
            <div>
                <x-input-label for="kode_kantor_imp" value="Kantor Pabean" />
                <x-searchable-select id="kode_kantor_imp" ::name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'kode_kantor' : ''" model="formData.kode_kantor" options="references.kantorMuat" placeholder="-- Pilih Kantor Pabean --" />
                <p class="text-[10px] text-slate-400 mt-1">Kosongkan = diambil dari kode pelabuhan bongkar.</p>
            </div>
            <div>
                <x-input-label for="jenis_impor_imp" value="Jenis Impor (kode CEISA)" />
                <input type="text" maxlength="5" id="jenis_impor_imp" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'jenis_impor' : ''" x-model="formData.jenis_impor" placeholder="1 = Untuk Dipakai" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
            </div>
            <div>
                <x-input-label for="cara_bayar_imp" value="Cara Bayar (kode CEISA)" />
                {{-- Tanpa name: nilai di-post oleh select cara_bayar BC30 (model sama) --}}
                <input type="text" maxlength="5" id="cara_bayar_imp" x-model="formData.cara_bayar" placeholder="1 = Biasa/Tunai" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
            </div>
        </div>
    </div>

    {{-- TPB — Kantor & Fasilitas --}}
    <div x-show="doc_type === 'TPB'" class="space-y-4">
        <div>
            <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Kantor & Fasilitas TPB</h4>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-3">
                <div>
                    <x-input-label for="kode_kantor_tpb" value="Kantor Bea Cukai (Referensi)" />
                    <x-searchable-select id="kode_kantor_tpb" ::name="doc_type === 'TPB' ? 'kode_kantor' : ''" model="formData.kode_kantor" options="references.kantorMuat" placeholder="-- Pilih Kantor Bea Cukai --" ::required="doc_type === 'TPB'" />
                </div>
                <div>
                    <x-input-label for="jenis_tpb" value="Jenis TPB (Referensi)" />
                    <x-searchable-select id="jenis_tpb" ::name="doc_type === 'TPB' ? 'jenis_tpb' : ''" model="formData.jenis_tpb" options="references.tpbTypes" placeholder="-- Pilih Jenis TPB --" ::required="doc_type === 'TPB'" />
                </div>
                <div>
                    <x-input-label for="tujuan_tpb" value="Tujuan Pengiriman (Referensi)" />
                    <x-searchable-select id="tujuan_tpb" ::name="doc_type === 'TPB' ? 'tujuan_tpb' : ''" model="formData.tujuan_tpb" options="references.tpbDestinations" placeholder="-- Pilih Tujuan --" ::required="doc_type === 'TPB'" />
                </div>
                <div>
                    <x-input-label for="dokumen_referensi" value="No. Dokumen Referensi / Kontrak" />
                    <input type="text" id="dokumen_referensi" :name="doc_type === 'TPB' ? 'dokumen_referensi' : ''" x-model="formData.dokumen_referensi" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'TPB'" />
                </div>
            </div>
        </div>
    </div>

    {{-- RUSH — Kantor --}}
    <div x-show="doc_type === 'RUSH'">
        <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Kantor Pabean</h4>
        <div class="grid sm:grid-cols-3 gap-4 mt-3">
            <div>
                <x-input-label for="kode_kantor_rush" value="Kantor Bea Cukai (Referensi)" />
                <x-searchable-select id="kode_kantor_rush" ::name="doc_type === 'RUSH' ? 'kode_kantor' : ''" model="formData.kode_kantor" options="references.kantorMuat" placeholder="-- Pilih Kantor Bea Cukai --" ::required="doc_type === 'RUSH'" />
            </div>
        </div>
    </div>
</div>
