@props(['href', 'route' => '', 'icon' => 'home', 'img' => null, 'raw' => false, 'badge' => 0])

@php $active = $route && request()->routeIs($route); @endphp

<a href="{{ $href }}" class="nav-item {{ $active ? 'nav-item-active' : '' }}">
    @if ($img && $raw)<img src="{{ asset('assets/'.$img) }}" alt="" class="nav-icon-raw h-5 w-5 shrink-0 object-contain" />@elseif ($img)<x-img-icon :name="$img" class="h-5 w-5 shrink-0" />@else<x-icon :name="$icon" class="h-5 w-5 shrink-0" />@endif
    <span class="flex-1">{{ $slot }}</span>
    @if ((int) $badge > 0)
        <span class="grid h-5 min-w-5 place-items-center rounded-full bg-rose-500/90 px-1 text-[10px] font-bold text-white">{{ $badge }}</span>
    @endif
</a>
