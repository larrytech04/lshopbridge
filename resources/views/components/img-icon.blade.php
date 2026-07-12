@props(['name'])
@php $src = asset('assets/'.$name); @endphp
{{-- Monochrome PNG (public/assets) rendered as a CSS mask so it inherits the
     current text colour and adapts to light/dark + active states. --}}
<span {{ $attributes->merge(['class' => 'png-icon']) }}
      style="-webkit-mask-image:url('{{ $src }}');mask-image:url('{{ $src }}')"></span>
