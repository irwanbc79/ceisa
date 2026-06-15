@props(['status'])

@php
    $map = [
        'draft' => ['Draft', 'bg-gray-100 text-gray-700'],
        'submitting' => ['Mengirim', 'bg-blue-100 text-blue-700'],
        'submitted' => ['Terkirim', 'bg-blue-100 text-blue-700'],
        'accepted' => ['Diterima', 'bg-green-100 text-green-700'],
        'rejected' => ['Ditolak', 'bg-red-100 text-red-700'],
        'error' => ['Error', 'bg-red-100 text-red-700'],
    ];
    [$label, $classes] = $map[$status] ?? [ucfirst($status), 'bg-gray-100 text-gray-700'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $classes"]) }}>
    {{ $label }}
</span>
