@props(['category', 'size' => 'h-10 w-10', 'colored' => true])
@php
    $meta = guide_category_meta($category);
    // Literal, complete class strings (not interpolated) so Tailwind's scanner
    // can find and generate them, see resources/css/app.css @source.
    $colorClass = ! $colored ? '' : match ($meta['color']) {
        'amber' => 'text-amber-600',
        'orange' => 'text-orange-600',
        'rose' => 'text-rose-600',
        'pink' => 'text-pink-600',
        'red' => 'text-red-600',
        'emerald' => 'text-emerald-600',
        'sky' => 'text-sky-600',
        'violet' => 'text-violet-600',
        'slate' => 'text-slate-600',
        'brand' => 'text-brand-600',
        default => 'text-slate-600',
    };

    // Each category maps to one of the illustrations sitting in public/assets,
    // recoloured to match via a CSS mask (works regardless of the source
    // file's own baked-in colours, SVG or PNG alike).
    $assetIcons = [
        'orientation' => 'Global-Learning--Streamline-Sharp-Remix.svg',
        '1688' => 'Investment-Agreement--Streamline-Nova.svg',
        'taobao' => 'Shop-Open--Streamline-Freehand.svg',
        'pinduoduo' => 'Multiple-Users-1--Streamline-Ultimate.svg',
        'customs' => 'Pricing-Consumption--Streamline-Carbon.svg',
        'tmall' => 'Shopping-Basket-Star--Streamline-Ultimate.svg',
        'xiaohongshu' => 'Shopping-Basket-Search--Streamline-Freehand.svg',
        'dhgate' => 'Shop--Streamline-Freehand.svg',
        'jd' => 'E-Commerce-Apparel-Buy-Laptop--Streamline-Freehand.png',
        'weidian' => 'Real-Estate-Message-Building-Buy-Sell--Streamline-Freehand.png',
        'aliexpress' => 'Mobile-Shopping-Shop-Basket--Streamline-Freehand.png',
        'alipay' => 'Money-Wallet-1--Streamline-Ultimate.png',
        'wechatpay' => 'Credit-Card-Payment--Streamline-Ultimate.png',
        'shipping' => 'Shipment-Package--Streamline-Ultimate.png',
        'mistakes' => 'Gateway-Security--Streamline-Ultimate.png',
        'glossary' => 'Translate--Streamline-Ultimate.png',
    ];
    $url = asset('assets/'.rawurlencode($assetIcons[$category] ?? $assetIcons['orientation']));
@endphp
<span {{ $attributes->merge(['class' => "inline-block shrink-0 $size $colorClass"]) }}>
    <span class="block h-full w-full" style="background-color: currentColor; -webkit-mask-image: url('{{ $url }}'); mask-image: url('{{ $url }}'); -webkit-mask-size: contain; mask-size: contain; -webkit-mask-repeat: no-repeat; mask-repeat: no-repeat; -webkit-mask-position: center; mask-position: center;"></span>
</span>
