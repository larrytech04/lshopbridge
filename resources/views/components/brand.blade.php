@props(['href' => null])

<a href="{{ $href ?? route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
    <img src="{{ site_logo() }}" alt="{{ setting('site_name', config('platform.name')) }}" class="h-9 w-auto" />
</a>
