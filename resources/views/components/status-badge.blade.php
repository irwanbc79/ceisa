@props(['status'])

@php
    $map = [
        'draft'      => ['Draft',     'pill-ink',     'bg-ink-400'],
        'submitting' => ['Mengirim',  'pill-gold',    'bg-gold-500'],
        'submitted'  => ['Terkirim',  'pill-gold',    'bg-gold-500'],
        'accepted'   => ['Diterima',  'pill-sea',     'bg-sea-500'],
        'rejected'   => ['Ditolak',   'pill-crimson', 'bg-crimson-500'],
        'error'      => ['Error',     'pill-crimson', 'bg-crimson-500'],
    ];
    [$label, $classes, $dotColor] = $map[$status] ?? [ucfirst($status), 'pill-ink', 'bg-ink-300'];
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    <span class="dot {{ $dotColor }}"></span>
    {{ $label }}
</span>
