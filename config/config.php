<?php
/**
 * Zesto — Global Application Configuration
 */

// ─── App Identity ───────────────────────────────────────────────────────────
define('APP_NAME',    'Zesto');
define('APP_TAGLINE', 'Delivering your favourite meals, fresh and fast.');
define('APP_VERSION', '1.0.0');

// ─── Base URL (adjust if installed in a subdirectory) ───────────────────────
// For XAMPP: http://localhost/Zesto
// For production: https://yourdomain.com
$baseDir = '/Zesto';
if (isset($_SERVER['REQUEST_URI'])) {
    if (stripos($_SERVER['REQUEST_URI'], '/zesto') === 0) {
        $baseDir = substr($_SERVER['REQUEST_URI'], 0, 6);
    }
}
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $baseDir);

// ─── Roles ───────────────────────────────────────────────────────────────────
define('ROLE_CUSTOMER',          'customer');
define('ROLE_RESTAURANT_OWNER',  'restaurant_owner');
define('ROLE_DELIVERY_PARTNER',  'delivery_partner');
define('ROLE_ADMIN',             'admin');

// ─── Session ─────────────────────────────────────────────────────────────────
define('SESSION_LIFETIME', 7200); // 2 hours

// ─── Pagination ──────────────────────────────────────────────────────────────
define('ITEMS_PER_PAGE', 12);

// ─── Upload Paths ─────────────────────────────────────────────────────────────
define('UPLOAD_DIR',     __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL',     BASE_URL . '/assets/uploads/');
define('MAX_UPLOAD_MB',  5);

// ─── Fees & Taxes ─────────────────────────────────────────────────────────────
define('DEFAULT_DELIVERY_FEE', 39.00);
define('TAX_RATE',             0.05);  // 5%
define('PLATFORM_FEE',         15.00);

// ─── Currency ─────────────────────────────────────────────────────────────────
define('CURRENCY_SYMBOL', '₹');

if (!function_exists('formatPrice')) {
    function formatPrice($amount): string {
        return CURRENCY_SYMBOL . number_format((float)$amount, 2);
    }
}
