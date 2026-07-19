
<?php
// Announcement bar messages + dismiss label.
$root = dirname(__DIR__);
$idx = ['es' => 0, 'fr' => 1, 'zh' => 2, 'pt' => 3];
$tr = [
    'Built for African shoppers funding China accounts' => [
        'Hecho para compradores africanos que recargan cuentas de China',
        'Conçu pour les acheteurs africains qui rechargent des comptes chinois',
        '专为为中国账户充值的非洲买家打造',
        'Feito para compradores africanos que carregam contas da China',
    ],
    'MoMo, bank, card & crypto funding in one place' => [
        'Recargas con MoMo, banco, tarjeta y cripto en un solo lugar',
        'Recharges MoMo, banque, carte et crypto au même endroit',
        'MoMo、银行、银行卡和加密货币充值，一站搞定',
        'Recargas com MoMo, banco, cartão e cripto num só lugar',
    ],
    'Trusted payment support for China shopping' => [
        'Soporte de pago confiable para comprar en China',
        'Assistance de paiement fiable pour acheter en Chine',
        '值得信赖的中国购物支付支持',
        'Suporte de pagamento confiável para comprar na China',
    ],
    'Simple funding. Clear rates. Reliable support.' => [
        'Recargas simples. Tarifas claras. Soporte confiable.',
        'Recharges simples. Taux clairs. Assistance fiable.',
        '充值简单。汇率清晰。支持可靠。',
        'Recargas simples. Taxas claras. Suporte confiável.',
    ],
    'Dismiss' => ['Cerrar', 'Fermer', '关闭', 'Fechar'],
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
