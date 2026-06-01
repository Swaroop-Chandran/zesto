<?php
/**
 * Zesto — User Model Class
 */

class User {
    public ?int $id = null;
    public string $name = '';
    public string $email = '';
    public string $role = '';
    public string $password = '';

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->name = $data['name'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->role = $data['role'] ?? 'customer';
        $this->password = $data['password'] ?? '';
    }

    /**
     * Fetch user by database primary ID.
     */
    public static function getById(int $id): ?self {
        $db = db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new self($row) : null;
    }

    /**
     * Fetch user by email address.
     */
    public static function getByEmail(string $email): ?self {
        $db = db();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new self($row) : null;
    }

    /**
     * Create a new user record.
     */
    public static function create(string $name, string $email, string $passwordHash, string $role): ?self {
        $db = db();
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, role)
            VALUES (:name, :email, :password, :role)
        ");
        $success = $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $passwordHash,
            ':role' => $role
        ]);

        if ($success) {
            $userId = (int)$db->lastInsertId();
            return self::getById($userId);
        }
        return null;
    }
}
