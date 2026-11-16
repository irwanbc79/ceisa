    <div x-show="showDraftModal" x-cloak style="display: none;" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/75 backdrop-blur-sm print:static print:bg-white print:p-0"
         @keydown.escape.window="showDraftModal = false">

        <div x-show="showDraftModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="bg-white text-slate-900 w-full max-w-5xl max-h-[94vh] overflow-y-auto rounded-2xl shadow-2xl border border-slate-300 print:max-w-none print:max-h-none print:overflow-visible print:border-0 print:shadow-none print:rounded-none"
             @click.stop>

            {{-- Modal Header --}}
            <div class="sticky top-0 z-20 bg-slate-900 text-white px-5 py-3.5 flex items-center justify-between border-b border-slate-700 print:hidden">
                <div>
                    <h3 class="font-bold text-sm tracking-tight">Preview Formulir Resmi (Draft Lokal)</h3>
                    <p class="text-[11px] text-slate-300 mt-0.5">Format diselaraskan dengan cetakan resmi CEISA 4.0 (PEB BC 3.0 / PIB BC 2.0)</p>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-500/20 text-amber-300 text-[11px] font-bold rounded-full border border-amber-400/30">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                        DRAFT — Belum Terdaftar di DJBC
                    </span>
                    <button type="button" onclick="window.print()" class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white text-[11px] font-semibold rounded-lg transition-colors cursor-pointer">
                        Cetak / Simpan PDF
                    </button>
                    <button type="button" @click="showDraftModal = false" class="text-slate-300 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors cursor-pointer">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            {{-- BODY: FORMULIR RESMI --}}
            @include('documents.partials._official-form-sheet')

            {{-- Action Buttons --}}
            <div class="sticky bottom-0 bg-panel border-t border-slate-200 px-6 py-3.5 flex items-center justify-between gap-3 print:hidden">
                <button type="button" @click="showDraftModal = false"
                        class="inline-flex items-center gap-2 px-5 py-2.5 border border-slate-200 text-slate-700 text-sm font-bold rounded-xl bg-panel hover:bg-slate-50 transition-colors shadow-sm cursor-pointer">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    Edit Kembali
                </button>
                <div class="flex items-center gap-3">
                    <button type="button" @click="submitForm('submit')"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-all shadow-md shadow-slate-900/10 cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                        Kirim ke CEISA Sekarang
                    </button>
                    <button type="button" @click="confirmSaveDraft()"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-ink-800 hover:bg-ink-950 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-slate-900/10 cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" /></svg>
                        Konfirmasi Simpan Draft
                    </button>
                </div>
            </div>
        </div>
    </div>
