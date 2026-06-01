<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/location_helper.php';
require_once __DIR__ . '/../../includes/earnings_helper.php';

header('Content-Type: application/json');

// GET: fetch order status
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $orderNum = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!$orderNum) { jsonResponse(['success' => false, 'message' => 'Order number required.'], 422); }

    $stmt = db()->prepare("
        SELECT o.order_status, o.updated_at, o.delivery_partner_id, u.name AS partner_name
        FROM orders o
        LEFT JOIN users u ON u.id = o.delivery_partner_id
        WHERE o.order_number = :onum LIMIT 1
    ");
    $stmt->execute([':onum' => $orderNum]);
    $row = $stmt->fetch();

    if (!$row) { jsonResponse(['success' => false, 'message' => 'Order not found.'], 404); }
    jsonResponse([
        'success' => true, 
        'status' => $row['order_status'], 
        'updated_at' => $row['updated_at'],
        'delivery_partner_id' => $row['delivery_partner_id'],
        'partner_name' => $row['partner_name']
    ]);
}

// POST: update order status (admin/restaurant/delivery panel)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    verifyCsrf();

    $data    = json_decode(file_get_contents('php://input'), true) ?? [];
    $orderId = (int)($data['order_id'] ?? 0);
    $status  = $data['status'] ?? '';

    $allowed = [
        'pending', 'accepted', 'preparing', 'ready_for_pickup', 
        'assigned_to_delivery', 'picked_up', 'out_for_delivery', 
        'delivered', 'cancelled'
    ];
    if (!in_array($status, $allowed, true)) {
        jsonResponse(['success' => false, 'message' => 'Invalid status.'], 422);
    }

    // Check if order exists
    $ordStmt = db()->prepare("SELECT o.*, r.name AS restaurant_name FROM orders o JOIN restaurants r ON r.id = o.restaurant_id WHERE o.id = :oid LIMIT 1");
    $ordStmt->execute([':oid' => $orderId]);
    $order = $ordStmt->fetch();
    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'Order not found.'], 404);
    }

    // Role-based authorization check
    $currentRole = getCurrentUser()['role'];
    $userId = getCurrentUser()['id'];

    if ($currentRole === ROLE_DELIVERY_PARTNER) {
        $allowedDP = ['accepted', 'picked_up', 'out_for_delivery', 'delivered'];
        if (!in_array($status, $allowedDP, true)) {
            jsonResponse(['success' => false, 'message' => 'Insufficient permissions for delivery partner.'], 403);
        }
    }

    try {
        db()->beginTransaction();

        // ── STATUS SPECIAL ACTION: ready_for_pickup ────────────────────────
        if ($status === 'ready_for_pickup') {
            $restaurantId = $order['restaurant_id'];
            $customerId = $order['user_id'];
            
            // Get coordinates
            $restLoc = getEntityLocation('restaurant', $restaurantId);
            $custLoc = getEntityLocation('customer', $customerId ?: 0);
            
            // Clear existing notification/assignments for this order first
            db()->prepare("DELETE FROM delivery_assignments WHERE order_id = :oid")->execute([':oid' => $orderId]);
            db()->prepare("DELETE FROM delivery_notifications WHERE order_id = :oid")->execute([':oid' => $orderId]);
            
            // Search active delivery partners
            $partners = db()->query("
                SELECT dp.*, u.id AS partner_user_id, u.name AS partner_name
                FROM delivery_partners dp
                JOIN users u ON u.id = dp.user_id
                WHERE dp.is_approved = 1 AND dp.is_available = 1
            ")->fetchAll();
            
            $assignedCount = 0;
            foreach ($partners as $p) {
                $pUserId = (int)$p['partner_user_id'];
                $dpLoc = getEntityLocation('delivery_partner', $pUserId);
                
                // Calculate distances using Haversine helper
                $distToRest = calculateDistance($dpLoc['lat'], $dpLoc['lng'], $restLoc['lat'], $restLoc['lng']);
                $distToCust = calculateDistance($restLoc['lat'], $restLoc['lng'], $custLoc['lat'], $custLoc['lng']);
                $totalDist = $distToRest + $distToCust;
                
                // Calculate dynamic earnings
                $calc = EarningsHelper::calculate($totalDist);
                
                // If partner is within 10km, or fallback to all active partners if only few exist (e.g. Marcus)
                if ($distToRest <= 10.0 || count($partners) <= 2) {
                    // Create notification
                    $notifStmt = db()->prepare("
                        INSERT INTO delivery_notifications (delivery_partner_id, order_id, message)
                        VALUES (:pid, :oid, :msg)
                    ");
                    $msg = "New Order {$order['order_number']} ready for pickup at " . $order['restaurant_name'];
                    $notifStmt->execute([
                        ':pid' => $pUserId,
                        ':oid' => $orderId,
                        ':msg' => $msg
                    ]);
                    
                    // Create assignment
                    $assignStmt = db()->prepare("
                        INSERT INTO delivery_assignments (
                            order_id, delivery_partner_id, status, 
                            restaurant_lat, restaurant_lng, 
                            customer_lat, customer_lng, 
                            delivery_partner_lat, delivery_partner_lng, 
                            distance_to_restaurant, distance_to_customer, 
                            total_distance, earnings
                        ) VALUES (
                            :oid, :pid, 'assigned',
                            :rlat, :rlng,
                            :clat, :clng,
                            :dlat, :dlng,
                            :dist_rest, :dist_cust,
                            :total_dist, :earnings
                        )
                    ");
                    $assignStmt->execute([
                        ':oid' => $orderId,
                        ':pid' => $pUserId,
                        ':rlat' => $restLoc['lat'],
                        ':rlng' => $restLoc['lng'],
                        ':clat' => $custLoc['lat'],
                        ':clng' => $custLoc['lng'],
                        ':dlat' => $dpLoc['lat'],
                        ':dlng' => $dpLoc['lng'],
                        ':dist_rest' => $distToRest,
                        ':dist_cust' => $distToCust,
                        ':total_dist' => $totalDist,
                        ':earnings' => $calc['total_earnings']
                    ]);
                    $assignedCount++;
                }
            }
        }

        // ── STATUS SPECIAL ACTION: delivered ────────────────────────────────
        if ($status === 'delivered') {
            // Find active accepted assignment for this order
            $asgStmt = db()->prepare("
                SELECT * FROM delivery_assignments 
                WHERE order_id = :oid AND status = 'accepted' LIMIT 1
            ");
            $asgStmt->execute([':oid' => $orderId]);
            $assignment = $asgStmt->fetch();
            
            if ($assignment) {
                // Set assignment delivered_at timestamp, but status remains 'accepted' (earnings held)
                $updAsg = db()->prepare("
                    UPDATE delivery_assignments 
                    SET delivered_at = CURRENT_TIMESTAMP 
                    WHERE id = :aid
                ");
                $updAsg->execute([':aid' => $assignment['id']]);

                // Create audit log
                $auditStmt = db()->prepare("
                    INSERT INTO delivery_audit_logs (order_id, action_name, details) 
                    VALUES (:oid, 'delivered_by_partner', :det)
                ");
                $auditStmt->execute([
                    ':oid' => $orderId,
                    ':det' => "Order marked delivered. Awaiting customer confirmation."
                ]);
            }
            // Change actual status to the holding state
            $status = 'awaiting_customer_confirmation';
        }

        // ── UPDATE MAIN ORDER TABLE ────────────────────────────────────────
        $stmt = db()->prepare("UPDATE orders SET order_status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $orderId]);

        db()->commit();
        jsonResponse(['success' => true, 'message' => 'Order status updated successfully.', 'new_status' => $status]);

    } catch (Exception $e) {
        db()->rollBack();
        error_log('Order status update failed: ' . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to update order status: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
