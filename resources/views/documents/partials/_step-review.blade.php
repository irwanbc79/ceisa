@php
/**
 * Step 5 — Review Data & Submit + tombol navigasi footer.
 *
 * Berbages Alpine scope dari root x-data="documentWizard()" di create.blade.php.
 *
 * Variabel yang dipakai:
 *   - step, steps         : state stepper Alpine
 *   - doc_type, formData  : data form
 *   - docTypes            : konfigurasi tipe dokumen (Alpine)
 */
@endphp

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

{{-- Footer Buttons (navigasi antar step) --}}
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
