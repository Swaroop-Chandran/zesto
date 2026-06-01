<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json');
error_log('[Zesto Checkout] create_checkout_session.php entered');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('[Zesto Checkout] create_checkout_session.php rejected non-POST request');
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

verifyCsrf();
error_log('[Zesto Checkout] CSRF verified for Stripe checkout session');

$userId = isLoggedIn() ? getCurrentUser()['id'] : null;
$isGuest = isset($_SESSION['is_guest']) && $_SESSION['is_guest'] === true;
$cartItems = getCart();

error_log('USER_ID=' . ($userId ?? 'null'));
error_log('SESSION_ID=' . session_id());
error_log('CART_ITEMS=' . json_encode($cartItems));
error_log('[Zesto Checkout] Cart item count=' . count($cartItems));

if (empty($cartItems)) {
    error_log('[Zesto Checkout] Stripe checkout blocked: cart is empty');
    jsonResponse(['success' => false, 'message' => 'Cart is empty.'], 422);
}
$cart = $cartItems;

$data            = json_decode(file_get_contents('php://input'), true) ?? [];
$deliveryAddress = trim($data['delivery_address'] ?? '');
$couponCode      = trim($data['coupon_code']      ?? '');
error_log('[Zesto Checkout] Stripe checkout payload received: has_address=' . ($deliveryAddress !== '' ? 'yes' : 'no') . ', coupon=' . ($couponCode !== '' ? $couponCode : 'none'));

if (empty($deliveryAddress)) {
    error_log('[Zesto Checkout] Stripe checkout blocked: missing delivery address');
    jsonResponse(['success' => false, 'message' => 'Delivery address is required.'], 422);
}

// Calculate totals from the same cart rows used to create the order.
$subtotal    = 0.0;
foreach ($cart as $item) {
    $subtotal += ((float)$item['price']) * ((int)$item['quantity']);
}
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

if (!$userId && !$isGuest) {
    error_log('[Zesto Checkout] Stripe checkout blocked: no customer or guest session');
    jsonResponse(['success' => false, 'message' => 'Please login or checkout as guest to place an order.', 'redirect' => BASE_URL . '/login.php'], 401);
}

try {
    db()->beginTransaction();
    error_log('[Zesto Checkout] Creating pending Stripe order: order_number=' . $orderNumber . ', total=' . number_format($total, 2, '.', ''));

    // Insert order with pending status
    $orderStmt = db()->prepare("
        INSERT INTO orders (order_number, user_id, restaurant_id, delivery_address, payment_method, payment_status, order_status, subtotal, delivery_fee, taxes, discount, coupon_code, total)
        VALUES (:onum, :uid, :rid, :addr, 'stripe', 'pending', 'pending_payment', :sub, :dfee, :tax, :discount, :ccode, :total)
    ");
    $orderStmt->execute([
        ':onum'  => $orderNumber,
        ':uid'   => $userId,
        ':rid'   => $restaurantId,
        ':addr'  => $deliveryAddress,
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

    error_log('[Zesto Checkout] Pending Stripe order staged: order_id=' . $orderId . ', order_number=' . $orderNumber);

    // Initiate Stripe Session
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    error_log('[Zesto Checkout] Creating Stripe Checkout Session for order_id=' . $orderId);

    $line_items = [];
    foreach ($cart as $item) {
        $product_data = [
            'name' => $item['name'],
        ];
        
        $desc = trim($item['customization'] ?? '');
        if (!empty($desc)) {
            $product_data['description'] = $desc;
        }

        $line_items[] = [
            'price_data' => [
                'currency' => 'inr',
                'product_data' => $product_data,
                // Stripe expects amount in smallest currency unit (paise)
                'unit_amount' => round($item['price'] * 100),
            ],
            'quantity' => $item['quantity'],
        ];
    }
    
    // Add delivery fee
    if ($deliveryFee > 0) {
        $line_items[] = [
            'price_data' => [
                'currency' => 'inr',
                'product_data' => [
                    'name' => 'Delivery Fee',
                ],
                'unit_amount' => round($deliveryFee * 100),
            ],
            'quantity' => 1,
        ];
    }
    
    // Add platform fee
    if ($taxes > 0) {
        $line_items[] = [
            'price_data' => [
                'currency' => 'inr',
                'product_data' => [
                    'name' => 'Platform Charges & Taxes',
                ],
                'unit_amount' => round($taxes * 100),
            ],
            'quantity' => 1,
        ];
    }
    
    // Apply discount
    $coupon_id = null;
    if ($discount > 0) {
        // Stripe expects coupons to be created in their dashboard, 
        // to simplify we add a negative line item if not supported, but Stripe checkout doesn't allow negative line items.
        // Instead, we will create an ad-hoc coupon or use a fixed amount discount.
        // The easiest way for a dynamic discount without creating a coupon is a fixed amount discount.
        try {
            $stripeCoupon = \Stripe\Coupon::create([
                'amount_off' => round($discount * 100),
                'currency' => 'inr',
                'duration' => 'once',
                'name' => 'Discount: ' . $couponApplied,
            ]);
            $coupon_id = $stripeCoupon->id;
        } catch (Exception $e) {
            error_log('Failed to create stripe coupon: ' . $e->getMessage());
        }
    }
    
    $sessionParams = [
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => BASE_URL . '/checkout_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => BASE_URL . '/checkout_cancel.php?order=' . urlencode($orderNumber),
        'client_reference_id' => $orderId,
        'metadata' => [
            'order_id' => $orderId,
            'order_number' => $orderNumber
        ]
    ];
    
    if ($coupon_id) {
        $sessionParams['discounts'] = [['coupon' => $coupon_id]];
    }

    $checkout_session = \Stripe\Checkout\Session::create($sessionParams);
    error_log('[Zesto Checkout] Stripe Checkout Session created: session_id=' . $checkout_session->id . ', order_id=' . $orderId);

    // Save stripe session id to database
    $stmt = db()->prepare("UPDATE orders SET stripe_session_id = :sid WHERE id = :id");
    $stmt->execute([':sid' => $checkout_session->id, ':id' => $orderId]);
    error_log('[Zesto Checkout] Stripe session id saved: session_id=' . $checkout_session->id . ', order_id=' . $orderId);

    db()->commit();
    error_log('[Zesto Checkout] Pending Stripe order committed: order_id=' . $orderId . ', order_number=' . $orderNumber);

    jsonResponse([
        'success'      => true,
        'message'      => 'Redirecting to Stripe...',
        'order_number' => $orderNumber,
        'order_id'     => $orderId,
        'url'          => $checkout_session->url
    ]);

} catch (Exception $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    error_log('[Zesto Checkout] Stripe checkout error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to initiate checkout. Please try again.'], 500);
}
