<?php
/**
 * Zesto — Courier Delivery, Earnings and Dispatch Management Service
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Delivery.php';
require_once __DIR__ . '/../includes/earnings_helper.php';

class DeliveryService {

    /**
     * Accept a pending delivery assignment.
     */
    public static function acceptDelivery(int $orderId, int $partnerId): bool {
        $db = db();
        try {
            $db->beginTransaction();

            // Lock check
            $stmt = $db->prepare("SELECT delivery_partner_id, order_status FROM orders WHERE id = :oid LIMIT 1");
            $stmt->execute([':oid' => $orderId]);
            $order = $stmt->fetch();

            if (!$order || $order['order_status'] !== 'ready_for_pickup' || $order['delivery_partner_id'] !== null) {
                $db->rollBack();
                return false;
            }

            // Update assignment status
            $stmt = $db->prepare("
                UPDATE delivery_assignments 
                SET status = 'accepted', accepted_at = CURRENT_TIMESTAMP 
                WHERE order_id = :oid AND delivery_partner_id = :pid
            ");
            $stmt->execute([':oid' => $orderId, ':pid' => $partnerId]);

            // Lock order
            $stmt = $db->prepare("
                UPDATE orders 
                SET delivery_partner_id = :pid, order_status = 'assigned_to_delivery' 
                WHERE id = :oid
            ");
            $stmt->execute([':pid' => $partnerId, ':oid' => $orderId]);

            // Reject all other assignments
            $stmt = $db->prepare("
                UPDATE delivery_assignments 
                SET status = 'rejected' 
                WHERE order_id = :oid AND delivery_partner_id != :pid
            ");
            $stmt->execute([':oid' => $orderId, ':pid' => $partnerId]);

            // Mark notifications read
            $stmt = $db->prepare("UPDATE delivery_notifications SET is_read = 1 WHERE order_id = :oid");
            $stmt->execute([':oid' => $orderId]);

            $db->commit();
            return true;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("DeliveryService::acceptDelivery Error: " . $e->getMessage());
            return false;
        }
    }
}
