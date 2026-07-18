@props(['active'])

@php
$classes = ($active ?? false)
    ? 'block w-full ps-4 pe-4 py-2.5 border-l-[3px] border-indigo-600 text-start text-sm font-bold text-indigo-700 bg-indigo-50'
    : 'block w-full ps-4 pe-4 py-2.5 border-l-[3px] border-transparent text-start text-sm font-medium text-slate-500 hover:text-slate-800 hover:bg-slate-50 hover:border-slate-200';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
