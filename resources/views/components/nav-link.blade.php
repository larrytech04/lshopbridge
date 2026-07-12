@props(['href', 'icon' => 'home', 'img' => null, 'raw' => false, 'active' => false])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'nav-item '.($active ? 'nav-item-active' : '')]) }}>
    @if ($img && $raw)
        <img src="{{ asset('assets/'.$img) }}" alt="" class="h-5 w-5 shrink-0 object-contain" />
    @elseif ($img)
        <x-img-icon :name="$img" class="h-5 w-5 shrink-0" />
    @else
        <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
    @endif
    <span>{{ $slot }}</span>
</a>
