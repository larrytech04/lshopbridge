<?php
/**
 * Adds the UI-chrome strings introduced by the China Buying Academy
 * (Learning Center redesign) to en.json (canonical) + every supported
 * catalog. Follows the same pattern as i18n_add_dynamic.php.
 *
 * Note: this covers page chrome only (headings, labels, meta text). The
 * guide CONTENT itself — titles, steps, FAQs, seeded in GuideSeeder — is
 * database-driven, not __()-wrapped, and is English-only for now. See the
 * i18n_manual_keys.json note in i18n-system memory for why DB content
 * needs a different (not-yet-built) translation mechanism.
 */
$root = dirname(__DIR__);
$langs = ['es', 'fr', 'zh', 'pt'];

// English => [es, fr, zh, pt]
$tr = [
    'The China Buying Academy' => ['La Academia de Compras en China', "L'Académie des achats en Chine", '中国购物学院', 'A Academia de Compras na China'],
    'Free for every member' => ['Gratis para todos los miembros', 'Gratuit pour chaque membre', '所有会员免费', 'Grátis para todos os membros'],
    ':n guides' => [':n guías', ':n guides', ':n 篇指南', ':n guias'],
    '9 shopping platforms' => ['9 plataformas de compra', "9 plateformes d'achat", '9 个购物平台', '9 plataformas de compras'],
    'No prior experience needed' => ['No se necesita experiencia previa', 'Aucune expérience préalable requise', '无需任何经验', 'Não é necessária experiência prévia'],
    'Start here' => ['Empieza aquí', 'Commencez ici', '从这里开始', 'Comece aqui'],
    'Choose your platform' => ['Elige tu plataforma', 'Choisissez votre plateforme', '选择您的平台', 'Escolha a sua plataforma'],
    'Set up your wallet' => ['Configura tu billetera', 'Configurez votre portefeuille', '设置您的钱包', 'Configure a sua carteira'],
    'Shipping & customs' => ['Envíos y aduanas', 'Expédition et douane', '运输与清关', 'Envio e alfândega'],
    'Shop safely' => ['Compra con seguridad', 'Achetez en toute sécurité', '安全购物', 'Compre com segurança'],
    'Getting started' => ['Primeros pasos', 'Pour commencer', '新手入门', 'Primeiros passos'],
    'Customs & delivery' => ['Aduanas y entrega', 'Douane et livraison', '清关与配送', 'Alfândega e entrega'],
    'Mistakes to avoid' => ['Errores que debes evitar', 'Erreurs à éviter', '应避免的错误', 'Erros a evitar'],
    'Glossary' => ['Glosario', 'Glossaire', '术语表', 'Glossário'],
    'Tip:' => ['Consejo:', 'Astuce :', '小贴士：', 'Dica:'],
    ':n steps' => [':n pasos', ':n étapes', ':n 个步骤', ':n passos'],
    ':n reads' => [':n lecturas', ':n lectures', ':n 次阅读', ':n leituras'],
    'More in :category' => ['Más sobre :category', 'Plus sur :category', '更多 :category 相关内容', 'Mais sobre :category'],
    'On this page' => ['En esta página', 'Sur cette page', '本页内容', 'Nesta página'],
    'Overview' => ['Resumen', 'Aperçu', '概览', 'Resumo'],
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

echo "en: ".count($en)." keys\n";
