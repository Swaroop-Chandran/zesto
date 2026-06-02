<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$q = trim(filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
if (strlen($q) < 2) { jsonResponse(['success' => true, 'results' => []]); }

// Search 1: Match restaurants by name/tags
$stmtR = db()->prepare("
    SELECT slug, name, tags, rating, image, delivery_time, 'restaurant' AS result_type, NULL AS item_name
    FROM restaurants
    WHERE is_active = 1 AND (name LIKE :q OR tags LIKE :q2)
    ORDER BY rating DESC LIMIT 6
");
$stmtR->execute([':q' => "%$q%", ':q2' => "%$q%"]);
$restaurantRows = $stmtR->fetchAll();

// Search 2: Match menu_items by name/description — return unique restaurants
$stmtM = db()->prepare("
    SELECT DISTINCT r.slug, r.name, r.tags, r.rating, r.image, r.delivery_time,
           'menu' AS result_type, mi.name AS item_name
    FROM menu_items mi
    JOIN restaurants r ON r.id = mi.restaurant_id
    WHERE mi.is_available = 1 AND r.is_active = 1
      AND (mi.name LIKE :q OR mi.description LIKE :q2)
    ORDER BY r.rating DESC LIMIT 6
");
$stmtM->execute([':q' => "%$q%", ':q2' => "%$q%"]);
$menuRows = $stmtM->fetchAll();

// Merge, deduplicating by slug (prefer restaurant direct match over menu match)
$seen    = [];
$results = [];

foreach ($restaurantRows as $r) {
    if (!isset($seen[$r['slug']])) {
        $seen[$r['slug']] = true;
        $results[] = [
            'slug'          => $r['slug'],
            'name'          => $r['name'],
            'tags'          => $r['tags'],
            'rating'        => $r['rating'],
            'image'         => $r['image'],
            'delivery_time' => $r['delivery_time'],
            'subtitle'      => $r['tags'],
            'result_type'   => 'restaurant',
        ];
    }
}

foreach ($menuRows as $r) {
    if (!isset($seen[$r['slug']])) {
        $seen[$r['slug']] = true;
        $results[] = [
            'slug'          => $r['slug'],
            'name'          => $r['name'],
            'tags'          => $r['tags'],
            'rating'        => $r['rating'],
            'image'         => $r['image'],
            'delivery_time' => $r['delivery_time'],
            'subtitle'      => 'Serves: ' . $r['item_name'],
            'result_type'   => 'menu',
        ];
    }
}

// Cap total at 8
$results = array_slice($results, 0, 8);

jsonResponse(['success' => true, 'results' => $results]);
