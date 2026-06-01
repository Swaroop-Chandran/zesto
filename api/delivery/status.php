<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/location_helper.php';
require_once __DIR__ . '/../../includes/earnings_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

requireRole(ROLE_DELIVERY_PARTNER);
verifyCsrf();

$data    = json_decode(file_get_contents('php://input'), true) ?? [];
$orderId = (int)($data['order_id'] ?? 0);
$status  = $data['status'] ?? ''; // accepted, picked_up, out_for_delivery, delivered, rejected, assigned_to_delivery

$allowed = ['accepted', 'picked_up', 'out_for_delivery', 'delivered', 'rejected', 'assigned_to_delivery'];
if (!in_array($status, $allowed, true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid status transition.'], 422);
}

$userId = getCurrentUser()['id'];

// Get order details
$stmt = db()->prepare("SELECT * FROM orders WHERE id = :oid LIMIT 1");
$stmt->execute([':oid' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
    jsonResponse(['success' => false, 'message' => 'Order not found.'], 404);
}

try {
    db()->beginTransaction();

    if ($status === 'accepted') {
        // Lock check: check if someone else accepted it first
        if ($order['delivery_partner_id'] !== null && (int)$order['delivery_partner_id'] !== $userId) {
            jsonResponse(['success' => false, 'message' => 'This delivery has already been claimed by another partner.'], 409);
        }

        // Verify order is actually in ready_for_pickup state (not already picked_up/delivered/etc.)
        if ($order['order_status'] !== 'ready_for_pickup') {
            jsonResponse(['success' => false, 'message' => 'This order is no longer ready for pickup.'], 409);
        }

        // Update assignment
        $updAsg = db()->prepare("
            UPDATE delivery_assignments 
            SET status = 'accepted', accepted_at = CURRENT_TIMESTAMP 
            WHERE order_id = :oid AND delivery_partner_id = :pid
        ");
        $updAsg->execute([':oid' => $orderId, ':pid' => $userId]);

        if ($updAsg->rowCount() === 0) {
            // No assignment found, create one just in case
            $restLoc = getEntityLocation('restaurant', (int)$order['restaurant_id']);
            $custLoc = getEntityLocation('customer', (int)($order['user_id'] ?: 0));
            $dpLoc = getEntityLocation('delivery_partner', $userId);
            $distToRest = calculateDistance($dpLoc['lat'], $dpLoc['lng'], $restLoc['lat'], $restLoc['lng']);
            $distToCust = calculateDistance($restLoc['lat'], $restLoc['lng'], $custLoc['lat'], $custLoc['lng']);
            $totalDist = $distToRest + $distToCust;
            $calc = EarningsHelper::calculate($totalDist);

            $insAsg = db()->prepare("
                INSERT INTO delivery_assignments (
                    order_id, delivery_partner_id, status, restaurant_lat, restaurant_lng, 
                    customer_lat, customer_lng, delivery_partner_lat, delivery_partner_lng, 
                    distance_to_restaurant, distance_to_customer, total_distance, earnings, accepted_at
                ) VALUES (
                    :oid, :pid, 'accepted', :rlat, :rlng, :clat, :clng, :dlat, :dlng, :dist_rest, :dist_cust, :total_dist, :earnings, CURRENT_TIMESTAMP
                )
            ");
            $insAsg->execute([
                ':oid' => $orderId, ':pid' => $userId, ':rlat' => $restLoc['lat'], ':rlng' => $restLoc['lng'],
                ':clat' => $custLoc['lat'], ':clng' => $custLoc['lng'], ':dlat' => $dpLoc['lat'], ':dlng' => $dpLoc['lng'],
                ':dist_rest' => $distToRest, ':dist_cust' => $distToCust, ':total_dist' => $totalDist, ':earnings' => $calc['total_earnings']
            ]);
        }

        // Lock order for this delivery partner
        $updOrd = db()->prepare("UPDATE orders SET delivery_partner_id = :pid, order_status = 'assigned_to_delivery' WHERE id = :oid");
        $updOrd->execute([':pid' => $userId, ':oid' => $orderId]);

        // Cancel other assignments for this order
        $cancelOthers = db()->prepare("UPDATE delivery_assignments SET status = 'rejected' WHERE order_id = :oid AND delivery_partner_id != :pid");
        $cancelOthers->execute([':oid' => $orderId, ':pid' => $userId]);

        // Mark notifications read
        $readNotif = db()->prepare("UPDATE delivery_notifications SET is_read = 1 WHERE order_id = :oid");
        $readNotif->execute([':oid' => $orderId]);

    } elseif ($status === 'assigned_to_delivery') {
        // Partner starts navigating to restaurant
        $updOrd = db()->prepare("UPDATE orders SET order_status = 'assigned_to_delivery' WHERE id = :oid");
        $updOrd->execute([':oid' => $orderId]);

    } elseif ($status === 'rejected') {
        // Mark assignment as rejected
        $updAsg = db()->prepare("UPDATE delivery_assignments SET status = 'rejected' WHERE order_id = :oid AND delivery_partner_id = :pid");
        $updAsg->execute([':oid' => $orderId, ':pid' => $userId]);

    } elseif ($status === 'picked_up') {
        // Transition to picked_up
        $updAsg = db()->prepare("UPDATE delivery_assignments SET picked_up_at = CURRENT_TIMESTAMP WHERE order_id = :oid AND delivery_partner_id = :pid");
        $updAsg->execute([':oid' => $orderId, ':pid' => $userId]);

        $updOrd = db()->prepare("UPDATE orders SET order_status = 'picked_up' WHERE id = :oid");
        $updOrd->execute([':oid' => $orderId]);

    } elseif ($status === 'out_for_delivery') {
        // Transition to out_for_delivery
        $updOrd = db()->prepare("UPDATE orders SET order_status = 'out_for_delivery' WHERE id = :oid");
        $updOrd->execute([':oid' => $orderId]);

    } elseif ($status === 'delivered') {
        // When rider marks 'Delivered':
        // 1. Move order to awaiting_customer_confirmation
        // 2. Set assignment delivered_at timestamp, but status remains 'accepted' (earnings held)
        
        $asgStmt = db()->prepare("SELECT * FROM delivery_assignments WHERE order_id = :oid AND delivery_partner_id = :pid AND status = 'accepted' LIMIT 1");
        $asgStmt->execute([':oid' => $orderId, ':pid' => $userId]);
        $assignment = $asgStmt->fetch();

        if ($assignment) {
            $updAsg = db()->prepare("UPDATE delivery_assignments SET delivered_at = CURRENT_TIMESTAMP WHERE id = :aid");
            $updAsg->execute([':aid' => $assignment['id']]);

            // Create audit log
            $auditStmt = db()->prepare("INSERT INTO delivery_audit_logs (order_id, action_name, details) VALUES (:oid, 'delivered_by_partner', :det)");
            $auditStmt->execute([
                ':oid' => $orderId,
                ':det' => "Order marked delivered by partner ID {$userId}. Awaiting customer confirmation."
            ]);
        }

        $updOrd = db()->prepare("UPDATE orders SET order_status = 'awaiting_customer_confirmation' WHERE id = :oid");
        $updOrd->execute([':oid' => $orderId]);
    }

    db()->commit();
    jsonResponse(['success' => true, 'message' => "Order transitioned to {$status} successfully.", 'new_status' => $status]);

} catch (Exception $e) {
    db()->rollBack();
    error_log('Delivery status transition failure: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Transition failed: ' . $e->getMessage()], 500);
}
