<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405); }
verifyCsrf();

$data     = json_decode(file_get_contents('php://input'), true) ?? [];
$email    = trim(filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL));
$password = $data['password'] ?? '';
$role     = trim(filter_var($data['role'] ?? ROLE_CUSTOMER, FILTER_SANITIZE_SPECIAL_CHARS));

if (empty($email) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Email and password are required.'], 422);
}

$stmt = db()->prepare("SELECT * FROM users WHERE email = :email AND role = :role LIMIT 1");
$stmt->execute([':email' => $email, ':role' => $role]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    if ($user['account_status'] === 'suspended') {
        jsonResponse(['success' => false, 'message' => 'Your account has been suspended. Contact support.'], 403);
    }
    if ($user['account_status'] === 'deleted' || $user['is_active'] == 0) {
        jsonResponse(['success' => false, 'message' => 'Your account is inactive.'], 403);
    }

    loginUser($user);
    
    // For Delivery Partners, verify if they are approved by admin
    if ($user['role'] === ROLE_DELIVERY_PARTNER) {
        $dpCheck = db()->prepare("SELECT is_approved FROM delivery_partners WHERE user_id = :uid LIMIT 1");
        $dpCheck->execute([':uid' => $user['id']]);
        $isApproved = $dpCheck->fetchColumn();
        if (!$isApproved) {
            logoutUser();
            jsonResponse(['success' => false, 'message' => 'Your Delivery Partner account is pending admin approval.'], 403);
        }
    }

    jsonResponse([
        'success'  => true,
        'message'  => 'Welcome back, ' . $user['name'] . '!',
        'user'     => ['name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']],
        'redirect' => BASE_URL . ($user['role'] === ROLE_ADMIN ? '/admin/dashboard.php'
                        : ($user['role'] === ROLE_RESTAURANT_OWNER ? '/restaurant-panel/dashboard.php'
                        : ($user['role'] === ROLE_DELIVERY_PARTNER ? '/delivery-panel/dashboard.php'
                        : '/index.php'))),
    ]);
} else {
    jsonResponse(['success' => false, 'message' => 'Invalid email or password for selected role.'], 401);
}
