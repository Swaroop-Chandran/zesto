<?php
/**
 * Zesto — Abuse Protection Auto-Confirmation Background Processor (Cron Task)
 * Auto-confirms orders in 'awaiting_customer_confirmation' for more than 24 hours.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/earnings_helper.php';

function runAutoConfirmations($customIntervalHours = 24) {
    $pdo = db();
    $interval = (int)$customIntervalHours;

    // Fetch orders awaiting confirmation older than the interval
    $stmt = $pdo->prepare("
        SELECT id, order_number, restaurant_id, delivery_partner_id
        FROM orders
        WHERE order_status = 'awaiting_customer_confirmation'
          AND updated_at <= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL :hours HOUR)
    ");
    $stmt->execute([':hours' => $interval]);
    $expiredOrders = $stmt->fetchAll();

    $processedCount = 0;
    foreach ($expiredOrders as $order) {
        $orderId = (int)$order['id'];
        
        try {
            $pdo->beginTransaction();

            // 1. Release Partner Payout & Complete Assignment
            $asgStmt = $pdo->prepare("SELECT * FROM delivery_assignments WHERE order_id = :oid AND status = 'accepted' LIMIT 1");
            $asgStmt->execute([':oid' => $orderId]);
            $assignment = $asgStmt->fetch();

            if ($assignment) {
                $pUserId = (int)$assignment['delivery_partner_id'];
                $calc = EarningsHelper::calculate((float)$assignment['total_distance']);

                // Insert into delivery_earnings
                $ernStmt = $pdo->prepare("
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
                $updDp = $pdo->prepare("
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
                $updAsg = $pdo->prepare("UPDATE delivery_assignments SET status = 'completed', confirmed_at = CURRENT_TIMESTAMP WHERE id = :aid");
                $updAsg->execute([':aid' => $assignment['id']]);

                // Courier Notification
                $notifStmt = $pdo->prepare("
                    INSERT INTO delivery_notifications (delivery_partner_id, order_id, message)
                    VALUES (:pid, :oid, :msg)
                ");
                $notifStmt->execute([
                    ':pid' => $pUserId,
                    ':oid' => $orderId,
                    ':msg' => "⚡ Order {$order['order_number']} has been automatically completed by Zesto Abuse Protection after customer inactivity. Earnings credited!"
                ]);
            }

            // 2. Mark Order Completed and set auto_confirmed = 1
            $updOrd = $pdo->prepare("UPDATE orders SET order_status = 'completed', auto_confirmed = 1 WHERE id = :oid");
            $updOrd->execute([':oid' => $orderId]);

            // 3. Store Audit Logs
            $auditStmt = $pdo->prepare("
                INSERT INTO delivery_audit_logs (order_id, action_name, details)
                VALUES (:oid, 'auto_confirmed', :details)
            ");
            $auditMsg = "Abuse Protection Auto-Confirmation: Order automatically completed after {$interval}-hour inactivity timeout. Partner payouts released.";
            $auditStmt->execute([
                ':oid' => $orderId,
                ':details' => $auditMsg
            ]);

            $pdo->commit();
            $processedCount++;
            echo "✔ Order {$order['order_number']} automatically completed (Auto Confirmed).\n";

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Failed auto-confirming order {$order['order_number']}: " . $e->getMessage());
            echo "❌ Failed auto-confirming order {$order['order_number']}: " . $e->getMessage() . "\n";
        }
    }

    return $processedCount;
}

// Allow CLI execution
if (defined('CLI_TEST') || php_sapi_name() === 'cli') {
    if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
        echo "Running Auto-Confirmation Background Processor...\n";
        $count = runAutoConfirmations(24);
        echo "Auto-confirmations complete. Total orders processed: {$count}\n";
    }
}
