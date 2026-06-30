@php
/**
 * JSON Preview Panel (sidebar) — tampilan real-time payload CEISA.
 *
 * Berbages Alpine scope dari root x-data="documentWizard()" di create.blade.php.
 * Variabel Alpine: showJson, doc_type, generateLivePayload().
 */
@endphp

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
    <div class="bg-white/70 backdrop-blur-xl border border-white/60 shadow-xl shadow-slate-100/30 rounded-2xl p-4 text-xs text-slate-500">
        <h4 class="font-bold text-slate-700 uppercase tracking-wider mb-2">Informasi Validasi Bea Cukai</h4>
        <ul class="space-y-1.5 list-disc pl-4">
            <li>Struktur JSON ini didesain sesuai schema API di <code class="text-slate-800 font-bold font-mono">openapi.beacukai.go.id</code>.</li>
            <li>Pastikan NPWP berstatus aktif di DJBC agar lolos pre-validation.</li>
            <li>Nilai mata uang (Valuta) wajib mengikuti standar ISO 3 digit.</li>
        </ul>
    </div>
</div>
