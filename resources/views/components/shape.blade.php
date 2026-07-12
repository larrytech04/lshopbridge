@props(['name'])
@php
    // Large decorative geometric shapes for the feature panels — flat/cel-shaded
    // (solid fills at varying opacity for depth, no gradients). Uses currentColor.
    $shapes = [
        'arrow'   => '<path d="M28 90 C 28 52, 52 32, 90 32" fill="none" stroke="currentColor" stroke-width="12" stroke-linecap="round" stroke-opacity="0.22"/><path d="M72 22 L98 30 L88 56 Z" fill="currentColor" fill-opacity="0.28"/>',
        'sphere'  => '<circle cx="60" cy="60" r="40" fill="currentColor" fill-opacity="0.16"/><circle cx="47" cy="47" r="12" fill="currentColor" fill-opacity="0.26"/>',
        'cube'    => '<path d="M60 22 L98 44 L60 66 L22 44 Z" fill="currentColor" fill-opacity="0.26"/><path d="M22 44 L60 66 L60 106 L22 84 Z" fill="currentColor" fill-opacity="0.18"/><path d="M98 44 L60 66 L60 106 L98 84 Z" fill="currentColor" fill-opacity="0.11"/>',
        'torus'   => '<circle cx="60" cy="60" r="36" fill="none" stroke="currentColor" stroke-width="20" stroke-opacity="0.18"/><circle cx="60" cy="60" r="36" fill="none" stroke="currentColor" stroke-width="20" stroke-opacity="0.12" stroke-linecap="round" stroke-dasharray="40 250" transform="rotate(-50 60 60)"/>',
        'hexagon' => '<polygon points="60,16 100,39 100,81 60,104 20,81 20,39" fill="currentColor" fill-opacity="0.16"/><polygon points="60,40 80,51 80,73 60,84 40,73 40,51" fill="currentColor" fill-opacity="0.12"/>',
        'wave'    => '<path d="M16 68 q 14 -22 28 0 t 28 0 t 28 0" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round" stroke-opacity="0.22"/><path d="M16 92 q 14 -22 28 0 t 28 0 t 28 0" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round" stroke-opacity="0.13"/>',
        // Flying money: a banknote/coin whose wings flap while it floats.
        'money' => '<g class="money-fly">'
            .'<g class="wing-l"><path d="M48 52 C 32 44, 14 48, 8 60 C 22 64, 36 62, 48 64 C 42 60, 42 56, 48 52 Z" fill="currentColor" fill-opacity="0.20"/></g>'
            .'<g class="wing-r"><path d="M72 52 C 88 44, 106 48, 112 60 C 98 64, 84 62, 72 64 C 78 60, 78 56, 72 52 Z" fill="currentColor" fill-opacity="0.20"/></g>'
            .'<rect x="44" y="46" width="32" height="28" rx="5" fill="currentColor" fill-opacity="0.26"/>'
            .'<rect x="44" y="46" width="32" height="28" rx="5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-opacity="0.32"/>'
            .'<circle cx="60" cy="60" r="8.5" fill="currentColor" fill-opacity="0.42"/>'
            .'<path d="M60 55 v10 M57.4 57.2 h5 a1.7 1.7 0 0 1 0 3.4 h-4.8 a1.7 1.7 0 0 0 0 3.4 h5" fill="none" stroke="#fff" stroke-width="1.4" stroke-linecap="round"/>'
            .'</g>',
        // Network security: ring + spokes + nodes spin; central shield stays still.
        'network' => '<g class="net-spin" fill="none" stroke="currentColor" stroke-width="1.5" stroke-opacity="0.5">'
            .'<circle cx="60" cy="60" r="46"/>'
            .'<path d="M60 36 V20 M76.97 43.03 L88.28 31.72 M84 60 H100 M76.97 76.97 L88.28 88.28 M60 84 V100 M43.03 76.97 L31.72 88.28 M36 60 H20 M43.03 43.03 L31.72 31.72"/>'
            .'<circle cx="60" cy="14" r="6"/><circle cx="92.5" cy="27.5" r="6"/><circle cx="106" cy="60" r="6"/><circle cx="92.5" cy="92.5" r="6"/><circle cx="60" cy="106" r="6"/><circle cx="27.5" cy="92.5" r="6"/><circle cx="14" cy="60" r="6"/><circle cx="27.5" cy="27.5" r="6"/>'
            .'</g>'
            .'<g fill="none" stroke="currentColor" stroke-width="1.8" stroke-opacity="0.7"><path d="M60 40 L78 47 V61 c0 11 -7 17 -18 21 c-11 -4 -18 -10 -18 -21 V47 Z"/><path d="M53 61 l5 5 9 -10" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></g>',
    ];
    $svg = $shapes[$name] ?? $shapes['sphere'];
@endphp
<svg viewBox="0 0 120 120" fill="none" {{ $attributes }}>{!! $svg !!}</svg>
