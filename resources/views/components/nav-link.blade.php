@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center whitespace-nowrap px-3 py-1.5 rounded-lg bg-indigo-50/90 text-indigo-700 border border-indigo-200/70 text-[13px] font-bold leading-5 shadow-sm focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center whitespace-nowrap px-2.5 py-1.5 rounded-lg border border-transparent text-[13px] font-semibold leading-5 text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
