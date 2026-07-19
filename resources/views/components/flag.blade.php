@props(['iso' => 'CM'])

{{-- Local flag (flag-icons package), renders correctly offline for every country. --}}
<span {{ $attributes->merge(['class' => 'fi fi-'.strtolower($iso).' inline-block shrink-0 rounded-[3px] ring-1 ring-app']) }}
      style="background-size: cover; background-position: center;"></span>
