<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
@php
    // Real, configured links only — a footer full of "#" placeholder social
    // icons would look broken/dishonest the moment someone actually taps one.
    $socialLinks = collect([
        'Facebook' => setting('social_facebook'),
        'X' => setting('social_x'),
        'Instagram' => setting('social_instagram'),
        'TikTok' => setting('social_tiktok'),
        'WhatsApp' => setting('social_whatsapp'),
    ])->filter(fn ($url) => $url && $url !== '#');
@endphp
@if ($socialLinks->isNotEmpty())
{{ $socialLinks->map(fn ($url, $label) => "[{$label}]({$url})")->implode('&nbsp;&nbsp;&nbsp;&nbsp;') }}
<br><br>
@endif
{{ config('platform.tagline') }}
<br>
@if (\Illuminate\Support\Facades\Route::has('contact'))
{{ __('Need a hand?') }} [{{ __('Contact support') }}]({{ route('contact') }})
<br>
@endif
© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
