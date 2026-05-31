<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405); }
verifyCsrf();

$data     = json_decode(file_get_contents('php://input'), true) ?? [];
$name     = trim(filter_var($data['name']  ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
$email    = trim(filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL));
$password = $data['password'] ?? '';
$phone    = trim(filter_var($data['phone'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
$role     = $data['role'] ?? ROLE_CUSTOMER;

$errors = [];
if (empty($name))  $errors[] = 'Name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
if (strlen($password) < 8) $errors[] = 'Password must be 8+ characters.';
if (!in_array($role, [ROLE_CUSTOMER, ROLE_RESTAURANT_OWNER, ROLE_DELIVERY_PARTNER], true)) $role = ROLE_CUSTOMER;

if (!empty($errors)) { jsonResponse(['success' => false, 'errors' => $errors], 422); }

$check = db()->prepare("SELECT id FROM users WHERE email = :email AND role = :role LIMIT 1");
$check->execute([':email' => $email, ':role' => $role]);
if ($check->fetch()) { jsonResponse(['success' => false, 'message' => 'An account with this email and role already exists.'], 409); }

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$ins  = db()->prepare("INSERT INTO users (name, email, password, phone, role, is_active) VALUES (:name,:email,:pass,:phone,:role,:active)");
// Delivery partners require admin approval before activation (set is_active = 0)
$isActive = ($role === ROLE_DELIVERY_PARTNER) ? 0 : 1;
$ins->execute([':name' => $name, ':email' => $email, ':pass' => $hash, ':phone' => $phone, ':role' => $role, ':active' => $isActive]);
$newId = db()->lastInsertId();

if ($role === ROLE_DELIVERY_PARTNER) {
    $vehicleType = trim(filter_var($data['vehicle_type'] ?? 'bike', FILTER_SANITIZE_SPECIAL_CHARS));
    $vehicleNumber = trim(filter_var($data['vehicle_number'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
    $licenseNumber = trim(filter_var($data['driving_license_number'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
    
    $dpStmt = db()->prepare("
        INSERT INTO delivery_partners (user_id, vehicle_type, vehicle_number, driving_license_number, is_approved, is_available)
        VALUES (:uid, :vtype, :vnum, :lnum, 0, 0)
    ");
    $dpStmt->execute([
        ':uid'   => $newId,
        ':vtype' => $vehicleType,
        ':vnum'  => $vehicleNumber,
        ':lnum'  => $licenseNumber
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Application submitted! Admin approval is required before you can log in.',
        'redirect' => BASE_URL . '/index.php'
    ]);
}

$user = db()->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$user->execute([':id' => $newId]);
loginUser($user->fetch());

jsonResponse(['success' => true, 'message' => 'Account created successfully!', 'redirect' => BASE_URL . '/index.php']);
