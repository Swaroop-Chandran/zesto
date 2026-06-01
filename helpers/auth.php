<?php
/**
 * Zesto — Session-Based Auth, Cookie, & Flash Session Helpers
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}

// ─── Session Bootstrap ───────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => false,   // Set true in production (HTTPS)
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ─── Auth Helpers ─────────────────────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name']  ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role']  ?? ROLE_CUSTOMER,
    ];
}

function requireLogin(string $redirect = ''): void {
    if (!isLoggedIn()) {
        $back = $redirect ?: (BASE_URL . $_SERVER['REQUEST_URI']);
        header('Location: ' . BASE_URL . '/login.php?redirect=' . urlencode($back));
        exit;
    }
}

function requireRole($roles): void {
    requireLogin();
    $current = $_SESSION['user_role'] ?? '';
    $allowed = (array) $roles;
    if (!in_array($current, $allowed, true)) {
        http_response_code(403);
        include __DIR__ . '/../includes/403.php';
        exit;
    }
}

function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['logged_in_at'] = time();
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

// ─── Flash Message Helpers ─────────────────────────────────────────────────────

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ─── Cart Helpers ──────────────────────────────────────────────────────────────

function getCart(): array {
    return $_SESSION['cart'] ?? [];
}

function getCartCount(): int {
    $cart = getCart();
    return array_sum(array_column($cart, 'quantity'));
}

function getCartSubtotal(): float {
    $cart = getCart();
    $total = 0.0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

// ─── Output Helpers ───────────────────────────────────────────────────────────

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function redirectToDashboard(): void {
    $role = $_SESSION['user_role'] ?? ROLE_CUSTOMER;
    $map = [
        ROLE_ADMIN            => BASE_URL . '/admin/dashboard.php',
        ROLE_RESTAURANT_OWNER => BASE_URL . '/restaurant-panel/dashboard.php',
        ROLE_DELIVERY_PARTNER => BASE_URL . '/delivery-panel/dashboard.php',
        ROLE_CUSTOMER         => BASE_URL . '/index.php',
    ];
    header('Location: ' . ($map[$role] ?? BASE_URL . '/index.php'));
    exit;
}
