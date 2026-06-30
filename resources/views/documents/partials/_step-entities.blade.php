@php
/**
 * Step 2 — Informasi Identitas Entitas (pelaku transaksi kepabeanan).
 *
 * Berbages Alpine scope dari root x-data="documentWizard()" di create.blade.php.
 *
 * Variabel Alpine yang dipakai: formData, doc_type, references.{...}, loadSampleData().
 * Setiap section dipisah per doc_type: BC30, BC20/BC24, TPB, RUSH.
 */
@endphp

{{-- Step 2: Entities/Parties --}}
<div x-show="step === 2" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 transition-all duration-300">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Informasi Identitas Entitas</h3>
            <p class="text-xs text-slate-500 mt-0.5">Lengkapi identitas para pihak pelaku transaksi kepabeanan</p>
        </div>
        <button type="button" @click="loadSampleData()"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg shadow-sm shadow-amber-100 transition-all">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
            </svg>
            Gunakan Data Contoh
        </button>
    </div>

    {{-- BC 3.0 (Header Klasifikasi + Eksportir & Penerima) --}}
    <div x-show="doc_type === 'BC30'" class="space-y-6">
        {{-- Data Header: klasifikasi ekspor sesuai CEISA 4.0 --}}
        <div class="border-b border-slate-100 pb-4">
            <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Data Header (Klasifikasi Ekspor)</h4>
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

        {{-- Entitas: Eksportir --}}
        <div class="border-b border-slate-100 pb-4">
            <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Identitas Eksportir</h4>
            <div class="grid sm:grid-cols-2 gap-4 mt-3">
                <div>
                    <x-input-label for="nama_eksportir" value="Nama Perusahaan Eksportir" />
                    <input type="text" id="nama_eksportir" name="nama_eksportir" x-model="formData.nama_eksportir" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required />
                </div>
                <div>
                    <x-input-label for="npwp_eksportir" value="NPWP (15 Digit)" />
                    <input type="text" id="npwp_eksportir" name="npwp_eksportir" x-model="formData.npwp_eksportir" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" placeholder="012345678901000" required />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="alamat_eksportir" value="Alamat Eksportir Lengkap" />
                    <textarea id="alamat_eksportir" name="alamat_eksportir" x-model="formData.alamat_eksportir" rows="2" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required></textarea>
                </div>
            </div>
        </div>

        {{-- Entitas: Penerima --}}
        <div>
            <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Identitas Penerima (Consignee)</h4>
            <div class="grid sm:grid-cols-2 gap-4 mt-3">
                <div>
                    <x-input-label for="nama_penerima" value="Nama Penerima" />
                    <input type="text" id="nama_penerima" name="nama_penerima" x-model="formData.nama_penerima" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required />
                </div>
                <div>
                    <x-input-label for="negara_tujuan" value="Negara Tujuan (Kode Referensi)" />
                    <x-searchable-select id="negara_tujuan" name="negara_tujuan" model="formData.negara_tujuan" options="references.countries" placeholder="-- Pilih Negara Tujuan --" required />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="alamat_penerima" value="Alamat Penerima (Opsional)" />
                    <textarea id="alamat_penerima" name="alamat_penerima" x-model="formData.alamat_penerima" rows="2" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm"></textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- BC 2.0 / BC 2.4 (Importir & Pemasok) --}}
    <div x-show="doc_type === 'BC20' || doc_type === 'BC24'" class="space-y-6">
        <div class="border-b border-slate-100 pb-4">
            <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider" x-text="doc_type === 'BC20' ? 'Identitas Importir BC 2.0' : 'Identitas Importir BC 2.4'"></h4>
            <div class="grid sm:grid-cols-2 gap-4 mt-3">
                <div>
                    <x-input-label for="nama_importir" value="Nama Importir" />
                    <input type="text" id="nama_importir" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'nama_importir' : ''" x-model="formData.nama_importir" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC20' || doc_type === 'BC24'" />
                </div>
                <div>
                    <x-input-label for="npwp_importir" value="NPWP (15 Digit)" />
                    <input type="text" id="npwp_importir" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'npwp_importir' : ''" x-model="formData.npwp_importir" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" placeholder="012345678901000" :required="doc_type === 'BC20' || doc_type === 'BC24'" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="alamat_importir" value="Alamat Importir Lengkap" />
                    <textarea id="alamat_importir" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'alamat_importir' : ''" x-model="formData.alamat_importir" rows="2" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC20' || doc_type === 'BC24'"></textarea>
                </div>
            </div>
        </div>
        <div>
            <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Identitas Pemasok (Supplier)</h4>
            <div class="grid sm:grid-cols-2 gap-4 mt-3">
                <div>
                    <x-input-label for="nama_pemasok" value="Nama Pemasok" />
                    <input type="text" id="nama_pemasok" :name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'nama_pemasok' : ''" x-model="formData.nama_pemasok" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC20' || doc_type === 'BC24'" />
                </div>
                <div>
                    <x-input-label for="negara_pemasok" value="Negara Asal Pemasok (Kode Referensi)" />
                    <x-searchable-select id="negara_pemasok" ::name="(doc_type === 'BC20' || doc_type === 'BC24') ? 'negara_pemasok' : ''" model="formData.negara_pemasok" options="references.countries" placeholder="-- Pilih Negara Asal --" ::required="doc_type === 'BC20' || doc_type === 'BC24'" />
                </div>
            </div>
        </div>
    </div>

    {{-- TPB (Pengusaha TPB & Fasilitas) --}}
    <div x-show="doc_type === 'TPB'" class="space-y-6">
        <div class="border-b border-slate-100 pb-4">
            <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Pengusaha Tempat Penimbunan Berikat (TPB)</h4>
            <div class="grid sm:grid-cols-2 gap-4 mt-3">
                <div>
                    <x-input-label for="nama_tpb" value="Nama Pengusaha TPB" />
                    <input type="text" id="nama_tpb" :name="doc_type === 'TPB' ? 'nama_tpb' : ''" x-model="formData.nama_tpb" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'TPB'" />
                </div>
                <div>
                    <x-input-label for="npwp_tpb" value="NPWP (15 Digit)" />
                    <input type="text" id="npwp_tpb" :name="doc_type === 'TPB' ? 'npwp_tpb' : ''" x-model="formData.npwp_tpb" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" placeholder="012345678901000" :required="doc_type === 'TPB'" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="alamat_tpb" value="Alamat Lokasi TPB" />
                    <textarea id="alamat_tpb" :name="doc_type === 'TPB' ? 'alamat_tpb' : ''" x-model="formData.alamat_tpb" rows="2" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'TPB'"></textarea>
                </div>
            </div>
        </div>
        <div>
            <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Detail Fasilitas TPB</h4>
            <div class="grid sm:grid-cols-3 gap-4 mt-3">
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

    {{-- RUSH (Pemohon) --}}
    <div x-show="doc_type === 'RUSH'" class="space-y-6">
        <div class="border-b border-slate-100 pb-4">
            <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Identitas Pemohon Rush Handling</h4>
            <div class="grid sm:grid-cols-2 gap-4 mt-3">
                <div>
                    <x-input-label for="nama_pemohon" value="Nama Pemohon / Perusahaan" />
                    <input type="text" id="nama_pemohon" :name="doc_type === 'RUSH' ? 'nama_pemohon' : ''" x-model="formData.nama_pemohon" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'RUSH'" />
                </div>
                <div>
                    <x-input-label for="npwp_pemohon" value="NPWP Pemohon (15 Digit)" />
                    <input type="text" id="npwp_pemohon" :name="doc_type === 'RUSH' ? 'npwp_pemohon' : ''" x-model="formData.npwp_pemohon" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" placeholder="012345678901000" :required="doc_type === 'RUSH'" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="alamat_pemohon" value="Alamat Lengkap Pemohon" />
                    <textarea id="alamat_pemohon" :name="doc_type === 'RUSH' ? 'alamat_pemohon' : ''" x-model="formData.alamat_pemohon" rows="2" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'RUSH'"></textarea>
                </div>
            </div>
        </div>
        <div>
            <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Alasan Kebutuhan Rush Handling</h4>
            <div class="mt-3">
                <x-input-label for="alasan_segera" value="Pilih Kebutuhan Barang Segera (Referensi)" />
                <x-searchable-select id="alasan_segera" ::name="doc_type === 'RUSH' ? 'alasan_segera' : ''" model="formData.alasan_segera" :options="[
                    'Organ Tubuh Manusia / Darah / Jenazah' => 'Organ Tubuh Manusia / Darah / Jenazah',
                    'Vaksin / Serum / Obat-obatan Kritis' => 'Vaksin / Serum / Obat-obatan Kritis',
                    'Binatang Hidup (Live Animals)' => 'Binatang Hidup (Live Animals)',
                    'Tumbuhan / Bibit Hidup (Live Plants)' => 'Tumbuhan / Bibit Hidup (Live Plants)',
                    'Surat Kabar / Majalah / Berita Aktual' => 'Surat Kabar / Majalah / Berita Aktual',
                    'Barang lain yang karena sifatnya membutuhkan penanganan segera' => 'Lainnya (Tulis detail di bawah)'
                ]" placeholder="-- Pilih Alasan Utama --" ::required="doc_type === 'RUSH'" />
                <input type="text" :name="doc_type === 'RUSH' ? 'alasan_segera' : ''" x-model="formData.alasan_segera" class="mt-2 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" placeholder="Tulis alasan khusus bila tidak ada di daftar..." x-show="formData.alasan_segera === 'Barang lain yang karena sifatnya membutuhkan penanganan segera' || !['Organ Tubuh Manusia / Darah / Jenazah', 'Vaksin / Serum / Obat-obatan Kritis', 'Binatang Hidup (Live Animals)', 'Tumbuhan / Bibit Hidup (Live Plants)', 'Surat Kabar / Majalah / Berita Aktual', ''].includes(formData.alasan_segera)" />
            </div>
        </div>
    </div>
</div>
