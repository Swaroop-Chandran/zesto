<?php
/**
 * Zesto — Simulate Register and Login E2E to find the root cause of the authentication issue.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

try {
    $db = db();
    echo "=== SIMULATING REGISTRATION ===\n";
    
    $name = "Test User Registration";
    $email = "testregister" . time() . "@example.com";
    $password = "SecretPassword123!";
    $role = "customer";
    
    // Hash password exactly like api/auth/register.php
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Insert into DB
    $ins = $db->prepare("INSERT INTO users (name, email, password, phone, role, is_active) VALUES (:name, :email, :pass, :phone, :role, :active)");
    $ins->execute([
        ':name' => $name,
        ':email' => $email,
        ':pass' => $hash,
        ':phone' => '+919999999999',
        ':role' => $role,
        ':active' => 1
    ]);
    
    $newId = $db->lastInsertId();
    echo "Registered successfully! Created User ID: $newId | Email: $email | Password Hash: $hash\n";

    // Now, let's try to query this user and verify password exactly like api/auth/login.php
    echo "\n=== SIMULATING LOGIN ===\n";
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND role = :role AND is_active = 1 LIMIT 1");
    $stmt->execute([':email' => $email, ':role' => $role]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "🔴 LOGIN FAILED: User not found in database for email: $email, role: $role\n";
    } else {
        echo "User found in database! Stored Hash in DB: " . $user['password'] . "\n";
        
        $verify = password_verify($password, $user['password']);
        if ($verify) {
            echo "🟢 LOGIN SUCCESS! Password verified successfully.\n";
        } else {
            echo "🔴 LOGIN FAILED: password_verify returned FALSE!\n";
            echo "Submitted Password: '$password'\n";
            echo "Length of Stored Hash in DB: " . strlen($user['password']) . "\n";
        }
    }

} catch (Exception $e) {
    echo "🔴 ERROR: " . $e->getMessage() . "\n";
}
