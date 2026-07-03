@props(['active'])

@php
$classes = ($active ?? false)
    ? 'block w-full ps-4 pe-4 py-2.5 border-l-[3px] border-gold-500 text-start text-sm font-bold text-slate-800 bg-panel-2'
    : 'block w-full ps-4 pe-4 py-2.5 border-l-[3px] border-transparent text-start text-sm font-medium text-slate-500 hover:text-slate-800 hover:bg-white/[0.05] hover:border-white/10';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
