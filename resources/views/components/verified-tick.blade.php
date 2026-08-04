{{-- Verified badge, recreated from assets/blue tick.jpg: a wavy 12-point seal
     (three rounded squares rotated 30° apart) with the checkmark cut out as a
     genuine transparent hole via mask, not filled with white or a theme colour.
     Because it's a real cutout, whatever sits behind the badge (page, card,
     avatar photo) simply shows through, so it always looks correct regardless
     of theme or background, exactly like the source asset's own transparency. --}}
@php $maskId = 'vt-'.uniqid(); @endphp
<svg {{ $attributes->merge(['class' => 'h-4 w-4 shrink-0']) }} viewBox="0 0 24 24" fill="none" role="img" aria-label="{{ __('Verified') }}" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <mask id="{{ $maskId }}" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="24">
            <rect x="0" y="0" width="24" height="24" fill="#fff"/>
            <path d="M7 12.4l3.1 3.1L17 8.6" fill="none" stroke="#000" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
        </mask>
    </defs>
    <g mask="url(#{{ $maskId }})">
        <rect x="4.25" y="4.25" width="15.5" height="15.5" rx="1.7" fill="#01A0FE" transform="rotate(0 12 12)"/>
        <rect x="4.25" y="4.25" width="15.5" height="15.5" rx="1.7" fill="#01A0FE" transform="rotate(30 12 12)"/>
        <rect x="4.25" y="4.25" width="15.5" height="15.5" rx="1.7" fill="#01A0FE" transform="rotate(60 12 12)"/>
    </g>
</svg>
