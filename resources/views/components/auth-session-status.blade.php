@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-xs font-bold text-sea-700 bg-sea-50 border border-sea-100 rounded-lg px-3 py-2']) }}>
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
        {{ $status }}
    </div>
@endif
