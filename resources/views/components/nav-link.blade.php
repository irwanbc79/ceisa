@props(['active'])

@php
$classes = ($active ?? false)
    ? 'tab' : 'tab';
$dataActive = ($active ?? false) ? 'true' : 'false';
@endphp

<a data-active="{{ $dataActive }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
