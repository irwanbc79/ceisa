<picture>
    <source srcset="{{ asset('images/logo-m2b-320.webp') }}" type="image/webp">
    <img
        src="{{ asset('images/logo-m2b.png') }}"
        alt="{{ config('app.name', 'PT. Mora Multi Berkah') }}"
        {{ $attributes->merge(['class' => 'object-contain']) }}
    >
</picture>
