<?php
/**
 * Zesto — Dynamic Rupee-Based Cart & Multi-Address Checkout (cart.php)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/location_helper.php';

$pageTitle   = 'Your Cart — Zesto';
$description = 'Review items, select from your saved addresses, choose payment, and complete your order.';

$cart        = getCart();
$cartCount   = getCartCount();
$subtotal    = getCartSubtotal();
$deliveryFee = $cartCount > 0 ? DEFAULT_DELIVERY_FEE : 0;
$taxes       = $cartCount > 0 ? PLATFORM_FEE : 0;
$total       = $subtotal + $deliveryFee + $taxes;

$userId = isLoggedIn() ? getCurrentUser()['id'] : null;
$session_id = session_id();

// Handle Saved Address POST submission directly for seamless flow
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_address') {
    verifyCsrf();
    $fname   = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $mobile  = trim(filter_input(INPUT_POST, 'mobile_number', FILTER_SANITIZE_SPECIAL_CHARS));
    $flat    = trim(filter_input(INPUT_POST, 'flat_number', FILTER_SANITIZE_SPECIAL_CHARS));
    $build   = trim(filter_input(INPUT_POST, 'building_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $street  = trim(filter_input(INPUT_POST, 'street', FILTER_SANITIZE_SPECIAL_CHARS));
    $area    = trim(filter_input(INPUT_POST, 'area', FILTER_SANITIZE_SPECIAL_CHARS));
    $landmark = trim(filter_input(INPUT_POST, 'landmark', FILTER_SANITIZE_SPECIAL_CHARS));
    $city    = trim(filter_input(INPUT_POST, 'city', FILTER_SANITIZE_SPECIAL_CHARS));
    $state   = trim(filter_input(INPUT_POST, 'state', FILTER_SANITIZE_SPECIAL_CHARS));
    $pin     = trim(filter_input(INPUT_POST, 'pincode', FILTER_SANITIZE_SPECIAL_CHARS));
    $type    = trim(filter_input(INPUT_POST, 'address_type', FILTER_SANITIZE_SPECIAL_CHARS));

    if (!empty($fname) && !empty($mobile) && !empty($street) && !empty($area) && !empty($city) && !empty($pin)) {
        $ins = db()->prepare("
            INSERT INTO addresses (user_id, guest_session_id, full_name, mobile_number, flat_number, building_name, street, area, landmark, city, state, pincode, address_type)
            VALUES (:uid, :gsid, :fname, :mobile, :flat, :build, :street, :area, :landmark, :city, :state, :pin, :type)
        ");
        $ins->execute([
            ':uid'   => $userId,
            ':gsid'  => $userId ? null : $session_id,
            ':fname' => $fname,
            ':mobile'=> $mobile,
            ':flat'  => $flat,
            ':build' => $build,
            ':street'=> $street,
            ':area'  => $area,
            ':landmark'=>$landmark,
            ':city'  => $city,
            ':state' => $state,
            ':pin'   => $pin,
            ':type'  => $type
        ]);
        setFlash('success', 'Address saved successfully!');
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    }
}

// Fetch saved addresses (either user-specific or guest session specific)
if ($userId) {
    $stmt = db()->prepare("SELECT * FROM addresses WHERE user_id = :uid ORDER BY id DESC");
    $stmt->execute([':uid' => $userId]);
} else {
    $stmt = db()->prepare("SELECT * FROM addresses WHERE guest_session_id = :sid ORDER BY id DESC");
    $stmt->execute([':sid' => $session_id]);
}
$savedAddresses = $stmt->fetchAll();

// Determine Checkout status
$isGuest = isset($_SESSION['is_guest']) && $_SESSION['is_guest'] === true;
$canCheckout = isLoggedIn() || $isGuest;

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 pb-16 md:pb-8 bg-[#fbf9f8]">
<div class="max-w-[1280px] mx-auto px-6 md:px-10 py-6 md:py-10 grid grid-cols-1 lg:grid-cols-12 gap-10 font-sans">

  <!-- Back Link + Title -->
  <div class="col-span-12 flex items-center gap-4 border-b border-gray-100 pb-5">
    <a href="<?= BASE_URL ?>/index.php"
       class="p-2.5 rounded-full border border-gray-250 bg-white hover:bg-gray-50 transition-all cursor-pointer shadow-sm">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#a83300]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    </a>
    <div>
      <h1 class="text-xl md:text-3xl font-black text-[#1b1c1c] tracking-tight">Your Cart</h1>
      <p class="text-xs text-gray-550 mt-0.5">Review items, select location addresses, and pay securely</p>
    </div>
  </div>

  <?php if (empty($cart)): ?>
  <!-- EMPTY STATE -->
  <div class="col-span-12 py-20 text-center flex flex-col items-center justify-center gap-6 bg-white rounded-3xl border border-gray-150 shadow-sm">
    <div class="w-24 h-24 bg-[#ffdbd0]/70 rounded-full flex items-center justify-center animate-bounce-gentle text-4xl shadow-inner">🍱</div>
    <div>
      <h2 class="text-xl font-extrabold text-gray-800">Your Basket is Empty</h2>
      <p class="text-xs text-gray-500 mt-2 max-w-sm mx-auto leading-relaxed">
        Discover fresh snacks, main courses, and gourmet cuisines from partner kitchens nearby.
      </p>
    </div>
    <a href="<?= BASE_URL ?>/index.php"
       class="bg-[#a83300] hover:bg-[#d24200] text-white text-xs font-bold px-8 py-3.5 rounded-xl flex items-center gap-2 shadow-md active:scale-95 transition-all">
      Browse Nearby Kitchens
    </a>
  </div>

  <?php else: ?>

  <!-- LEFT COLUMN: CART ITEMS & ADDRESSES -->
  <div class="lg:col-span-7 flex flex-col gap-8">

    <!-- DISHES LIST -->
    <section class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm flex flex-col gap-4">
      <div class="flex justify-between items-center border-b border-gray-100 pb-3.5">
        <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest">Added Dishes (<?= $cartCount ?>)</h2>
        <a href="<?= BASE_URL ?>/index.php" class="text-xs text-[#a83300] font-bold hover:underline">+ Add more dishes</a>
      </div>

      <div class="divide-y divide-gray-100">
        <?php foreach ($cart as $cartKey => $item): ?>
        <div class="py-4 flex gap-4 items-center">
          <?php if (!empty($item['image'])): ?>
          <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>"
               class="w-16 h-16 rounded-2xl object-cover border border-gray-100 shrink-0" referrerpolicy="no-referrer">
          <?php else: ?>
          <div class="w-16 h-16 rounded-2xl bg-[#ffdbd0] flex items-center justify-center text-[#a83300] font-bold shrink-0 text-xl shadow-sm">🍔</div>
          <?php endif; ?>

          <div class="flex-1 flex flex-col justify-between h-16 py-0.5 min-w-0">
            <div class="min-w-0">
              <h3 class="font-extrabold text-sm text-[#1b1c1c] truncate"><?= e($item['name']) ?></h3>
              <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-0.5 truncate">
                <?= e($item['restaurant_name']) ?>
                <?= !empty($item['customization']) ? ' • Customized: ' . e($item['customization']) : '' ?>
              </p>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[#a83300] font-black text-sm">
                <?= formatPrice($item['price'] * $item['quantity']) ?>
              </span>
              <!-- Quantity Controls -->
              <div class="flex items-center bg-gray-50 rounded-xl px-2 py-1 border border-gray-200/50 shadow-inner shrink-0">
                <button onclick="updateCartQuantity('<?= e($cartKey) ?>', -1)"
                        class="w-5.5 h-5.5 flex items-center justify-center hover:bg-white rounded-lg transition-all active:scale-90 text-gray-600">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <span class="mx-3 text-xs font-bold font-sans text-gray-700"><?= (int)$item['quantity'] ?></span>
                <button onclick="updateCartQuantity('<?= e($cartKey) ?>', 1)"
                        class="w-5.5 h-5.5 flex items-center justify-center hover:bg-white rounded-lg transition-all active:scale-90 text-gray-600">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Remove -->
          <button onclick="removeFromCart('<?= e($cartKey) ?>')"
                  class="p-2 text-gray-400 hover:text-red-500 rounded-full hover:bg-red-50 transition-colors shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
          </button>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- DELIVERY ADDRESS SELECTION -->
    <section class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm flex flex-col gap-5">
      <div class="flex justify-between items-center border-b border-gray-100 pb-3">
        <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest">Delivery Address Selection</h2>
        <button onclick="document.getElementById('cart-new-address-form').classList.toggle('hidden')" class="text-xs text-[#a83300] font-bold">+ New Address</button>
      </div>

      <!-- Add New Address Form (Hidden by default) -->
      <div id="cart-new-address-form" class="hidden bg-gray-50 border border-[#e5beb2]/50 p-5 rounded-2xl space-y-4">
        <h3 class="font-extrabold text-xs text-[#a83300] uppercase tracking-wider border-b border-gray-200 pb-2">Add New Location</h3>
        <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="save_address">
          
          <div>
            <label class="block text-[9px] font-bold text-gray-650 mb-1 uppercase">Full Name *</label>
            <input type="text" name="full_name" required placeholder="Alex Johnson" class="zesto-input bg-white">
          </div>
          <div>
            <label class="block text-[9px] font-bold text-gray-650 mb-1 uppercase">Mobile Number *</label>
            <input type="tel" name="mobile_number" required placeholder="+91 98765 43210" class="zesto-input bg-white">
          </div>
          <div>
            <label class="block text-[9px] font-bold text-gray-650 mb-1 uppercase">Flat / House No.</label>
            <input type="text" name="flat_number" placeholder="Flat No. 402" class="zesto-input bg-white">
          </div>
          <div>
            <label class="block text-[9px] font-bold text-gray-650 mb-1 uppercase">Building Name</label>
            <input type="text" name="building_name" placeholder="Skyline Towers" class="zesto-input bg-white">
          </div>
          <div class="sm:col-span-2">
            <label class="block text-[9px] font-bold text-gray-650 mb-1 uppercase">Street Address *</label>
            <input type="text" name="street" required placeholder="Main Street Road" class="zesto-input bg-white">
          </div>
          <div>
            <label class="block text-[9px] font-bold text-gray-650 mb-1 uppercase">Area / Locality *</label>
            <input type="text" name="area" required placeholder="Andheri West" class="zesto-input bg-white">
          </div>
          <div>
            <label class="block text-[9px] font-bold text-gray-650 mb-1 uppercase">Landmark</label>
            <input type="text" name="landmark" placeholder="Near Railway Station" class="zesto-input bg-white">
          </div>
          <div>
            <label class="block text-[9px] font-bold text-gray-650 mb-1 uppercase">City *</label>
            <input type="text" name="city" required value="<?= e(getCurrentCity()) ?>" class="zesto-input bg-white">
          </div>
          <div>
            <label class="block text-[9px] font-bold text-gray-650 mb-1 uppercase">State *</label>
            <input type="text" name="state" required placeholder="Maharashtra" class="zesto-input bg-white">
          </div>
          <div>
            <label class="block text-[9px] font-bold text-gray-650 mb-1 uppercase">Pincode *</label>
            <input type="text" name="pincode" required placeholder="400053" class="zesto-input bg-white">
          </div>
          <div>
            <label class="block text-[9px] font-bold text-gray-650 mb-1 uppercase">Address Type *</label>
            <select name="address_type" class="zesto-input bg-white text-xs font-semibold">
              <option value="home">Home (All Day)</option>
              <option value="work">Work (Office Hours)</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div class="sm:col-span-2 flex gap-3 pt-2">
            <button type="submit" class="btn-primary text-xs font-bold px-5 py-2.5 rounded-xl">Save Address</button>
            <button type="button" onclick="document.getElementById('cart-new-address-form').classList.add('hidden')" class="btn-secondary text-xs font-bold px-5 py-2.5 rounded-xl">Cancel</button>
          </div>
        </form>
      </div>

      <!-- Address Cards Grid -->
      <?php if (empty($savedAddresses)): ?>
      <div class="bg-gray-50 border border-dashed border-gray-250 rounded-2xl p-6 text-center text-gray-500 text-xs">
        📍 You have no saved delivery addresses. Please add a new address to continue.
      </div>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($savedAddresses as $index => $addr): 
          $addrStr = ($addr['flat_number'] ? $addr['flat_number'].', ' : '') . 
                     ($addr['building_name'] ? $addr['building_name'].', ' : '') . 
                     $addr['street'] . ', ' . $addr['area'] . ', ' . $addr['city'] . ' - ' . $addr['pincode'];
        ?>
        <label class="cursor-pointer flex flex-col justify-between p-4 bg-white border-2 rounded-2xl transition-all relative hover:border-[#a83300] shadow-sm select-addr-card <?= $index === 0 ? 'border-[#a83300] bg-[#ffdbd0]/5' : 'border-gray-200' ?>">
          <input type="radio" name="delivery_address_select" value="<?= e($addrStr) ?>" class="sr-only" <?= $index === 0 ? 'checked' : '' ?> 
                 onchange="document.querySelectorAll('.select-addr-card').forEach(c => c.classList.replace('border-[#a83300]','border-gray-200')); this.parentNode.classList.add('border-[#a83300]')">
          
          <div class="space-y-1.5 min-w-0 pr-10">
            <div class="flex items-center gap-1.5">
              <span class="text-sm">🏠</span>
              <h4 class="font-extrabold text-xs text-[#1b1c1c]"><?= e($addr['full_name']) ?></h4>
              <span class="text-[8px] font-black px-1.5 py-0.5 rounded bg-[#ffdbd0] text-[#a83300] uppercase shrink-0"><?= e($addr['address_type']) ?></span>
            </div>
            <p class="text-[10px] text-gray-500 leading-relaxed max-w-full line-clamp-3">
              <?= e($addrStr) ?>
            </p>
            <p class="text-[9px] text-gray-400 font-semibold mt-1">📞 Mobile: <?= e($addr['mobile_number']) ?></p>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Fallback hidden input to store selected address value -->
      <input type="hidden" id="delivery-address-value" value="<?= !empty($savedAddresses) ? e(($savedAddresses[0]['flat_number'] ? $savedAddresses[0]['flat_number'].', ' : '') . ($savedAddresses[0]['building_name'] ? $savedAddresses[0]['building_name'].', ' : '') . $savedAddresses[0]['street'] . ', ' . $savedAddresses[0]['area'] . ', ' . $savedAddresses[0]['city'] . ' - ' . $savedAddresses[0]['pincode']) : '' ?>">
    </section>

    <!-- PAYMENT METHOD -->
    <section class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm">
      <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 border-b border-gray-100 pb-3">Secure Payment Portal</h2>
      <div class="flex flex-col gap-3">
        <!-- Razorpay / UPI -->
        <label class="flex items-center p-4 rounded-2xl cursor-pointer transition-all border border-[#a83300] bg-[#ffdbd0]/20 shadow-sm label-payment">
          <input type="radio" name="payment_method" value="razorpay" checked class="hidden">
          <div class="w-10 h-10 rounded-xl bg-[#3395FF]/10 flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#3395FF]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
          </div>
          <div class="ml-4 flex-1">
            <span class="block font-extrabold text-xs md:text-sm text-[#1b1c1c]">Razorpay / UPI Wallet</span>
            <span class="block text-[10px] text-gray-450 mt-0.5">Sizzling fast checkout with direct UPI</span>
          </div>
          <div class="w-5 h-5 rounded-full border-2 border-[#a83300] flex items-center justify-center font-bold">
            <div class="w-2.5 h-2.5 rounded-full bg-[#a83300]"></div>
          </div>
        </label>

        <!-- Stripe / Card -->
        <label class="flex items-center p-4 rounded-2xl cursor-pointer transition-all border border-gray-200 hover:bg-gray-50 label-payment">
          <input type="radio" name="payment_method" value="stripe" class="hidden">
          <div class="w-10 h-10 rounded-xl bg-[#635BFF]/10 flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#635BFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          </div>
          <div class="ml-4 flex-1">
            <span class="block font-extrabold text-xs md:text-sm text-[#1b1c1c]">Credit / Debit Card</span>
            <span class="block text-[10px] text-gray-450 mt-0.5">Stripe secure card payment gate</span>
          </div>
          <div class="w-5 h-5 rounded-full border-2 border-gray-250 flex items-center justify-center font-bold">
          </div>
        </label>
      </div>
    </section>
  </div>

  <!-- RIGHT COLUMN: BILL SUMMARY -->
  <div class="lg:col-span-5 flex flex-col gap-6">
    <section class="bg-white rounded-3xl border border-gray-150 p-6 shadow-md sticky top-24">
      <h2 class="text-sm font-black text-[#1b1c1c] tracking-tight mb-5 border-b border-gray-100 pb-3 uppercase">Billing Overview</h2>

      <div class="space-y-4 text-xs font-sans font-semibold text-gray-500">
        <div class="flex justify-between items-center">
          <span>Cart Subtotal</span>
          <span class="font-extrabold text-gray-800"><?= formatPrice($subtotal) ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span>Platform Delivery Fee</span>
          <span class="font-extrabold text-gray-800"><?= formatPrice($deliveryFee) ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span>Platform Charges &amp; Taxes</span>
          <span class="font-extrabold text-gray-800"><?= formatPrice($taxes) ?></span>
        </div>
        <div id="bill-discount-row" class="hidden flex justify-between items-center text-[#00c853]">
          <span>Coupon Discount</span>
          <span id="bill-discount-value" class="font-extrabold">-₹0.00</span>
        </div>

        <div class="h-px bg-gray-150 my-4"></div>

        <div class="flex justify-between items-center text-[#1b1c1c] pt-2">
          <span class="text-sm font-black uppercase tracking-wider">Grand Total</span>
          <span id="bill-grand-total" class="text-xl font-black text-[#a83300]" data-total="<?= $total ?>"><?= formatPrice($total) ?></span>
        </div>
      </div>

      <!-- Promo Code Claiming Panel -->
      <div class="mt-6 bg-[#ffdbd0]/10 border border-[#ffdbd0] rounded-2xl p-4 flex flex-col gap-3 font-sans">
        <h3 class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Apply Coupon Code</h3>
        <div class="flex gap-2">
          <input type="text" id="coupon-input" placeholder="e.g. WELCOME50" class="zesto-input bg-white text-xs h-10 uppercase font-bold font-mono">
          <button onclick="applyCoupon()" class="bg-[#a83300] hover:bg-[#d24200] text-white text-xs font-bold px-4 rounded-xl active:scale-95 transition-all select-none cursor-pointer">
            APPLY
          </button>
        </div>
        <div id="coupon-status" class="hidden text-[10px] font-semibold"></div>
      </div>

      <!-- Place Order button -->
      <button id="cart-order-btn" onclick="triggerCheckout()"
              class="mt-6 w-full h-14 bg-[#d24200] hover:bg-[#a83300] text-white rounded-2xl flex items-center justify-between px-6 font-bold tracking-wide active:scale-[0.98] transition-all shadow-md shadow-[#d24200]/20 cursor-pointer">
        <span>PLACE ORDER</span>
        <div class="flex items-center gap-2">
          <span class="h-6 w-[1.5px] bg-white/20"></span>
          <span id="btn-grand-total"><?= formatPrice($total) ?></span>
        </div>
      </button>
    </section>
  </div>

  <?php endif; ?>
</div>
</main>

<script>
let appliedCouponCode = null;
let baseSubtotal = <?= floatval($subtotal) ?>;
let baseTotal = <?= floatval($total) ?>;

// Validate & Apply Coupon code client-side via validate.php
async function applyCoupon() {
  const input = document.getElementById('coupon-input');
  const statusDiv = document.getElementById('coupon-status');
  const discountRow = document.getElementById('bill-discount-row');
  const discountVal = document.getElementById('bill-discount-value');
  const grandTotalSpan = document.getElementById('bill-grand-total');
  const btnTotalSpan = document.getElementById('btn-grand-total');

  const code = input.value.trim().toUpperCase();
  if (!code) {
    Zesto.toast('Please enter a coupon code.', 'error');
    return;
  }

  statusDiv.className = "text-[10px] font-semibold text-gray-500";
  statusDiv.innerHTML = "Validating coupon...";
  statusDiv.classList.remove('hidden');

  try {
    const res = await fetch((window.ZESTO_BASE || '/Zesto') + '/api/coupons/validate.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfToken(),
      },
      body: JSON.stringify({ code: code, subtotal: baseSubtotal })
    });
    const data = await res.json();

    if (data.success) {
      appliedCouponCode = data.code;
      statusDiv.className = "text-[10px] font-semibold text-[#00c853]";
      statusDiv.innerHTML = "✓ " + data.message;
      
      // Update totals
      const discount = parseFloat(data.discount_amount);
      const newTotal = Math.max(0, baseTotal - discount);
      
      discountVal.innerHTML = "-₹" + discount.toFixed(2);
      discountRow.classList.remove('hidden');
      
      grandTotalSpan.innerHTML = "₹" + newTotal.toFixed(2);
      btnTotalSpan.innerHTML = "₹" + newTotal.toFixed(2);
      
      Zesto.toast('Coupon applied successfully!', 'success');
    } else {
      appliedCouponCode = null;
      statusDiv.className = "text-[10px] font-semibold text-red-500";
      statusDiv.innerHTML = "✗ " + data.message;
      
      discountRow.classList.add('hidden');
      grandTotalSpan.innerHTML = "₹" + baseTotal.toFixed(2);
      btnTotalSpan.innerHTML = "₹" + baseTotal.toFixed(2);
    }
  } catch(e) {
    statusDiv.className = "text-[10px] font-semibold text-red-500";
    statusDiv.innerHTML = "✗ Network error during validation.";
  }
}

// Payment methods toggling selection highlight
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.label-payment').forEach(lbl => {
      lbl.className = "flex items-center p-4 rounded-2xl cursor-pointer transition-all border border-gray-200 hover:bg-gray-50 label-payment";
      lbl.querySelector('.w-5').className = "w-5 h-5 rounded-full border-2 border-gray-250 flex items-center justify-center font-bold";
      lbl.querySelector('.w-5').innerHTML = '';
    });
    this.closest('label').className = "flex items-center p-4 rounded-2xl cursor-pointer transition-all border border-[#a83300] bg-[#ffdbd0]/20 shadow-sm label-payment";
    this.closest('label').querySelector('.w-5').className = "w-5 h-5 rounded-full border-2 border-[#a83300] flex items-center justify-center font-bold";
    this.closest('label').querySelector('.w-5').innerHTML = '<div class="w-2.5 h-2.5 rounded-full bg-[#a83300]"></div>';
  });
});

// Dynamic Checkout Placement Checks
function triggerCheckout() {
  const canCheckout = <?= $canCheckout ? 'true' : 'false' ?>;
  
  if (!canCheckout) {
    // Show sliding drawer with checkout parameters
    ZestoAuth.open({ checkout: true });
    return;
  }

  // Address selection evaluation
  const addrRadio = document.querySelector('input[name="delivery_address_select"]:checked');
  const selectedAddr = addrRadio ? addrRadio.value : '';

  if (!selectedAddr) {
    Zesto.toast('Please select or create a delivery address.', 'error');
    return;
  }

  // Update hidden field value
  document.getElementById('delivery-address-value').value = selectedAddr;

  const payment = document.querySelector('input[name="payment_method"]:checked')?.value || 'razorpay';
  
  // Trigger order placing
  placeOrder(payment, selectedAddr, appliedCouponCode);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
