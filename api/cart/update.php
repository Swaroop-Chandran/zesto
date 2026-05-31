<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405); }
verifyCsrf();

$data    = json_decode(file_get_contents('php://input'), true) ?? [];
$cartKey = $data['cart_key'] ?? '';
$delta   = (int)($data['delta'] ?? 0);

if (!$cartKey || !isset($_SESSION['cart'][$cartKey])) {
    jsonResponse(['success' => false, 'message' => 'Cart item not found.'], 404);
}

$newQty = $_SESSION['cart'][$cartKey]['quantity'] + $delta;

if ($newQty <= 0) {
    unset($_SESSION['cart'][$cartKey]);
} else {
    $_SESSION['cart'][$cartKey]['quantity'] = $newQty;
}

jsonResponse(['success' => true, 'cart_count' => getCartCount(), 'cart_total' => number_format(getCartSubtotal(), 2)]);
