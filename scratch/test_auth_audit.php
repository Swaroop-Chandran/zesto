<?php
/**
 * Zesto — Authentication & Password Hashing Audit Script
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

try {
    $db = db();
    echo "=== DB CONNECTION SUCCESS ===\n";

    // 1. Let's select all users in the database to see what they look like
    $stmt = $db->query("SELECT id, name, email, password, role, is_active FROM users ORDER BY id DESC LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\n--- Recent users in Database ---\n";
    foreach ($users as $user) {
        printf("ID: %d | Name: %s | Email: %s | Role: %s | Active: %d | Hash: %s\n",
            $user['id'], $user['name'], $user['email'], $user['role'], $user['is_active'], substr($user['password'], 0, 20) . '...'
        );
    }

    // 2. Let's simulate a registration and verify the password hash
    echo "\n--- Hashing Test ---\n";
    $plainText = 'Zesto@123';
    $bcryptHash = password_hash($plainText, PASSWORD_BCRYPT, ['cost' => 12]);
    $defaultHash = password_hash($plainText, PASSWORD_DEFAULT);

    echo "Plaintext: $plainText\n";
    echo "BCRYPT Hash: $bcryptHash\n";
    echo "DEFAULT Hash: $defaultHash\n";
    
    $verifyBcrypt = password_verify($plainText, $bcryptHash);
    $verifyDefault = password_verify($plainText, $defaultHash);
    echo "Verify Bcrypt: " . ($verifyBcrypt ? "TRUE" : "FALSE") . "\n";
    echo "Verify Default: " . ($verifyDefault ? "TRUE" : "FALSE") . "\n";

    // 3. Let's test checking password_verify on one of the seeded users
    echo "\n--- Seed User Verification ---\n";
    $mario = $db->query("SELECT * FROM users WHERE email='mario@zesto.com' LIMIT 1")->fetch();
    if ($mario) {
        $verifyMario = password_verify($plainText, $mario['password']);
        echo "Verify Mario: " . ($verifyMario ? "TRUE" : "FALSE") . " (Stored Hash: " . $mario['password'] . ")\n";
    }

} catch (Exception $e) {
    echo "🔴 ERROR: " . $e->getMessage() . "\n";
}
