<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success' => false], 405); }
verifyCsrf();

$data    = json_decode(file_get_contents('php://input'), true) ?? [];
$cartKey = $data['cart_key'] ?? '';

if ($cartKey && isset($_SESSION['cart'][$cartKey])) {
    unset($_SESSION['cart'][$cartKey]);
}

jsonResponse(['success' => true, 'cart_count' => getCartCount()]);
