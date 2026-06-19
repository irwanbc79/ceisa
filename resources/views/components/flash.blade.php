@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition.opacity
         x-init="setTimeout(() => show = false, 5000)"
         class="mb-5 rounded-xl bg-white border-l-[3px] border-sea-500 shadow-soft px-4 py-3 flex items-start gap-3">
        <span class="h-8 w-8 rounded-lg bg-sea-50 text-sea-600 flex items-center justify-center shrink-0">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
        </span>
        <div class="flex-1 text-sm text-ink-700 font-medium">{{ session('success') }}</div>
        <button @click="show = false" class="text-ink-300 hover:text-ink-700 text-xs">✕</button>
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition.opacity
         class="mb-5 rounded-xl bg-white border-l-[3px] border-crimson-500 shadow-soft px-4 py-3 flex items-start gap-3">
        <span class="h-8 w-8 rounded-lg bg-crimson-50 text-crimson-600 flex items-center justify-center shrink-0">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
        </span>
        <div class="flex-1 text-sm text-ink-700 font-medium">{{ session('error') }}</div>
        <button @click="show = false" class="text-ink-300 hover:text-ink-700 text-xs">✕</button>
    </div>
@endif
