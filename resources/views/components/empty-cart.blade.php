@props(['img' => 'h-44', 'src' => 'shop cart.png', 'drops' => false])
{{-- Empty-cart illustration that feels alive: the figure gently bobs, and
     (when enabled) little "items" drop into the cart on a repeating loop. --}}
<div {{ $attributes->merge(['class' => 'cart-scene relative inline-block']) }} data-cart-scene>
    <img src="{{ asset('assets/'.rawurlencode($src)) }}" alt="{{ __('Your cart is empty') }}"
         class="cart-figure {{ $img }} w-auto" />
    @if ($drops)
        <span class="cart-drop" aria-hidden="true"></span>
        <span class="cart-drop cart-drop-2" aria-hidden="true"></span>
        <span class="cart-drop cart-drop-3" aria-hidden="true"></span>
    @endif
</div>
