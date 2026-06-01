<?php
/**
 * Zesto — Check if existing users can be verified with common passwords.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

try {
    $db = db();
    $stmt = $db->query("SELECT * FROM users WHERE id IN (18, 27, 28, 29)");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $candidates = ['Zesto@123', 'swaroop', 'password', 'SecretPassword123!', '12345678', 'achuboss80', 'apex', 'alex'];

    foreach ($users as $user) {
        echo "User ID: {$user['id']} | Email: {$user['email']} | Role: {$user['role']}\n";
        $matched = false;
        foreach ($candidates as $cand) {
            if (password_verify($cand, $user['password'])) {
                echo "  🟢 MATCH FOUND: password is '$cand'\n";
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            echo "  🔴 NO MATCH FOUND for common candidates.\n";
        }
    }

} catch (Exception $e) {
    echo "🔴 ERROR: " . $e->getMessage() . "\n";
}
