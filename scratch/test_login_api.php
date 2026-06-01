<?php
/**
 * Zesto — Simulate direct API call to login.php to see why it fails or succeeds.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

try {
    $db = db();
    
    // We want to test login for ID 27
    $email = 'swaroop@zyrops.in';
    $password = 'password';
    $role = 'customer';

    echo "=== TESTING LOGIN FOR $email (Role: $role, Password: $password) ===\n";

    // 1. Let's trace how api/auth/login.php does it:
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND role = :role AND is_active = 1 LIMIT 1");
    $stmt->execute([':email' => $email, ':role' => $role]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "🔴 Step 1: User not found in DB with active=1 and role=$role.\n";
    } else {
        echo "🟢 Step 1: User found in DB!\n";
        
        // Check password verify
        $verify = password_verify($password, $user['password']);
        if ($verify) {
            echo "🟢 Step 2: Password verified successfully!\n";
        } else {
            echo "🔴 Step 2: Password verification FAILED.\n";
        }
    }

} catch (Exception $e) {
    echo "🔴 ERROR: " . $e->getMessage() . "\n";
}
