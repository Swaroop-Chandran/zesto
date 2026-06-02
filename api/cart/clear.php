<?php
/**
 * Zesto — Clear Cart (session + DB)
 * Called when user confirms "Clear Cart & Continue" from the conflict modal.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}
verifyCsrf();

// Clear session cart
$_SESSION['cart'] = [];

// If logged in, also clear the DB cart row for this user
if (isLoggedIn()) {
    $userId = getCurrentUser()['id'];
    $stmt = db()->prepare("DELETE FROM cart WHERE user_id = :uid");
    $stmt->execute([':uid' => $userId]);
}

jsonResponse(['success' => true, 'cart_count' => 0]);
