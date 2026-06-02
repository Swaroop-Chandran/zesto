<?php
/**
 * Zesto — Session-Based Auth & RBAC Helpers
 */

require_once __DIR__ . '/config.php';

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

/**
 * Check if a user is currently logged in.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the current logged-in user's data array (from session).
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name']  ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role']  ?? ROLE_CUSTOMER,
    ];
}

/**
 * Redirect to login page if not authenticated.
 * @param string $redirect URL to redirect back after login
 */
function requireLogin(string $redirect = ''): void {
    if (!isLoggedIn()) {
        $back = $redirect ?: (BASE_URL . $_SERVER['REQUEST_URI']);
        header('Location: ' . BASE_URL . '/login.php?redirect=' . urlencode($back));
        exit;
    }
}

/**
 * Require a specific role (or array of roles). Redirect if wrong role.
 * @param string|array $roles
 */
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

/**
 * Log a user into the session after authentication.
 */
function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['logged_in_at'] = time();
}

/**
 * Destroy the session (logout).
 */
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

// ─── CSRF Helpers ─────────────────────────────────────────────────────────────

/**
 * Generate (or retrieve) the CSRF token for this session.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Return an HTML hidden input field with the CSRF token.
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Verify the CSRF token from a POST request. Kills on failure.
 */
function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'CSRF token validation failed.']));
    }
}

// ─── Cart Helpers ──────────────────────────────────────────────────────────────

/**
 * Get the current session cart (array of items).
 */
function getCart(): array {
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        return $_SESSION['cart'];
    }

    if (!isLoggedIn()) {
        return [];
    }

    require_once __DIR__ . '/database.php';

    $stmt = db()->prepare("
        SELECT
            c.menu_item_id,
            c.restaurant_id,
            c.quantity,
            c.customization,
            mi.name,
            mi.price,
            mi.image,
            r.name AS restaurant_name
        FROM cart c
        JOIN menu_items mi ON mi.id = c.menu_item_id
        JOIN restaurants r ON r.id = c.restaurant_id
        WHERE c.user_id = :uid
          AND mi.is_available = 1
        ORDER BY c.id ASC
    ");
    $stmt->execute([':uid' => getCurrentUser()['id']]);

    $cart = [];
    foreach ($stmt->fetchAll() as $item) {
        $customization = $item['customization'] ?? '';
        $cartKey = $item['menu_item_id'] . '_' . md5($customization);
        $cart[$cartKey] = [
            'menu_item_id'    => (int)$item['menu_item_id'],
            'restaurant_id'   => (int)$item['restaurant_id'],
            'restaurant_name' => $item['restaurant_name'],
            'name'            => $item['name'],
            'price'           => (float)$item['price'],
            'image'           => $item['image'] ?? '',
            'customization'   => $customization,
            'quantity'        => (int)$item['quantity'],
        ];
    }

    if (!empty($cart)) {
        $_SESSION['cart'] = $cart;
    }

    return $cart;
}

/**
 * Get total count of items in the cart.
 */
function getCartCount(): int {
    $cart = getCart();
    return array_sum(array_column($cart, 'quantity'));
}

/**
 * Get cart subtotal.
 */
function getCartSubtotal(): float {
    $cart = getCart();
    $total = 0.0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

// ─── Flash Message Helpers ─────────────────────────────────────────────────────

/**
 * Set a one-time flash message.
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear the flash message.
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ─── Output Helpers ───────────────────────────────────────────────────────────

/**
 * Safely echo HTML-escaped output.
 */
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Return a JSON response and terminate.
 */
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// ─── Role Dashboard Redirect ──────────────────────────────────────────────────

/**
 * Redirect the user to their role-specific dashboard.
 */
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

/**
 * Check if the active session is valid against database state.
 * If the user is suspended, deleted, or session was invalidated, force logout.
 */
function checkSessionValidity(): void {
    if (!isset($_SESSION['user_id'])) return;

    require_once __DIR__ . '/database.php';
    try {
        $stmt = db()->prepare("SELECT account_status, session_invalidated_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            logoutUser();
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }

        if ($user['account_status'] !== 'active') {
            logoutUser();
            session_start();
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Your account has been suspended. Contact support.'];
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }

        if ($user['session_invalidated_at']) {
            $invalidatedAt = strtotime($user['session_invalidated_at']);
            $loggedInAt = $_SESSION['logged_in_at'] ?? 0;
            if ($loggedInAt < $invalidatedAt) {
                logoutUser();
                session_start();
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Your session has expired. Please log in again.'];
                header('Location: ' . BASE_URL . '/login.php');
                exit;
            }
        }
    } catch (Exception $e) {
        // Fail silently
    }
}

// Run check on every load
checkSessionValidity();

