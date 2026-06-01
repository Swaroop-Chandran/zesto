<?php
/**
 * Zesto — Review Model Class
 */

class Review {
    public ?int $id = null;
    public int $order_id;
    public int $customer_id;
    public int $restaurant_id;
    public ?int $delivery_partner_id = null;
    public int $restaurant_rating;
    public int $delivery_rating;
    public ?string $review_text = '';
    public string $created_at;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->order_id = (int)($data['order_id'] ?? 0);
        $this->customer_id = (int)($data['customer_id'] ?? 0);
        $this->restaurant_id = (int)($data['restaurant_id'] ?? 0);
        $this->delivery_partner_id = isset($data['delivery_partner_id']) ? (int)$data['delivery_partner_id'] : null;
        $this->restaurant_rating = (int)($data['restaurant_rating'] ?? 5);
        $this->delivery_rating = (int)($data['delivery_rating'] ?? 5);
        $this->review_text = $data['review_text'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
    }

    /**
     * Create a new order review entry.
     */
    public static function create(
        int $orderId, int $customerId, int $restaurantId, ?int $deliveryPartnerId,
        int $restaurantRating, int $deliveryRating, string $reviewText
    ): ?self {
        $db = db();
        $stmt = $db->prepare("
            INSERT INTO order_reviews (
                order_id, customer_id, restaurant_id, delivery_partner_id, 
                restaurant_rating, delivery_rating, review_text
            ) VALUES (
                :oid, :cid, :rid, :dpid, :rrat, :drat, :text
            )
        ");
        $success = $stmt->execute([
            ':oid' => $orderId,
            ':cid' => $customerId,
            ':rid' => $restaurantId,
            ':dpid' => $deliveryPartnerId,
            ':rrat' => $restaurantRating,
            ':drat' => $deliveryRating,
            ':text' => trim($reviewText)
        ]);

        if ($success) {
            $reviewId = (int)$db->lastInsertId();
            $stmt = $db->prepare("SELECT * FROM order_reviews WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $reviewId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return new self($row);
        }
        return null;
    }

    /**
     * Fetch reviews for a specific restaurant.
     */
    public static function getByRestaurantId(int $restaurantId, int $limit = 5): array {
        $db = db();
        $stmt = $db->prepare("
            SELECT * FROM order_reviews 
            WHERE restaurant_id = :rid 
            ORDER BY created_at DESC LIMIT :limit
        ");
        $stmt->bindValue(':rid', $restaurantId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new self($row), $rows);
    }
}
