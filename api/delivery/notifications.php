<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

requireRole(ROLE_DELIVERY_PARTNER);

$userId = getCurrentUser()['id'];

try {
    $stmt = db()->prepare("
        SELECT dn.id, dn.message, dn.order_id, dn.created_at, 
               o.order_number, o.total AS order_value,
               r.name AS restaurant_name, r.address AS restaurant_address,
               da.distance_to_restaurant, da.total_distance, da.earnings
        FROM delivery_notifications dn
        JOIN orders o ON o.id = dn.order_id
        JOIN restaurants r ON r.id = o.restaurant_id
        LEFT JOIN delivery_assignments da ON da.order_id = o.id AND da.delivery_partner_id = dn.delivery_partner_id
        WHERE dn.delivery_partner_id = :pid AND dn.is_read = 0
        ORDER BY dn.created_at DESC
    ");
    $stmt->execute([':pid' => $userId]);
    $notifications = $stmt->fetchAll();

    jsonResponse(['success' => true, 'notifications' => $notifications, 'unread_count' => count($notifications)]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to retrieve notifications: ' . $e->getMessage()], 500);
}
