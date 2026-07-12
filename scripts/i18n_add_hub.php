<?php
$root = dirname(__DIR__);
$idx = ['es' => 0, 'fr' => 1, 'zh' => 2, 'pt' => 3];
$tr = [
    'All-in-one platform' => ['Plataforma todo en uno', 'Plateforme tout-en-un', '一体化平台', 'Plataforma tudo-em-um'],
    'Your hub for China funding & digital goods' => ['Tu centro para recargas a China y productos digitales', 'Votre plateforme pour les recharges Chine et les produits numériques', '您的中国充值与数字商品中心', 'O seu hub para recargas na China e produtos digitais'],
    'Move money into China wallets and instantly shop digital products — gift cards, eSIMs, VPN, data and more — all in one secure platform.' => [
        'Mueve dinero a billeteras de China y compra al instante productos digitales —tarjetas de regalo, eSIM, VPN, datos y más— todo en una plataforma segura.',
        'Transférez de l\'argent vers des portefeuilles chinois et achetez instantanément des produits numériques — cartes-cadeaux, eSIM, VPN, données et plus — sur une seule plateforme sécurisée.',
        '将资金转入中国钱包，并即时购买数字产品 — 礼品卡、eSIM、VPN、流量等 — 全部在一个安全平台。',
        'Mova dinheiro para carteiras da China e compre instantaneamente produtos digitais — cartões-presente, eSIM, VPN, dados e mais — tudo numa plataforma segura.',
    ],
    'Fund China wallets' => ['Recarga billeteras de China', 'Rechargez des portefeuilles chinois', '为中国钱包充值', 'Carregue carteiras da China'],
    'Live exchange rates' => ['Tipos de cambio en vivo', 'Taux de change en direct', '实时汇率', 'Câmbios em tempo real'],
    'Instant auto-funding' => ['Recarga automática instantánea', 'Recharge automatique instantanée', '即时自动充值', 'Recarga automática instantânea'],
    'Alipay & WeChat Pay' => ['Alipay & WeChat Pay', 'Alipay & WeChat Pay', '支付宝和微信支付', 'Alipay & WeChat Pay'],
    'Transparent fees' => ['Comisiones transparentes', 'Frais transparents', '透明费用', 'Taxas transparentes'],
    'Shop digital goods' => ['Compra productos digitales', 'Achetez des produits numériques', '购买数字商品', 'Compre produtos digitais'],
    'VPN' => ['VPN', 'VPN', 'VPN', 'VPN'],
    'Secure & protected' => ['Seguro y protegido', 'Sécurisé et protégé', '安全有保障', 'Seguro e protegido'],
    'Encryption' => ['Cifrado', 'Chiffrement', '加密', 'Encriptação'],
    '256-bit' => ['256-bit', '256-bit', '256 位', '256-bit'],
    'KYC & audits' => ['KYC y auditorías', 'KYC et audits', 'KYC 与审计', 'KYC e auditorias'],
    'Passed' => ['Aprobado', 'Validé', '已通过', 'Aprovado'],
    'Encrypted documents, audit logs & automatic fraud screening.' => ['Documentos cifrados, registros de auditoría y detección automática de fraude.', 'Documents chiffrés, journaux d\'audit et détection automatique de la fraude.', '加密文件、审计日志和自动欺诈筛查。', 'Documentos encriptados, registos de auditoria e deteção automática de fraude.'],
    'Live funding rate' => ['Tasa de recarga en vivo', 'Taux de recharge en direct', '实时充值汇率', 'Taxa de recarga em tempo real'],
    'Live' => ['En vivo', 'En direct', '实时', 'Ao vivo'],
    'Service fee' => ['Comisión de servicio', 'Frais de service', '服务费', 'Taxa de serviço'],
    'Estimate · final rate at checkout' => ['Estimación · tasa final al pagar', 'Estimation · taux final au paiement', '估算 · 最终汇率以结账为准', 'Estimativa · taxa final no checkout'],
];

$en = json_decode(file_get_contents("$root/lang/en.json"), true);
$new = [];
foreach ($tr as $k => $_) { if (!isset($en[$k])) { $en[$k] = $k; $new[$k] = $tr[$k]; } }
ksort($en, SORT_NATURAL | SORT_FLAG_CASE);
file_put_contents("$root/lang/en.json", json_encode($en, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

foreach ($idx as $l => $i) {
    $j = json_decode(file_get_contents("$root/lang/$l.json"), true);
    foreach ($new as $k => $vals) { $j[$k] = $vals[$i]; }
    ksort($j, SORT_NATURAL | SORT_FLAG_CASE);
    file_put_contents("$root/lang/$l.json", json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo "$l: ".json_last_error_msg()." (".count($j).")\n";
}
$man = json_decode(file_get_contents("$root/storage/i18n_manual_keys.json"), true);
file_put_contents("$root/storage/i18n_manual_keys.json", json_encode(array_values(array_unique(array_merge($man, array_keys($tr)))), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo 'added '.count($new)." new keys; en=".count($en)."\n";
