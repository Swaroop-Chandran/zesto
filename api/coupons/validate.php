<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

verifyCsrf();

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$code = strtoupper(trim($data['code'] ?? ''));
$subtotal = floatval($data['subtotal'] ?? 0);

if (empty($code)) {
    jsonResponse(['success' => false, 'message' => 'Please enter a coupon code.'], 422);
}

try {
    $stmt = db()->prepare("SELECT * FROM coupons WHERE code = :code AND is_active = 1 LIMIT 1");
    $stmt->execute([':code' => $code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        jsonResponse(['success' => false, 'message' => 'Invalid or expired coupon code.'], 422);
    }

    if ($subtotal < floatval($coupon['min_order_value'])) {
        jsonResponse([
            'success' => false, 
            'message' => 'Minimum order value to apply this coupon is ' . formatPrice($coupon['min_order_value']) . '.'
        ], 422);
    }

    // Calculate discount
    $discountPercentage = floatval($coupon['discount_percentage']);
    $discountAmount = ($subtotal * $discountPercentage) / 100;
    
    $maxDiscount = floatval($coupon['max_discount']);
    if ($maxDiscount > 0 && $discountAmount > $maxDiscount) {
        $discountAmount = $maxDiscount;
    }

    jsonResponse([
        'success' => true,
        'code' => $coupon['code'],
        'discount_percentage' => $discountPercentage,
        'discount_amount' => $discountAmount,
        'message' => 'Coupon ' . $coupon['code'] . ' applied successfully! You saved ' . formatPrice($discountAmount) . '.'
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to validate coupon.'], 500);
}
