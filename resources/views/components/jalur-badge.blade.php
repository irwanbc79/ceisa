@props(['jalur' => null])

@php
    $map = [
        'H' => ['Hijau',  'pill-sea',     'bg-sea-500'],
        'K' => ['Kuning', 'pill-amber',   'bg-amber-400'],
        'M' => ['Merah',  'pill-crimson', 'bg-crimson-500'],
    ];
    $j = $map[$jalur] ?? null;
@endphp

@if ($j)
    <span {{ $attributes->merge(['class' => $j[1]]) }}>
        <span class="dot {{ $j[2] }}"></span>
        Jalur {{ $j[0] }}
    </span>
@endif
