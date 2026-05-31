<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success' => false], 405); }
verifyCsrf();

$data       = json_decode(file_get_contents('php://input'), true) ?? [];
$cartKey    = $data['cart_key']    ?? '';
$menuItemId = (int)($data['menu_item_id'] ?? 0);

// Support both cart_key (explicit) and menu_item_id (derive key)
if (!$cartKey && $menuItemId) {
    // Find the cart key matching this item id
    foreach ($_SESSION['cart'] ?? [] as $k => $v) {
        if ((int)($v['menu_item_id'] ?? 0) === $menuItemId) {
            $cartKey = $k;
            break;
        }
    }
}

if ($cartKey && isset($_SESSION['cart'][$cartKey])) {
    unset($_SESSION['cart'][$cartKey]);
    // Also remove from DB cart if logged in
    if (isLoggedIn() && $menuItemId) {
        require_once __DIR__ . '/../../config/database.php';
        db()->prepare("DELETE FROM cart WHERE user_id=? AND menu_item_id=?")->execute([getCurrentUser()['id'], $menuItemId]);
    }
}

jsonResponse(['success' => true, 'cart_count' => getCartCount()]);
