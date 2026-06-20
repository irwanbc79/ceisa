{{--
    Modal "Tampilan Cepat" dokumen. Mendengarkan event global `quick-view`
    yang membawa detail dokumen (Document::quickViewData()). Tombol Detail
    pada tabel cukup men-dispatch event ini — tanpa pindah halaman.
--}}
<div
    x-data="{ open: false, doc: {} }"
    x-on:quick-view.window="doc = $event.detail; open = true"
    x-on:keydown.escape.window="open = false"
    x-cloak
>
    {{-- Overlay --}}
    <div
        x-show="open"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm"
        x-on:click="open = false"
        style="display:none"
    ></div>

    {{-- Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 pointer-events-none"
        style="display:none"
    >
        <div class="w-full max-w-lg bg-white rounded-3xl shadow-2xl ring-1 ring-slate-900/5 pointer-events-auto overflow-hidden"
             x-on:click.stop>
            {{-- Header --}}
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-5 flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-200">Tampilan Cepat Dokumen</p>
                    <h3 class="text-white font-extrabold text-lg leading-tight mt-1 truncate" x-text="doc.doc_type_label"></h3>
                </div>
                <button type="button" x-on:click="open = false"
                        class="shrink-0 -mr-1 -mt-1 p-1.5 rounded-xl text-indigo-200 hover:text-white hover:bg-white/10 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-4 max-h-[60vh] overflow-y-auto">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200" x-text="doc.source"></span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 capitalize" x-text="doc.status"></span>
                    <template x-if="doc.jalur">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold"
                              :class="{
                                'bg-emerald-50 text-emerald-700 border border-emerald-100': doc.jalur_color === 'emerald',
                                'bg-amber-50 text-amber-700 border border-amber-100': doc.jalur_color === 'amber',
                                'bg-rose-50 text-rose-700 border border-rose-100': doc.jalur_color === 'rose',
                              }"
                              x-text="doc.jalur"></span>
                    </template>
                </div>

                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="py-2.5 flex items-start justify-between gap-4">
                        <dt class="text-slate-400 font-medium shrink-0">No. Aju</dt>
                        <dd class="font-mono font-semibold text-slate-700 text-right break-all" x-text="doc.nomor_aju || '—'"></dd>
                    </div>
                    <div class="py-2.5 flex items-start justify-between gap-4">
                        <dt class="text-slate-400 font-medium shrink-0">No. Pendaftaran</dt>
                        <dd class="font-mono font-semibold text-slate-700 text-right break-all" x-text="doc.nomor_daftar || '—'"></dd>
                    </div>
                    <div class="py-2.5 flex items-start justify-between gap-4" x-show="doc.id_header">
                        <dt class="text-slate-400 font-medium shrink-0">idHeader</dt>
                        <dd class="font-mono text-xs font-semibold text-slate-500 text-right break-all" x-text="doc.id_header"></dd>
                    </div>
                    <div class="py-2.5 flex items-start justify-between gap-4">
                        <dt class="text-slate-400 font-medium shrink-0">Pihak / Entitas</dt>
                        <dd class="font-semibold text-slate-700 text-right" x-text="doc.party_name || '—'"></dd>
                    </div>
                    <div class="py-2.5 flex items-start justify-between gap-4" x-show="doc.party_npwp">
                        <dt class="text-slate-400 font-medium shrink-0">NPWP</dt>
                        <dd class="font-mono text-xs font-semibold text-slate-600 text-right" x-text="doc.party_npwp"></dd>
                    </div>
                    <div class="py-2.5 flex items-start justify-between gap-4" x-show="doc.uraian">
                        <dt class="text-slate-400 font-medium shrink-0">Uraian</dt>
                        <dd class="text-slate-700 text-right" x-text="doc.uraian"></dd>
                    </div>
                    <div class="py-2.5 flex items-start justify-between gap-4">
                        <dt class="text-slate-400 font-medium shrink-0">Dibuat</dt>
                        <dd class="font-medium text-slate-600 text-right" x-text="doc.created_at"></dd>
                    </div>
                </dl>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <template x-if="doc.editable">
                    <a :href="doc.edit_url"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-amber-200 hover:bg-amber-50 text-amber-700 text-xs font-bold rounded-xl shadow-sm transition-colors">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                        Ubah
                    </a>
                </template>
                <a :href="doc.show_url"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-100 transition-all">
                    Halaman Detail Lengkap
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>
        </div>
    </div>
</div>
