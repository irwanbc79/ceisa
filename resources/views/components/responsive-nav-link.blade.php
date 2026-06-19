@props(['active'])

@php
$classes = ($active ?? false)
    ? 'block w-full ps-4 pe-4 py-2.5 border-l-[3px] border-gold-500 text-start text-sm font-bold text-ink-900 bg-cream-200'
    : 'block w-full ps-4 pe-4 py-2.5 border-l-[3px] border-transparent text-start text-sm font-medium text-ink-500 hover:text-ink-900 hover:bg-cream-100 hover:border-cream-400';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
