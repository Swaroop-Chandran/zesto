<?php
/**
 * Zesto — Dynamic Rupee-Based Cart & Multi-Address Checkout (cart.php)
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/location_helper.php';

$pageTitle   = 'Checkout — Zesto Nights';
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

// Fetch saved addresses
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

include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/navbar.php';
?>

<main class="flex-1 bg-zesto-dark font-sans text-[#dfe2eb]">
<div class="w-full max-w-7xl mx-auto px-4 sm:px-10 py-6 animate-fade-in pb-20">

  <div class="flex items-center gap-4 mb-8">
    <h1 class="text-3xl font-display font-black text-white tracking-tight">Checkout</h1>
  </div>

  <?php if (empty($cart)): ?>
    <div class="text-center py-20 bg-white/5 rounded-2xl border border-white/5 max-w-xl mx-auto space-y-4">
      <span class="text-3xl">🛒</span>
      <h3 class="text-base font-bold text-white">Your basket is empty</h3>
      <p class="text-xs text-white/50 max-w-xs mx-auto">
        Your late-night plate is currently empty. Go back and select items to feast!
      </p>
      <a href="<?= BASE_URL ?>/index.php" class="inline-block mt-4 px-6 py-2.5 bg-zesto-orange text-white text-xs font-bold rounded-lg no-underline transition hover:bg-zesto-orange/90">
        Browse Thattukadas
      </a>
    </div>
  <?php else: ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
      
      <!-- Left Side: Deliveries & Payments -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- 1. Delivery Address Box -->
        <div class="glass-panel rounded-2xl p-6 border border-white/10 space-y-5">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-display font-extrabold text-white flex items-center gap-2">
              <span class="text-zesto-orange">1.</span> Delivery Address
            </h3>
            <span class="text-xs font-semibold text-zesto-cyan"><?= e(getCurrentCity()) ?>, Kerala</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Address Select Cards -->
            <div class="space-y-3">
              <?php if (empty($savedAddresses)): ?>
                <div class="p-4 rounded-xl border border-dashed border-white/20 text-center text-xs text-white/50 bg-white/5">
                  No saved addresses found.
                </div>
              <?php else: ?>
                <?php foreach ($savedAddresses as $index => $addr): 
                  $addrStr = ($addr['flat_number'] ? $addr['flat_number'].', ' : '') . 
                            ($addr['building_name'] ? $addr['building_name'].', ' : '') . 
                            $addr['street'] . ', ' . $addr['area'] . ', ' . $addr['city'] . ' - ' . $addr['pincode'];
                ?>
                  <label class="select-addr-card block p-4 rounded-xl cursor-pointer border transition duration-200 flex items-start gap-3.5 <?= $index === 0 ? 'bg-[#1c2026] border-zesto-amber/70 shadow-lg shadow-zesto-amber/5' : 'bg-white/5 border-white/5 hover:bg-white/10' ?>">
                    <input type="radio" name="delivery_address_select" value="<?= e($addrStr) ?>" class="sr-only" <?= $index === 0 ? 'checked' : '' ?> onchange="document.querySelectorAll('.select-addr-card').forEach(c => { c.classList.remove('bg-[#1c2026]','border-zesto-amber/70','shadow-lg','shadow-zesto-amber/5'); c.classList.add('bg-white/5','border-white/5'); c.querySelector('.addr-icon').classList.replace('bg-zesto-orange/20','bg-white/5'); c.querySelector('.addr-icon').classList.replace('text-zesto-orange','text-white/40'); if(c.querySelector('.addr-check')) c.querySelector('.addr-check').classList.add('hidden'); }); this.closest('.select-addr-card').classList.remove('bg-white/5','border-white/5'); this.closest('.select-addr-card').classList.add('bg-[#1c2026]','border-zesto-amber/70','shadow-lg','shadow-zesto-amber/5'); this.closest('.select-addr-card').querySelector('.addr-icon').classList.replace('bg-white/5','bg-zesto-orange/20'); this.closest('.select-addr-card').querySelector('.addr-icon').classList.replace('text-white/40','text-zesto-orange'); if(this.closest('.select-addr-card').querySelector('.addr-check')) this.closest('.select-addr-card').querySelector('.addr-check').classList.remove('hidden');" />
                    
                    <div class="addr-icon p-2 rounded-lg transition <?= $index === 0 ? 'bg-zesto-orange/20 text-zesto-orange' : 'bg-white/5 text-white/40' ?>">
                      <?php if (strtolower($addr['address_type']) === 'home'): ?>
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                      <?php else: ?>
                        <i data-lucide="building" class="w-4 h-4"></i>
                      <?php endif; ?>
                    </div>
                    <div class="flex-1 text-left">
                      <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-white"><?= e($addr['full_name']) ?> <span class="text-[9px] text-white/50 uppercase ml-1">(<?= e($addr['address_type']) ?>)</span></span>
                        <div class="addr-check w-4 h-4 rounded-full bg-zesto-orange flex items-center justify-center text-[8px] text-white <?= $index === 0 ? '' : 'hidden' ?>">
                          ✓
                        </div>
                      </div>
                      <p class="text-[11px] text-white/80 mt-1 line-clamp-2"><?= e($addrStr) ?></p>
                      <p class="text-[10px] text-white/40 mt-0.5"><?= e($addr['mobile_number']) ?></p>
                    </div>
                  </label>
                <?php endforeach; ?>
              <?php endif; ?>

              <button
                type="button"
                id="btn-add-new-address-toggle"
                onclick="document.getElementById('cart-new-address-form').classList.toggle('hidden')"
                class="w-full py-2.5 px-4 rounded-xl border border-dashed border-white/10 hover:border-zesto-orange/40 text-xs text-left font-semibold text-white/70 hover:text-white transition flex items-center justify-center gap-1.5 cursor-pointer bg-white/5"
              >
                <i data-lucide="plus" class="w-3.5 h-3.5 text-zesto-orange"></i>
                <span>Add New Address</span>
              </button>
            </div>

            <!-- Dark styled Kochi Map illustration container -->
            <div class="relative rounded-xl overflow-hidden h-44 md:h-auto min-h-[170px] bg-zesto-dark border border-white/10 flex flex-col justify-end">
              <div class="absolute inset-0 z-0 opacity-40">
                <div class="absolute inset-0 bg-[#0d1117] grid grid-cols-6 grid-rows-6 border border-white/5"></div>
                <div class="absolute h-0.5 left-0 right-0 top-1/4 bg-white/10 -rotate-12"></div>
                <div class="absolute h-0.5 left-0 right-0 top-2/3 bg-white/10 rotate-6"></div>
                <div class="absolute w-0.5 top-0 bottom-0 left-1/3 bg-white/10 rotate-12"></div>
                <div class="absolute w-0.5 top-0 bottom-0 left-2/3 bg-white/10 -rotate-3"></div>
                <div class="absolute w-1.5 h-1.5 rounded-full bg-zesto-amber/80 blur-[2px] top-4 left-8"></div>
                <div class="absolute w-1.5 h-1.5 rounded-full bg-zesto-amber/80 blur-[2px] top-20 left-20"></div>
                <div class="absolute w-1.5 h-1.5 rounded-full bg-zesto-amber/80 blur-[2px] bottom-10 left-44"></div>
              </div>

              <div class="absolute left-[45%] top-[40%] text-center z-10 flex flex-col items-center">
                <div class="w-8 h-8 rounded-full bg-zesto-orange/20 animate-ping absolute -top-1"></div>
                <i data-lucide="map-pin" class="w-6 h-6 text-zesto-orange fill-zesto-orange animate-bounce drop-shadow"></i>
                <span class="bg-[#1c2026] text-[8px] font-bold text-white px-1.5 py-0.5 rounded-md border border-white/15 shadow-2xl mt-1 select-none">
                  Your Feast Target
                </span>
              </div>

              <div class="relative z-10 bg-[#10141a]/90 px-3 py-2 border-t border-white/5 text-[10px] text-white/55 font-semibold text-center select-none">
                Delivering dynamically within Kerala limits
              </div>
            </div>
          </div>

          <!-- Add New Address Form (Hidden by default) -->
          <div id="cart-new-address-form" class="hidden bg-white/5 border border-white/10 p-5 rounded-2xl space-y-4">
            <h4 class="text-xs font-bold text-white flex items-center gap-1.5 border-b border-white/10 pb-2">
              <i data-lucide="sparkles" class="w-3.5 h-3.5 text-zesto-amber"></i>
              <span>Provide Late-Night Address details</span>
            </h4>
            <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="save_address">
              
              <div>
                <label class="block text-[9px] font-bold text-white/60 mb-1 uppercase">Full Name *</label>
                <input type="text" name="full_name" required placeholder="Alex Johnson" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
              </div>
              <div>
                <label class="block text-[9px] font-bold text-white/60 mb-1 uppercase">Mobile Number *</label>
                <input type="tel" name="mobile_number" required placeholder="+91 98765 43210" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
              </div>
              <div class="sm:col-span-2">
                <label class="block text-[9px] font-bold text-white/60 mb-1 uppercase">Street Address *</label>
                <input type="text" name="street" required placeholder="Main Street Road" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
              </div>
              <div>
                <label class="block text-[9px] font-bold text-white/60 mb-1 uppercase">Flat / House No.</label>
                <input type="text" name="flat_number" placeholder="Flat No. 402" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
              </div>
              <div>
                <label class="block text-[9px] font-bold text-white/60 mb-1 uppercase">Building Name</label>
                <input type="text" name="building_name" placeholder="Skyline Towers" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
              </div>
              <div>
                <label class="block text-[9px] font-bold text-white/60 mb-1 uppercase">Area / Locality *</label>
                <input type="text" name="area" required placeholder="Andheri West" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
              </div>
              <div>
                <label class="block text-[9px] font-bold text-white/60 mb-1 uppercase">City *</label>
                <input type="text" name="city" required value="<?= e(getCurrentCity()) ?>" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
              </div>
              <div>
                <label class="block text-[9px] font-bold text-white/60 mb-1 uppercase">State *</label>
                <input type="text" name="state" required placeholder="Kerala" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
              </div>
              <div>
                <label class="block text-[9px] font-bold text-white/60 mb-1 uppercase">Pincode *</label>
                <input type="text" name="pincode" required placeholder="682001" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
              </div>
              <div class="sm:col-span-2">
                <label class="block text-[9px] font-bold text-white/60 mb-1 uppercase">Address Type *</label>
                <select name="address_type" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange font-semibold">
                  <option value="home">Home</option>
                  <option value="work">Work</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div class="sm:col-span-2 flex gap-3 pt-2">
                <button type="submit" class="bg-zesto-orange text-white text-xs font-bold px-5 py-2.5 rounded-lg cursor-pointer hover:bg-zesto-orange/90 transition border-none">Save Address</button>
                <button type="button" onclick="document.getElementById('cart-new-address-form').classList.add('hidden')" class="bg-white/10 text-white text-xs font-bold px-5 py-2.5 rounded-lg cursor-pointer hover:bg-white/20 transition border-none">Cancel</button>
              </div>
            </form>
          </div>

          <input type="hidden" id="delivery-address-value" value="<?= !empty($savedAddresses) ? e(($savedAddresses[0]['flat_number'] ? $savedAddresses[0]['flat_number'].', ' : '') . ($savedAddresses[0]['building_name'] ? $savedAddresses[0]['building_name'].', ' : '') . $savedAddresses[0]['street'] . ', ' . $savedAddresses[0]['area'] . ', ' . $savedAddresses[0]['city'] . ' - ' . $savedAddresses[0]['pincode']) : '' ?>">
        </div>

        <!-- 2. Payment Method Selector -->
        <div class="glass-panel rounded-2xl p-6 border border-white/10 space-y-4">
          <h3 class="text-base font-display font-extrabold text-white flex items-center gap-2">
            <span class="text-zesto-orange">2.</span> Payment Method
          </h3>

          <div class="space-y-2.5">
            <!-- Credit/Debit Cards / Stripe -->
            <label class="label-payment flex items-center p-4 rounded-xl cursor-pointer transition border border-zesto-amber/70 bg-[#1c2026]">
              <input type="radio" name="payment_method" value="stripe" checked class="hidden">
              <div class="flex items-center gap-3">
                <span class="pay-check w-4.5 h-4.5 rounded-full border border-zesto-orange bg-zesto-orange text-white flex items-center justify-center">
                  <i data-lucide="check" class="w-3 h-3 stroke-[3]"></i>
                </span>
                <span class="text-xs font-semibold text-white">Credit / Debit Cards (Stripe)</span>
              </div>
              <div class="ml-auto flex gap-1.5">
                <span class="bg-white/5 text-[8px] font-bold text-white/50 px-1.5 py-0.5 rounded border border-white/10 uppercase">Visa</span>
                <span class="bg-white/5 text-[8px] font-bold text-white/50 px-1.5 py-0.5 rounded border border-white/10 uppercase">Master</span>
              </div>
            </label>

            <!-- Razorpay -->
            <label class="label-payment flex items-center justify-between p-4 rounded-xl cursor-pointer transition border border-white/5 bg-white/3 hover:bg-white/8">
              <input type="radio" name="payment_method" value="razorpay" class="hidden">
              <div class="flex items-center gap-3">
                <span class="pay-check w-4.5 h-4.5 rounded-full border border-white/20 flex items-center justify-center">
                </span>
                <span class="text-xs font-semibold text-white">Razorpay / UPI Wallet</span>
              </div>
              <span class="text-[10px] font-black italic tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-teal-400 to-indigo-400">
                UPI ▷
              </span>
            </label>

            <!-- COD -->
            <label class="label-payment flex items-center justify-between p-4 rounded-xl cursor-pointer transition border border-white/5 bg-white/3 hover:bg-white/8">
              <input type="radio" name="payment_method" value="COD" class="hidden">
              <div class="flex items-center gap-3">
                <span class="pay-check w-4.5 h-4.5 rounded-full border border-white/20 flex items-center justify-center">
                </span>
                <span class="text-xs font-semibold text-white">Cash on Delivery (Handover)</span>
              </div>
              <i data-lucide="hand-coins" class="w-4 h-4 text-white/40"></i>
            </label>
          </div>
        </div>
      </div>

      <!-- Right Side: Order Summary -->
      <div class="lg:col-span-1 sticky top-24">
        <div class="glass-panel rounded-2xl p-5 border border-white/10 space-y-5">
          <div>
            <h3 class="text-sm font-display font-extrabold text-white">Order Summary</h3>
            <p class="text-[10px] text-white/50 tracking-wider uppercase mt-0.5">Your items from cart</p>
          </div>

          <div class="space-y-4 max-h-[220px] overflow-y-auto pr-1">
            <?php foreach ($cart as $cartKey => $item): ?>
              <div class="flex gap-3 justify-between items-start text-xs border-b border-white/5 pb-3 relative group">
                <div class="flex gap-2">
                  <img 
                    src="<?= getFoodImage($item['image'] ?? '', $item['name']) ?>" 
                    alt="<?= e($item['name']) ?>"
                    class="w-10 h-10 object-cover rounded-lg border border-white/10"
                  />
                  <div>
                    <h4 class="font-semibold text-white line-clamp-1 pr-4"><?= e($item['name']) ?></h4>
                    <span class="text-[10px] text-white/50 block mt-0.5"><?= e($item['restaurant_name']) ?></span>
                    
                    <div class="flex items-center gap-2 mt-1">
                      <button onclick="updateCartQuantity('<?= e($cartKey) ?>', -1)" class="w-4 h-4 rounded bg-white/5 flex items-center justify-center text-white/60 hover:bg-white/20 hover:text-white border-none cursor-pointer">-</button>
                      <span class="text-white text-[10px] font-bold">x<?= $item['quantity'] ?></span>
                      <button onclick="updateCartQuantity('<?= e($cartKey) ?>', 1)" class="w-4 h-4 rounded bg-white/5 flex items-center justify-center text-white/60 hover:bg-white/20 hover:text-white border-none cursor-pointer">+</button>
                    </div>
                  </div>
                </div>
                <div class="flex flex-col items-end gap-1">
                  <span class="font-bold text-white">₹<?= $item['price'] * $item['quantity'] ?></span>
                  <button onclick="removeFromCart('<?= e($cartKey) ?>')" class="text-[9px] text-red-500 hover:underline border-none bg-transparent cursor-pointer hidden group-hover:block">Remove</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Promo Code Panel -->
          <div class="bg-white/5 border border-white/10 rounded-xl p-3 flex flex-col gap-2">
            <h3 class="text-[9px] font-bold text-white/50 uppercase tracking-wider">Apply Coupon</h3>
            <div class="flex gap-2">
              <input type="text" id="coupon-input" placeholder="e.g. WELCOME50" class="flex-1 bg-[#10141a] border border-white/10 text-white rounded-lg px-3 py-1.5 text-xs font-mono uppercase focus:outline-none focus:border-zesto-orange">
              <button onclick="applyCoupon()" class="bg-zesto-orange text-white text-[10px] font-bold px-3 rounded-lg hover:bg-zesto-orange/90 transition border-none cursor-pointer">
                APPLY
              </button>
            </div>
            <div id="coupon-status" class="hidden text-[10px] font-semibold"></div>
          </div>

          <div class="space-y-2.5 text-xs pt-2">
            <div class="flex justify-between text-white/60">
              <span>Subtotal</span>
              <span class="text-white">₹<?= $subtotal ?></span>
            </div>
            <div class="flex justify-between text-white/60">
              <span class="flex items-center gap-1">
                Delivery Fee
                <i data-lucide="alert-circle" class="w-3 h-3 text-zesto-amber cursor-help" onclick="Zesto.toast('Extra fee to support our amazing night riders!', 'info')"></i>
              </span>
              <span class="text-white">₹<?= $deliveryFee ?></span>
            </div>
            <div class="flex justify-between text-white/60">
              <span>Taxes</span>
              <span class="text-white">₹<?= $taxes ?></span>
            </div>
            <div id="bill-discount-row" class="hidden flex justify-between items-center text-zesto-cyan">
              <span>Coupon Discount</span>
              <span id="bill-discount-value" class="font-extrabold">-₹0.00</span>
            </div>
            
            <div class="border-t border-white/10 my-2 pb-1"></div>
            
            <div class="flex justify-between items-center text-base font-bold text-white">
              <span>Total</span>
              <div class="flex items-center gap-2">
                <span id="bill-grand-total" class="text-zesto-orange text-lg">₹<?= $total ?></span>
              </div>
            </div>
          </div>

          <button
            id="cart-order-btn"
            onclick="triggerCheckout(event)"
            class="w-full py-4 text-center bg-gradient-to-r from-zesto-orange to-zesto-amber font-extrabold text-sm text-[#402d00] rounded-full hover:opacity-95 text-white active:scale-95 transition-all shadow-lg shadow-zesto-orange/20 cursor-pointer border-none"
          >
            Place Order • <span id="btn-grand-total">₹<?= $total ?></span>
          </button>

          <p class="text-[9px] text-white/40 text-center leading-relaxed font-semibold">
            By placing this order you agree with Zesto's 2 AM lightning speed Thattukada compliance rules.
          </p>
        </div>
      </div>
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

  statusDiv.className = "text-[10px] font-semibold text-white/50";
  statusDiv.innerHTML = "Validating coupon...";
  statusDiv.classList.remove('hidden');

  try {
    const res = await fetch((window.ZESTO_BASE || '') + '/api/coupons/validate.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify({ code: code, subtotal: baseSubtotal })
    });
    const data = await res.json();

    if (data.success) {
      appliedCouponCode = data.code;
      statusDiv.className = "text-[10px] font-semibold text-zesto-cyan";
      statusDiv.innerHTML = "✓ " + data.message;
      
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
      lbl.className = "label-payment flex items-center justify-between p-4 rounded-xl cursor-pointer transition border border-white/5 bg-white/3 hover:bg-white/8";
      const chk = lbl.querySelector('.pay-check');
      chk.className = "pay-check w-4.5 h-4.5 rounded-full border border-white/20 flex items-center justify-center";
      chk.innerHTML = '';
    });
    const lbl = this.closest('label');
    lbl.className = "label-payment flex items-center justify-between p-4 rounded-xl cursor-pointer transition border border-zesto-amber/70 bg-[#1c2026]";
    const chk = lbl.querySelector('.pay-check');
    chk.className = "pay-check w-4.5 h-4.5 rounded-full border border-zesto-orange bg-zesto-orange text-white flex items-center justify-center";
    chk.innerHTML = '<i data-lucide="check" class="w-3 h-3 stroke-[3]"></i>';
    lucide.createIcons();
  });
});

// Dynamic Checkout Placement Checks
function triggerCheckout(event) {
  event?.preventDefault();
  event?.stopPropagation();
  const canCheckout = <?= $canCheckout ? 'true' : 'false' ?>;
  
  if (!canCheckout) {
    ZestoAuth.open({ checkout: true });
    return;
  }

  const addrRadio = document.querySelector('input[name="delivery_address_select"]:checked');
  const selectedAddr = addrRadio ? addrRadio.value : '';

  if (!selectedAddr) {
    Zesto.toast('Please select or create a delivery address.', 'error');
    return;
  }

  document.getElementById('delivery-address-value').value = selectedAddr;

  const payment = document.querySelector('input[name="payment_method"]:checked')?.value || 'stripe';
  
  // Trigger order placing
  placeOrder(payment, selectedAddr, appliedCouponCode);
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
