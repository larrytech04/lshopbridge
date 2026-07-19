@props(['name' => 'gift'])

{{--
  Duotone SVG illustrations rendered in a soft circular glass badge.
  Hand-built inline, no external assets. Two-tone: brand fill (back) + accent (front).
--}}
@php
$art = [
    'gift' => '
        <rect x="16" y="28" width="32" height="22" rx="3" class="fill-brand-500/25"/>
        <rect x="16" y="28" width="32" height="8" rx="2" class="fill-brand-500/40"/>
        <path d="M32 24v26" stroke-width="2.5" class="stroke-accent-500"/>
        <path d="M32 24c-2-6-12-7-12-1 0 4 8 5 12 1Zm0 0c2-6 12-7 12-1 0 4-8 5-12 1Z" class="fill-accent-500/30 stroke-accent-500" stroke-width="2"/>
        <circle cx="48" cy="20" r="2" class="fill-accent-500"/><circle cx="18" cy="22" r="1.5" class="fill-brand-500"/>',
    'esim' => '
        <circle cx="32" cy="30" r="16" class="fill-brand-500/15"/>
        <path d="M32 18c6 4 6 20 0 24-6-4-6-20 0-24Z" class="stroke-brand-500" stroke-width="2"/>
        <path d="M18 30h28M20 23h24M20 37h24" class="stroke-brand-500/50" stroke-width="1.6"/>
        <path d="M32 44c4 4 9 5 14 3" class="stroke-accent-500" stroke-width="2.5" stroke-dasharray="2 4"/>
        <circle cx="46" cy="47" r="3" class="fill-accent-500"/>',
    'shield' => '
        <path d="M32 14 18 19v9c0 9 6 14.5 14 17 8-2.5 14-8 14-17v-9Z" class="fill-brand-500/20 stroke-brand-500" stroke-width="2"/>
        <circle cx="32" cy="28" r="4" class="fill-accent-500/30 stroke-accent-500" stroke-width="2"/>
        <path d="M25 40c1.5-4 12.5-4 14 0" class="stroke-accent-500" stroke-width="2"/>
        <path d="m27 30 3 3 5-6" class="stroke-accent-500" stroke-width="2.4"/>',
    'device' => '
        <rect x="18" y="16" width="20" height="32" rx="3" class="fill-brand-500/15 stroke-brand-500" stroke-width="2"/>
        <path d="M26 43h4" class="stroke-brand-500" stroke-width="2"/>
        <rect x="22" y="21" width="12" height="9" rx="1.5" class="fill-accent-500/30"/>
        <path d="M40 26c5-3 9 2 6 6l-4 6" class="stroke-accent-500" stroke-width="2"/>
        <path d="M44 22l1.5 3 3 1.5-3 1.5L45.5 31 44 28l-3-1.5 3-1.5Z" class="fill-accent-500"/>',
    'browse' => '
        <path d="M20 24h24l-2 22a3 3 0 0 1-3 2.7H25a3 3 0 0 1-3-2.7Z" class="fill-brand-500/18 stroke-brand-500" stroke-width="2"/>
        <path d="M27 24v-2a5 5 0 0 1 10 0v2" class="stroke-brand-500" stroke-width="2"/>
        <circle cx="33" cy="36" r="6" class="fill-none stroke-accent-500" stroke-width="2.2"/>
        <path d="m37.5 40.5 4 4" class="stroke-accent-500" stroke-width="2.6"/>',
    'bolt' => '
        <circle cx="32" cy="32" r="17" class="fill-brand-500/12"/>
        <path d="M34 16 22 34h9l-3 14 14-20h-9Z" class="fill-accent-500/30 stroke-accent-500" stroke-width="2"/>',
    'globe' => '
        <circle cx="32" cy="32" r="16" class="fill-brand-500/15 stroke-brand-500" stroke-width="2"/>
        <path d="M16 32h32M32 16c7 6 7 26 0 32-7-6-7-26 0-32Z" class="stroke-brand-500/60" stroke-width="1.6"/>
        <circle cx="44" cy="22" r="3" class="fill-accent-500"/>',
    'headset' => '
        <path d="M18 34v-2a14 14 0 0 1 28 0v2" class="stroke-brand-500" stroke-width="2"/>
        <rect x="15" y="33" width="7" height="12" rx="3" class="fill-accent-500/30 stroke-accent-500" stroke-width="2"/>
        <rect x="42" y="33" width="7" height="12" rx="3" class="fill-accent-500/30 stroke-accent-500" stroke-width="2"/>
        <path d="M45 45v2a5 5 0 0 1-5 5h-6" class="stroke-brand-500" stroke-width="2"/>',
];
$svg = $art[$name] ?? $art['gift'];
@endphp

<span {{ $attributes->merge(['class' => 'illus-badge']) }}>
    <svg viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round" class="h-full w-full p-2.5">
        {!! $svg !!}
    </svg>
</span>
