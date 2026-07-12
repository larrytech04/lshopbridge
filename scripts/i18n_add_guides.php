<?php
// Seeded guide + review content (DB strings rendered via __()) -> en.json + catalogs.
$root = dirname(__DIR__);
$idx = ['es' => 0, 'fr' => 1, 'zh' => 2, 'pt' => 3];

$tr = [
    'How to buy from 1688' => ['Cómo comprar en 1688', 'Comment acheter sur 1688', '如何在 1688 采购', 'Como comprar no 1688'],
    'How to buy from Taobao' => ['Cómo comprar en Taobao', 'Comment acheter sur Taobao', '如何在淘宝购物', 'Como comprar no Taobao'],
    'How to use Alipay as a foreigner' => ['Cómo usar Alipay como extranjero', 'Comment utiliser Alipay en tant qu\'étranger', '外国人如何使用支付宝', 'Como usar o Alipay como estrangeiro'],
    'Shipping goods to a warehouse' => ['Envío de mercancías a un almacén', 'Expédier des marchandises vers un entrepôt', '将货物运送到仓库', 'Enviar mercadorias para um armazém'],
    'Customs & delivery explained' => ['Aduanas y entrega explicadas', 'Douane et livraison expliquées', '海关与配送详解', 'Alfândega e entrega explicadas'],
    'Common mistakes to avoid' => ['Errores comunes que evitar', 'Erreurs courantes à éviter', '应避免的常见错误', 'Erros comuns a evitar'],
    'A complete beginner guide to sourcing wholesale from 1688.com.' => ['Una guía completa para principiantes sobre cómo comprar al por mayor en 1688.com.', 'Un guide complet pour débutants pour s\'approvisionner en gros sur 1688.com.', '面向新手的 1688.com 批发采购完整指南。', 'Um guia completo para iniciantes sobre como comprar por grosso no 1688.com.'],
    'Shop retail items from China the smart way.' => ['Compra productos al por menor de China de forma inteligente.', 'Achetez des articles au détail en Chine intelligemment.', '聪明地从中国购买零售商品。', 'Compre artigos a retalho da China de forma inteligente.'],
    'Set up and fund Alipay without a Chinese bank account.' => ['Configura y recarga Alipay sin una cuenta bancaria china.', 'Configurez et rechargez Alipay sans compte bancaire chinois.', '无需中国银行账户即可设置并充值支付宝。', 'Configure e carregue o Alipay sem uma conta bancária chinesa.'],
    'Consolidate orders and ship to Africa affordably.' => ['Consolida pedidos y envía a África a bajo costo.', 'Regroupez les commandes et expédiez vers l\'Afrique à moindre coût.', '合并订单并以实惠的价格运往非洲。', 'Consolide encomendas e envie para África a baixo custo.'],
    'Understand duties, clearance and last-mile delivery.' => ['Comprende aranceles, despacho y entrega de última milla.', 'Comprenez les droits, le dédouanement et la livraison du dernier kilomètre.', '了解关税、清关和最后一公里配送。', 'Compreenda direitos, desalfandegamento e entrega de última milha.'],
    'Avoid scams, overpaying and shipping delays.' => ['Evita estafas, pagar de más y retrasos en el envío.', 'Évitez les arnaques, les surcoûts et les retards d\'expédition.', '避免诈骗、多付款和运输延误。', 'Evite fraudes, pagar a mais e atrasos no envio.'],
    'Shipping' => ['Envío', 'Expédition', '运输', 'Envio'],
    'Customs' => ['Aduanas', 'Douane', '海关', 'Alfândega'],
    'Mistakes' => ['Errores', 'Erreurs', '错误', 'Erros'],
    ':n min read' => [':n min de lectura', ':n min de lecture', '阅读 :n 分钟', ':n min de leitura'],
    'Fast shipping and great communication!' => ['¡Envío rápido y excelente comunicación!', 'Expédition rapide et excellente communication !', '发货快，沟通很好！', 'Envio rápido e excelente comunicação!'],
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
$man = json_decode(@file_get_contents("$root/storage/i18n_manual_keys.json") ?: '[]', true);
file_put_contents("$root/storage/i18n_manual_keys.json", json_encode(array_values(array_unique(array_merge($man, array_keys($tr)))), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "en: ".count($en)." keys\n";
