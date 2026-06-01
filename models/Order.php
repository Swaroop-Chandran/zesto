<?php
/**
 * Zesto — Order Model Class
 */

class Order {
    public ?int $id = null;
    public string $order_number = '';
    public int $user_id;
    public int $restaurant_id;
    public ?int $delivery_partner_id = null;
    public string $delivery_address = '';
    public string $payment_method = '';
    public string $payment_status = 'pending';
    public string $order_status = 'pending';
    public float $subtotal = 0.0;
    public float $delivery_fee = 0.0;
    public float $taxes = 0.0;
    public float $discount = 0.0;
    public float $total = 0.0;
    public int $auto_confirmed = 0;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->order_number = $data['order_number'] ?? '';
        $this->user_id = (int)($data['user_id'] ?? 0);
        $this->restaurant_id = (int)($data['restaurant_id'] ?? 0);
        $this->delivery_partner_id = isset($data['delivery_partner_id']) ? (int)$data['delivery_partner_id'] : null;
        $this->delivery_address = $data['delivery_address'] ?? '';
        $this->payment_method = $data['payment_method'] ?? '';
        $this->payment_status = $data['payment_status'] ?? 'pending';
        $this->order_status = $data['order_status'] ?? 'pending';
        $this->subtotal = (float)($data['subtotal'] ?? 0.0);
        $this->delivery_fee = (float)($data['delivery_fee'] ?? 0.0);
        $this->taxes = (float)($data['taxes'] ?? 0.0);
        $this->discount = (float)($data['discount'] ?? 0.0);
        $this->total = (float)($data['total'] ?? 0.0);
        $this->auto_confirmed = (int)($data['auto_confirmed'] ?? 0);
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? '';
    }

    /**
     * Fetch order by ID.
     */
    public static function getById(int $id): ?self {
        $db = db();
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new self($row) : null;
    }

    /**
     * Fetch order by Order Number.
     */
    public static function getByOrderNumber(string $onum): ?self {
        $db = db();
        $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = :onum LIMIT 1");
        $stmt->execute([':onum' => $onum]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new self($row) : null;
    }

    /**
     * Fetch the active untracked order for a customer.
     */
    public static function getActiveByCustomerId(int $customerId): ?self {
        $db = db();
        $stmt = $db->prepare("
            SELECT * FROM orders 
            WHERE user_id = :uid AND order_status NOT IN ('completed', 'cancelled')
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([':uid' => $customerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new self($row) : null;
    }
}
