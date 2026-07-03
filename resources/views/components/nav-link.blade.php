@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3 pt-1 border-b-2 border-gold-400 text-sm font-semibold leading-5 text-gold-300 focus:outline-none transition duration-150 ease-in-out tracking-wide'
            : 'inline-flex items-center px-3 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-slate-500 hover:text-slate-800 hover:border-white/20 focus:outline-none transition duration-150 ease-in-out tracking-wide';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
