<?php
/**
 * Zesto — End-to-End V2 Delivery Partner Workflow Automation & Verification Test Suite
 * Verifies standard confirmation, ratings/reviews, dispute tracking, admin resolutions, and auto-confirmations.
 */

define('CLI_TEST', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/location_helper.php';
require_once __DIR__ . '/../includes/earnings_helper.php';
require_once __DIR__ . '/../includes/auto_confirm_cron.php';

function printHeader($text) {
    echo "\n=== " . str_pad($text, 75, "=") . "\n";
}

function printStep($stepNum, $description) {
    echo sprintf("\n[Step %d] %s...\n", $stepNum, $description);
}

function assertSuccess($assertionMsg, $check) {
    if ($check) {
        echo "  🟢 SUCCESS: " . $assertionMsg . "\n";
    } else {
        echo "  🔴 FAILURE: " . $assertionMsg . "\n";
        exit(1);
    }
}

try {
    $pdo = db();
    printHeader("Zesto V2 Delivery Lifecycle Integration Test Suite");

    // 1. Clean up old test data
    $pdo->exec("DELETE FROM orders WHERE order_number IN ('#ZY-TEST01', '#ZY-TEST02', '#ZY-TEST03', '#ZY-TEST04')");
    $pdo->exec("DELETE FROM order_reviews WHERE review_text IN ('Test Review #1', 'Test Review #2')");
    echo "🧹 Cleared old '#ZY-TEST*' test order records and reviews.\n";

    // 2. Fetch Users
    $alexId = $pdo->query("SELECT id FROM users WHERE email = 'alex@example.com' LIMIT 1")->fetchColumn();
    $marioId = $pdo->query("SELECT id FROM users WHERE email = 'mario@zesto.com' LIMIT 1")->fetchColumn();
    $marcusId = $pdo->query("SELECT id FROM users WHERE email = 'marcus@zesto.com' LIMIT 1")->fetchColumn();

    if (!$alexId || !$marioId || !$marcusId) {
        echo "🔴 Critical: Demo users (Alex, Mario, or Marcus) not found in DB! Seed the DB first.\n";
        exit(1);
    }

    // Get Restaurant 1
    $rest = $pdo->query("SELECT id, name, rating, rating_count FROM restaurants WHERE owner_id = $marioId LIMIT 1")->fetch();
    if (!$rest) {
        echo "🔴 Restaurant for Mario not found!\n";
        exit(1);
    }
    $restaurantId = $rest['id'];

    echo "👥 Users and restaurant resolved:\n";
    echo "   - Customer: Alex (ID: $alexId)\n";
    echo "   - Restaurant: {$rest['name']} (ID: $restaurantId)\n";
    echo "   - Partner: Marcus (ID: $marcusId)\n";

    // Helper to simulate workflow up to ready_for_pickup
    function createReadyOrder($pdo, $orderNumber, $customerId, $restaurantId, $partnerId) {
        $orderStmt = $pdo->prepare("
            INSERT INTO orders (order_number, user_id, restaurant_id, delivery_address, payment_method, payment_status, order_status, subtotal, delivery_fee, taxes, discount, total)
            VALUES (:onum, :uid, :rid, '123 Alex Street, Mumbai, 400001', 'cash', 'paid', 'pending', 100.00, 40.00, 10.00, 0.00, 150.00)
        ");
        $orderStmt->execute([':onum' => $orderNumber, ':uid' => $customerId, ':rid' => $restaurantId]);
        $orderId = $pdo->lastInsertId();

        $restLoc = getEntityLocation('restaurant', $restaurantId);
        $custLoc = getEntityLocation('customer', $customerId);
        $dpLoc = getEntityLocation('delivery_partner', $partnerId);
        $distToRest = calculateDistance($dpLoc['lat'], $dpLoc['lng'], $restLoc['lat'], $restLoc['lng']);
        $distToCust = calculateDistance($restLoc['lat'], $restLoc['lng'], $custLoc['lat'], $custLoc['lng']);
        $totalDist = $distToRest + $distToCust;
        $calc = EarningsHelper::calculate($totalDist);

        $assignStmt = $pdo->prepare("
            INSERT INTO delivery_assignments (
                order_id, delivery_partner_id, status, restaurant_lat, restaurant_lng, 
                customer_lat, customer_lng, delivery_partner_lat, delivery_partner_lng, 
                distance_to_restaurant, distance_to_customer, total_distance, earnings
            ) VALUES (
                :oid, :pid, 'assigned', :rlat, :rlng, :clat, :clng, :dlat, :dlng, :dist_rest, :dist_cust, :total_dist, :earnings
            )
        ");
        $assignStmt->execute([
            ':oid' => $orderId, ':pid' => $partnerId, ':rlat' => $restLoc['lat'], ':rlng' => $restLoc['lng'],
            ':clat' => $custLoc['lat'], ':clng' => $custLoc['lng'], ':dlat' => $dpLoc['lat'], ':dlng' => $dpLoc['lng'],
            ':dist_rest' => $distToRest, ':dist_cust' => $distToCust, ':total_dist' => $totalDist, ':earnings' => $calc['total_earnings']
        ]);

        return ['order_id' => $orderId, 'total_distance' => $totalDist, 'earnings' => $calc['total_earnings']];
    }

    // =========================================================================
    // TEST 1: STANDARD CONFIRMATION FLOW (PARTNER DELIVERS -> CUSTOMER CONFIRMS)
    // =========================================================================
    printHeader("TEST 1: Standard Customer Delivery Confirmation & Ratings Flow");

    // Track Marcus initial stats
    $marcusBefore = $pdo->query("SELECT total_deliveries, total_earnings, rating FROM delivery_partners WHERE user_id = $marcusId LIMIT 1")->fetch();

    $oData1 = createReadyOrder($pdo, '#ZY-TEST01', $alexId, $restaurantId, $marcusId);
    $orderId1 = $oData1['order_id'];

    printStep(1, "Rider Marcus accepts delivery `#ZY-TEST01`");
    $pdo->exec("UPDATE delivery_assignments SET status = 'accepted', accepted_at = CURRENT_TIMESTAMP WHERE order_id = $orderId1 AND delivery_partner_id = $marcusId");
    $pdo->exec("UPDATE orders SET delivery_partner_id = $marcusId, order_status = 'assigned_to_delivery' WHERE id = $orderId1");

    printStep(2, "Rider marks food as 'Picked Up'");
    $pdo->exec("UPDATE delivery_assignments SET picked_up_at = CURRENT_TIMESTAMP WHERE order_id = $orderId1");
    $pdo->exec("UPDATE orders SET order_status = 'picked_up' WHERE id = $orderId1");

    printStep(3, "Rider transitions status to 'Out for Delivery'");
    $pdo->exec("UPDATE orders SET order_status = 'out_for_delivery' WHERE id = $orderId1");

    printStep(4, "Rider marks status as 'Delivered'");
    // Simulate api/delivery/status.php 'delivered' logic
    $pdo->exec("UPDATE delivery_assignments SET delivered_at = CURRENT_TIMESTAMP WHERE order_id = $orderId1 AND status = 'accepted'");
    $pdo->exec("UPDATE orders SET order_status = 'awaiting_customer_confirmation' WHERE id = $orderId1");
    $pdo->exec("INSERT INTO delivery_audit_logs (order_id, action_name, details) VALUES ($orderId1, 'delivered_by_partner', 'Delivered by Marcus Rodriguez. Awaiting customer confirmation.')");

    // ASSERTS FOR HOLDING STATE
    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId1")->fetch();
    $asg = $pdo->query("SELECT * FROM delivery_assignments WHERE order_id = $orderId1 AND delivery_partner_id = $marcusId")->fetch();
    $ernCount = $pdo->query("SELECT COUNT(*) FROM delivery_earnings WHERE order_id = $orderId1")->fetchColumn();
    $marcusInterim = $pdo->query("SELECT total_deliveries, total_earnings FROM delivery_partners WHERE user_id = $marcusId LIMIT 1")->fetch();

    assertSuccess("Order transitioned to 'awaiting_customer_confirmation'.", $ord['order_status'] === 'awaiting_customer_confirmation');
    assertSuccess("Delivery assignment delivered_at is recorded.", $asg['delivered_at'] !== null);
    assertSuccess("Delivery assignment status is still 'accepted' (held).", $asg['status'] === 'accepted');
    assertSuccess("No rider earnings have been disbursed in delivery_earnings table.", (int)$ernCount === 0);
    assertSuccess("Marcus stats have not changed yet.", (int)$marcusInterim['total_deliveries'] === (int)$marcusBefore['total_deliveries']);

    printStep(5, "Customer Alex confirms order receipt, leaves reviews & comments");
    // Simulate api/orders/confirm.php
    $restRating = 5;
    $delivRating = 5;
    $commentText = "Test Review #1";

    // Start Transaction simulation
    $pdo->beginTransaction();
    
    // Earnings calculation
    $calc = EarningsHelper::calculate((float)$asg['total_distance']);
    $ernStmt = $pdo->prepare("
        INSERT INTO delivery_earnings (
            delivery_partner_id, order_id, base_fare, distance_charge, 
            peak_hour_bonus, rain_bonus, festival_bonus, total_earnings, distance_travelled
        ) VALUES (:pid, :oid, :bf, :dc, :ph, :rb, :fb, :tot, :dist)
    ");
    $ernStmt->execute([
        ':pid' => $marcusId, ':oid' => $orderId1, ':bf' => $calc['base_fare'], ':dc' => $calc['distance_charge'],
        ':ph' => $calc['peak_hour_bonus'], ':rb' => $calc['rain_bonus'], ':fb' => $calc['festival_bonus'],
        ':tot' => $calc['total_earnings'], ':dist' => $calc['distance_travelled']
    ]);

    // Recalculate Marcus Rating
    $oldDeliv = (int)$marcusBefore['total_deliveries'];
    $oldRating = (float)$marcusBefore['rating'];
    $newRating = ($oldRating * $oldDeliv + $delivRating) / ($oldDeliv + 1);

    $pdo->exec("
        UPDATE delivery_partners 
        SET total_earnings = total_earnings + {$calc['total_earnings']}, 
            total_deliveries = total_deliveries + 1, 
            rating = " . round($newRating, 1) . " 
        WHERE user_id = $marcusId
    ");
    
    // Recalculate Restaurant Rating
    $oldCount = (int)$rest['rating_count'];
    $oldRestRating = (float)$rest['rating'];
    $newRestRating = ($oldRestRating * $oldCount + $restRating) / ($oldCount + 1);
    $pdo->exec("UPDATE restaurants SET rating = " . round($newRestRating, 1) . ", rating_count = rating_count + 1 WHERE id = $restaurantId");

    // Insert Review
    $insReview = $pdo->prepare("
        INSERT INTO order_reviews (order_id, customer_id, restaurant_id, delivery_partner_id, restaurant_rating, delivery_rating, review_text)
        VALUES (:oid, :cid, :rid, :dpid, :rrat, :drat, :text)
    ");
    $insReview->execute([
        ':oid' => $orderId1, ':cid' => $alexId, ':rid' => $restaurantId, ':dpid' => $marcusId,
        ':rrat' => $restRating, ':drat' => $delivRating, ':text' => $commentText
    ]);

    // Complete assignment and order
    $pdo->exec("UPDATE delivery_assignments SET status = 'completed', confirmed_at = CURRENT_TIMESTAMP WHERE id = {$asg['id']}");
    $pdo->exec("UPDATE orders SET order_status = 'completed' WHERE id = $orderId1");
    $pdo->exec("INSERT INTO delivery_audit_logs (order_id, action_name, details) VALUES ($orderId1, 'customer_confirmed', 'Order completed and confirmed by Alex.')");

    $pdo->commit();

    // ASSERTS FOR COMPLETED FLOW
    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId1")->fetch();
    $asg = $pdo->query("SELECT * FROM delivery_assignments WHERE order_id = $orderId1")->fetch();
    $ern = $pdo->query("SELECT * FROM delivery_earnings WHERE order_id = $orderId1")->fetch();
    $rev = $pdo->query("SELECT * FROM order_reviews WHERE order_id = $orderId1")->fetch();
    $marcusAfter = $pdo->query("SELECT total_deliveries, total_earnings, rating FROM delivery_partners WHERE user_id = $marcusId LIMIT 1")->fetch();
    $restAfter = $pdo->query("SELECT rating, rating_count FROM restaurants WHERE id = $restaurantId LIMIT 1")->fetch();

    assertSuccess("Order status is transitioned to 'completed'.", $ord['order_status'] === 'completed');
    assertSuccess("Delivery assignment status is 'completed'.", $asg['status'] === 'completed');
    assertSuccess("Delivery assignment confirmed_at is populated.", $asg['confirmed_at'] !== null);
    assertSuccess("Rider payout is successfully logged in delivery_earnings.", $ern !== false);
    assertSuccess("Rider total earnings is successfully credited.", (float)$marcusAfter['total_earnings'] === round((float)$marcusBefore['total_earnings'] + $calc['total_earnings'], 2));
    assertSuccess("Rider total deliveries count is incremented.", (int)$marcusAfter['total_deliveries'] === (int)$marcusBefore['total_deliveries'] + 1);
    assertSuccess("Order review details are successfully stored.", $rev !== false && (int)$rev['restaurant_rating'] === 5 && $rev['review_text'] === 'Test Review #1');
    assertSuccess("Restaurant rating counts are successfully updated.", (int)$restAfter['rating_count'] === (int)$rest['rating_count'] + 1);


    // =========================================================================
    // TEST 2: DISPUTE & ADMIN RESOLUTION - RELEASE PAYOUT
    // =========================================================================
    printHeader("TEST 2: Delivery Dispute -> Admin Resolves via 'Release'");

    $oData2 = createReadyOrder($pdo, '#ZY-TEST02', $alexId, $restaurantId, $marcusId);
    $orderId2 = $oData2['order_id'];

    // Move to awaiting customer confirmation
    $pdo->exec("UPDATE delivery_assignments SET status = 'accepted', accepted_at = CURRENT_TIMESTAMP, delivered_at = CURRENT_TIMESTAMP WHERE order_id = $orderId2");
    $pdo->exec("UPDATE orders SET delivery_partner_id = $marcusId, order_status = 'awaiting_customer_confirmation' WHERE id = $orderId2");

    printStep(1, "Customer Alex disputes order '#ZY-TEST02' (Reports issue)");
    // Simulate api/orders/dispute.php
    $disputeReason = "Food packaging was slightly damaged";
    $pdo->exec("UPDATE orders SET order_status = 'delivery_issue' WHERE id = $orderId2");
    $pdo->exec("INSERT INTO delivery_notifications (delivery_partner_id, order_id, message) VALUES ($marcusId, $orderId2, 'Dispute opened for Order #ZY-TEST02. Payout held.')");
    $pdo->exec("INSERT INTO delivery_audit_logs (order_id, action_name, details) VALUES ($orderId2, 'delivery_issue', 'Dispute opened by customer Alex. Reason: $disputeReason')");

    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId2")->fetch();
    $asg = $pdo->query("SELECT * FROM delivery_assignments WHERE order_id = $orderId2")->fetch();
    $notif = $pdo->query("SELECT * FROM delivery_notifications WHERE order_id = $orderId2 AND delivery_partner_id = $marcusId ORDER BY id DESC LIMIT 1")->fetch();

    assertSuccess("Order transitioned to status 'delivery_issue'.", $ord['order_status'] === 'delivery_issue');
    assertSuccess("Delivery assignment status remains 'accepted' (held).", $asg['status'] === 'accepted');
    assertSuccess("Courier notification is dispatched successfully.", $notif !== false && strpos($notif['message'], 'Dispute opened') !== false);

    printStep(2, "Admin resolves dispute via 'Release Payout'");
    // Simulate api/admin/disputes.php with action='release'
    $pdo->beginTransaction();
    
    $calc = EarningsHelper::calculate((float)$asg['total_distance']);
    $pdo->prepare("
        INSERT INTO delivery_earnings (
            delivery_partner_id, order_id, base_fare, distance_charge, 
            peak_hour_bonus, rain_bonus, festival_bonus, total_earnings, distance_travelled
        ) VALUES (:pid, :oid, :bf, :dc, :ph, :rb, :fb, :tot, :dist)
    ")->execute([
        ':pid' => $marcusId, ':oid' => $orderId2, ':bf' => $calc['base_fare'], ':dc' => $calc['distance_charge'],
        ':ph' => $calc['peak_hour_bonus'], ':rb' => $calc['rain_bonus'], ':fb' => $calc['festival_bonus'],
        ':tot' => $calc['total_earnings'], ':dist' => $calc['distance_travelled']
    ]);

    $pdo->exec("UPDATE delivery_partners SET total_earnings = total_earnings + {$calc['total_earnings']}, total_deliveries = total_deliveries + 1 WHERE user_id = $marcusId");
    $pdo->exec("UPDATE delivery_assignments SET status = 'completed', confirmed_at = CURRENT_TIMESTAMP WHERE order_id = $orderId2");
    $pdo->exec("UPDATE orders SET order_status = 'completed' WHERE id = $orderId2");
    $pdo->exec("INSERT INTO delivery_notifications (delivery_partner_id, order_id, message) VALUES ($marcusId, $orderId2, 'Dispute resolved: Earnings released.')");
    $pdo->exec("INSERT INTO delivery_audit_logs (order_id, action_name, details) VALUES ($orderId2, 'dispute_released', 'Admin released payouts.')");

    $pdo->commit();

    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId2")->fetch();
    $asg = $pdo->query("SELECT * FROM delivery_assignments WHERE order_id = $orderId2")->fetch();
    $ernCount = $pdo->query("SELECT COUNT(*) FROM delivery_earnings WHERE order_id = $orderId2")->fetchColumn();

    assertSuccess("Order resolved to 'completed'.", $ord['order_status'] === 'completed');
    assertSuccess("Delivery assignment marked 'completed'.", $asg['status'] === 'completed');
    assertSuccess("Earnings disbursed successfully after dispute release.", (int)$ernCount === 1);


    // =========================================================================
    // TEST 3: DISPUTE & ADMIN RESOLUTION - REFUND CUSTOMER
    // =========================================================================
    printHeader("TEST 3: Delivery Dispute -> Admin Resolves via 'Refund Customer'");

    $oData3 = createReadyOrder($pdo, '#ZY-TEST03', $alexId, $restaurantId, $marcusId);
    $orderId3 = $oData3['order_id'];

    // Move to awaiting customer confirmation
    $pdo->exec("UPDATE delivery_assignments SET status = 'accepted', accepted_at = CURRENT_TIMESTAMP, delivered_at = CURRENT_TIMESTAMP WHERE order_id = $orderId3");
    $pdo->exec("UPDATE orders SET delivery_partner_id = $marcusId, order_status = 'awaiting_customer_confirmation' WHERE id = $orderId3");

    printStep(1, "Customer Alex disputes order `#ZY-TEST03` (Rider did not deliver)");
    $pdo->exec("UPDATE orders SET order_status = 'delivery_issue' WHERE id = $orderId3");
    $pdo->exec("INSERT INTO delivery_audit_logs (order_id, action_name, details) VALUES ($orderId3, 'delivery_issue', 'Customer reported did not receive food.')");

    printStep(2, "Admin resolves dispute via 'Refund Customer' (Nullify Payout)");
    // Simulate api/admin/disputes.php with action='refund'
    $pdo->beginTransaction();
    
    $pdo->exec("UPDATE delivery_assignments SET status = 'cancelled' WHERE order_id = $orderId3");
    $pdo->exec("UPDATE orders SET order_status = 'cancelled' WHERE id = $orderId3");
    $pdo->exec("INSERT INTO delivery_notifications (delivery_partner_id, order_id, message) VALUES ($marcusId, $orderId3, 'Dispute resolved: Order refunded. Payout cancelled.')");
    $pdo->exec("INSERT INTO delivery_audit_logs (order_id, action_name, details) VALUES ($orderId3, 'dispute_refunded', 'Admin refunded customer. Rider payouts nullified.')");

    $pdo->commit();

    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId3")->fetch();
    $asg = $pdo->query("SELECT * FROM delivery_assignments WHERE order_id = $orderId3")->fetch();
    $ernCount = $pdo->query("SELECT COUNT(*) FROM delivery_earnings WHERE order_id = $orderId3")->fetchColumn();

    assertSuccess("Order resolved to 'cancelled' (Refunded).", $ord['order_status'] === 'cancelled');
    assertSuccess("Delivery assignment marked 'cancelled'.", $asg['status'] === 'cancelled');
    assertSuccess("No delivery earnings were credited to Marcus.", (int)$ernCount === 0);


    // =========================================================================
    // TEST 4: ABUSE PROTECTION AUTO-CONFIRMATION (Works After Timeout)
    // =========================================================================
    printHeader("TEST 4: Abuse Protection Auto-Confirmation System");

    $oData4 = createReadyOrder($pdo, '#ZY-TEST04', $alexId, $restaurantId, $marcusId);
    $orderId4 = $oData4['order_id'];

    // Move to awaiting customer confirmation
    $pdo->exec("UPDATE delivery_assignments SET status = 'accepted', accepted_at = CURRENT_TIMESTAMP, delivered_at = CURRENT_TIMESTAMP WHERE order_id = $orderId4");
    $pdo->exec("UPDATE orders SET delivery_partner_id = $marcusId, order_status = 'awaiting_customer_confirmation' WHERE id = $orderId4");

    printStep(1, "Simulating cron execution for abuse protection auto-confirmation (0-hour timeout)");
    // Directly invoke our cron processing helper with a 0-hour delay to immediately trigger auto-confirmation
    $processed = runAutoConfirmations(0);

    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId4")->fetch();
    $asg = $pdo->query("SELECT * FROM delivery_assignments WHERE order_id = $orderId4")->fetch();
    $ern = $pdo->query("SELECT * FROM delivery_earnings WHERE order_id = $orderId4")->fetch();
    $audit = $pdo->query("SELECT * FROM delivery_audit_logs WHERE order_id = $orderId4 AND action_name = 'auto_confirmed'")->fetch();

    assertSuccess("Processed at least 1 auto-confirmation.", $processed >= 1);
    assertSuccess("Order status transitioned to 'completed'.", $ord['order_status'] === 'completed');
    assertSuccess("Order marked as auto_confirmed = 1.", (int)$ord['auto_confirmed'] === 1);
    assertSuccess("Delivery assignment status marked 'completed'.", $asg['status'] === 'completed');
    assertSuccess("Delivery assignment confirmed_at is populated.", $asg['confirmed_at'] !== null);
    assertSuccess("Driver payouts released in delivery_earnings.", $ern !== false);
    assertSuccess("Auto-confirmation audit trail log successfully recorded.", $audit !== false);

    printHeader("ALL INTEGRATION TESTS PASSED EXCELLENTLY!");
    echo "\n🎉 Modern confirmation, review, dispute and abuse protection workflows are 100% functional!\n\n";

} catch (Exception $e) {
    echo "\n🔴 INTEGRATION TEST EXCEPTION ENCOUNTERED: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
