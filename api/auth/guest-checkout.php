<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}
verifyCsrf();

$_SESSION['is_guest'] = true;
$_SESSION['user_id'] = null;
$_SESSION['user_name'] = 'Guest Customer';
$_SESSION['user_email'] = 'guest@zesto.com';
$_SESSION['user_role'] = ROLE_CUSTOMER;
$_SESSION['guest_session_id'] = session_id();

jsonResponse([
    'success' => true,
    'message' => 'Guest session activated!'
]);
