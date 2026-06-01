<?php
/**
 * Zesto — Report Delivery Dispute / Issue Endpoint
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

requireLogin();
verifyCsrf();

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$orderId = (int)($data['order_id'] ?? 0);
$reason = trim($data['dispute_reason'] ?? 'No specific details provided.');

$userId = getCurrentUser()['id'];

// Get order details
$stmt = db()->prepare("SELECT o.*, r.name AS restaurant_name FROM orders o JOIN restaurants r ON r.id = o.restaurant_id WHERE o.id = :oid AND o.user_id = :uid LIMIT 1");
$stmt->execute([':oid' => $orderId, ':uid' => $userId]);
$order = $stmt->fetch();

if (!$order) {
    jsonResponse(['success' => false, 'message' => 'Order not found.'], 404);
}

if ($order['order_status'] !== 'awaiting_customer_confirmation') {
    jsonResponse(['success' => false, 'message' => 'This order is not awaiting confirmation.'], 409);
}

try {
    db()->beginTransaction();

    // 1. Alert Courier (Insert Dispute Notification)
    if ($order['delivery_partner_id']) {
        $notifStmt = db()->prepare("
            INSERT INTO delivery_notifications (delivery_partner_id, order_id, message)
            VALUES (:pid, :oid, :msg)
        ");
        $notifStmt->execute([
            ':pid' => $order['delivery_partner_id'],
            ':oid' => $orderId,
            ':msg' => "⚠️ Dispute opened for Order {$order['order_number']}. Earnings held pending support review."
        ]);
    }

    // 2. Change Order Status to delivery_issue
    $updOrd = db()->prepare("UPDATE orders SET order_status = 'delivery_issue' WHERE id = :oid");
    $updOrd->execute([':oid' => $orderId]);

    // 3. Record Audit Log for Admin & Restaurant Notification
    $auditStmt = db()->prepare("
        INSERT INTO delivery_audit_logs (order_id, action_name, details)
        VALUES (:oid, 'delivery_issue', :details)
    ");
    $auditStmt->execute([
        ':oid' => $orderId,
        ':details' => "Customer Alex reported a delivery issue for Order {$order['order_number']}. Reason: {$reason}. Payout held for support verification."
    ]);

    db()->commit();
    jsonResponse(['success' => true, 'message' => 'Your issue has been reported. Rider earnings have been locked, and the support team is reviewing the order.', 'order_status' => 'delivery_issue']);

} catch (Exception $e) {
    db()->rollBack();
    error_log('Delivery dispute filing failed: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Dispute filing failed: ' . $e->getMessage()], 500);
}
