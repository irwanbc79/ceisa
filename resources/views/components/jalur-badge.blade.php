@props(['jalur' => null])

@php
    $map = [
        'H' => ['Jalur Hijau', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'K' => ['Jalur Kuning', 'bg-amber-50 text-amber-700 border-amber-200'],
        'M' => ['Jalur Merah', 'bg-rose-50 text-rose-700 border-rose-200'],
    ];
    $j = $map[$jalur] ?? null;
@endphp

@if ($j)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border '.$j[1]]) }}>{{ $j[0] }}</span>
@endif
