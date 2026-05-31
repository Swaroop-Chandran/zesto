<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405); }
verifyCsrf();

$data          = json_decode(file_get_contents('php://input'), true) ?? [];
$menuItemId    = (int)($data['menu_item_id']    ?? 0);
$restaurantId  = (int)($data['restaurant_id']   ?? 0);
$customization = trim($data['customization']    ?? '');
$quantity      = max(1, (int)($data['quantity'] ?? 1));

if (!$menuItemId || !$restaurantId) {
    jsonResponse(['success' => false, 'message' => 'Invalid item.'], 422);
}

// Fetch item from DB to validate and get price/name
$stmt = db()->prepare("
    SELECT mi.*, r.name AS restaurant_name, r.slug AS restaurant_slug
    FROM menu_items mi
    JOIN restaurants r ON r.id = mi.restaurant_id
    WHERE mi.id = :id AND mi.is_available = 1 LIMIT 1
");
$stmt->execute([':id' => $menuItemId]);
$item = $stmt->fetch();

if (!$item) {
    jsonResponse(['success' => false, 'message' => 'Item not available.'], 404);
}

// Check cross-restaurant conflict
if (!empty($_SESSION['cart'])) {
    $firstRestaurant = reset($_SESSION['cart'])['restaurant_id'] ?? null;
    if ($firstRestaurant && $firstRestaurant != $restaurantId) {
        jsonResponse([
            'success' => false,
            'message' => 'Your cart has items from another restaurant. Clear the cart first.',
            'conflict' => true,
        ], 409);
    }
}

// Build unique cart key
$cartKey = $menuItemId . '_' . md5($customization);

if (isset($_SESSION['cart'][$cartKey])) {
    $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
} else {
    $_SESSION['cart'][$cartKey] = [
        'menu_item_id'   => $menuItemId,
        'restaurant_id'  => $restaurantId,
        'restaurant_name'=> $item['restaurant_name'],
        'name'           => $item['name'],
        'price'          => (float)$item['price'],
        'image'          => $item['image'] ?? '',
        'customization'  => $customization,
        'quantity'       => $quantity,
    ];
}

// If user is logged in, sync to DB cart
if (isLoggedIn()) {
    $userId = getCurrentUser()['id'];
    $sync = db()->prepare("
        INSERT INTO cart (user_id, menu_item_id, restaurant_id, quantity, customization)
        VALUES (:uid, :mid, :rid, :qty, :cust)
        ON DUPLICATE KEY UPDATE quantity = quantity + :qty2
    ");
    $sync->execute([
        ':uid'  => $userId,
        ':mid'  => $menuItemId,
        ':rid'  => $restaurantId,
        ':qty'  => $quantity,
        ':cust' => $customization,
        ':qty2' => $quantity,
    ]);
}

jsonResponse([
    'success'    => true,
    'message'    => $item['name'] . ' added to cart!',
    'cart_count' => getCartCount(),
    'cart_total' => number_format(getCartSubtotal(), 2),
]);
