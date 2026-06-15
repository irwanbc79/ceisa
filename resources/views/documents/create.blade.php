<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 tracking-tight">
                    {{ __('Perekaman Dokumen CEISA 4.0') }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">Sistem Input Kepabeanan Host-to-Host (H2H) M2B</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen" x-data="documentWizard()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-flash />

            @if ($errors->any())
                <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 shadow-sm text-sm text-rose-800 flex items-start gap-3">
                    <svg class="h-5 w-5 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    <div>
                        <span class="font-semibold">Kesalahan Validasi:</span> Terdapat {{ $errors->count() }} kesalahan input. Silakan cek kembali form isian Anda.
                    </div>
                </div>
            @endif

            {{-- Client-side validation banner (Alpine) --}}
            <div x-show="formError" x-transition style="display:none"
                 class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 shadow-sm text-sm text-rose-800 flex items-start gap-3">
                <svg class="h-5 w-5 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <div><span class="font-semibold">Belum lengkap:</span> <span x-text="formError"></span></div>
            </div>

            {{-- Stepper Progress Bar --}}
            <div class="mb-8 bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <div class="relative flex items-center justify-between">
                    {{-- Progress Line Background --}}
                    <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-1 bg-slate-100 rounded"></div>
                    {{-- Active Progress Line --}}
                    <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-indigo-600 rounded transition-all duration-500"
                         :style="`width: ${((step - 1) / (steps.length - 1)) * 100}%`"></div>

                    {{-- Steps --}}
                    <template x-for="(s, idx) in steps" :key="idx">
                        <button type="button" @click="goToStep(idx + 1)"
                                :disabled="idx + 1 > step && !isStepValid(step)"
                                class="relative z-10 flex flex-col items-center group focus:outline-none disabled:cursor-not-allowed">
                            <div class="h-10 w-10 rounded-full flex items-center justify-center font-semibold text-sm border-2 transition-all duration-300"
                                 :class="step === idx + 1 
                                    ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-100 scale-110' 
                                    : (step > idx + 1 
                                        ? 'bg-emerald-500 border-emerald-500 text-white' 
                                        : 'bg-white border-slate-200 text-slate-400 group-hover:border-slate-300')">
                                <span x-show="step <= idx + 1" x-text="idx + 1"></span>
                                <svg x-show="step > idx + 1" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <span class="text-xs font-semibold mt-2 transition-colors duration-300"
                                  :class="step === idx + 1 ? 'text-indigo-600 font-bold' : (step > idx + 1 ? 'text-emerald-600' : 'text-slate-400')"
                                  x-text="s.title"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Main Layout: Form & JSON Preview --}}
            <div class="grid lg:grid-cols-3 gap-8 items-start">
                
                {{-- Form Section --}}
                <div class="lg:col-span-2 space-y-6">
                    <form method="POST" action="{{ route('documents.store') }}" id="ceisaDocForm" novalidate>
                        @csrf
                        <input type="hidden" name="doc_type" :value="doc_type" />
                        <input type="hidden" name="submit_action" id="submit_action" value="submit" />

                        {{-- Step 1: Portal Selection --}}
                        <div x-show="step === 1" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 transition-all duration-300">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">Pilih Jenis Layanan Portal</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Tentukan jenis dokumen kepabeanan yang ingin Anda rekam</p>
                                </div>
                                <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-full border border-indigo-100">Tahap 1 dari 5</span>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <template x-for="item in docTypes" :key="item.code">
                                    <button type="button" @click="selectDocType(item.code)"
                                            class="flex items-start text-left p-5 border-2 rounded-2xl transition-all duration-300 hover:shadow-md group relative overflow-hidden"
                                            :class="doc_type === item.code 
                                                ? 'border-indigo-600 bg-indigo-50/50 shadow-sm ring-1 ring-indigo-600' 
                                                : 'border-slate-100 bg-white hover:border-slate-300'">
                                        <div class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                                             :class="doc_type === item.code ? 'bg-indigo-600 text-white' : 'bg-slate-50 text-slate-500 group-hover:bg-slate-100'">
                                            <span x-html="item.icon"></span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="font-bold text-slate-800 flex items-center gap-2">
                                                <span x-text="item.label"></span>
                                                <span class="text-[10px] uppercase px-1.5 py-0.5 font-extrabold rounded-md tracking-wider transition-colors"
                                                      :class="item.badgeClass" x-text="item.code"></span>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-1" x-text="item.description"></p>
                                        </div>
                                        
                                        {{-- Glow Effect --}}
                                        <div class="absolute -right-10 -bottom-10 h-24 w-24 rounded-full bg-indigo-100/30 blur-2xl transition-opacity duration-300 opacity-0 group-hover:opacity-100"
                                             x-show="doc_type === item.code"></div>
                                    </button>
                                </template>
                            </div>
                        </div>

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
                                            <select id="kantor_muat" name="kantor_muat" x-model="formData.kantor_muat" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                                                <option value="">-- Pilih Kantor Muat --</option>
                                                <template x-for="k in references.kantorMuat" :key="k.code">
                                                    <option :value="k.code" x-text="k.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <x-input-label for="jenis_ekspor" value="Jenis Ekspor" />
                                            <select id="jenis_ekspor" name="jenis_ekspor" x-model="formData.jenis_ekspor" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                                                <template x-for="j in references.jenisEkspor" :key="j.code">
                                                    <option :value="j.code" x-text="j.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <x-input-label for="kategori_ekspor" value="Kategori Ekspor" />
                                            <select id="kategori_ekspor" name="kategori_ekspor" x-model="formData.kategori_ekspor" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                                                <template x-for="k in references.kategoriEkspor" :key="k.code">
                                                    <option :value="k.code" x-text="k.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <x-input-label for="cara_dagang" value="Cara Dagang" />
                                            <select id="cara_dagang" name="cara_dagang" x-model="formData.cara_dagang" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                                                <template x-for="c in references.caraDagang" :key="c.code">
                                                    <option :value="c.code" x-text="c.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <x-input-label for="cara_bayar" value="Cara Bayar" />
                                            <select id="cara_bayar" name="cara_bayar" x-model="formData.cara_bayar" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                                                <template x-for="c in references.caraBayar" :key="c.code">
                                                    <option :value="c.code" x-text="c.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <x-input-label for="komoditi" value="Komoditi" />
                                                <select id="komoditi" name="komoditi" x-model="formData.komoditi" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                                                    <option value="NON_MIGAS">Non Migas</option>
                                                    <option value="MIGAS">Migas</option>
                                                </select>
                                            </div>
                                            <div>
                                                <x-input-label for="curah" value="Curah" />
                                                <select id="curah" name="curah" x-model="formData.curah" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                                                    <option value="NON_CURAH">Non Curah</option>
                                                    <option value="CURAH">Curah</option>
                                                </select>
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
                                            <select id="negara_tujuan" name="negara_tujuan" x-model="formData.negara_tujuan" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required>
                                                <option value="">-- Pilih Negara Tujuan --</option>
                                                <template x-for="c in references.countries" :key="c.code">
                                                    <option :value="c.code" x-text="c.label"></option>
                                                </template>
                                            </select>
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
                                            <input type="text" id="nama_importir" name="nama_importir" x-model="formData.nama_importir" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required />
                                        </div>
                                        <div>
                                            <x-input-label for="npwp_importir" value="NPWP (15 Digit)" />
                                            <input type="text" id="npwp_importir" name="npwp_importir" x-model="formData.npwp_importir" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" placeholder="012345678901000" required />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <x-input-label for="alamat_importir" value="Alamat Importir Lengkap" />
                                            <textarea id="alamat_importir" name="alamat_importir" x-model="formData.alamat_importir" rows="2" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Identitas Pemasok (Supplier)</h4>
                                    <div class="grid sm:grid-cols-2 gap-4 mt-3">
                                        <div>
                                            <x-input-label for="nama_pemasok" value="Nama Pemasok" />
                                            <input type="text" id="nama_pemasok" name="nama_pemasok" x-model="formData.nama_pemasok" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required />
                                        </div>
                                        <div>
                                            <x-input-label for="negara_pemasok" value="Negara Asal Pemasok (Kode Referensi)" />
                                            <select id="negara_pemasok" name="negara_pemasok" x-model="formData.negara_pemasok" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required>
                                                <option value="">-- Pilih Negara Asal --</option>
                                                <template x-for="c in references.countries" :key="c.code">
                                                    <option :value="c.code" x-text="c.label"></option>
                                                </template>
                                            </select>
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
                                            <input type="text" id="nama_tpb" name="nama_tpb" x-model="formData.nama_tpb" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required />
                                        </div>
                                        <div>
                                            <x-input-label for="npwp_tpb" value="NPWP (15 Digit)" />
                                            <input type="text" id="npwp_tpb" name="npwp_tpb" x-model="formData.npwp_tpb" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" placeholder="012345678901000" required />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <x-input-label for="alamat_tpb" value="Alamat Lokasi TPB" />
                                            <textarea id="alamat_tpb" name="alamat_tpb" x-model="formData.alamat_tpb" rows="2" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Detail Fasilitas TPB</h4>
                                    <div class="grid sm:grid-cols-3 gap-4 mt-3">
                                        <div>
                                            <x-input-label for="jenis_tpb" value="Jenis TPB (Referensi)" />
                                            <select id="jenis_tpb" name="jenis_tpb" x-model="formData.jenis_tpb" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required>
                                                <option value="">-- Pilih Jenis TPB --</option>
                                                <template x-for="t in references.tpbTypes" :key="t.code">
                                                    <option :value="t.code" x-text="t.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <x-input-label for="tujuan_tpb" value="Tujuan Pengiriman (Referensi)" />
                                            <select id="tujuan_tpb" name="tujuan_tpb" x-model="formData.tujuan_tpb" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required>
                                                <option value="">-- Pilih Tujuan --</option>
                                                <template x-for="d in references.tpbDestinations" :key="d.code">
                                                    <option :value="d.code" x-text="d.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <x-input-label for="dokumen_referensi" value="No. Dokumen Referensi / Kontrak" />
                                            <input type="text" id="dokumen_referensi" name="dokumen_referensi" x-model="formData.dokumen_referensi" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required />
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
                                            <input type="text" id="nama_pemohon" name="nama_pemohon" x-model="formData.nama_pemohon" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required />
                                        </div>
                                        <div>
                                            <x-input-label for="npwp_pemohon" value="NPWP Pemohon (15 Digit)" />
                                            <input type="text" id="npwp_pemohon" name="npwp_pemohon" x-model="formData.npwp_pemohon" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" placeholder="012345678901000" required />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <x-input-label for="alamat_pemohon" value="Alamat Lengkap Pemohon" />
                                            <textarea id="alamat_pemohon" name="alamat_pemohon" x-model="formData.alamat_pemohon" rows="2" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Alasan Kebutuhan Rush Handling</h4>
                                    <div class="mt-3">
                                        <x-input-label for="alasan_segera" value="Pilih Kebutuhan Barang Segera (Referensi)" />
                                        <select id="alasan_segera" name="alasan_segera" x-model="formData.alasan_segera" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                                            <option value="">-- Pilih Alasan Utama --</option>
                                            <option value="Organ Tubuh Manusia / Darah / Jenazah">Organ Tubuh Manusia / Darah / Jenazah</option>
                                            <option value="Vaksin / Serum / Obat-obatan Kritis">Vaksin / Serum / Obat-obatan Kritis</option>
                                            <option value="Binatang Hidup (Live Animals)">Binatang Hidup (Live Animals)</option>
                                            <option value="Tumbuhan / Bibit Hidup (Live Plants)">Tumbuhan / Bibit Hidup (Live Plants)</option>
                                            <option value="Surat Kabar / Majalah / Berita Aktual">Surat Kabar / Majalah / Berita Aktual</option>
                                            <option value="Barang lain yang karena sifatnya membutuhkan penanganan segera">Lainnya (Tulis detail di bawah)</option>
                                        </select>
                                        <input type="text" name="alasan_segera_custom" x-model="formData.alasan_segera" class="mt-2 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" placeholder="Tulis alasan khusus bila tidak ada di daftar..." x-show="formData.alasan_segera === 'Barang lain yang karena sifatnya membutuhkan penanganan segera' || !['Organ Tubuh Manusia / Darah / Jenazah', 'Vaksin / Serum / Obat-obatan Kritis', 'Binatang Hidup (Live Animals)', 'Tumbuhan / Bibit Hidup (Live Plants)', 'Surat Kabar / Majalah / Berita Aktual', ''].includes(formData.alasan_segera)" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Step 3: Logistics & Transactions --}}
                        <div x-show="step === 3" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 transition-all duration-300">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">Pengangkutan &amp; Transaksi</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Rincian sarana logistik dan nilai moneter dokumen</p>
                                </div>
                                <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-full border border-indigo-100">Tahap 3 dari 5</span>
                            </div>

                            {{-- BC 3.0 — Pengangkut & Transaksi (CEISA 4.0) --}}
                            <div x-show="doc_type === 'BC30'" class="space-y-6">
                                <div class="border-b border-slate-100 pb-4">
                                    <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Data Pengangkut</h4>
                                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-3">
                                        <div>
                                            <x-input-label for="cara_angkut" value="Cara Pengangkutan" />
                                            <select id="cara_angkut" name="cara_angkut" x-model="formData.cara_angkut" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                                                <template x-for="c in references.caraAngkut" :key="c.code">
                                                    <option :value="c.code" x-text="c.label"></option>
                                                </template>
                                            </select>
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
                                            <select id="pelabuhan_muat_bc30" name="pelabuhan_muat" x-model="formData.pelabuhan_muat" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC30'">
                                                <option value="">-- Pilih Pelabuhan Muat --</option>
                                                <template x-for="p in references.ports" :key="p.code">
                                                    <option :value="p.code" x-text="p.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <x-input-label for="pelabuhan_tujuan" value="Pelabuhan Tujuan" />
                                            <select id="pelabuhan_tujuan" name="pelabuhan_tujuan" x-model="formData.pelabuhan_tujuan" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC30'">
                                                <option value="">-- Pilih Pelabuhan Tujuan --</option>
                                                <template x-for="p in references.ports" :key="p.code">
                                                    <option :value="p.code" x-text="p.label"></option>
                                                </template>
                                            </select>
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
                                            <select id="kode_valuta_bc30" name="kode_valuta" x-model="formData.kode_valuta" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC30'">
                                                <option value="">-- Pilih Mata Uang --</option>
                                                <template x-for="c in references.currencies" :key="c.code">
                                                    <option :value="c.code" x-text="c.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <x-input-label for="ndpbm" value="NDPBM / Kurs (ke IDR)" />
                                            <input type="number" step="0.0001" min="0" id="ndpbm" name="ndpbm" x-model="formData.ndpbm" placeholder="mis. 15800" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC30'" />
                                        </div>
                                        <div>
                                            <x-input-label for="incoterm" value="Cara Penyerahan (Incoterm)" />
                                            <select id="incoterm" name="incoterm" x-model="formData.incoterm" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC30'">
                                                <template x-for="i in references.incoterms" :key="i.code">
                                                    <option :value="i.code" x-text="i.label"></option>
                                                </template>
                                            </select>
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
                                            <select id="asuransi_jenis" name="asuransi_jenis" x-model="formData.asuransi_jenis" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                                                <option value="DN">Dalam Negeri</option>
                                                <option value="LN">Luar Negeri</option>
                                            </select>
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
                                            <select id="cara_pembayaran_bc30" name="cara_pembayaran" x-model="formData.cara_pembayaran" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                                                <option value="">-- Pilih Cara Pembayaran --</option>
                                                <template x-for="m in references.paymentMethods" :key="m.code">
                                                    <option :value="m.code" x-text="m.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- BC 2.0 / BC 2.4 --}}
                            <div x-show="doc_type === 'BC20' || doc_type === 'BC24'" class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="pelabuhan_muat" value="Pelabuhan Muat (Kode Referensi)" />
                                    <select id="pelabuhan_muat" name="pelabuhan_muat" x-model="formData.pelabuhan_muat" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required>
                                        <option value="">-- Pilih Pelabuhan Muat --</option>
                                        <template x-for="p in references.ports" :key="p.code">
                                            <option :value="p.code" x-text="p.label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="pelabuhan_bongkar" value="Pelabuhan Bongkar (Kode Referensi)" />
                                    <select id="pelabuhan_bongkar" name="pelabuhan_bongkar" x-model="formData.pelabuhan_bongkar" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type !== 'BC30'">
                                        <option value="">-- Pilih Pelabuhan Bongkar --</option>
                                        <template x-for="p in references.ports" :key="p.code">
                                            <option :value="p.code" x-text="p.label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="kode_valuta" value="Mata Uang / Valuta (Referensi)" />
                                    <select id="kode_valuta" name="kode_valuta" x-model="formData.kode_valuta" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" required>
                                        <option value="">-- Pilih Mata Uang --</option>
                                        <template x-for="c in references.currencies" :key="c.code">
                                            <option :value="c.code" x-text="c.label"></option>
                                        </template>
                                    </select>
                                </div>
                                
                                <div x-show="doc_type === 'BC20' || doc_type === 'BC24'">
                                    <x-input-label for="nilai_cif" value="Nilai CIF Total (Cost, Insurance, Freight)" />
                                    <input type="number" step="0.01" min="0" id="nilai_cif" name="nilai_cif" x-model="formData.nilai_cif" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC20' || doc_type === 'BC24'" />
                                </div>

                                <div class="sm:col-span-2">
                                    <x-input-label for="cara_pembayaran" value="Cara Pembayaran (Referensi)" />
                                    <select id="cara_pembayaran" name="cara_pembayaran" x-model="formData.cara_pembayaran" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm">
                                        <option value="">-- Pilih Cara Pembayaran --</option>
                                        <template x-for="m in references.paymentMethods" :key="m.code">
                                            <option :value="m.code" x-text="m.label"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            {{-- TPB --}}
                            <div x-show="doc_type === 'TPB'" class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="kode_valuta_tpb" value="Mata Uang / Valuta (Referensi)" />
                                    <select id="kode_valuta_tpb" name="kode_valuta" x-model="formData.kode_valuta" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'TPB'">
                                        <option value="">-- Pilih Mata Uang --</option>
                                        <template x-for="c in references.currencies" :key="c.code">
                                            <option :value="c.code" x-text="c.label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="nilai_barang" value="Nilai Total Barang TPB" />
                                    <input type="number" step="0.01" min="0" id="nilai_barang" name="nilai_barang" x-model="formData.nilai_barang" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'TPB'" />
                                </div>
                            </div>

                            {{-- RUSH --}}
                            <div x-show="doc_type === 'RUSH'" class="space-y-4">
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="nama_sarana_pengangkut" value="Sarana Pengangkut (Airlines / Carrier)" />
                                        <input type="text" id="nama_sarana_pengangkut" name="nama_sarana_pengangkut" x-model="formData.nama_sarana_pengangkut" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'RUSH'" />
                                    </div>
                                    <div>
                                        <x-input-label for="nomor_flight" value="Nomor Flight / Voyage" />
                                        <input type="text" id="nomor_flight" name="nomor_flight" x-model="formData.nomor_flight" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'RUSH'" />
                                    </div>
                                    <div>
                                        <x-input-label for="nomor_awb_bl" value="Nomor AWB (Air Waybill) / Bill of Lading" />
                                        <input type="text" id="nomor_awb_bl" name="nomor_awb_bl" x-model="formData.nomor_awb_bl" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'RUSH'" />
                                    </div>
                                    <div>
                                        <x-input-label for="tanggal_awb_bl" value="Tanggal AWB / BL" />
                                        <input type="date" id="tanggal_awb_bl" name="tanggal_awb_bl" x-model="formData.tanggal_awb_bl" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'RUSH'" />
                                    </div>
                                </div>
                                <div class="border-t border-slate-100 pt-4">
                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Informasi Kemasan</h4>
                                    <div class="grid sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label for="jumlah_kemasan" value="Jumlah Kemasan" />
                                            <input type="number" min="1" id="jumlah_kemasan" name="jumlah_kemasan" x-model="formData.jumlah_kemasan" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'RUSH'" />
                                        </div>
                                        <div>
                                            <x-input-label for="jenis_kemasan" value="Jenis Kemasan (Referensi)" />
                                            <select id="jenis_kemasan" name="jenis_kemasan" x-model="formData.jenis_kemasan" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'RUSH'">
                                                <option value="">-- Pilih Jenis Kemasan --</option>
                                                <template x-for="p in references.packages" :key="p.code">
                                                    <option :value="p.code" x-text="p.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

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
                                            <button type="button" @click="removeItem(index)" x-show="formData.barang.length > 1"
                                                    class="text-xs text-rose-600 hover:underline">Hapus Pos</button>
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

                        {{-- Step 5: Review & Send --}}
                        <div x-show="step === 5" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 transition-all duration-300">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">Review Data &amp; Submit</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Konfirmasi seluruh isian sebelum didaftarkan ke Bea Cukai</p>
                                </div>
                                <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-full border border-emerald-100">Siap Submit</span>
                            </div>

                            <div class="space-y-6">
                                {{-- Summary Cards --}}
                                <div class="bg-slate-50 rounded-xl p-5 border border-slate-100">
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Rangkuman Header Dokumen</h4>
                                    <dl class="grid sm:grid-cols-2 gap-y-3 gap-x-6 text-sm">
                                        <div>
                                            <dt class="text-slate-500">Tipe Dokumen:</dt>
                                            <dd class="font-bold text-slate-800" x-text="docTypes.find(d => d.code === doc_type)?.label || doc_type"></dd>
                                        </div>
                                        <div>
                                            <dt class="text-slate-500">Valuta &amp; Nilai:</dt>
                                            <dd class="font-bold text-indigo-700" x-text="calculateTotalValue() + ' ' + (formData.kode_valuta || 'USD')"></dd>
                                        </div>
                                        
                                        {{-- Conditional review labels --}}
                                        <template x-if="doc_type === 'BC30'">
                                            <div class="sm:col-span-2 grid sm:grid-cols-2 gap-3 mt-1 border-t border-slate-200/50 pt-2">
                                                <div>
                                                    <span class="text-xs text-slate-400 block">Eksportir:</span>
                                                    <span class="font-semibold text-slate-700 text-xs" x-text="formData.nama_eksportir || '—'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-slate-400 block">Penerima:</span>
                                                    <span class="font-semibold text-slate-700 text-xs" x-text="formData.nama_penerima || '—'"></span>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="doc_type === 'BC20' || doc_type === 'BC24'">
                                            <div class="sm:col-span-2 grid sm:grid-cols-2 gap-3 mt-1 border-t border-slate-200/50 pt-2">
                                                <div>
                                                    <span class="text-xs text-slate-400 block">Importir:</span>
                                                    <span class="font-semibold text-slate-700 text-xs" x-text="formData.nama_importir || '—'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-slate-400 block">Pemasok:</span>
                                                    <span class="font-semibold text-slate-700 text-xs" x-text="formData.nama_pemasok || '—'"></span>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="doc_type === 'TPB'">
                                            <div class="sm:col-span-2 grid sm:grid-cols-2 gap-3 mt-1 border-t border-slate-200/50 pt-2">
                                                <div>
                                                    <span class="text-xs text-slate-400 block">Pengusaha TPB:</span>
                                                    <span class="font-semibold text-slate-700 text-xs" x-text="formData.nama_tpb || '—'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-slate-400 block">Fasilitas / Ref:</span>
                                                    <span class="font-semibold text-slate-700 text-xs" x-text="`${formData.jenis_tpb || '—'} / ${formData.dokumen_referensi || '—'}`"></span>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="doc_type === 'RUSH'">
                                            <div class="sm:col-span-2 grid sm:grid-cols-2 gap-3 mt-1 border-t border-slate-200/50 pt-2">
                                                <div>
                                                    <span class="text-xs text-slate-400 block">Pemohon:</span>
                                                    <span class="font-semibold text-slate-700 text-xs" x-text="formData.nama_pemohon || '—'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-slate-400 block">Sarana &amp; AWB/BL:</span>
                                                    <span class="font-semibold text-slate-700 text-xs" x-text="`${formData.nama_sarana_pengangkut || '—'} / ${formData.nomor_awb_bl || '—'}`"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </dl>
                                </div>

                                {{-- Pernyataan (BC 3.0) --}}
                                <template x-if="doc_type === 'BC30'">
                                    <div class="bg-slate-50 rounded-xl p-5 border border-slate-100">
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Pernyataan Penanggung Jawab</h4>
                                        <div class="grid sm:grid-cols-3 gap-4">
                                            <div>
                                                <x-input-label for="pernyataan_nama" value="Nama Penanggung Jawab" />
                                                <input type="text" id="pernyataan_nama" name="pernyataan_nama" x-model="formData.pernyataan_nama" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC30'" />
                                            </div>
                                            <div>
                                                <x-input-label for="pernyataan_jabatan" value="Jabatan" />
                                                <input type="text" id="pernyataan_jabatan" name="pernyataan_jabatan" x-model="formData.pernyataan_jabatan" placeholder="mis. Direktur" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" :required="doc_type === 'BC30'" />
                                            </div>
                                            <div>
                                                <x-input-label for="pernyataan_kota" value="Kota (Opsional)" />
                                                <input type="text" id="pernyataan_kota" name="pernyataan_kota" x-model="formData.pernyataan_kota" placeholder="mis. Jakarta" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm shadow-sm" />
                                            </div>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-3">Dengan ini menyatakan bahwa data yang diisi adalah benar dan bertanggung jawab penuh sesuai ketentuan kepabeanan.</p>
                                    </div>
                                </template>

                                {{-- Validation Guard Message --}}
                                <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 text-xs text-indigo-900 flex items-start gap-2.5">
                                    <svg class="h-4 w-4 shrink-0 text-indigo-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <div>
                                        <span class="font-bold">Konfirmasi:</span> Dengan mengklik tombol kirim di bawah, payload JSON yang diformat di sebelah kanan akan disimpan ke database lokal dan dikirim langsung melalui gateway Host-to-Host CEISA 4.0 Bea Cukai.
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Footer Buttons --}}
                        <div class="mt-6 flex items-center justify-between border-t border-slate-200/50 pt-4">
                            <button type="button" @click="prevStep()" x-show="step > 1"
                                    class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-bold rounded-xl bg-white hover:bg-slate-50 transition-colors shadow-sm">
                                Sebelumnya
                            </button>
                            <div class="ml-auto flex items-center gap-3">
                                <a href="{{ route('dashboard') }}" class="text-slate-500 hover:text-slate-800 text-sm transition-colors">Batal</a>
                                
                                <button type="button" @click="nextStep()" x-show="step < steps.length"
                                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-indigo-100">
                                    Lanjutkan &rarr;
                                </button>
                                
                                <button type="button" @click="openDraftPreview()" x-show="step === steps.length"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-700 hover:bg-slate-800 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-slate-100">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                                    Preview
                                </button>

                                <button type="button" @click="submitForm('draft')" x-show="step === steps.length"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-bold rounded-xl transition-all shadow-sm">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" /></svg>
                                    Simpan Draft
                                </button>

                                <button type="button" @click="submitForm('submit')" x-show="step === steps.length"
                                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-indigo-100">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                                    Kirim ke CEISA
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- JSON Real-time Preview Panel (Sidebar) --}}
                <div class="lg:col-span-1 space-y-4 lg:sticky lg:top-6">
                    <div class="bg-slate-900 text-slate-300 rounded-2xl shadow-xl overflow-hidden border border-slate-800">
                        {{-- Collapsible Header --}}
                        <button type="button" @click="showJson = !showJson" 
                                class="w-full bg-slate-950 px-4 py-3 border-b border-slate-800 flex items-center justify-between hover:bg-slate-900/50 transition-colors focus:outline-none">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-2 rounded-full transition-colors duration-300" :class="showJson ? 'bg-emerald-500 animate-pulse' : 'bg-slate-500'"></div>
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">CEISA JSON Payload</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] bg-slate-800 text-indigo-400 px-2 py-0.5 rounded font-mono" x-text="doc_type"></span>
                                <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="showJson ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>
                        
                        <div class="p-4" x-show="showJson" x-transition>
                            <p class="text-[10px] text-slate-500 mb-3">Payload di bawah disusun secara dinamis sesuai struktur Bea Cukai (DJBC):</p>
                            <pre class="text-[11px] font-mono leading-relaxed overflow-x-auto text-emerald-400 max-h-[480px] scrollbar-thin scrollbar-thumb-slate-800 scrollbar-track-transparent select-all font-semibold" 
                                 x-text="JSON.stringify(generateLivePayload(), null, 2)"></pre>
                        </div>
                    </div>
                    
                    {{-- Quick Portal Info Cards --}}
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-xs text-slate-500">
                        <h4 class="font-bold text-slate-700 uppercase tracking-wider mb-2">Informasi Validasi Bea Cukai</h4>
                        <ul class="space-y-1.5 list-disc pl-4">
                            <li>Struktur JSON ini didesain sesuai schema API di <code class="text-slate-800 font-bold font-mono">openapi.beacukai.go.id</code>.</li>
                            <li>Pastikan NPWP berstatus aktif di DJBC agar lolos pre-validation.</li>
                            <li>Nilai mata uang (Valuta) wajib mengikuti standar ISO 3 digit.</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

        {{-- =====================================================================
             MODAL: PREVIEW DRAFT DOKUMEN — Bergaya Dokumen Resmi Bea Cukai
             (harus berada DI DALAM scope x-data agar Alpine memprosesnya)
             ===================================================================== --}}
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
    </div>{{-- /x-data documentWizard --}}

    @push('scripts')

    <script>
        function documentWizard() {
            return {
                step: 1,
                doc_type: 'BC30',
                showJson: false,
                showDraftModal: false,
                formError: '',
                steps: [
                    { title: 'Portal Layanan' },
                    { title: 'Data Entitas' },
                    { title: 'Logistik & Valuta' },
                    { title: 'Pos Barang' },
                    { title: 'Review & Submit' }
                ],
                docTypes: [
                    {
                        code: 'BC30',
                        label: 'BC 3.0 (Ekspor)',
                        description: 'Pemberitahuan Ekspor Barang (PEB) untuk pelaporan komoditas ke luar negeri.',
                        badgeClass: 'bg-indigo-100 text-indigo-800',
                        icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                               </svg>`
                    },
                    {
                        code: 'BC20',
                        label: 'BC 2.0 (Impor)',
                        description: 'Pemberitahuan Impor Barang (PIB) untuk penyelesaian kewajiban pabean barang masuk.',
                        badgeClass: 'bg-emerald-100 text-emerald-800',
                        icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
                               </svg>`
                    },
                    {
                        code: 'BC24',
                        label: 'BC 2.4 (Impor TPB)',
                        description: 'Impor Barang yang dimasukkan untuk ditimbun di Tempat Penimbunan Berikat.',
                        badgeClass: 'bg-blue-100 text-blue-800',
                        icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                               </svg>`
                    },
                    {
                        code: 'TPB',
                        label: 'Portal TPB',
                        description: 'Perekaman dokumen TPB (BC 2.3, BC 2.5, BC 2.7, BC 4.0) khusus logistik berikat.',
                        badgeClass: 'bg-violet-100 text-violet-800',
                        icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h18" />
                               </svg>`
                    },
                    {
                        code: 'RUSH',
                        label: 'Rush Handling',
                        description: 'Pengajuan persetujuan pengeluaran barang segera karena sifatnya yang mendesak.',
                        badgeClass: 'bg-rose-100 text-rose-800',
                        icon: `<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                               </svg>`
                    }
                ],

                // Referensi diambil dari tabel ceisa_references (server-side; dapat disinkron dari API CEISA).
                references: @json($references ?? new \stdClass()),
                
                // Form Fields Data Model
                formData: {
                    // BC30 — Header klasifikasi ekspor (CEISA 4.0)
                    kantor_muat: '',
                    jenis_ekspor: 'Biasa',
                    kategori_ekspor: 'Umum',
                    cara_dagang: 'Biasa',
                    cara_bayar: 'Biasa/Tunai',
                    komoditi: 'NON_MIGAS',
                    curah: 'NON_CURAH',
                    // BC30 — Entitas
                    nama_eksportir: '',
                    npwp_eksportir: '',
                    alamat_eksportir: '',
                    nama_penerima: '',
                    negara_tujuan: '',
                    alamat_penerima: '',
                    // BC30 — Pengangkut
                    cara_angkut: 'Laut',
                    nama_sarana: '',
                    voy_flight: '',
                    pelabuhan_tujuan: '',
                    tanggal_ekspor: '',
                    // BC30 — Transaksi tambahan
                    ndpbm: '',
                    incoterm: 'FOB',
                    freight: '',
                    asuransi_jenis: 'DN',
                    nilai_asuransi: '',
                    bruto: '',
                    bank_devisa: '',
                    // BC30 — Pernyataan
                    pernyataan_nama: '',
                    pernyataan_jabatan: '',
                    pernyataan_kota: '',

                    // BC20 / BC24
                    nama_importir: '',
                    npwp_importir: '',
                    alamat_importir: '',
                    nama_pemasok: '',
                    negara_pemasok: '',
                    
                    // TPB
                    nama_tpb: '',
                    npwp_tpb: '',
                    alamat_tpb: '',
                    jenis_tpb: '',
                    tujuan_tpb: '',
                    dokumen_referensi: '',

                    // RUSH
                    nama_pemohon: '',
                    npwp_pemohon: '',
                    alamat_pemohon: '',
                    nama_sarana_pengangkut: '',
                    nomor_flight: '',
                    nomor_awb_bl: '',
                    tanggal_awb_bl: '',
                    alasan_segera: '',
                    jumlah_kemasan: 1,
                    jenis_kemasan: '',

                    // Common
                    pelabuhan_muat: '',
                    pelabuhan_bongkar: '',
                    kode_valuta: 'USD',
                    nilai_fob: '',
                    nilai_cif: '',
                    nilai_barang: '',
                    cara_pembayaran: '',
                    
                    // Pos Barang
                    barang: [
                        { hs_code: '', uraian: '', merk: '', tipe: '', ukuran: '', negara_asal: '', daerah_asal: '', jumlah_satuan: '', kode_satuan: '', jumlah_kemasan: '', kode_kemasan: '', netto: '', volume: '', nilai_fob: '', nilai_cif: '', nilai_barang: '' }
                    ]
                },

                selectDocType(code) {
                    this.doc_type = code;
                    // Reset or adapt validation states as needed
                },

                goToStep(s) {
                    if (s < this.step || this.isStepValid(this.step)) {
                        this.step = s;
                    }
                },

                nextStep() {
                    if (this.isStepValid(this.step)) {
                        this.step++;
                    }
                },

                prevStep() {
                    if (this.step > 1) {
                        this.step--;
                    }
                },

                isStepValid(s) {
                    if (s === 2) {
                        if (this.doc_type === 'BC30') {
                            return this.formData.kantor_muat && this.formData.jenis_ekspor && this.formData.kategori_ekspor && this.formData.cara_bayar
                                && this.formData.nama_eksportir && this.formData.npwp_eksportir && this.formData.alamat_eksportir
                                && this.formData.nama_penerima && this.formData.negara_tujuan;
                        }
                        if (this.doc_type === 'BC20' || this.doc_type === 'BC24') {
                            return this.formData.nama_importir && this.formData.npwp_importir && this.formData.nama_pemasok && this.formData.negara_pemasok;
                        }
                        if (this.doc_type === 'TPB') {
                            return this.formData.nama_tpb && this.formData.npwp_tpb && this.formData.jenis_tpb;
                        }
                        if (this.doc_type === 'RUSH') {
                            return this.formData.nama_pemohon && this.formData.npwp_pemohon && this.formData.alasan_segera;
                        }
                    }
                    if (s === 3) {
                        if (this.doc_type === 'BC30') {
                            return this.formData.pelabuhan_muat && this.formData.pelabuhan_tujuan && this.formData.kode_valuta
                                && this.formData.ndpbm && this.formData.incoterm && this.formData.nilai_fob && this.formData.bruto;
                        }
                        if (this.doc_type === 'BC20' || this.doc_type === 'BC24') {
                            return this.formData.pelabuhan_muat && this.formData.pelabuhan_bongkar && this.formData.nilai_cif;
                        }
                        if (this.doc_type === 'TPB') {
                            return this.formData.nilai_barang;
                        }
                        if (this.doc_type === 'RUSH') {
                            return this.formData.nama_sarana_pengangkut && this.formData.nomor_awb_bl;
                        }
                    }
                    return true;
                },

                addItem() {
                    this.formData.barang.push({
                        hs_code: '', uraian: '', merk: '', tipe: '', ukuran: '', negara_asal: '', daerah_asal: '', jumlah_satuan: '', kode_satuan: '', jumlah_kemasan: '', kode_kemasan: '', netto: '', volume: '', nilai_fob: '', nilai_cif: '', nilai_barang: ''
                    });
                },

                removeItem(idx) {
                    this.formData.barang.splice(idx, 1);
                },

                calculateTotalNetto() {
                    return this.formData.barang.reduce((acc, curr) => acc + (parseFloat(curr.netto) || 0), 0);
                },

                calculateTotalValue() {
                    let field = 'nilai_barang';
                    if (this.doc_type === 'BC30') field = 'nilai_fob';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') field = 'nilai_cif';
                    
                    const total = this.formData.barang.reduce((acc, curr) => acc + (parseFloat(curr[field]) || 0), 0);
                    
                    // Sync total value back to header
                    if (this.doc_type === 'BC30') this.formData.nilai_fob = total;
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') this.formData.nilai_cif = total;
                    if (this.doc_type === 'TPB' || this.doc_type === 'RUSH') this.formData.nilai_barang = total;
                    
                    return total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                // Generate CEISA Payload for real-time JSON display
                generateLivePayload() {
                    const docType = this.doc_type;
                    const f = this.formData;
                    
                    if (docType === 'BC30') {
                        return {
                            header: {
                                kantor_muat: f.kantor_muat,
                                jenis_ekspor: f.jenis_ekspor,
                                kategori_ekspor: f.kategori_ekspor,
                                cara_dagang: f.cara_dagang || null,
                                cara_bayar: f.cara_bayar,
                                komoditi: f.komoditi,
                                curah: f.curah,
                                eksportir: { nama: f.nama_eksportir, npwp: f.npwp_eksportir, alamat: f.alamat_eksportir },
                                penerima: { nama: f.nama_penerima, negara: (f.negara_tujuan || '').toUpperCase(), alamat: f.alamat_penerima || null },
                                pengangkutan: {
                                    cara_angkut: f.cara_angkut || null,
                                    sarana_angkut: f.nama_sarana || null,
                                    voy_flight: f.voy_flight || null,
                                    pelabuhan_muat: f.pelabuhan_muat,
                                    pelabuhan_bongkar: f.pelabuhan_bongkar || null,
                                    pelabuhan_tujuan: f.pelabuhan_tujuan,
                                    tanggal_ekspor: f.tanggal_ekspor || null
                                },
                                valuta: (f.kode_valuta || '').toUpperCase(),
                                ndpbm: parseFloat(f.ndpbm) || 0.0,
                                incoterm: (f.incoterm || '').toUpperCase(),
                                nilai_fob: parseFloat(f.nilai_fob) || 0.0,
                                freight: f.freight !== '' ? (parseFloat(f.freight) || 0.0) : null,
                                asuransi: { jenis: f.asuransi_jenis || null, nilai: f.nilai_asuransi !== '' ? (parseFloat(f.nilai_asuransi) || 0.0) : null },
                                bruto: parseFloat(f.bruto) || 0.0,
                                bank_devisa: f.bank_devisa || null,
                                cara_pembayaran: f.cara_pembayaran || null,
                                pernyataan: { nama: f.pernyataan_nama, jabatan: f.pernyataan_jabatan, kota: f.pernyataan_kota || null }
                            },
                            barang: f.barang.map((b, i) => {
                                const fob = parseFloat(b.nilai_fob) || 0.0;
                                const qty = parseFloat(b.jumlah_satuan) || 0.0;
                                return {
                                    seri: i + 1,
                                    hs_code: b.hs_code,
                                    uraian: b.uraian,
                                    merk: b.merk || null,
                                    tipe: b.tipe || null,
                                    ukuran: b.ukuran || null,
                                    negara_asal: b.negara_asal ? b.negara_asal.toUpperCase() : null,
                                    daerah_asal: b.daerah_asal || null,
                                    jumlah_satuan: qty,
                                    kode_satuan: b.kode_satuan,
                                    jumlah_kemasan: b.jumlah_kemasan !== '' ? (parseFloat(b.jumlah_kemasan) || 0.0) : null,
                                    kode_kemasan: b.kode_kemasan || null,
                                    netto: parseFloat(b.netto) || 0.0,
                                    volume: b.volume !== '' ? (parseFloat(b.volume) || 0.0) : null,
                                    nilai_fob: fob,
                                    harga_satuan: qty > 0 ? Math.round((fob / qty) * 10000) / 10000 : 0.0
                                };
                            })
                        };
                    } else if (docType === 'BC20' || docType === 'BC24') {
                        return {
                            header: {
                                importir: { nama: f.nama_importir, npwp: f.npwp_importir, alamat: f.alamat_importir },
                                pemasok: { nama: f.nama_pemasok, negara: (f.negara_pemasok || '').toUpperCase() },
                                pengangkutan: { pelabuhan_muat: f.pelabuhan_muat, pelabuhan_bongkar: f.pelabuhan_bongkar },
                                valuta: (f.kode_valuta || '').toUpperCase(),
                                nilai_cif: parseFloat(f.nilai_cif) || 0.0,
                                cara_pembayaran: f.cara_pembayaran || null
                            },
                            barang: f.barang.map((b, i) => ({
                                seri: i + 1,
                                hs_code: b.hs_code,
                                uraian: b.uraian,
                                jumlah_satuan: parseFloat(b.jumlah_satuan) || 0.0,
                                kode_satuan: b.kode_satuan,
                                netto: parseFloat(b.netto) || 0.0,
                                nilai_cif: parseFloat(b.nilai_cif) || 0.0
                            }))
                        };
                    } else if (docType === 'TPB') {
                        return {
                            header: {
                                pengusaha_tpb: { nama: f.nama_tpb, npwp: f.npwp_tpb, alamat: f.alamat_tpb },
                                jenis_tpb: f.jenis_tpb,
                                tujuan_pengiriman: f.tujuan_tpb,
                                dokumen_referensi: f.dokumen_referensi,
                                valuta: (f.kode_valuta || '').toUpperCase(),
                                nilai_barang: parseFloat(f.nilai_barang) || 0.0
                            },
                            barang: f.barang.map((b, i) => ({
                                seri: i + 1,
                                hs_code: b.hs_code,
                                uraian: b.uraian,
                                jumlah_satuan: parseFloat(b.jumlah_satuan) || 0.0,
                                kode_satuan: b.kode_satuan,
                                netto: parseFloat(b.netto) || 0.0,
                                nilai_barang: parseFloat(b.nilai_barang) || 0.0
                            }))
                        };
                    } else if (docType === 'RUSH') {
                        return {
                            header: {
                                pemohon: { nama: f.nama_pemohon, npwp: f.npwp_pemohon, alamat: f.alamat_pemohon },
                                pengangkutan: { sarana: f.nama_sarana_pengangkut, flight_no: f.nomor_flight },
                                dokumen_pengangkutan: { awb_bl: f.nomor_awb_bl, tanggal: f.tanggal_awb_bl },
                                alasan_rush_handling: f.alasan_segera,
                                kemasan: { jumlah: parseInt(f.jumlah_kemasan) || 1, jenis: f.jenis_kemasan }
                            },
                            barang: f.barang.map((b, i) => ({
                                seri: i + 1,
                                hs_code: b.hs_code,
                                uraian: b.uraian,
                                jumlah_satuan: parseFloat(b.jumlah_satuan) || 0.0,
                                kode_satuan: b.kode_satuan,
                                netto: parseFloat(b.netto) || 0.0,
                                nilai_barang: parseFloat(b.nilai_barang) || 0.0
                            }))
                        };
                    }
                },

                // Mock Data Injector
                loadSampleData() {
                    const docType = this.doc_type;
                    
                    if (docType === 'BC30') {
                        this.formData.kantor_muat = '050100';
                        this.formData.jenis_ekspor = 'Biasa';
                        this.formData.kategori_ekspor = 'Umum';
                        this.formData.cara_dagang = 'Biasa';
                        this.formData.cara_bayar = 'Biasa/Tunai';
                        this.formData.komoditi = 'NON_MIGAS';
                        this.formData.curah = 'NON_CURAH';
                        this.formData.nama_eksportir = 'PT Mora Multi Berkah';
                        this.formData.npwp_eksportir = '012345678901000';
                        this.formData.alamat_eksportir = 'Jl. Kemang Timur No. 45, Mampang Prapatan, Jakarta Selatan';
                        this.formData.nama_penerima = 'Global Trade Logistics Pte Ltd';
                        this.formData.negara_tujuan = 'SG';
                        this.formData.alamat_penerima = '8 Marina Boulevard, Singapore 018981';
                        this.formData.cara_angkut = 'Laut';
                        this.formData.nama_sarana = 'MV Sinar Bintang';
                        this.formData.voy_flight = 'V-1024E';
                        this.formData.pelabuhan_muat = 'IDJKT';
                        this.formData.pelabuhan_bongkar = 'SGSIN';
                        this.formData.pelabuhan_tujuan = 'SGSIN';
                        this.formData.tanggal_ekspor = '2026-06-20';
                        this.formData.kode_valuta = 'USD';
                        this.formData.ndpbm = 15800;
                        this.formData.incoterm = 'FOB';
                        this.formData.nilai_fob = 12500.00;
                        this.formData.freight = 350.00;
                        this.formData.asuransi_jenis = 'DN';
                        this.formData.nilai_asuransi = 125.00;
                        this.formData.bruto = 135.5;
                        this.formData.bank_devisa = 'Bank Mandiri';
                        this.formData.cara_pembayaran = 'L/C';
                        this.formData.pernyataan_nama = 'Irwan';
                        this.formData.pernyataan_jabatan = 'Direktur';
                        this.formData.pernyataan_kota = 'Jakarta';
                        this.formData.barang = [
                            { hs_code: '6109100000', uraian: 'Kaos Katun Premium Polos M2B', merk: 'M2B', tipe: 'Round Neck', ukuran: 'All Size', negara_asal: 'ID', daerah_asal: 'Jawa Barat', jumlah_satuan: 500, kode_satuan: 'PCE', jumlah_kemasan: 20, kode_kemasan: 'CT', netto: 120, volume: 2.5, nilai_fob: 12500.00 }
                        ];
                    } else if (docType === 'BC20') {
                        this.formData.nama_importir = 'PT Mora Multi Berkah';
                        this.formData.npwp_importir = '012345678901000';
                        this.formData.alamat_importir = 'Jl. Kemang Timur No. 45, Mampang Prapatan, Jakarta Selatan';
                        this.formData.nama_pemasok = 'Tokyo Machinery Industrial Corp';
                        this.formData.negara_pemasok = 'JP';
                        this.formData.pelabuhan_muat = 'JPTYO';
                        this.formData.pelabuhan_bongkar = 'IDTPP';
                        this.formData.kode_valuta = 'JPY';
                        this.formData.nilai_cif = 1800000.00;
                        this.formData.cara_pembayaran = 'Telegraphic Transfer (TT)';
                        this.formData.barang = [
                            { hs_code: '8471302000', uraian: 'Unit Laptop Kantor Core i7 16GB RAM', jumlah_satuan: 15, kode_satuan: 'UNT', netto: 35, nilai_cif: 1800000.00 }
                        ];
                    } else if (docType === 'BC24') {
                        this.formData.nama_importir = 'PT Mora Multi Berkah';
                        this.formData.npwp_importir = '012345678901000';
                        this.formData.alamat_importir = 'Jl. Kemang Timur No. 45, Mampang Prapatan, Jakarta Selatan';
                        this.formData.nama_pemasok = 'Shenzhen Electronic Components Ltd';
                        this.formData.negara_pemasok = 'CN';
                        this.formData.pelabuhan_muat = 'CNSZN';
                        this.formData.pelabuhan_bongkar = 'IDTPP';
                        this.formData.kode_valuta = 'USD';
                        this.formData.nilai_cif = 42500.00;
                        this.formData.cara_pembayaran = 'Open Account';
                        this.formData.barang = [
                            { hs_code: '8541410000', uraian: 'Modul LED Display P3.9 Outdoor', jumlah_satuan: 1200, kode_satuan: 'PCE', netto: 180, nilai_cif: 42500.00 }
                        ];
                    } else if (docType === 'TPB') {
                        this.formData.nama_tpb = 'PT Mora Multi Berkah (Kawasan Berikat)';
                        this.formData.npwp_tpb = '012345678901000';
                        this.formData.alamat_tpb = 'Kawasan Industri Jababeka Tahap II Blok B-12, Cikarang, Bekasi';
                        this.formData.jenis_tpb = 'Kawasan Berikat (KB)';
                        this.formData.tujuan_tpb = 'Pengeluaran Hasil Produksi ke TLDDP (BC 2.5)';
                        this.formData.dokumen_referensi = 'Kontrak Penjualan No. KB-889/M2B/VI/2026';
                        this.formData.kode_valuta = 'USD';
                        this.formData.nilai_barang = 31500.00;
                        this.formData.barang = [
                            { hs_code: '3926909990', uraian: 'Plastic Parts Injection Molded Automotive', jumlah_satuan: 10000, kode_satuan: 'PCE', netto: 250, nilai_barang: 31500.00 }
                        ];
                    } else if (docType === 'RUSH') {
                        this.formData.nama_pemohon = 'PT Mora Multi Berkah';
                        this.formData.npwp_pemohon = '012345678901000';
                        this.formData.alamat_pemohon = 'Jl. Kemang Timur No. 45, Mampang Prapatan, Jakarta Selatan';
                        this.formData.nama_sarana_pengangkut = 'Singapore Airlines';
                        this.formData.nomor_flight = 'SQ-956';
                        this.formData.nomor_awb_bl = 'AWB-618-9920188';
                        this.formData.tanggal_awb_bl = '2026-06-15';
                        this.formData.alasan_segera = 'Vaksin / Serum / Obat-obatan Kritis';
                        this.formData.jumlah_kemasan = 4;
                        this.formData.jenis_kemasan = 'CO';
                        this.formData.barang = [
                            { hs_code: '3002200000', uraian: 'Vaksin Influenza Impor (Suhu Dingin)', jumlah_satuan: 2000, kode_satuan: 'VIA', netto: 12, nilai_barang: 8500.00 }
                        ];
                    }
                },

                // Field nilai transaksi per jenis dokumen.
                valueField() {
                    if (this.doc_type === 'BC30') return 'nilai_fob';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') return 'nilai_cif';
                    return 'nilai_barang';
                },

                // Validasi ringan sisi klien. Mengembalikan {step, message} bila ada
                // yang belum lengkap, atau null bila valid. Hanya memeriksa field
                // yang relevan dengan doc_type (field tersembunyi diabaikan).
                firstInvalidStep() {
                    if (!this.isStepValid(2)) {
                        return { step: 2, message: 'Lengkapi data identitas entitas (Tahap 2) terlebih dahulu.' };
                    }
                    if (!this.isStepValid(3)) {
                        return { step: 3, message: 'Lengkapi data pengangkutan & nilai transaksi (Tahap 3).' };
                    }
                    if (!this.formData.barang.length) {
                        return { step: 4, message: 'Tambahkan minimal satu pos barang (Tahap 4).' };
                    }
                    const vf = this.valueField();
                    for (let i = 0; i < this.formData.barang.length; i++) {
                        const b = this.formData.barang[i];
                        if (!b.hs_code || !b.uraian || !b.jumlah_satuan || !b.kode_satuan || !b.netto || !b[vf]) {
                            return { step: 4, message: `Lengkapi seluruh isian pada Pos Barang #${i + 1} (Tahap 4).` };
                        }
                    }
                    if (this.doc_type === 'BC30' && (!this.formData.pernyataan_nama || !this.formData.pernyataan_jabatan)) {
                        return { step: 5, message: 'Lengkapi Pernyataan Penanggung Jawab (nama & jabatan) di Tahap 5.' };
                    }
                    return null;
                },

                // Buka modal preview (read-only) — selalu merespons.
                openDraftPreview() {
                    this.formError = '';
                    this.showDraftModal = true;
                },

                // Simpan sebagai draft (dari dalam modal preview).
                confirmSaveDraft() {
                    this.submitForm('draft');
                },

                // Submit form: validasi relevan dulu, lalu kirim ke server.
                submitForm(action = 'submit') {
                    const invalid = this.firstInvalidStep();
                    if (invalid) {
                        this.showDraftModal = false;
                        this.step = invalid.step;
                        this.formError = invalid.message;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }
                    this.formError = '';
                    document.getElementById('submit_action').value = action;
                    document.getElementById('ceisaDocForm').submit();
                },

                // Get party name for display in modal
                getPartyName() {
                    const f = this.formData;
                    if (this.doc_type === 'BC30') return f.nama_eksportir || '—';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') return f.nama_importir || '—';
                    if (this.doc_type === 'TPB') return f.nama_tpb || '—';
                    if (this.doc_type === 'RUSH') return f.nama_pemohon || '—';
                    return '—';
                },

                getPartyNPWP() {
                    const f = this.formData;
                    if (this.doc_type === 'BC30') return f.npwp_eksportir || '—';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') return f.npwp_importir || '—';
                    if (this.doc_type === 'TPB') return f.npwp_tpb || '—';
                    if (this.doc_type === 'RUSH') return f.npwp_pemohon || '—';
                    return '—';
                },

                getCounterPartyName() {
                    const f = this.formData;
                    if (this.doc_type === 'BC30') return f.nama_penerima || '—';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') return f.nama_pemasok || '—';
                    return '—';
                },

                getDocTypeLabel() {
                    const map = { BC30: 'BC 3.0 — Pemberitahuan Ekspor Barang', BC20: 'BC 2.0 — Pemberitahuan Impor Barang', BC24: 'BC 2.4 — Impor Barang TPB', TPB: 'Portal TPB', RUSH: 'Rush Handling' };
                    return map[this.doc_type] || this.doc_type;
                },

                getTotalValue() {
                    let field = 'nilai_barang';
                    if (this.doc_type === 'BC30') field = 'nilai_fob';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') field = 'nilai_cif';
                    return this.formData.barang.reduce((acc, b) => acc + (parseFloat(b[field]) || 0), 0).toLocaleString('id-ID', {minimumFractionDigits: 2});
                },

                getValueLabel() {
                    if (this.doc_type === 'BC30') return 'Nilai FOB Total';
                    if (this.doc_type === 'BC20' || this.doc_type === 'BC24') return 'Nilai CIF Total';
                    return 'Nilai Barang Total';
                },

                todayFormatted() {
                    return new Date().toLocaleDateString('id-ID', {day:'2-digit', month:'2-digit', year:'numeric'});
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
