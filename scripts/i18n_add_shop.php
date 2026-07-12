<?php
// Storefront restructure: categories, subcategories, taglines & UI chrome.
$root = dirname(__DIR__);
$idx = ['es' => 0, 'fr' => 1, 'zh' => 2, 'pt' => 3];
$tr = [
    // UI chrome
    'Digital Shop' => ['Tienda Digital', 'Boutique Numérique', '数字商店', 'Loja Digital'],
    'Categories' => ['Categorías', 'Catégories', '分类', 'Categorias'],
    'Subcategories' => ['Subcategorías', 'Sous-catégories', '子分类', 'Subcategorias'],
    'All categories' => ['Todas las categorías', 'Toutes les catégories', '全部分类', 'Todas as categorias'],
    'All' => ['Todos', 'Tous', '全部', 'Todos'],
    'Instant Delivery' => ['Entrega instantánea', 'Livraison instantanée', '即时交付', 'Entrega instantânea'],
    'Clear Refund Policy' => ['Política de reembolso clara', 'Politique de remboursement claire', '清晰的退款政策', 'Política de reembolso clara'],
    'Search all products — brands, eSIMs, countries' => [
        'Busca todos los productos — marcas, eSIM, países',
        'Rechercher tous les produits — marques, eSIM, pays',
        '搜索所有产品 — 品牌、eSIM、国家',
        'Pesquise todos os produtos — marcas, eSIMs, países',
    ],
    'Popularity' => ['Popularidad', 'Popularité', '热门', 'Popularidade'],
    'Range' => ['Rango', 'Fourchette', '区间', 'Intervalo'],
    'Price' => ['Precio', 'Prix', '价格', 'Preço'],
    'Gift cards, eSIMs, top-ups, bills, flights & stays — delivered instantly.' => [
        'Tarjetas de regalo, eSIM, recargas, facturas, vuelos y estancias — al instante.',
        'Cartes-cadeaux, eSIM, recharges, factures, vols et séjours — livrés instantanément.',
        '礼品卡、eSIM、充值、账单、机票和住宿 — 即时交付。',
        'Cartões-presente, eSIMs, recargas, contas, voos e estadias — entregues na hora.',
    ],

    // Top-level categories
    'Gift Cards' => ['Tarjetas de regalo', 'Cartes-cadeaux', '礼品卡', 'Cartões-presente'],
    'Mobile top up & data' => ['Recargas y datos móviles', 'Recharge mobile & data', '话费与流量充值', 'Recargas e dados móveis'],
    'eSIMs' => ['eSIMs', 'eSIM', 'eSIM', 'eSIMs'],
    'Bill payments' => ['Pago de facturas', 'Paiement de factures', '账单支付', 'Pagamento de contas'],
    'Flights' => ['Vuelos', 'Vols', '机票', 'Voos'],
    'Stays' => ['Estancias', 'Séjours', '住宿', 'Estadias'],

    // Top-level taglines
    'Amazon, Apple, Steam & more' => ['Amazon, Apple, Steam y más', 'Amazon, Apple, Steam et plus', 'Amazon、Apple、Steam 等', 'Amazon, Apple, Steam e mais'],
    'Airtime & data for any network' => ['Saldo y datos para cualquier red', 'Crédit & data pour tous les réseaux', '适用于任意网络的话费与流量', 'Crédito e dados para qualquer rede'],
    'Instant data in 190+ countries' => ['Datos al instante en más de 190 países', 'Data instantanée dans plus de 190 pays', '190+ 国家即时数据', 'Dados instantâneos em mais de 190 países'],
    'Pay utilities, TV & internet' => ['Paga servicios, TV e internet', 'Payez eau, TV et internet', '缴纳水电、电视和网络费', 'Pague serviços, TV e internet'],
    'Book flights worldwide' => ['Reserva vuelos en todo el mundo', 'Réservez des vols partout', '预订全球机票', 'Reserve voos no mundo todo'],
    'Hotels & stays, paid instantly' => ['Hoteles y estancias, pago al instante', 'Hôtels & séjours, payés instantanément', '酒店与住宿，即时支付', 'Hotéis e estadias, pagos na hora'],

    // Gift-card subcategories
    'Auto & Moto' => ['Auto y moto', 'Auto & Moto', '汽车与摩托', 'Auto e Moto'],
    'Clothing & Accessories' => ['Ropa y accesorios', 'Vêtements & accessoires', '服装与配饰', 'Roupas e acessórios'],
    'Dating' => ['Citas', 'Rencontres', '交友', 'Encontros'],
    'Digital Apps' => ['Apps digitales', 'Applications', '数字应用', 'Apps digitais'],
    'EGIFT' => ['EGIFT', 'EGIFT', '电子礼品', 'EGIFT'],
    'Electronics' => ['Electrónica', 'Électronique', '电子产品', 'Eletrónicos'],
    'Entertainment' => ['Entretenimiento', 'Divertissement', '娱乐', 'Entretenimento'],
    'Food & Drink' => ['Comida y bebida', 'Alimentation & boissons', '餐饮', 'Comida e bebida'],
    'Games' => ['Juegos', 'Jeux', '游戏', 'Jogos'],
    'Groceries' => ['Supermercado', 'Épicerie', '杂货', 'Mercearia'],
    'Health & Beauty' => ['Salud y belleza', 'Santé & beauté', '健康与美妆', 'Saúde e beleza'],
    'Home & Garden' => ['Hogar y jardín', 'Maison & jardin', '家居与园艺', 'Casa e jardim'],
    'Marketplace' => ['Marketplace', 'Marketplace', '购物平台', 'Marketplace'],
    'Restaurants' => ['Restaurantes', 'Restaurants', '餐厅', 'Restaurantes'],
    'Retail' => ['Comercio', 'Commerce', '零售', 'Varejo'],
    'Streaming' => ['Streaming', 'Streaming', '流媒体', 'Streaming'],
    'Travel' => ['Viajes', 'Voyage', '旅行', 'Viagens'],
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
