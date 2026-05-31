<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$q = trim(filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
if (strlen($q) < 2) { jsonResponse(['success' => true, 'results' => []]); }

$stmt = db()->prepare("
    SELECT slug, name, tags, rating, image, delivery_time
    FROM restaurants
    WHERE is_active = 1 AND (name LIKE :q OR tags LIKE :q2)
    ORDER BY rating DESC LIMIT 8
");
$stmt->execute([':q' => "%$q%", ':q2' => "%$q%"]);
$rows = $stmt->fetchAll();

$results = array_map(fn($r) => [
    'slug'          => $r['slug'],
    'name'          => $r['name'],
    'tags'          => $r['tags'],
    'rating'        => $r['rating'],
    'image'         => $r['image'],
    'delivery_time' => $r['delivery_time'],
], $rows);

jsonResponse(['success' => true, 'results' => $results]);
