@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3 pt-1 border-b-2 border-indigo-600 text-sm font-semibold leading-5 text-indigo-600 focus:outline-none transition duration-150 ease-in-out tracking-wide'
            : 'inline-flex items-center px-3 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-slate-500 hover:text-slate-900 hover:border-slate-300 focus:outline-none transition duration-150 ease-in-out tracking-wide';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
