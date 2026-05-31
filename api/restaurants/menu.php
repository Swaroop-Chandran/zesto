<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$slug = trim(filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
if (empty($slug)) { jsonResponse(['success' => false, 'message' => 'Restaurant ID required.'], 422); }

$restStmt = db()->prepare("SELECT * FROM restaurants WHERE slug = :slug AND is_active = 1 LIMIT 1");
$restStmt->execute([':slug' => $slug]);
$restaurant = $restStmt->fetch();

if (!$restaurant) { jsonResponse(['success' => false, 'message' => 'Restaurant not found.'], 404); }

$menuStmt = db()->prepare("SELECT * FROM menu_items WHERE restaurant_id = :rid AND is_available = 1 ORDER BY display_order ASC, id ASC");
$menuStmt->execute([':rid' => $restaurant['id']]);
$menuItems = $menuStmt->fetchAll();

// Decode customization JSON
$menu = array_map(function($item) {
    $opts = null;
    if (!empty($item['customization_options'])) {
        $opts = json_decode($item['customization_options'], true);
    }
    return [
        'id'                   => $item['id'],
        'name'                 => $item['name'],
        'description'          => $item['description'],
        'price'                => $item['price'],
        'image'                => $item['image'],
        'customization_options'=> $opts ?? [],
    ];
}, $menuItems);

jsonResponse([
    'success'    => true,
    'restaurant' => [
        'id'            => $restaurant['id'],
        'slug'          => $restaurant['slug'],
        'name'          => $restaurant['name'],
        'tags'          => $restaurant['tags'],
        'rating'        => $restaurant['rating'],
        'delivery_time' => $restaurant['delivery_time'],
        'distance'      => $restaurant['distance'],
        'image'         => $restaurant['image'],
        'is_free_delivery' => (bool)$restaurant['is_free_delivery'],
    ],
    'menu' => $menu,
]);
