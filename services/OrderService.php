<?php
/**
 * Zesto — Order Placement and Status Management Service
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Order.php';

class OrderService {

    /**
     * Places a customer order from their session cart.
     */
    public static function placeOrder(
        int $customerId, int $restaurantId, string $address, 
        string $paymentMethod, float $subtotal, float $deliveryFee, 
        float $taxes, float $discount, float $total
    ): ?Order {
        $db = db();
        $orderNumber = '#ZY-' . strtoupper(bin2hex(random_bytes(3)));

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO orders (
                    order_number, user_id, restaurant_id, delivery_address, 
                    payment_method, payment_status, order_status, 
                    subtotal, delivery_fee, taxes, discount, total
                ) VALUES (
                    :onum, :uid, :rid, :addr, :pay_method, 'paid', 'pending',
                    :sub, :fee, :tax, :disc, :tot
                )
            ");
            $success = $stmt->execute([
                ':onum' => $orderNumber,
                ':uid' => $customerId,
                ':rid' => $restaurantId,
                ':addr' => $address,
                ':pay_method' => $paymentMethod,
                ':sub' => $subtotal,
                ':fee' => $deliveryFee,
                ':tax' => $taxes,
                ':disc' => $discount,
                ':tot' => $total
            ]);

            if (!$success) {
                throw new Exception("Order table insert failed.");
            }

            $orderId = (int)$db->lastInsertId();

            // Insert cart items into order_items
            $cart = $_SESSION['cart'] ?? [];
            $insItem = $db->prepare("
                INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, item_price)
                VALUES (:oid, :mid, :name, :qty, :price)
            ");

            foreach ($cart as $id => $item) {
                $insItem->execute([
                    ':oid' => $orderId,
                    ':mid' => (int)$id,
                    ':name' => $item['name'],
                    ':qty' => (int)$item['quantity'],
                    ':price' => (float)$item['price']
                ]);
            }

            // Empty the cart
            $_SESSION['cart'] = [];

            $db->commit();
            return Order::getById($orderId);

        } catch (Exception $e) {
            $db->rollBack();
            error_log("OrderService::placeOrder Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch order queue for a specific restaurant.
     */
    public static function getRestaurantOrdersQueue(int $restaurantId, int $limit = 10): array {
        $db = db();
        $stmt = $db->prepare("
            SELECT o.*, u.name AS customer 
            FROM orders o
            JOIN users u ON u.id = o.user_id
            WHERE o.restaurant_id = :rid
            ORDER BY o.created_at DESC LIMIT :limit
        ");
        $stmt->bindValue(':rid', $restaurantId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
