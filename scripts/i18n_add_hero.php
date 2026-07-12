<?php
// New hero + services-carousel strings, added to en.json + every catalog.
$root = dirname(__DIR__);
$idx = ['es' => 0, 'fr' => 1, 'zh' => 2, 'pt' => 3];

$tr = [
    'Fund Alipay, WeChat Pay & shop digital' => [
        'Recarga Alipay, WeChat Pay y compra digital',
        'Rechargez Alipay, WeChat Pay et achetez du numérique',
        '充值支付宝、微信支付，畅购数字商品',
        'Recarregue Alipay, WeChat Pay e compre digital',
    ],
    'Top up with MoMo, bank, card or crypto — fund any China wallet automatically, plus shop gift cards, eSIMs & VPN delivered in minutes.' => [
        'Recarga con MoMo, banco, tarjeta o cripto: financia cualquier billetera de China automáticamente y compra tarjetas de regalo, eSIMs y VPN entregados en minutos.',
        'Rechargez avec MoMo, banque, carte ou crypto — financez automatiquement n\'importe quel portefeuille chinois, et achetez des cartes-cadeaux, eSIM et VPN livrés en quelques minutes.',
        '使用 MoMo、银行、银行卡或加密货币充值 — 自动为任意中国钱包充值，还可购买礼品卡、eSIM 和 VPN，几分钟内交付。',
        'Carregue com MoMo, banco, cartão ou cripto — financie qualquer carteira da China automaticamente e compre cartões-presente, eSIMs e VPN entregues em minutos.',
    ],
    'Send to any China wallet, delivered automatically.' => [
        'Envía a cualquier billetera de China, entregado automáticamente.',
        'Envoyez vers n\'importe quel portefeuille chinois, livré automatiquement.',
        '发送到任意中国钱包，自动到账。',
        'Envie para qualquer carteira da China, entregue automaticamente.',
    ],
    '210+ countries covered' => ['Más de 210 países cubiertos', 'Plus de 210 pays couverts', '覆盖 210 多个国家', 'Mais de 210 países cobertos'],
    'Travel eSIMs and local top-ups in over 210 destinations.' => [
        'eSIMs de viaje y recargas locales en más de 210 destinos.',
        'eSIM de voyage et recharges locales dans plus de 210 destinations.',
        '在 210 多个目的地提供旅行 eSIM 和本地充值。',
        'eSIMs de viagem e recargas locais em mais de 210 destinos.',
    ],
    'Secure VPN' => ['VPN seguro', 'VPN sécurisé', '安全 VPN', 'VPN seguro'],
    'Fast, private VPN for all your devices.' => [
        'VPN rápido y privado para todos tus dispositivos.',
        'VPN rapide et privé pour tous vos appareils.',
        '为您所有设备提供快速、私密的 VPN。',
        'VPN rápido e privado para todos os seus dispositivos.',
    ],
    'Gift cards, data, gaming & streaming.' => [
        'Tarjetas de regalo, datos, juegos y streaming.',
        'Cartes-cadeaux, données, jeux et streaming.',
        '礼品卡、流量、游戏和流媒体。',
        'Cartões-presente, dados, jogos e streaming.',
    ],
    'Amazon, Apple, Steam & more — delivered instantly.' => [
        'Amazon, Apple, Steam y más, entregados al instante.',
        'Amazon, Apple, Steam et plus — livrés instantanément.',
        'Amazon、Apple、Steam 等 — 即时交付。',
        'Amazon, Apple, Steam e mais — entregues instantaneamente.',
    ],
];

$en = json_decode(file_get_contents("$root/lang/en.json"), true);
foreach ($tr as $k => $_) { $en[$k] = $k; }
ksort($en, SORT_NATURAL | SORT_FLAG_CASE);
file_put_contents("$root/lang/en.json", json_encode($en, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

foreach ($idx as $l => $i) {
    $j = json_decode(file_get_contents("$root/lang/$l.json"), true);
    foreach ($tr as $k => $vals) { $j[$k] = $vals[$i]; }
    ksort($j, SORT_NATURAL | SORT_FLAG_CASE);
    file_put_contents("$root/lang/$l.json", json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo "$l: ".json_last_error_msg()." (".count($j).")\n";
}
// keep the manual-keys record in sync
$man = json_decode(@file_get_contents("$root/storage/i18n_manual_keys.json") ?: '[]', true);
$man = array_values(array_unique(array_merge($man, array_keys($tr))));
file_put_contents("$root/storage/i18n_manual_keys.json", json_encode($man, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "en: ".count($en)." keys\n";
