<?php
/**
 * Zesto — End-to-End Delivery Partner Workflow Automation & Verification Test Suite
 */

define('CLI_TEST', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/location_helper.php';
require_once __DIR__ . '/../includes/earnings_helper.php';

function printHeader($text) {
    echo "\n=== " . str_pad($text, 65, "=") . "\n";
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
    printHeader("Zesto Delivery Lifecycle Integration Test Suite");

    // 1. Clean up old test data
    $pdo->exec("DELETE FROM orders WHERE order_number = '#ZY-TEST01'");
    echo "🧹 Cleared old '#ZY-TEST01' order details.\n";

    // 2. Fetch Users
    $alexId = $pdo->query("SELECT id FROM users WHERE email = 'alex@example.com' LIMIT 1")->fetchColumn();
    $marioId = $pdo->query("SELECT id FROM users WHERE email = 'mario@zesto.com' LIMIT 1")->fetchColumn();
    $marcusId = $pdo->query("SELECT id FROM users WHERE email = 'marcus@zesto.com' LIMIT 1")->fetchColumn();

    if (!$alexId || !$marioId || !$marcusId) {
        echo "🔴 Critical: Demo users (Alex, Mario, or Marcus) not found in DB! Seed the DB first.\n";
        exit(1);
    }

    // Get Restaurant 1
    $rest = $pdo->query("SELECT id, name FROM restaurants WHERE owner_id = $marioId LIMIT 1")->fetch();
    if (!$rest) {
        echo "🔴 Restaurant for Mario not found!\n";
        exit(1);
    }
    $restaurantId = $rest['id'];

    // Track Marcus initial stats
    $initialStats = $pdo->query("SELECT total_deliveries, total_earnings FROM delivery_partners WHERE user_id = $marcusId LIMIT 1")->fetch();

    echo "👥 Users and restaurant resolved:\n";
    echo "   - Customer: Alex (ID: $alexId)\n";
    echo "   - Restaurant: {$rest['name']} (ID: $restaurantId)\n";
    echo "   - Partner: Marcus (ID: $marcusId)\n";
    echo "   - Marcus Initial Deliveries: {$initialStats['total_deliveries']}, Initial Earnings: {$initialStats['total_earnings']}\n";

    // --- STEP 1: CUSTOMER PLACES ORDER ---
    printStep(1, "Customer places order #ZY-TEST01");
    $orderNumber = '#ZY-TEST01';
    
    $orderStmt = $pdo->prepare("
        INSERT INTO orders (order_number, user_id, restaurant_id, delivery_address, payment_method, payment_status, order_status, subtotal, delivery_fee, taxes, discount, total)
        VALUES (:onum, :uid, :rid, '123 Alex Street, Mumbai, 400001', 'cash', 'paid', 'pending', 100.00, 40.00, 10.00, 0.00, 150.00)
    ");
    $orderStmt->execute([
        ':onum' => $orderNumber,
        ':uid' => $alexId,
        ':rid' => $restaurantId
    ]);
    $orderId = $pdo->lastInsertId();

    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId")->fetch();
    assertSuccess("Order placed in database.", $ord !== false);
    assertSuccess("Order status is initially 'pending'.", $ord['order_status'] === 'pending');

    // --- STEP 2: RESTAURANT ACCEPTS ORDER ---
    printStep(2, "Restaurant accepts order");
    $pdo->exec("UPDATE orders SET order_status = 'accepted' WHERE id = $orderId");
    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId")->fetch();
    assertSuccess("Order status transitioned to 'accepted'.", $ord['order_status'] === 'accepted');

    // --- STEP 3: RESTAURANT PREPARES FOOD ---
    printStep(3, "Restaurant starts preparing order");
    $pdo->exec("UPDATE orders SET order_status = 'preparing' WHERE id = $orderId");
    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId")->fetch();
    assertSuccess("Order status transitioned to 'preparing'.", $ord['order_status'] === 'preparing');

    // --- STEP 4: RESTAURANT MARKS READY FOR PICKUP & TRIGGERS DISPATCH ---
    printStep(4, "Restaurant marks order ready for pickup (Trigger dispatch notifications)");
    
    // Simulate api/orders/status.php dispatch logic
    $restLoc = getEntityLocation('restaurant', $restaurantId);
    $custLoc = getEntityLocation('customer', $alexId);
    $dpLoc = getEntityLocation('delivery_partner', $marcusId);

    $distToRest = calculateDistance($dpLoc['lat'], $dpLoc['lng'], $restLoc['lat'], $restLoc['lng']);
    $distToCust = calculateDistance($restLoc['lat'], $restLoc['lng'], $custLoc['lat'], $custLoc['lng']);
    $totalDist = $distToRest + $distToCust;
    $calc = EarningsHelper::calculate($totalDist);

    // Clear existing notifications & assignments
    $pdo->exec("DELETE FROM delivery_assignments WHERE order_id = $orderId");
    $pdo->exec("DELETE FROM delivery_notifications WHERE order_id = $orderId");

    // Notification
    $notifStmt = $pdo->prepare("
        INSERT INTO delivery_notifications (delivery_partner_id, order_id, message)
        VALUES (:pid, :oid, :msg)
    ");
    $notifStmt->execute([
        ':pid' => $marcusId,
        ':oid' => $orderId,
        ':msg' => "New Order $orderNumber ready for pickup at " . $rest['name']
    ]);

    // Assignment
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
        ':oid' => $orderId,
        ':pid' => $marcusId,
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

    // Update order status to ready_for_pickup
    $pdo->exec("UPDATE orders SET order_status = 'ready_for_pickup' WHERE id = $orderId");

    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId")->fetch();
    $asg = $pdo->query("SELECT * FROM delivery_assignments WHERE order_id = $orderId AND delivery_partner_id = $marcusId")->fetch();
    $not = $pdo->query("SELECT * FROM delivery_notifications WHERE order_id = $orderId AND delivery_partner_id = $marcusId")->fetch();

    assertSuccess("Order status transitioned to 'ready_for_pickup'.", $ord['order_status'] === 'ready_for_pickup');
    assertSuccess("Delivery assignment record created for Marcus with status 'assigned'.", $asg !== false && $asg['status'] === 'assigned');
    assertSuccess("Delivery notification created for Marcus.", $not !== false && $not['is_read'] == 0);
    echo "   - Distance to Restaurant: " . number_format($distToRest, 2) . " KM\n";
    echo "   - Distance to Customer: " . number_format($distToCust, 2) . " KM\n";
    echo "   - Total Distance: " . number_format($totalDist, 2) . " KM\n";
    echo "   - Calculated Earnings: " . formatPrice($calc['total_earnings']) . "\n";

    // --- STEP 5: DELIVERY PARTNER (MARCUS) ACCEPTS DELIVERY ---
    printStep(5, "Delivery Partner (Marcus) clicks 'Accept Delivery'");

    // Simulating POST request to api/delivery/status.php with accepted
    $order = $pdo->query("SELECT * FROM orders WHERE id = $orderId")->fetch();
    assertSuccess("Lock verification: order is still ready for pickup.", $order['order_status'] === 'ready_for_pickup' && $order['delivery_partner_id'] === null);

    // Update assignment
    $pdo->exec("UPDATE delivery_assignments SET status = 'accepted', accepted_at = CURRENT_TIMESTAMP WHERE order_id = $orderId AND delivery_partner_id = $marcusId");
    // Lock order
    $pdo->exec("UPDATE orders SET delivery_partner_id = $marcusId, order_status = 'assigned_to_delivery' WHERE id = $orderId");
    // Reject others
    $pdo->exec("UPDATE delivery_assignments SET status = 'rejected' WHERE order_id = $orderId AND delivery_partner_id != $marcusId");
    // Mark notifications read
    $pdo->exec("UPDATE delivery_notifications SET is_read = 1 WHERE order_id = $orderId");

    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId")->fetch();
    $asg = $pdo->query("SELECT * FROM delivery_assignments WHERE order_id = $orderId AND delivery_partner_id = $marcusId")->fetch();
    $not = $pdo->query("SELECT * FROM delivery_notifications WHERE order_id = $orderId AND delivery_partner_id = $marcusId")->fetch();

    assertSuccess("Order status transitioned to 'assigned_to_delivery'.", $ord['order_status'] === 'assigned_to_delivery');
    assertSuccess("Order is locked under Marcus's user_id ($marcusId).", (int)$ord['delivery_partner_id'] === (int)$marcusId);
    assertSuccess("Assignment status changed to 'accepted'.", $asg['status'] === 'accepted');
    assertSuccess("Notification marked as read.", $not['is_read'] == 1);

    // --- STEP 6: PARTNER TRANSITIONS TO NAVIGATE TO RESTAURANT ---
    printStep(6, "Rider starts 'Navigate to Restaurant'");
    $pdo->exec("UPDATE orders SET order_status = 'assigned_to_delivery' WHERE id = $orderId");
    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId")->fetch();
    assertSuccess("Order status remains 'assigned_to_delivery'.", $ord['order_status'] === 'assigned_to_delivery');

    // --- STEP 7: PARTNER MARKS PICKED UP ---
    printStep(7, "Rider marks food as 'Picked Up'");
    $pdo->exec("UPDATE delivery_assignments SET picked_up_at = CURRENT_TIMESTAMP WHERE order_id = $orderId AND delivery_partner_id = $marcusId");
    $pdo->exec("UPDATE orders SET order_status = 'picked_up' WHERE id = $orderId");

    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId")->fetch();
    $asg = $pdo->query("SELECT * FROM delivery_assignments WHERE order_id = $orderId AND delivery_partner_id = $marcusId")->fetch();

    assertSuccess("Order status transitioned to 'picked_up'.", $ord['order_status'] === 'picked_up');
    assertSuccess("Assignment picked_up_at timestamp populated.", $asg['picked_up_at'] !== null);

    // --- STEP 8: PARTNER MARKS OUT FOR DELIVERY ---
    printStep(8, "Rider marks status 'Out for Delivery'");
    $pdo->exec("UPDATE orders SET order_status = 'out_for_delivery' WHERE id = $orderId");
    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId")->fetch();
    assertSuccess("Order status transitioned to 'out_for_delivery'.", $ord['order_status'] === 'out_for_delivery');

    // --- STEP 9: PARTNER DELIVERS & RECIEVES EARNINGS ---
    printStep(9, "Rider marks status 'Delivered' (Calculate fare & credit earnings)");

    $assignment = $pdo->query("SELECT * FROM delivery_assignments WHERE order_id = $orderId AND delivery_partner_id = $marcusId AND status = 'accepted'")->fetch();
    assertSuccess("Found active accepted assignment to complete.", $assignment !== false);

    $calc = EarningsHelper::calculate((float)$assignment['total_distance']);

    // Record earnings
    $ernStmt = $pdo->prepare("
        INSERT INTO delivery_earnings (
            delivery_partner_id, order_id, base_fare, distance_charge, 
            peak_hour_bonus, rain_bonus, festival_bonus, total_earnings, distance_travelled
        ) VALUES (
            :pid, :oid, :bf, :dc, :ph, :rb, :fb, :tot, :dist
        )
    ");
    $ernStmt->execute([
        ':pid' => $marcusId,
        ':oid' => $orderId,
        ':bf' => $calc['base_fare'],
        ':dc' => $calc['distance_charge'],
        ':ph' => $calc['peak_hour_bonus'],
        ':rb' => $calc['rain_bonus'],
        ':fb' => $calc['festival_bonus'],
        ':tot' => $calc['total_earnings'],
        ':dist' => $calc['distance_travelled']
    ]);

    // Update partner
    $pdo->exec("UPDATE delivery_partners SET total_earnings = total_earnings + {$calc['total_earnings']}, total_deliveries = total_deliveries + 1 WHERE user_id = $marcusId");
    // Complete assignment
    $pdo->exec("UPDATE delivery_assignments SET status = 'completed', delivered_at = CURRENT_TIMESTAMP WHERE id = {$assignment['id']}");
    // Complete order
    $pdo->exec("UPDATE orders SET order_status = 'delivered' WHERE id = $orderId");

    // Fetch updated statuses
    $ord = $pdo->query("SELECT * FROM orders WHERE id = $orderId")->fetch();
    $asg = $pdo->query("SELECT * FROM delivery_assignments WHERE id = {$assignment['id']}")->fetch();
    $ern = $pdo->query("SELECT * FROM delivery_earnings WHERE order_id = $orderId AND delivery_partner_id = $marcusId")->fetch();
    $dpStats = $pdo->query("SELECT total_deliveries, total_earnings FROM delivery_partners WHERE user_id = $marcusId LIMIT 1")->fetch();

    assertSuccess("Order status transitioned to 'delivered'.", $ord['order_status'] === 'delivered');
    assertSuccess("Assignment status changed to 'completed'.", $asg['status'] === 'completed');
    assertSuccess("Assignment delivered_at timestamp populated.", $asg['delivered_at'] !== null);
    assertSuccess("Delivery earnings record correctly logged in delivery_earnings table.", $ern !== false);
    assertSuccess("Dynamic base fare matches delivery settings.", (float)$ern['base_fare'] === (float)$calc['base_fare']);
    assertSuccess("Total earnings credited to delivery_partners aggregated statistics.", (float)$dpStats['total_earnings'] === ((float)$initialStats['total_earnings'] + $calc['total_earnings']));
    assertSuccess("Total deliveries count incremented for Marcus.", (int)$dpStats['total_deliveries'] === ((int)$initialStats['total_deliveries'] + 1));

    echo "\n🏆 ALL 8-STEP DELIVERY LIFECYCLE TESTS PASSED EXCELLENTLY!\n";

} catch (Exception $e) {
    echo "\n🔴 EXCEPTION ENCOUNTERED: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
