<?php
// Accepted-payment-methods section: heading, subtitle, group labels & generic names.
$root = dirname(__DIR__);
$idx = ['es' => 0, 'fr' => 1, 'zh' => 2, 'pt' => 3];
$tr = [
    'Accepted payment methods' => ['Métodos de pago aceptados', 'Moyens de paiement acceptés', '支持的支付方式', 'Métodos de pagamento aceitos'],
    'Mobile money, cards, bank transfer, USSD & crypto — pay the way you already do.' => [
        'Dinero móvil, tarjetas, transferencia bancaria, USSD y cripto — paga como siempre lo haces.',
        'Mobile money, cartes, virement bancaire, USSD et crypto — payez comme vous le faites déjà.',
        '移动支付、银行卡、银行转账、USSD 和加密货币 — 用您习惯的方式付款。',
        'Dinheiro móvel, cartões, transferência bancária, USSD e cripto — pague como já faz.',
    ],
    'Top up your wallet using the channels you already trust — mobile money, cards, bank transfer, USSD & crypto, accepted across Africa.' => [
        'Recarga tu billetera con los canales que ya usas — dinero móvil, tarjetas, transferencia, USSD y cripto, aceptados en toda África.',
        'Rechargez votre portefeuille avec les canaux que vous utilisez déjà — mobile money, cartes, virement, USSD et crypto, acceptés dans toute l\'Afrique.',
        '使用您熟悉的渠道为钱包充值 — 移动支付、银行卡、转账、USSD 和加密货币，全非洲通用。',
        'Recarregue a sua carteira com os canais que já confia — dinheiro móvel, cartões, transferência, USSD e cripto, aceitos em toda a África.',
    ],
    'How your top-up is processed' => ['Cómo se procesa tu recarga', 'Comment votre recharge est traitée', '您的充值如何处理', 'Como a sua recarga é processada'],
    'Secure & encrypted' => ['Seguro y cifrado', 'Sécurisé et chiffré', '安全加密', 'Seguro e encriptado'],
    '40+ African countries' => ['Más de 40 países africanos', 'Plus de 40 pays africains', '40+ 非洲国家', 'Mais de 40 países africanos'],
    'Min' => ['Mín', 'Min', '最低', 'Mín'],
    'Max' => ['Máx', 'Max', '最高', 'Máx'],

    // Group labels
    'Cards' => ['Tarjetas', 'Cartes', '银行卡', 'Cartões'],
    'Mobile Money' => ['Dinero móvil', 'Mobile Money', '移动支付', 'Dinheiro móvel'],
    'Bank & USSD' => ['Banco y USSD', 'Banque & USSD', '银行与 USSD', 'Banco e USSD'],
    'Wallets & crypto' => ['Billeteras y cripto', 'Portefeuilles & crypto', '钱包与加密货币', 'Carteiras e cripto'],

    // Generic method names (brand names stay as-is)
    'American Express' => ['American Express', 'American Express', '美国运通', 'American Express'],
    'Bank transfer' => ['Transferencia bancaria', 'Virement bancaire', '银行转账', 'Transferência bancária'],
    'Bank account' => ['Cuenta bancaria', 'Compte bancaire', '银行账户', 'Conta bancária'],
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
