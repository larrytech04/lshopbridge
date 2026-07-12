@props(['name'])
@php
    // Round, coin-style brand badges (colored circle + white glyph) for the
    // payment / crypto logo marquee. Self-contained so they grayscale cleanly.
    $s = 'viewBox="0 0 32 32" style="height:100%;width:100%;display:block" xmlns="http://www.w3.org/2000/svg"';

    // [circle colour, glyph, font-size, (optional) glyph colour]
    $txt = [
        'visa'      => ['#1434CB', 'VISA', 8],
        'verve'     => ['#ED1C24', 'V', 15],
        'amex'      => ['#2E77BC', 'AE', 12],
        'mtn'       => ['#FFCB05', 'MTN', 8.5, '#00558C'],
        'airtel'    => ['#E40000', 'a', 17],
        'mpesa'     => ['#1BB24B', 'M', 15],
        'tigo'      => ['#0A2A66', 't', 17],
        'moov'      => ['#F58220', 'm', 16],
        'ussd'      => ['#475569', '*#', 12],
        'enaira'    => ['#008751', '&#8358;', 15],
        'barter'    => ['#FB6340', 'b', 16],
        'googlepay' => ['#4285F4', 'G', 15],
        'paypal'    => ['#003087', 'P', 15],
        'usdt'      => ['#26A17B', '&#8366;', 15],
        'btc'       => ['#F7931A', '&#8383;', 14],
        'usdc'      => ['#2775CA', '$', 15],
        'ltc'       => ['#345D9D', '&#321;', 15],
        'trx'       => ['#EB0029', 'T', 15],
        'doge'      => ['#C2A633', '&#208;', 15],
        'dai'       => ['#F5AC37', 'D', 15],
        'euroc'     => ['#2775CA', '&#8364;', 15],
        'usds'      => ['#1652F0', 'S', 15],
        'pyusd'     => ['#0070BA', 'P', 15],
        'usde'      => ['#1F2937', '$', 15],
        'fdusd'     => ['#1F2937', 'F', 15],
    ];

    $custom = [
        'mastercard' => '<circle cx="16" cy="16" r="16" fill="#252525"/><circle cx="13" cy="16" r="7" fill="#EB001B"/><circle cx="19" cy="16" r="7" fill="#F79E1B" fill-opacity=".9"/>',
        'orange'     => '<circle cx="16" cy="16" r="16" fill="#FF7900"/><rect x="10" y="10" width="12" height="12" rx="1.5" fill="#fff"/>',
        'vodafone'   => '<circle cx="16" cy="16" r="16" fill="#E60000"/><path d="M18 8.5c-4 .2-7 3.4-7 7.2 0 3.5 2.6 6.4 6 6.7-.1-.3-.2-.7-.2-1.1 0-2.9 2.2-5.2 5-5.4 0-.3.1-.6.1-1C22 11.3 20.3 9 18 8.5z" fill="#fff"/>',
        'wave'       => '<circle cx="16" cy="16" r="16" fill="#1DC3F0"/><path d="M7 18c3 0 3-4 6-4s3 4 6 4 3-4 6-4" stroke="#fff" stroke-width="2.4" fill="none" stroke-linecap="round"/>',
        'bank'       => '<circle cx="16" cy="16" r="16" fill="#475569"/><path fill="#fff" d="M16 8 8 12v1.5h16V12L16 8zM9.5 15v6H8.5v2h15v-2h-1v-6h-2v6h-2.5v-6h-2v6h-2.5v-6z"/>',
        'account'    => '<circle cx="16" cy="16" r="16" fill="#475569"/><path fill="#fff" d="M16 8 9 11.5v1.5h14v-1.5L16 8zM10.5 14h2v5h-2zM15 14h2v5h-2zM19.5 14h2v5h-2zM9 20h14v1.6H9z"/>',
        'applepay'   => '<circle cx="16" cy="16" r="16" fill="#000"/><path fill="#fff" d="M19.6 16.2c0-1.9 1.6-2.8 1.6-2.9-.9-1.3-2.2-1.5-2.7-1.5-1.1-.1-2.2.7-2.8.7-.6 0-1.5-.6-2.4-.6-1.2 0-2.4.7-3 1.8-1.3 2.3-.3 5.6 1 7.4.6.9 1.4 1.9 2.3 1.9.9 0 1.3-.6 2.4-.6 1.1 0 1.4.6 2.4.6 1 0 1.6-.9 2.2-1.8.7-1 1-2 1-2.1-.1 0-1.9-.8-1.9-2.9zM18 11.1c.5-.6.8-1.4.7-2.3-.7 0-1.6.5-2.1 1.1-.5.5-.9 1.4-.8 2.2.8.1 1.6-.4 2.2-1z"/>',
        'ton'        => '<circle cx="16" cy="16" r="16" fill="#0098EA"/><path d="M16 9 24 14 16 24 8 14Z" fill="#fff" fill-opacity=".95"/>',
        'gram'       => '<circle cx="16" cy="16" r="16" fill="#1DA1F2"/><path d="M16 9 24 14 16 24 8 14Z" fill="#fff"/>',
        'bnb'        => '<circle cx="16" cy="16" r="16" fill="#F0B90B"/><path fill="#fff" d="M16 9.5l2.1 2.1L16 13.7l-2.1-2.1L16 9.5zm-4.4 4.4L13.7 16l-2.1 2.1L9.5 16l2.1-2.1zm8.8 0L22.5 16l-2.1 2.1L18.3 16l2.1-2.1zM16 18.3l2.1 2.1L16 22.5l-2.1-2.1L16 18.3zM16 13.9L18.1 16 16 18.1 13.9 16 16 13.9z"/>',
        'sol'        => '<circle cx="16" cy="16" r="16" fill="#9945FF"/><path fill="#fff" d="M10.2 12.6c.1-.1.3-.2.5-.2h10.8c.3 0 .4.3.2.5l-1.5 1.5c-.1.1-.3.2-.5.2H8.9c-.3 0-.4-.3-.2-.5l1.5-1.5zm0 6.4c.1-.1.3-.2.5-.2h10.8c.3 0 .4.3.2.5l-1.5 1.5c-.1.1-.3.2-.5.2H8.9c-.3 0-.4-.3-.2-.5L10.2 19zm11.5-3.4c-.1-.1-.3-.2-.5-.2H10.4c-.3 0-.4.3-.2.5l1.5 1.5c.1.1.3.2.5.2h10.8c.3 0 .4-.3.2-.5l-1.5-1.5z"/>',
    ];

    if (isset($custom[$name])) {
        $svg = '<svg '.$s.'>'.$custom[$name].'</svg>';
    } elseif (isset($txt[$name])) {
        [$c, $g, $fs] = $txt[$name];
        $tc = $txt[$name][3] ?? '#fff';
        $y = 16 + $fs * 0.35;
        $svg = '<svg '.$s.'><circle cx="16" cy="16" r="16" fill="'.$c.'"/><text x="16" y="'.$y.'" font-family="Arial,Helvetica,sans-serif" font-size="'.$fs.'" font-weight="800" fill="'.$tc.'" text-anchor="middle">'.$g.'</text></svg>';
    } else {
        $svg = '<svg '.$s.'><circle cx="16" cy="16" r="16" fill="#475569"/></svg>';
    }
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full']) }}>{!! $svg !!}</span>
