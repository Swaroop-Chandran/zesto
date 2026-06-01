<?php
/**
 * Zesto — Delivery Assignment Model Class
 */

class Delivery {
    public ?int $id = null;
    public int $order_id;
    public int $delivery_partner_id;
    public string $status = 'assigned';
    public ?string $accepted_at = null;
    public ?string $picked_up_at = null;
    public ?string $delivered_at = null;
    public ?string $confirmed_at = null;
    public float $distance_to_restaurant = 0.0;
    public float $distance_to_customer = 0.0;
    public float $total_distance = 0.0;
    public float $earnings = 0.0;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->order_id = (int)($data['order_id'] ?? 0);
        $this->delivery_partner_id = (int)($data['delivery_partner_id'] ?? 0);
        $this->status = $data['status'] ?? 'assigned';
        $this->accepted_at = $data['accepted_at'] ?? null;
        $this->picked_up_at = $data['picked_up_at'] ?? null;
        $this->delivered_at = $data['delivered_at'] ?? null;
        $this->confirmed_at = $data['confirmed_at'] ?? null;
        $this->distance_to_restaurant = (float)($data['distance_to_restaurant'] ?? 0.0);
        $this->distance_to_customer = (float)($data['distance_to_customer'] ?? 0.0);
        $this->total_distance = (float)($data['total_distance'] ?? 0.0);
        $this->earnings = (float)($data['earnings'] ?? 0.0);
    }

    /**
     * Fetch delivery assignment by Order ID.
     */
    public static function getByOrderId(int $orderId): ?self {
        $db = db();
        $stmt = $db->prepare("SELECT * FROM delivery_assignments WHERE order_id = :oid LIMIT 1");
        $stmt->execute([':oid' => $orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new self($row) : null;
    }

    /**
     * Fetch the active accepted assignment for a partner.
     */
    public static function getActiveByPartnerId(int $partnerId): ?self {
        $db = db();
        $stmt = $db->prepare("
            SELECT da.* 
            FROM delivery_assignments da
            JOIN orders o ON o.id = da.order_id
            WHERE da.delivery_partner_id = :pid 
              AND da.status = 'accepted' 
              AND o.order_status NOT IN ('completed', 'cancelled')
            LIMIT 1
        ");
        $stmt->execute([':pid' => $partnerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new self($row) : null;
    }
}
