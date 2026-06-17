@props(['status'])

@php
    $map = [
        'draft' => [
            'label' => 'Draft', 
            'bg' => 'bg-slate-50', 
            'text' => 'text-slate-600', 
            'border' => 'border-slate-200', 
            'dot' => 'bg-slate-400'
        ],
        'submitting' => [
            'label' => 'Mengirim', 
            'bg' => 'bg-blue-50/70', 
            'text' => 'text-blue-700', 
            'border' => 'border-blue-200/60', 
            'dot' => 'bg-blue-500 animate-pulse'
        ],
        'submitted' => [
            'label' => 'Terkirim', 
            'bg' => 'bg-blue-50/70', 
            'text' => 'text-blue-700', 
            'border' => 'border-blue-200/60', 
            'dot' => 'bg-blue-500'
        ],
        'accepted' => [
            'label' => 'Diterima', 
            'bg' => 'bg-emerald-50/80', 
            'text' => 'text-emerald-700', 
            'border' => 'border-emerald-200/60', 
            'dot' => 'bg-emerald-500'
        ],
        'rejected' => [
            'label' => 'Ditolak', 
            'bg' => 'bg-rose-50/80', 
            'text' => 'text-rose-700', 
            'border' => 'border-rose-200/60', 
            'dot' => 'bg-rose-500'
        ],
        'error' => [
            'label' => 'Error', 
            'bg' => 'bg-rose-50/80', 
            'text' => 'text-rose-700', 
            'border' => 'border-rose-200/60', 
            'dot' => 'bg-rose-500 animate-pulse'
        ],
    ];
    $s = $map[$status] ?? [
        'label' => ucfirst($status), 
        'bg' => 'bg-slate-50', 
        'text' => 'text-slate-600', 
        'border' => 'border-slate-200', 
        'dot' => 'bg-slate-400'
    ];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {$s['bg']} {$s['text']} {$s['border']} shadow-sm"]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span>
    {{ $s['label'] }}
</span>
