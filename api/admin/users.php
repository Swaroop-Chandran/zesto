<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Only Admins allowed
requireRole(ROLE_ADMIN);
verifyCsrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$userId = (int)($data['user_id'] ?? 0);
$action = trim($data['action'] ?? '');

if (!$userId || !$action) {
    jsonResponse(['success' => false, 'message' => 'Invalid parameters.'], 422);
}

// Check that we aren't trying to suspend ourselves (safety check)
if ($userId === (int)getCurrentUser()['id']) {
    jsonResponse(['success' => false, 'message' => 'You cannot modify your own admin account.'], 400);
}

try {
    $db = db();
    
    // Check if user exists
    $stmt = $db->prepare("SELECT id, role, name FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
    }
    
    if ($action === 'suspend') {
        $stmt = $db->prepare("
            UPDATE users 
            SET is_active = 0, 
                account_status = 'suspended', 
                session_invalidated_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $userId]);
        jsonResponse(['success' => true, 'message' => "User '{$user['name']}' has been suspended and their active sessions revoked."]);
        
    } elseif ($action === 'activate') {
        $stmt = $db->prepare("
            UPDATE users 
            SET is_active = 1, 
                account_status = 'active', 
                session_invalidated_at = NULL 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $userId]);
        jsonResponse(['success' => true, 'message' => "User '{$user['name']}' has been activated successfully."]);
        
    } elseif ($action === 'soft_delete') {
        $stmt = $db->prepare("
            UPDATE users 
            SET is_active = 0, 
                account_status = 'deleted', 
                session_invalidated_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $userId]);
        jsonResponse(['success' => true, 'message' => "User '{$user['name']}' has been marked as deleted."]);
        
    } else {
        jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
    }

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
