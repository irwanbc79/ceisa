@php
/**
 * Step 5 — Peti Kemas / Kontainer.
 */
@endphp

<div x-show="step === 5" class="bg-white/70 backdrop-blur-xl border border-white/60 shadow-xl shadow-slate-100/30 rounded-2xl p-6 transition-all duration-300">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Detail Peti Kemas (Kontainer)</h3>
            <p class="text-xs text-slate-500 mt-0.5">Input nomor kontainer, ukuran, tipe, dan status isi jika menggunakan kontainer</p>
        </div>
        <button type="button" @click="addContainer()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-colors shadow-md shadow-indigo-100">
            + Tambah Kontainer
        </button>
    </div>

    {{-- Empty State Info --}}
    <div x-show="formData.kontainer.length === 0" class="rounded-xl border border-dashed border-slate-200 p-6 text-center bg-slate-50/50">
        <svg class="mx-auto h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
        </svg>
        <h4 class="mt-2 text-xs font-bold text-slate-700">Tidak Ada Kontainer</h4>
        <p class="mt-1 text-[11px] text-slate-400 max-w-sm mx-auto">Jika pengiriman ini tidak menggunakan kontainer (misalnya Cargo Curah/LCL/Udara), Anda bisa mengosongkan langkah ini dan langsung mengeklik tombol <strong>Lanjut</strong>.</p>
    </div>

    <div class="space-y-4">
        <template x-for="(cont, index) in formData.kontainer" :key="index">
            <div class="border border-slate-100 rounded-xl p-4 bg-slate-50/50 hover:border-slate-300 transition-all relative group">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                    <span class="text-xs font-extrabold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded uppercase tracking-wider">Kontainer #<span x-text="index + 1"></span></span>
                    <button type="button" @click="removeContainer(index)"
                            class="text-xs text-rose-600 hover:underline">Hapus Kontainer</button>
                </div>

                <div class="grid sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Nomor Kontainer</label>
                        <input type="text" :name="`kontainer[${index}][nomor_kontainer]`" x-model="cont.nomor_kontainer"
                               class="mt-1 block w-full rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs shadow-sm uppercase" placeholder="mis. MSKU1234567" required />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Ukuran</label>
                        <x-searchable-select ::name="'kontainer[' + index + '][kode_ukuran]'" model="cont.kode_ukuran" options="references.containerSizes" placeholder="-- Pilih Ukuran --" required />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Tipe</label>
                        <x-searchable-select ::name="'kontainer[' + index + '][kode_tipe]'" model="cont.kode_tipe" options="references.containerTypes" placeholder="-- Pilih Tipe --" required />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase">Status Isi</label>
                        <x-searchable-select ::name="'kontainer[' + index + '][kode_status]'" model="cont.kode_status" options="references.containerStatuses" placeholder="-- Pilih Status --" required />
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
