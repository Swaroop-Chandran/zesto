<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// GET: fetch order status
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $orderNum = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!$orderNum) { jsonResponse(['success' => false, 'message' => 'Order number required.'], 422); }

    $stmt = db()->prepare("SELECT order_status, updated_at FROM orders WHERE order_number = :onum LIMIT 1");
    $stmt->execute([':onum' => $orderNum]);
    $row = $stmt->fetch();

    if (!$row) { jsonResponse(['success' => false, 'message' => 'Order not found.'], 404); }
    jsonResponse(['success' => true, 'status' => $row['order_status'], 'updated_at' => $row['updated_at']]);
}

// POST: update order status (admin/restaurant/delivery panel)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    verifyCsrf();

    $data    = json_decode(file_get_contents('php://input'), true) ?? [];
    $orderId = (int)($data['order_id'] ?? 0);
    $status  = $data['status'] ?? '';

    $allowed = ['placed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
    if (!in_array($status, $allowed, true)) {
        jsonResponse(['success' => false, 'message' => 'Invalid status.'], 422);
    }

    // Restrict: delivery partners can only set out_for_delivery / delivered
    $currentRole = getCurrentUser()['role'];
    if ($currentRole === ROLE_DELIVERY_PARTNER && !in_array($status, ['out_for_delivery', 'delivered'], true)) {
        jsonResponse(['success' => false, 'message' => 'Insufficient permissions.'], 403);
    }

    $stmt = db()->prepare("UPDATE orders SET order_status = :status WHERE id = :id");
    $stmt->execute([':status' => $status, ':id' => $orderId]);

    jsonResponse(['success' => true, 'message' => 'Order status updated.', 'new_status' => $status]);
}

jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
