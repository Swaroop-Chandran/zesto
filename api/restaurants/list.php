<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$search       = trim(filter_input(INPUT_GET, 'search',       FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$category     = trim(filter_input(INPUT_GET, 'category',     FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$sort         = filter_input(INPUT_GET, 'sort',              FILTER_SANITIZE_SPECIAL_CHARS) ?? 'none';
$freeDelivery = filter_input(INPUT_GET, 'free_delivery',     FILTER_VALIDATE_BOOLEAN);

$sql    = "SELECT * FROM restaurants WHERE is_active = 1";
$params = [];

if ($search !== '') {
    $sql .= " AND (name LIKE :s OR tags LIKE :s2)";
    $params[':s'] = "%$search%"; $params[':s2'] = "%$search%";
}
if ($category !== '') {
    $sql .= " AND tags LIKE :cat";
    $params[':cat'] = "%$category%";
}
if ($freeDelivery) {
    $sql .= " AND is_free_delivery = 1";
}

$sorts = ['rating' => 'rating DESC', 'time' => 'delivery_time_value ASC', 'distance' => 'distance ASC', 'none' => 'id ASC'];
$sql .= ' ORDER BY ' . ($sorts[$sort] ?? 'id ASC');

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$results = array_map(fn($r) => [
    'id'             => $r['id'],
    'slug'           => $r['slug'],
    'name'           => $r['name'],
    'tags'           => $r['tags'],
    'rating'         => $r['rating'],
    'delivery_time'  => $r['delivery_time'],
    'distance'       => $r['distance'],
    'delivery_fee'   => $r['delivery_fee'],
    'is_free_delivery'=> (bool)$r['is_free_delivery'],
    'discount'       => $r['discount'],
    'image'          => $r['image'],
], $rows);

jsonResponse(['success' => true, 'count' => count($results), 'results' => $results]);
