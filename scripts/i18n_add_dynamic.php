<?php
/**
 * Adds strings that are translated via a DYNAMIC key (`__($var)` from a PHP
 * array / DB field) and therefore can't be auto-detected by i18n_scan.
 * Writes them into en.json (canonical) + every supported catalog.
 */
$root = dirname(__DIR__);
$langs = ['es', 'fr', 'zh', 'pt'];

// English => [es, fr, zh, pt]
$tr = [
    'Top up your wallet with MoMo, Orange, bank, card or crypto. Payments confirm automatically.' => [
        'Recarga tu billetera con MoMo, Orange, banco, tarjeta o cripto. Los pagos se confirman automáticamente.',
        'Rechargez votre portefeuille avec MoMo, Orange, banque, carte ou crypto. Les paiements sont confirmés automatiquement.',
        '使用 MoMo、Orange、银行、银行卡或加密货币为钱包充值。付款自动确认。',
        'Carregue a sua carteira com MoMo, Orange, banco, cartão ou cripto. Os pagamentos são confirmados automaticamente.',
    ],
    'Save a China wallet' => ['Guarda una billetera de China', 'Enregistrez un portefeuille chinois', '保存中国钱包', 'Guarde uma carteira da China'],
    'Add your Alipay / WeChat Pay account once. We verify it for safe, repeat funding.' => [
        'Añade tu cuenta de Alipay / WeChat Pay una vez. La verificamos para recargas seguras y repetidas.',
        'Ajoutez votre compte Alipay / WeChat Pay une fois. Nous le vérifions pour des recharges sûres et répétées.',
        '添加一次您的支付宝 / 微信支付账户。我们会验证它，以便安全、重复充值。',
        'Adicione a sua conta Alipay / WeChat Pay uma vez. Verificamo-la para recargas seguras e repetidas.',
    ],
    'We auto-fund' => ['Recargamos automáticamente', 'Nous rechargeons automatiquement', '我们自动充值', 'Recarregamos automaticamente'],
    'Enter an amount — our engine pays your China wallet instantly at a fair rate.' => [
        'Introduce un importe: nuestro motor paga tu billetera de China al instante a una tasa justa.',
        'Saisissez un montant — notre moteur paie votre portefeuille chinois instantanément à un taux équitable.',
        '输入金额 — 我们的引擎以公平汇率即时支付到您的中国钱包。',
        'Introduza um valor — o nosso motor paga a sua carteira da China instantaneamente a uma taxa justa.',
    ],
    'Bank-grade security' => ['Seguridad de nivel bancario', 'Sécurité de niveau bancaire', '银行级安全', 'Segurança de nível bancário'],
    'KYC tiers, encrypted documents, audit logs and automatic fraud screening on every transaction.' => [
        'Niveles KYC, documentos cifrados, registros de auditoría y detección automática de fraude en cada transacción.',
        'Niveaux KYC, documents chiffrés, journaux d\'audit et détection automatique de la fraude à chaque transaction.',
        'KYC 等级、加密文件、审计日志以及每笔交易的自动欺诈筛查。',
        'Níveis KYC, documentos encriptados, registos de auditoria e deteção automática de fraude em cada transação.',
    ],
    'Automatic funding' => ['Recarga automática', 'Recharge automatique', '自动充值', 'Recarga automática'],
    'Webhook-confirmed payments trigger instant payouts to your saved China wallet.' => [
        'Los pagos confirmados por webhook activan pagos instantáneos a tu billetera de China guardada.',
        'Les paiements confirmés par webhook déclenchent des versements instantanés vers votre portefeuille chinois enregistré.',
        '经 Webhook 确认的付款会触发即时付款到您保存的中国钱包。',
        'Os pagamentos confirmados por webhook acionam pagamentos instantâneos para a sua carteira da China guardada.',
    ],
    'Transparent pricing' => ['Precios transparentes', 'Tarification transparente', '透明定价', 'Preços transparentes'],
    'See the exact rate and fee before you confirm. No surprises, ever.' => [
        'Ve la tasa y la comisión exactas antes de confirmar. Sin sorpresas, nunca.',
        'Voyez le taux et les frais exacts avant de confirmer. Aucune surprise, jamais.',
        '确认前查看确切的汇率和手续费。绝无意外。',
        'Veja a taxa e a comissão exatas antes de confirmar. Sem surpresas, nunca.',
    ],
    'Hire trusted procurement & shipping agents with ratings and warehouse details.' => [
        'Contrata agentes de compras y envío de confianza con valoraciones y datos de almacén.',
        'Engagez des agents d\'approvisionnement et d\'expédition de confiance avec des évaluations et des détails d\'entrepôt.',
        '聘请有评分和仓库信息的可信采购与货运代理。',
        'Contrate agentes de compras e envio de confiança com avaliações e detalhes de armazém.',
    ],
    'Step-by-step guides for 1688, Taobao, Pinduoduo, Alipay, shipping and customs.' => [
        'Guías paso a paso para 1688, Taobao, Pinduoduo, Alipay, envíos y aduanas.',
        'Guides pas à pas pour 1688, Taobao, Pinduoduo, Alipay, l\'expédition et la douane.',
        '1688、淘宝、拼多多、支付宝、运输和清关的分步指南。',
        'Guias passo a passo para 1688, Taobao, Pinduoduo, Alipay, envios e alfândega.',
    ],
    '24/7 self-service' => ['Autoservicio 24/7', 'Libre-service 24/7', '全天候自助服务', 'Autosserviço 24/7'],
    'Track every deposit and funding order in real time from your dashboard.' => [
        'Sigue cada depósito y orden de recarga en tiempo real desde tu panel.',
        'Suivez chaque dépôt et ordre de recharge en temps réel depuis votre tableau de bord.',
        '在您的仪表板上实时跟踪每笔充值和代付订单。',
        'Acompanhe cada depósito e pedido de recarga em tempo real a partir do seu painel.',
    ],
    'Company' => ['Empresa', 'Entreprise', '公司', 'Empresa'],
    'Legal' => ['Legal', 'Mentions légales', '法律', 'Legal'],
    'Gift cards' => ['Tarjetas de regalo', 'Cartes-cadeaux', '礼品卡', 'Cartões-presente'],
    'eSIMs' => ['eSIMs', 'eSIMs', 'eSIM', 'eSIMs'],
    'Fees & rates' => ['Comisiones y tasas', 'Frais et taux', '费用与汇率', 'Taxas e câmbios'],
    'China academy' => ['Academia de China', 'Académie Chine', '中国学院', 'Academia da China'],
    'Refund policy' => ['Política de reembolso', 'Politique de remboursement', '退款政策', 'Política de reembolso'],
    'Gift Cards' => ['Tarjetas de regalo', 'Cartes-cadeaux', '礼品卡', 'Cartões-presente'],
    'eSIM' => ['eSIM', 'eSIM', 'eSIM', 'eSIM'],
    'Gaming' => ['Juegos', 'Jeux', '游戏', 'Jogos'],
    'Streaming' => ['Streaming', 'Streaming', '流媒体', 'Streaming'],
    'VPN & Proxy' => ['VPN y proxy', 'VPN et proxy', 'VPN 与代理', 'VPN e proxy'],
    'All products' => ['Todos los productos', 'Tous les produits', '所有产品', 'Todos os produtos'],
];

// 1) en.json (canonical): key => key
$en = json_decode(file_get_contents("$root/lang/en.json"), true);
foreach ($tr as $k => $_) { $en[$k] = $k; }
ksort($en, SORT_NATURAL | SORT_FLAG_CASE);
file_put_contents("$root/lang/en.json", json_encode($en, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

// 2) each catalog: only set our keys (never overwrite existing translations)
$idx = ['es' => 0, 'fr' => 1, 'zh' => 2, 'pt' => 3];
foreach ($langs as $l) {
    $j = json_decode(file_get_contents("$root/lang/$l.json"), true);
    foreach ($tr as $k => $vals) { $j[$k] = $vals[$idx[$l]]; }
    ksort($j, SORT_NATURAL | SORT_FLAG_CASE);
    file_put_contents("$root/lang/$l.json", json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo "$l: ".json_last_error_msg()." (".count($j).")\n";
}

// 3) record manual (dynamic-key) strings so en.json can be safely regenerated later
file_put_contents("$root/storage/i18n_manual_keys.json", json_encode(array_keys($tr), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "en: ".count($en)." keys; manual list saved\n";
