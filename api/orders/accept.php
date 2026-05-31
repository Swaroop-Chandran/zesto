<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireRole(ROLE_DELIVERY_PARTNER);
verifyCsrf();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.BASE_URL.'/delivery-panel/dashboard.php'); exit; }

$orderId = (int)($_POST['order_id'] ?? 0);
$userId  = getCurrentUser()['id'];

$stmt = db()->prepare("UPDATE orders SET delivery_partner_id=:uid, order_status='out_for_delivery' WHERE id=:oid AND order_status='preparing' AND delivery_partner_id IS NULL");
$stmt->execute([':uid' => $userId, ':oid' => $orderId]);

if ($stmt->rowCount() > 0) {
    // Increment deliveries counter
    db()->prepare("UPDATE delivery_partners SET total_deliveries=total_deliveries+1 WHERE user_id=:uid")->execute([':uid' => $userId]);
    setFlash('success', 'Order accepted! Head to the restaurant for pickup.');
} else {
    setFlash('error', 'Could not accept order. It may have been taken already.');
}

header('Location: '.BASE_URL.'/delivery-panel/deliveries.php'); exit;
