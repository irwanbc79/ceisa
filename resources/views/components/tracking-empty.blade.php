@props(['pesan' => 'Belum ada data.'])

<div class="text-center py-8 text-slate-400">
    <svg class="h-9 w-9 mx-auto text-slate-200 mb-2.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <p class="text-[11px] font-semibold">{{ $pesan }}</p>
</div>
