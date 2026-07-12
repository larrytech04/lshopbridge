<?php
// Hero heading fragments (split for responsive line breaks).
$root = dirname(__DIR__);
$idx = ['es' => 0, 'fr' => 1, 'zh' => 2, 'pt' => 3];
$tr = [
    'Your All-in-One Hub' => ['Tu plataforma todo en uno', 'Votre plateforme tout-en-un', '您的一体化平台', 'A sua plataforma tudo-em-um'],
    'for China'           => ['para China', 'pour la Chine', '中国', 'para a China'],
    'Funding &'           => ['Recargas y', 'Recharges et', '充值与', 'Recargas e'],
    'Digital Shop'        => ['Tienda Digital', 'Boutique Numérique', '数字商店', 'Loja Digital'],
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
