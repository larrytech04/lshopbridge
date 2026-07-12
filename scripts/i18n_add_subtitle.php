<?php
// Combined hero subtitle (funding + digital shop), translated into every catalog.
$root = dirname(__DIR__);
$idx = ['es' => 0, 'fr' => 1, 'zh' => 2, 'pt' => 3];
$tr = [
    'Top up with MoMo, bank, card or crypto and we deliver to any China wallet automatically — plus shop gift cards, eSIMs, VPN & more, delivered in minutes.' => [
        'Recarga con MoMo, banco, tarjeta o cripto y entregamos a cualquier billetera de China automáticamente — además, compra tarjetas de regalo, eSIMs, VPN y más, entregados en minutos.',
        'Rechargez avec MoMo, banque, carte ou crypto et nous livrons sur n\'importe quel portefeuille chinois automatiquement — et achetez des cartes-cadeaux, eSIM, VPN et plus, livrés en quelques minutes.',
        '使用 MoMo、银行、银行卡或加密货币充值，我们自动到账任意中国钱包 — 还可购买礼品卡、eSIM、VPN 等，几分钟内交付。',
        'Carregue com MoMo, banco, cartão ou cripto e entregamos em qualquer carteira da China automaticamente — além de comprar cartões-presente, eSIMs, VPN e mais, entregues em minutos.',
    ],
];

$en = json_decode(file_get_contents("$root/lang/en.json"), true);
$new = [];
foreach ($tr as $k => $_) { if (!isset($en[$k])) { $en[$k] = $k; $new[$k] = $tr[$k]; } }
ksort($en, SORT_NATURAL | SORT_FLAG_CASE);
file_put_contents("$root/lang/en.json", json_encode($en, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

foreach ($idx as $l => $i) {
    $j = json_decode(file_get_contents("$root/lang/$l.json"), true);
    foreach ($tr as $k => $vals) { $j[$k] = $vals[$i]; }
    ksort($j, SORT_NATURAL | SORT_FLAG_CASE);
    file_put_contents("$root/lang/$l.json", json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo "$l: ".json_last_error_msg()." (".count($j).")\n";
}
$man = json_decode(@file_get_contents("$root/storage/i18n_manual_keys.json") ?: '[]', true);
$man = array_values(array_unique(array_merge($man, array_keys($tr))));
file_put_contents("$root/storage/i18n_manual_keys.json", json_encode($man, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo 'added '.count($new)." new keys; en=".count($en)."\n";
