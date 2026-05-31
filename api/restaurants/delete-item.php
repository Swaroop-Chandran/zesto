<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success' => false], 405); }
requireRole(ROLE_RESTAURANT_OWNER);
verifyCsrf();

$data    = json_decode(file_get_contents('php://input'), true) ?? [];
$itemId  = (int)($data['item_id'] ?? $_POST['item_id'] ?? 0);
$ownerId = getCurrentUser()['id'];

$res = db()->prepare("SELECT id FROM restaurants WHERE owner_id=:oid LIMIT 1");
$res->execute([':oid' => $ownerId]);
$restaurant = $res->fetch();

if (!$restaurant) { jsonResponse(['success' => false, 'message' => 'No restaurant found.'], 404); }

$stmt = db()->prepare("DELETE FROM menu_items WHERE id=:id AND restaurant_id=:rid");
$stmt->execute([':id' => $itemId, ':rid' => $restaurant['id']]);

setFlash('success', 'Menu item deleted.');
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    jsonResponse(['success' => true]);
} else {
    header('Location: '.BASE_URL.'/restaurant-panel/menu.php'); exit;
}
