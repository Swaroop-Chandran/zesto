<?php
/**
 * Zesto — Restaurant Model Class
 */

class Restaurant {
    public ?int $id = null;
    public int $owner_id;
    public string $name = '';
    public string $slug = '';
    public string $address = '';
    public string $image = '';
    public float $rating = 4.0;
    public int $rating_count = 0;
    public int $is_active = 1;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->owner_id = (int)($data['owner_id'] ?? 0);
        $this->name = $data['name'] ?? '';
        $this->slug = $data['slug'] ?? '';
        $this->address = $data['address'] ?? '';
        $this->image = $data['image'] ?? '';
        $this->rating = (float)($data['rating'] ?? 4.0);
        $this->rating_count = (int)($data['rating_count'] ?? 0);
        $this->is_active = (int)($data['is_active'] ?? 1);
    }

    /**
     * Fetch restaurant by database primary ID.
     */
    public static function getById(int $id): ?self {
        $db = db();
        $stmt = $db->prepare("SELECT * FROM restaurants WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new self($row) : null;
    }

    /**
     * Fetch restaurant by Owner ID.
     */
    public static function getByOwnerId(int $ownerId): ?self {
        $db = db();
        $stmt = $db->prepare("SELECT * FROM restaurants WHERE owner_id = :oid LIMIT 1");
        $stmt->execute([':oid' => $ownerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new self($row) : null;
    }

    /**
     * Fetch all active restaurants.
     */
    public static function getAllActive(): array {
        $db = db();
        $stmt = $db->query("SELECT * FROM restaurants WHERE is_active = 1 ORDER BY rating DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new self($row), $rows);
    }
}
