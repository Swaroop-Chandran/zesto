<?php
/**
 * Zesto — Admin Operational Dispute Resolution Controller
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/earnings_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

requireRole(ROLE_ADMIN);
verifyCsrf();

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$orderId = (int)($data['order_id'] ?? 0);
$action  = trim($data['action'] ?? ''); // release, refund

$allowedActions = ['release', 'refund'];
if (!in_array($action, $allowedActions, true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid resolution action.'], 422);
}

// Get order details
$stmt = db()->prepare("SELECT * FROM orders WHERE id = :oid LIMIT 1");
$stmt->execute([':oid' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
    jsonResponse(['success' => false, 'message' => 'Order not found.'], 404);
}

try {
    db()->beginTransaction();

    $asgStmt = db()->prepare("SELECT * FROM delivery_assignments WHERE order_id = :oid AND status = 'accepted' LIMIT 1");
    $asgStmt->execute([':oid' => $orderId]);
    $assignment = $asgStmt->fetch();

    if ($action === 'release') {
        // 1. Release Partner Payout (similar to normal confirmation)
        if ($assignment) {
            $pUserId = (int)$assignment['delivery_partner_id'];
            $calc = EarningsHelper::calculate((float)$assignment['total_distance']);

            // Insert into delivery_earnings
            $ernStmt = db()->prepare("
                INSERT INTO delivery_earnings (
                    delivery_partner_id, order_id, base_fare, distance_charge, 
                    peak_hour_bonus, rain_bonus, festival_bonus, total_earnings, distance_travelled
                ) VALUES (
                    :pid, :oid, :bf, :dc, :ph, :rb, :fb, :tot, :dist
                )
            ");
            $ernStmt->execute([
                ':pid' => $pUserId, ':oid' => $orderId, ':bf' => $calc['base_fare'], ':dc' => $calc['distance_charge'],
                ':ph' => $calc['peak_hour_bonus'], ':rb' => $calc['rain_bonus'], ':fb' => $calc['festival_bonus'],
                ':tot' => $calc['total_earnings'], ':dist' => $calc['distance_travelled']
            ]);

            // Update partner aggregates
            $updDp = db()->prepare("
                UPDATE delivery_partners 
                SET total_earnings = total_earnings + :earned, 
                    total_deliveries = total_deliveries + 1 
                WHERE user_id = :pid
            ");
            $updDp->execute([
                ':earned' => $calc['total_earnings'], 
                ':pid' => $pUserId
            ]);

            // Complete assignment status
            $updAsg = db()->prepare("UPDATE delivery_assignments SET status = 'completed', confirmed_at = CURRENT_TIMESTAMP WHERE id = :aid");
            $updAsg->execute([':aid' => $assignment['id']]);

            // Courier Notification
            $notifStmt = db()->prepare("
                INSERT INTO delivery_notifications (delivery_partner_id, order_id, message)
                VALUES (:pid, :oid, :msg)
            ");
            $notifStmt->execute([
                ':pid' => $pUserId,
                ':oid' => $orderId,
                ':msg' => "🎉 Admin resolved the dispute for Order {$order['order_number']}. Earnings released successfully!"
            ]);
        }

        // 2. Mark Order Completed
        $updOrd = db()->prepare("UPDATE orders SET order_status = 'completed' WHERE id = :oid");
        $updOrd->execute([':oid' => $orderId]);

        // 3. Audit Log
        $auditStmt = db()->prepare("
            INSERT INTO delivery_audit_logs (order_id, action_name, details)
            VALUES (:oid, 'dispute_released', 'Admin reviewed dispute and released partner earnings. Order status marked completed.')
        ");
        $auditStmt->execute([':oid' => $orderId]);

    } elseif ($action === 'refund') {
        // 1. Cancel Assignment & Penalize Partner Payout
        if ($assignment) {
            $pUserId = (int)$assignment['delivery_partner_id'];
            
            // Mark assignment cancelled / held
            $updAsg = db()->prepare("UPDATE delivery_assignments SET status = 'cancelled' WHERE id = :aid");
            $updAsg->execute([':aid' => $assignment['id']]);

            // Courier Notification
            $notifStmt = db()->prepare("
                INSERT INTO delivery_notifications (delivery_partner_id, order_id, message)
                VALUES (:pid, :oid, :msg)
            ");
            $notifStmt->execute([
                ':pid' => $pUserId,
                ':oid' => $orderId,
                ':msg' => "❌ Dispute resolved: Order {$order['order_number']} has been refunded. Delivery earnings cancelled."
            ]);
        }

        // 2. Mark Order Cancelled
        $updOrd = db()->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = :oid");
        $updOrd->execute([':oid' => $orderId]);

        // 3. Audit Log
        $auditStmt = db()->prepare("
            INSERT INTO delivery_audit_logs (order_id, action_name, details)
            VALUES (:oid, 'dispute_refunded', 'Admin reviewed dispute and issued customer refund. Order status cancelled, partner payout nullified.')
        ");
        $auditStmt->execute([':oid' => $orderId]);
    }

    db()->commit();
    jsonResponse(['success' => true, 'message' => "Dispute resolved successfully: {$action}"]);

} catch (Exception $e) {
    db()->rollBack();
    error_log('Admin dispute resolution failed: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Dispute resolution failed: ' . $e->getMessage()], 500);
}
