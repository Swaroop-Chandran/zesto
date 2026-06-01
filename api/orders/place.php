<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405); }
verifyCsrf();

$cart = getCart();
if (empty($cart)) { jsonResponse(['success' => false, 'message' => 'Cart is empty.'], 422); }

$data            = json_decode(file_get_contents('php://input'), true) ?? [];
$paymentMethod   = trim($data['payment_method']   ?? 'stripe');
$deliveryAddress = trim($data['delivery_address'] ?? '');
$couponCode      = trim($data['coupon_code']      ?? '');

if (empty($deliveryAddress)) { jsonResponse(['success' => false, 'message' => 'Delivery address is required.'], 422); }

// Validate payment method
$allowedPayments = ['stripe', 'razorpay', 'cash'];
if (!in_array($paymentMethod, $allowedPayments, true)) $paymentMethod = 'stripe';

// Calculate totals
$subtotal    = getCartSubtotal();
$deliveryFee = DEFAULT_DELIVERY_FEE;
$taxes       = PLATFORM_FEE;

$discount = 0.00;
$couponApplied = null;

if (!empty($couponCode)) {
    $couponStmt = db()->prepare("SELECT * FROM coupons WHERE code = :code AND is_active = 1 LIMIT 1");
    $couponStmt->execute([':code' => $couponCode]);
    $coupon = $couponStmt->fetch();
    if ($coupon && $subtotal >= floatval($coupon['min_order_value'])) {
        $couponApplied = $coupon['code'];
        $discountAmount = ($subtotal * floatval($coupon['discount_percentage'])) / 100;
        $maxDiscount = floatval($coupon['max_discount']);
        if ($maxDiscount > 0 && $discountAmount > $maxDiscount) {
            $discountAmount = $maxDiscount;
        }
        $discount = $discountAmount;
    }
}

$total = max(0, $subtotal + $deliveryFee + $taxes - $discount);

// Get restaurant_id from cart
$firstItem    = reset($cart);
$restaurantId = $firstItem['restaurant_id'];

// Generate order number
$orderNumber = '#ZY-' . strtoupper(substr(uniqid(), -6));

// Get user ID (guest orders allowed)
$userId = isLoggedIn() ? getCurrentUser()['id'] : null;
$isGuest = isset($_SESSION['is_guest']) && $_SESSION['is_guest'] === true;

if (!$userId && !$isGuest) {
    jsonResponse(['success' => false, 'message' => 'Please login or checkout as guest to place an order.', 'redirect' => BASE_URL . '/login.php'], 401);
}

try {
    db()->beginTransaction();

    // Insert order
    $orderStmt = db()->prepare("
        INSERT INTO orders (order_number, user_id, restaurant_id, delivery_address, payment_method, payment_status, order_status, subtotal, delivery_fee, taxes, discount, coupon_code, total)
        VALUES (:onum, :uid, :rid, :addr, :pm, 'paid', 'pending', :sub, :dfee, :tax, :discount, :ccode, :total)
    ");
    $orderStmt->execute([
        ':onum'  => $orderNumber,
        ':uid'   => $userId,
        ':rid'   => $restaurantId,
        ':addr'  => $deliveryAddress,
        ':pm'    => $paymentMethod,
        ':sub'   => $subtotal,
        ':dfee'  => $deliveryFee,
        ':tax'   => $taxes,
        ':discount' => $discount,
        ':ccode' => $couponApplied,
        ':total' => $total,
    ]);
    $orderId = db()->lastInsertId();

    // Insert order items
    $itemStmt = db()->prepare("
        INSERT INTO order_items (order_id, menu_item_id, item_name, item_price, quantity, customization)
        VALUES (:oid, :mid, :name, :price, :qty, :cust)
    ");
    foreach ($cart as $item) {
        $itemStmt->execute([
            ':oid'   => $orderId,
            ':mid'   => $item['menu_item_id'],
            ':name'  => $item['name'],
            ':price' => $item['price'],
            ':qty'   => $item['quantity'],
            ':cust'  => $item['customization'] ?? '',
        ]);
    }

    db()->commit();

    // Clear cart
    $_SESSION['cart'] = [];

    jsonResponse([
        'success'      => true,
        'message'      => 'Order placed successfully!',
        'order_number' => $orderNumber,
        'order_id'     => $orderId,
        'total'        => number_format($total, 2),
        'redirect'     => BASE_URL . '/checkout.php?order=' . urlencode($orderNumber),
    ]);

} catch (Exception $e) {
    db()->rollBack();
    error_log('Order placement error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Order failed. Please try again.'], 500);
}
