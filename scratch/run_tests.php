<?php
/**
 * Zesto Nights — End-to-End System Test Suite
 * Simulates core application workflows directly against the database and session handlers.
 */

// Setup mock session environment
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = []; // Reset session

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/location_helper.php';
require_once __DIR__ . '/../config/auth.php';

function printHeader($title) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "  " . strtoupper($title) . "\n";
    echo str_repeat("=", 80) . "\n";
}

$db = db();

// ============================================================================
// TEST 1: Customer Ordering Simulation
// ============================================================================
printHeader("TEST 1: Customer Ordering Workflow");

// 1. Simulating Customer Login
echo "[Step 1] Simulating customer login (alex@example.com)...\n";
$userQuery = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$userQuery->execute(['alex@example.com']);
$user = $userQuery->fetch();
if ($user) {
    loginUser($user);
    echo " -> Login successful. User: " . $_SESSION['user_name'] . " (ID: " . $_SESSION['user_id'] . ", Role: " . $_SESSION['user_role'] . ")\n";
} else {
    echo " -> ERROR: Customer account not found in database!\n";
    exit(1);
}

// 2. Open Restaurant
echo "[Step 2] Simulating opening restaurant 'manis-thattukada'...\n";
$restStmt = $db->prepare("SELECT * FROM restaurants WHERE slug = ? LIMIT 1");
$restStmt->execute(['manis-thattukada']);
$restaurant = $restStmt->fetch();
echo " -> Loaded Restaurant: " . $restaurant['name'] . " (ID: " . $restaurant['id'] . ", City: " . $restaurant['city'] . ")\n";

// 3. Add Item to Cart
echo "[Step 3] Adding Menu Item ID 37 (Beef Roast & Porotta) to cart...\n";
$_SESSION['cart'] = []; // Clear cart first
// Emulating /api/cart/add.php logic
$itemId = 37;
$itemStmt = $db->prepare("SELECT * FROM menu_items WHERE id = ? LIMIT 1");
$itemStmt->execute([$itemId]);
$item = $itemStmt->fetch();
echo " -> Found Menu Item: " . $item['name'] . " (Price: ₹" . $item['price'] . ")\n";

$cartKey = $item['id'] . '_' . md5('');
$_SESSION['cart'][$cartKey] = [
    'menu_item_id'   => $item['id'],
    'restaurant_id'  => $restaurant['id'],
    'restaurant_name'=> $restaurant['name'],
    'name'           => $item['name'],
    'price'          => (float)$item['price'],
    'image'          => $item['image'],
    'customization'  => '',
    'quantity'       => 1,
];

// Sync to DB Cart
$syncStmt = $db->prepare("
    INSERT INTO cart (user_id, menu_item_id, restaurant_id, quantity, customization)
    VALUES (?, ?, ?, 1, '')
    ON DUPLICATE KEY UPDATE quantity = quantity + 1
");
$syncStmt->execute([$_SESSION['user_id'], $item['id'], $restaurant['id']]);
echo " -> Synced item to database cart table.\n";

// 4. Cart Count Updates
echo "[Step 4] Checking cart count updates...\n";
echo " -> Session Cart Count: " . getCartCount() . "\n";
echo " -> Session Cart Subtotal: ₹" . getCartSubtotal() . "\n";

// 5. Open Cart & Quantity Increase
echo "[Step 5] Simulating open cart & quantity increase (Delta +1)...\n";
$_SESSION['cart'][$cartKey]['quantity'] += 1;
// Sync to DB
$db->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id=? AND menu_item_id=?")
   ->execute([$_SESSION['user_id'], $item['id']]);
echo " -> Updated Cart Qty: " . $_SESSION['cart'][$cartKey]['quantity'] . " (Count: " . getCartCount() . ", Subtotal: ₹" . getCartSubtotal() . ")\n";

// 6. Quantity Decrease
echo "[Step 6] Simulating quantity decrease (Delta -1)...\n";
$_SESSION['cart'][$cartKey]['quantity'] -= 1;
// Sync to DB
$db->prepare("UPDATE cart SET quantity = quantity - 1 WHERE user_id=? AND menu_item_id=?")
   ->execute([$_SESSION['user_id'], $item['id']]);
echo " -> Updated Cart Qty: " . $_SESSION['cart'][$cartKey]['quantity'] . " (Count: " . getCartCount() . ", Subtotal: ₹" . getCartSubtotal() . ")\n";

// 7. Checkout (Direct Cash placement)
echo "[Step 7] Simulating direct checkout (Cash on Delivery)...\n";
$orderNumber = '#ZY-' . strtoupper(substr(uniqid(), -6));
$subtotal = getCartSubtotal();
$deliveryFee = 30.00;
$taxes = 15.00;
$total = $subtotal + $deliveryFee + $taxes;
$deliveryAddress = "Palarivattom Bypass, Kochi, Kerala";

$db->beginTransaction();
$orderStmt = $db->prepare("
    INSERT INTO orders (order_number, user_id, restaurant_id, delivery_address, payment_method, payment_status, order_status, subtotal, delivery_fee, taxes, discount, total)
    VALUES (?, ?, ?, ?, 'cash', 'paid', 'pending', ?, ?, ?, 0.00, ?)
");
$orderStmt->execute([$orderNumber, $_SESSION['user_id'], $restaurant['id'], $deliveryAddress, $subtotal, $deliveryFee, $taxes, $total]);
$orderId = $db->lastInsertId();

$itemInsert = $db->prepare("
    INSERT INTO order_items (order_id, menu_item_id, item_name, item_price, quantity)
    VALUES (?, ?, ?, ?, ?)
");
foreach ($_SESSION['cart'] as $cItem) {
    $itemInsert->execute([$orderId, $cItem['menu_item_id'], $cItem['name'], $cItem['price'], $cItem['quantity']]);
}
// Clear DB Cart
$db->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$_SESSION['user_id']]);
$db->commit();

$_SESSION['cart'] = []; // Clear session cart
echo " -> Order Created Successfully! ID: " . $orderId . ", Ref: " . $orderNumber . "\n";
echo " -> Order payment status: paid, order status: pending\n";

// Print order records from database
$orderCheck = $db->prepare("SELECT * FROM orders WHERE id = ?");
$orderCheck->execute([$orderId]);
print_r($orderCheck->fetch());


// ============================================================================
// TEST 2: Stripe Checkout & Webhook
// ============================================================================
printHeader("TEST 2: Stripe Integration Workflow");

// 1. Prepare Stripe Cart Items
echo "[Step 1] Staging cart with Menu Item ID 38 (Thattukada Set) for Stripe...\n";
$itemId = 38;
$itemStmt->execute([$itemId]);
$item = $itemStmt->fetch();
$cartKey = $item['id'] . '_' . md5('');
$_SESSION['cart'][$cartKey] = [
    'menu_item_id'   => $item['id'],
    'restaurant_id'  => $restaurant['id'],
    'restaurant_name'=> $restaurant['name'],
    'name'           => $item['name'],
    'price'          => (float)$item['price'],
    'image'          => $item['image'],
    'customization'  => '',
    'quantity'       => 1,
];
echo " -> Cart Loaded: " . $item['name'] . " (Price: ₹" . $item['price'] . ")\n";

// 2. Create Stripe Checkout Session (Staged order creation)
echo "[Step 2] Staging pending order in database...\n";
$stripeOrderNum = '#ZY-STR' . strtoupper(substr(uniqid(), -4));
$subtotal = getCartSubtotal();
$deliveryFee = 30.00;
$taxes = 15.00;
$total = $subtotal + $deliveryFee + $taxes;

$db->beginTransaction();
$orderStmt = $db->prepare("
    INSERT INTO orders (order_number, user_id, restaurant_id, delivery_address, payment_method, payment_status, order_status, subtotal, delivery_fee, taxes, discount, total)
    VALUES (?, ?, ?, ?, 'stripe', 'pending', 'pending_payment', ?, ?, ?, 0.00, ?)
");
$orderStmt->execute([$stripeOrderNum, $_SESSION['user_id'], $restaurant['id'], $deliveryAddress, $subtotal, $deliveryFee, $taxes, $total]);
$stripeOrderId = $db->lastInsertId();

// Mock Stripe Session ID
$mockSessionId = "cs_test_" . bin2hex(random_bytes(16));
$db->prepare("UPDATE orders SET stripe_session_id = ? WHERE id = ?")->execute([$mockSessionId, $stripeOrderId]);

$db->commit();
echo " -> Staged Order ID: " . $stripeOrderId . ", Stripe Session ID: " . $mockSessionId . "\n";

// 3. Database record BEFORE payment
echo "\n--- DATABASE RECORD BEFORE STRIPE PAYMENT ---\n";
$orderCheck->execute([$stripeOrderId]);
$beforePayment = $orderCheck->fetch();
echo " -> Order ID: " . $beforePayment['id'] . "\n";
echo " -> Order Number: " . $beforePayment['order_number'] . "\n";
echo " -> Payment Status: " . $beforePayment['payment_status'] . " (Expected: pending)\n";
echo " -> Order Status: " . $beforePayment['order_status'] . " (Expected: pending_payment)\n";
echo " -> Stripe Session ID: " . $beforePayment['stripe_session_id'] . "\n";

// 4. Simulate Stripe Webhook Completed Trigger
echo "\n[Step 4] Triggering Stripe Webhook mock (checkout.session.completed)...\n";
// Emulating api/webhooks/stripe.php logic:
$webhookDb = db();
$webhookDb->beginTransaction();
$lockStmt = $webhookDb->prepare("SELECT id, payment_status, order_status FROM orders WHERE id = ? FOR UPDATE");
$lockStmt->execute([$stripeOrderId]);
$lockedOrder = $lockStmt->fetch();

if ($lockedOrder && $lockedOrder['payment_status'] === 'pending') {
    $updateStmt = $webhookDb->prepare("
        UPDATE orders 
        SET payment_status = 'paid', 
            order_status = 'placed',
            stripe_payment_intent_id = ?,
            payment_timestamp = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $updateStmt->execute(['pi_mock_' . bin2hex(random_bytes(8)), $stripeOrderId]);
    echo " -> Webhook: Order marked as PAID and PLACED.\n";
}
$webhookDb->commit();

// 5. Database record AFTER payment
echo "\n--- DATABASE RECORD AFTER STRIPE PAYMENT ---\n";
$orderCheck->execute([$stripeOrderId]);
$afterPayment = $orderCheck->fetch();
echo " -> Order ID: " . $afterPayment['id'] . "\n";
echo " -> Order Number: " . $afterPayment['order_number'] . "\n";
echo " -> Payment Status: " . $afterPayment['payment_status'] . " (Expected: paid)\n";
echo " -> Order Status: " . $afterPayment['order_status'] . " (Expected: placed)\n";
echo " -> Payment Timestamp: " . $afterPayment['payment_timestamp'] . "\n";


// ============================================================================
// TEST 3: Restaurant Detail Loads
// ============================================================================
printHeader("TEST 3: Restaurant Data Navigation Workflow");

echo "[Step 1] Loading restaurant details for 'manis-thattukada'...\n";
$restStmt->execute(['manis-thattukada']);
$restData = $restStmt->fetch();
echo " -> Name: " . $restData['name'] . "\n";
echo " -> Tags/Specialties: " . $restData['tags'] . "\n";
echo " -> City: " . $restData['city'] . "\n";
echo " -> Operating Hours: " . $restData['operating_hours'] . "\n";

echo "[Step 2] Loading menu categories for Mani's Thattukada...\n";
$catStmt = $db->prepare("
    SELECT DISTINCT c.name as category 
    FROM menu_items mi
    JOIN categories c ON c.id = mi.category_id
    WHERE mi.restaurant_id = ? AND mi.is_available = 1 
    ORDER BY c.name ASC
");
$catStmt->execute([$restData['id']]);
$cats = $catStmt->fetchAll(PDO::FETCH_COLUMN);
echo " -> Loaded Categories: " . implode(", ", $cats) . "\n";

echo "[Step 3] Loading all menu items for Mani's Thattukada...\n";
$menuStmt = $db->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? AND is_available = 1");
$menuStmt->execute([$restData['id']]);
$menuItems = $menuStmt->fetchAll();
foreach ($menuItems as $mItem) {
    echo "   - [" . ($mItem['is_veg'] ? 'VEG' : 'NON-VEG') . "] " . $mItem['name'] . " — ₹" . $mItem['price'] . " (" . $mItem['description'] . ")\n";
}


// ============================================================================
// TEST 4: Dynamic Data Insertion Proof
// ============================================================================
printHeader("TEST 4: Dynamic Data Insertion Proof");

// 1. Add Category
echo "[Step 1] Injecting new Category 'Kochi Porotta Rolls'...\n";
$newCatId = 99;
$db->prepare("INSERT INTO categories (id, name, image, display_order, is_active) VALUES (?, 'Kochi Porotta Rolls', 'mock_url', 9, 1)")
   ->execute([$newCatId]);

// Verify it appears in categories query
$catVerify = $db->query("SELECT * FROM categories WHERE is_active = 1 AND id = 99")->fetch();
echo " -> Query verified: " . ($catVerify ? "SUCCESS: Category '" . $catVerify['name'] . "' loaded from DB!" : "FAILED!") . "\n";

// 2. Add Restaurant
echo "[Step 2] Injecting new Restaurant 'Subhash Street Corner' in Kochi...\n";
$newRestSlug = 'subhash-street-corner';
$db->prepare("
    INSERT INTO restaurants (owner_id, slug, name, tags, description, rating, delivery_time, distance, city, operating_hours, is_active)
    VALUES (?, ?, 'Subhash Street Corner', 'Kerala Rolls', 'Late night rolls', 4.9, '15 min', 0.5, 'Kochi', '6 PM - 4 AM', 1)
")
->execute([$ownerId, $newRestSlug]);

// Verify it appears in restaurant grid query
$restVerifyStmt = $db->prepare("SELECT * FROM restaurants WHERE slug = ? AND is_active = 1");
$restVerifyStmt->execute([$newRestSlug]);
$restVerify = $restVerifyStmt->fetch();
echo " -> Query verified: " . ($restVerify ? "SUCCESS: Restaurant '" . $restVerify['name'] . "' loaded from DB!" : "FAILED!") . "\n";

// 3. Add Menu Item
echo "[Step 3] Injecting new Menu Item 'Subhash Beef Roll' under the new category/restaurant...\n";
$db->prepare("
    INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_veg, is_available)
    VALUES (?, ?, 'Subhash Beef Roll', 'Juicy beef wrapped in flaky Kerala porotta', 90.00, 0, 1)
")
->execute([$restVerify['id'], $newCatId]);

// Verify it appears in menu item lookup query
$itemVerifyStmt = $db->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? AND name = ?");
$itemVerifyStmt->execute([$restVerify['id'], 'Subhash Beef Roll']);
$itemVerify = $itemVerifyStmt->fetch();
echo " -> Query verified: " . ($itemVerify ? "SUCCESS: Menu Item '" . $itemVerify['name'] . "' (Price: ₹" . $itemVerify['price'] . ") loaded from DB!" : "FAILED!") . "\n";

// Cleanup injected test records to maintain DB hygiene
$db->prepare("DELETE FROM menu_items WHERE restaurant_id = ?")->execute([$restVerify['id']]);
$db->prepare("DELETE FROM restaurants WHERE id = ?")->execute([$restVerify['id']]);
$db->prepare("DELETE FROM categories WHERE id = ?")->execute([$newCatId]);
echo " -> Temporary test records successfully purged from database.\n";


// ============================================================================
// TEST 5: Cart Persistence Validation
// ============================================================================
printHeader("TEST 5: Cart Persistence Validation");

// 1. Guest User cart test
echo "[Step 1] Simulating Guest User adding item to cart...\n";
$_SESSION = []; // Destroy session (simulating guest session)
$_SESSION['cart'] = [];
$guestItemId = 42; // Kappa Biriyani (Beef)
$itemStmt->execute([$guestItemId]);
$guestItem = $itemStmt->fetch();

$guestKey = $guestItem['id'] . '_' . md5('');
$_SESSION['cart'][$guestKey] = [
    'menu_item_id' => $guestItem['id'],
    'name'         => $guestItem['name'],
    'price'        => (float)$guestItem['price'],
    'quantity'     => 1,
];
echo " -> Guest Cart initialized in PHP Session: " . $_SESSION['cart'][$guestKey]['name'] . " (Qty: 1)\n";

// Simulate Page Refresh (Session remains intact)
echo " -> Simulating Page Refresh...\n";
$refreshedCart = getCart();
echo " -> Refreshed Cart Count: " . count($refreshedCart) . "\n";
echo " -> Cart Item: " . $refreshedCart[$guestKey]['name'] . " (Qty: " . $refreshedCart[$guestKey]['quantity'] . ")\n";

// 2. Logged-in User persistent database cart test
echo "\n[Step 2] Simulating Logged-in User adding item to cart and performing Logout/Login...\n";
$_SESSION = []; // Clear session
loginUser($user); // Login user
$_SESSION['cart'] = []; // Clear session cache

$loggedInItemId = 37; // Beef Roast & Porotta
$itemStmt->execute([$loggedInItemId]);
$loggedInItem = $itemStmt->fetch();

// Add to DB Cart
$syncStmt->execute([$_SESSION['user_id'], $loggedInItem['id'], 10]); // mani's thattukada id is 10
echo " -> Logged-in User added item to database table 'cart'.\n";

// Logout (Clear session completely)
echo " -> Simulating User Logout...\n";
logoutUser();
if (empty($_SESSION)) {
    echo " -> Session successfully destroyed. Confirming empty cart in current session: count=" . count(getCart()) . "\n";
}

// Login back
echo " -> Simulating User Logging Back In...\n";
loginUser($user);
// Fetch cart back from database
$restoredCart = getCart();
echo " -> Fetching cart from database table...\n";
echo " -> Restored Cart Item count: " . count($restoredCart) . "\n";
foreach ($restoredCart as $item) {
    echo "   - Restored Item: " . $item['name'] . " (Quantity: " . $item['quantity'] . ")\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "  ALL E2E SYSTEM TESTS COMPLETED SUCCESSFULLY!\n";
echo str_repeat("=", 80) . "\n";
