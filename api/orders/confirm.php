<?php
/**
 * Zesto — Confirm Delivery and Record Ratings & Reviews Endpoint
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/earnings_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

requireLogin();
verifyCsrf();

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$orderId = (int)($data['order_id'] ?? 0);
$restRating = (int)($data['restaurant_rating'] ?? 5);
$delivRating = (int)($data['delivery_rating'] ?? 5);
$comment = trim($data['review_comment'] ?? '');

$userId = getCurrentUser()['id'];

// Get order details
$stmt = db()->prepare("SELECT * FROM orders WHERE id = :oid AND user_id = :uid LIMIT 1");
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

    // 1. Release Partner Earnings & Update Assignment
    $asgStmt = db()->prepare("SELECT * FROM delivery_assignments WHERE order_id = :oid AND status = 'accepted' LIMIT 1");
    $asgStmt->execute([':oid' => $orderId]);
    $assignment = $asgStmt->fetch();

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

        // Get current courier details for rating recalculation
        $dpStmt = db()->prepare("SELECT * FROM delivery_partners WHERE user_id = :pid LIMIT 1");
        $dpStmt->execute([':pid' => $pUserId]);
        $dp = $dpStmt->fetch();

        if ($dp) {
            $oldDeliv = (int)$dp['total_deliveries'];
            $oldRating = (float)$dp['rating'];
            // Weighted average recalculation
            $newDelivRating = ($oldRating * $oldDeliv + $delivRating) / ($oldDeliv + 1);

            // Update delivery partner profile stats
            $updDp = db()->prepare("
                UPDATE delivery_partners 
                SET total_earnings = total_earnings + :earned, 
                    total_deliveries = total_deliveries + 1, 
                    rating = :rating 
                WHERE user_id = :pid
            ");
            $updDp->execute([
                ':earned' => $calc['total_earnings'], 
                ':rating' => round($newDelivRating, 1), 
                ':pid' => $pUserId
            ]);
        }

        // Complete assignment status and mark confirmed_at timestamp
        $updAsg = db()->prepare("UPDATE delivery_assignments SET status = 'completed', confirmed_at = CURRENT_TIMESTAMP WHERE id = :aid");
        $updAsg->execute([':aid' => $assignment['id']]);
    }

    // 2. Update Restaurant Ratings
    $restStmt = db()->prepare("SELECT rating, rating_count FROM restaurants WHERE id = :rid LIMIT 1");
    $restStmt->execute([':rid' => $order['restaurant_id']]);
    $restaurant = $restStmt->fetch();

    if ($restaurant) {
        $oldCount = (int)$restaurant['rating_count'];
        $oldRating = (float)$restaurant['rating'];
        $newRestRating = ($oldRating * $oldCount + $restRating) / ($oldCount + 1);

        $updRest = db()->prepare("UPDATE restaurants SET rating = :rating, rating_count = rating_count + 1 WHERE id = :rid");
        $updRest->execute([
            ':rating' => round($newRestRating, 1), 
            ':rid' => $order['restaurant_id']
        ]);
    }

    // 3. Record Reviews & Feedback
    $insReview = db()->prepare("
        INSERT INTO order_reviews (order_id, customer_id, restaurant_id, delivery_partner_id, restaurant_rating, delivery_rating, review_text)
        VALUES (:oid, :cid, :rid, :dpid, :rrat, :drat, :text)
    ");
    $insReview->execute([
        ':oid' => $orderId,
        ':cid' => $userId,
        ':rid' => $order['restaurant_id'],
        ':dpid' => $order['delivery_partner_id'],
        ':rrat' => $restRating,
        ':drat' => $delivRating,
        ':text' => $comment
    ]);

    // 4. Update order status to completed
    $updOrd = db()->prepare("UPDATE orders SET order_status = 'completed' WHERE id = :oid");
    $updOrd->execute([':oid' => $orderId]);

    // 5. Store audit log
    $auditStmt = db()->prepare("INSERT INTO delivery_audit_logs (order_id, action_name, details) VALUES (:oid, 'customer_confirmed', 'Order completed and confirmed by customer Alex. Earnings disbursed and reviews stored successfully.')");
    $auditStmt->execute([':oid' => $orderId]);

    db()->commit();
    jsonResponse(['success' => true, 'message' => 'Order completed successfully! Thank you for your feedback.', 'order_status' => 'completed']);

} catch (Exception $e) {
    db()->rollBack();
    error_log('Customer delivery confirmation failed: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Completion failed: ' . $e->getMessage()], 500);
}
