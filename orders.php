<?php
/**
 * Zesto — Order Tracking / Orders History Page
 * Includes the dynamic 8-step vertical order status tracking timeline
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

$pageTitle   = 'My Orders — Zesto';
$description = 'Track your active order in real-time and view your complete order history.';
$extraJs     = [BASE_URL . '/assets/js/tracking.js'];

// Fetch latest active order for tracking view (any status except delivered or cancelled)
$activeOrder = null;
if (isLoggedIn()) {
    $stmt = db()->prepare("
        SELECT o.*, r.name AS restaurant_name, r.slug AS restaurant_slug
        FROM orders o
        JOIN restaurants r ON r.id = o.restaurant_id
        WHERE o.user_id = :uid AND o.order_status NOT IN ('delivered','cancelled')
        ORDER BY o.created_at DESC LIMIT 1
    ");
    $stmt->execute([':uid' => getCurrentUser()['id']]);
    $activeOrder = $stmt->fetch() ?: null;
}

// Order history
$orderHistory = [];
if (isLoggedIn()) {
    $histStmt = db()->prepare("
        SELECT o.*, r.name AS restaurant_name
        FROM orders o
        JOIN restaurants r ON r.id = o.restaurant_id
        WHERE o.user_id = :uid
        ORDER BY o.created_at DESC LIMIT 20
    ");
    $histStmt->execute([':uid' => getCurrentUser()['id']]);
    $orderHistory = $histStmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<?php if (!isLoggedIn()): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof ZestoAuth !== 'undefined') {
    ZestoAuth.open();
  }
});
</script>
<main class="flex-1 pb-16 md:pb-8 bg-[#fcfbfa] font-sans">
  <div class="max-w-2xl mx-auto px-6 py-24 text-center flex flex-col items-center gap-6">
    <div class="w-20 h-20 bg-[#ffdbd0]/75 rounded-full flex items-center justify-center text-3xl">📦</div>
    <h1 class="text-2xl font-black text-[#1b1c1c]">Sign in to view your orders</h1>
    <p class="text-sm text-gray-500 max-w-sm">Track your deliveries and view order history after signing in.</p>
    <button onclick="ZestoAuth.open()" class="btn-primary px-8">
      Sign In to Continue
    </button>
    <a href="<?= BASE_URL ?>/index.php" class="text-xs text-gray-400 hover:text-[#a83300] font-semibold">← Back to Home</a>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; exit; endif; ?>

<!-- Print tracking meta variables for Javascript -->
<?php if ($activeOrder): ?>
<script>
window.ZESTO_ORDER_NUMBER = '<?= e($activeOrder['order_number']) ?>';
window.ZESTO_BASE = '<?= BASE_URL ?>';
</script>
<?php endif; ?>

<main class="flex-1 pb-16 md:pb-8 bg-[#fcfbfa] font-sans text-[#1b1c1c]">
<div class="max-w-[1280px] mx-auto px-4 md:px-10 py-6 md:py-10">

  <!-- ═══ LIVE TRACKING (if active order) ══════════════════════ -->
  <?php if ($activeOrder): ?>

  <!-- Active Tracking Banner -->
  <div class="mb-8 bg-[#ffdbd0]/30 border border-[#f0d4cc] rounded-3xl p-5 flex flex-col sm:flex-row justify-between items-center gap-4">
    <div class="flex items-center gap-3">
      <span class="relative flex h-3.5 w-3.5 shrink-0">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-[#a83300]"></span>
      </span>
      <div>
        <p class="text-[10px] font-bold text-[#a83300] uppercase tracking-wider">Live Order Status Tracker</p>
        <p class="text-sm text-gray-700 font-extrabold mt-0.5">Order <?= e($activeOrder['order_number']) ?> • <?= e($activeOrder['restaurant_name']) ?></p>
      </div>
    </div>
    <div class="flex gap-2">
      <span class="text-xs font-black bg-white px-3.5 py-1.5 rounded-full border border-gray-150 shadow-xs capitalize">
        Current State: <strong class="text-[#a83300]" id="banner-status"><?= e(str_replace('_',' ',$activeOrder['order_status'])) ?></strong>
      </span>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 md:gap-12 mb-16">
    <!-- LEFT: Map + Chat -->
    <div class="lg:col-span-7 flex flex-col gap-6">

      <!-- Map Display (Mock Visualization) -->
      <div class="tracking-map rounded-3xl border border-gray-150 overflow-hidden shadow-sm relative h-80 bg-gray-100">
        <img src="https://images.unsplash.com/photo-1524661135-423995f22d0b?w=1000&q=80"
             alt="Live tracking map" class="w-full h-full object-cover opacity-60 grayscale brightness-105" referrerpolicy="no-referrer">
        <div class="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-white via-white/40 to-transparent pointer-events-none"></div>

        <!-- Home marker -->
        <div class="absolute top-[25%] left-[25%] -translate-x-1/2 -translate-y-1/2 flex flex-col items-center z-10">
          <div class="bg-[#303031] text-white rounded-full p-2.5 shadow-md border-2 border-white text-sm">🏠</div>
          <div class="bg-white border border-gray-200 px-2.5 py-1 rounded-md shadow-sm mt-1.5">
            <p class="text-[10px] font-extrabold text-center text-gray-800">Home Dropoff</p>
          </div>
        </div>

        <!-- Restaurant marker -->
        <div class="absolute top-[67%] left-[76%] -translate-x-1/2 -translate-y-1/2 flex flex-col items-center z-10">
          <div class="bg-[#a83300] text-white rounded-full p-2.5 shadow-md border-2 border-white text-sm">🍴</div>
          <div class="bg-white border border-gray-200 px-2.5 py-1 rounded-md shadow-sm mt-1.5 max-w-[124px]">
            <p class="text-[10px] font-extrabold text-center text-[#a83300] truncate"><?= e($activeOrder['restaurant_name']) ?></p>
          </div>
        </div>

        <!-- Agent marker (controlled by JS) -->
        <div id="agent-marker" class="absolute transition-all duration-1000 -translate-x-1/2 -translate-y-1/2 flex flex-col items-center z-20" style="top:48%;left:46%">
          <div class="bg-[#00c853] text-white rounded-full p-3 shadow-xl border-2 border-white scale-110 animate-bounce">🏍</div>
        </div>
        <!-- Agent minutes away badge -->
        <div id="agent-badge-wrap" class="absolute" style="top:calc(48% + 56px);left:46%;transform:translateX(-50%)">
          <div class="bg-[#00c853] text-white text-[10px] font-bold px-3 py-1 rounded-full shadow-md flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span>
            <span id="agent-badge">Partner Active</span>
          </div>
        </div>
      </div>

      <!-- Secure Chat Panel -->
      <section class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm flex flex-col gap-4">
        <div class="flex justify-between items-center border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#a83300]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <h3 class="font-extrabold text-sm text-gray-800">Secure Dispatch speaking line</h3>
          </div>
          <span class="w-2 h-2 bg-[#00c853] rounded-full"></span>
        </div>
        <div id="chat-messages" class="h-48 overflow-y-auto px-1 space-y-3.5 text-xs text-left">
          <div class="max-w-[85%] rounded-2xl p-3 text-sm bg-gray-50 mr-auto rounded-tl-none text-gray-700">
            <p>Hi! Your order is being managed securely. We'll alert you the moment it leaves the kitchen.</p>
            <span class="text-[9px] text-gray-400 font-bold uppercase block mt-1">System</span>
          </div>
        </div>
        <form id="chat-form" class="flex gap-2 border-t border-gray-100 pt-3">
          <input id="chat-input" type="text" placeholder="Send courier a secure message..."
                 class="bg-gray-50 focus:bg-white text-xs px-4 py-3 rounded-xl flex-1 outline-none focus:ring-1 focus:ring-[#a83300] border border-gray-150 text-gray-800 font-semibold">
          <button type="submit" class="bg-[#a83300] text-white px-4 rounded-xl hover:bg-[#d24200] active:scale-95 transition-transform cursor-pointer">
            Send
          </button>
        </form>
      </section>
    </div>

    <!-- RIGHT: Dynamic Vertical Visual Timeline -->
    <div class="lg:col-span-5 flex flex-col gap-8">
      
      <!-- Timeline Section -->
      <section class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm">
        <div class="flex justify-between items-start mb-6 border-b border-gray-100 pb-4">
          <div>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Estimated Delivery</p>
            <h2 id="tracking-eta" class="text-2xl font-black text-[#1b1c1c] tracking-tight mt-0.5">30–40 Mins</h2>
          </div>
          <span id="tracking-status" class="px-3.5 py-1.5 rounded-full text-[10px] font-black uppercase bg-amber-50 text-amber-700 border border-amber-200">Processing</span>
        </div>

        <!-- Visual Timeline -->
        <div id="order-tracking-timeline" class="relative pl-8 space-y-6 text-left">
          <?php
          $allSteps = [
              'pending' => ['label' => 'Order Placed', 'desc' => 'Your order has been placed successfully.'],
              'accepted' => ['label' => 'Accepted', 'desc' => 'Restaurant has accepted and verified your order.'],
              'preparing' => ['label' => 'Preparing Food', 'desc' => 'Our chefs are cooking your fresh hot meal.'],
              'ready_for_pickup' => ['label' => 'Ready for Pickup', 'desc' => 'Food is packaged and ready to hand over.'],
              'assigned_to_delivery' => ['label' => 'Delivery Partner Assigned', 'desc' => 'A nearby partner has accepted your delivery.'],
              'picked_up' => ['label' => 'Picked Up', 'desc' => 'Partner has picked up your food carrier bag.'],
              'out_for_delivery' => ['label' => 'Out for Delivery', 'desc' => 'Courier is riding towards your home area.'],
              'delivered' => ['label' => 'Delivered', 'desc' => 'Delivered! Bon appétit! Enjoy your fresh meal!'],
          ];
          
          $statusKeys = array_keys($allSteps);
          $currentStatus = $activeOrder['order_status'];
          $currentIdx = array_search($currentStatus, $statusKeys);
          if ($currentIdx === false) $currentIdx = 0;
          
          $stepCount = count($allSteps);
          $i = 0;
          foreach ($allSteps as $statusKey => $stepInfo):
              $done = $i <= $currentIdx;
              $active = $i === $currentIdx;
              $isLast = $i === $stepCount - 1;
          ?>
          <div class="relative timeline-step-row" data-step-index="<?= $i ?>" data-status-key="<?= $statusKey ?>">
            <!-- Indicator circle -->
            <div class="absolute -left-8 top-1.5 w-6 h-6 rounded-full flex items-center justify-center border-4 border-white z-10 transition-all duration-500 font-bold text-[10px]
                        <?= $active ? 'bg-[#a83300] text-white shadow-md' : ($done ? 'bg-[#00c853] text-white' : 'bg-gray-100 text-gray-400') ?>">
              <?php if ($active): ?>
                ★
              <?php else: ?>
                ✓
              <?php endif; ?>
            </div>
            
            <!-- Connection Line -->
            <?php if (!$isLast): ?>
            <div class="absolute -left-5 top-7 w-[2px] h-10 transition-all duration-500
                        <?= $i < $currentIdx ? 'bg-[#00c853]' : 'bg-gray-100' ?>"></div>
            <?php endif; ?>
            
            <div>
              <h4 class="font-extrabold text-sm transition-colors duration-500
                         <?= $active ? 'text-[#a83300]' : ($done ? 'text-gray-800' : 'text-gray-400') ?>">
                <?= e($stepInfo['label']) ?>
              </h4>
              <p class="text-[11px] mt-0.5 text-gray-500 font-medium leading-relaxed">
                <?= e($stepInfo['desc']) ?>
              </p>
            </div>
          </div>
          <?php 
              $i++;
          endforeach; 
          ?>
        </div>
      </section>

      <!-- Agent Card -->
      <section class="bg-white rounded-3xl border border-gray-150 p-5 shadow-sm" id="agent-card-wrap">
        <div class="flex gap-4 items-center">
          <div class="w-14 h-14 rounded-full bg-[#ffdbd0] text-2xl flex items-center justify-center border-2 border-[#ffdbd0] shrink-0">🏍</div>
          <div class="flex-1">
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Assigned Delivery Executive</p>
            <h4 class="font-black text-sm text-[#1b1c1c]" id="agent-name">Searching for Partner...</h4>
            <p class="text-[10px] font-bold text-gray-500 mt-0.5" id="agent-rating">★ 4.9 Professional Fleet</p>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 mt-5">
          <button class="px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-800 flex items-center justify-center gap-2 text-[10px] font-bold hover:bg-gray-100 active:scale-95 transition-all">
            💬 Message
          </button>
          <button id="call-driver-btn"
                  class="px-4 py-2.5 rounded-xl bg-[#a83300] text-white flex items-center justify-center gap-2 text-[10px] font-bold hover:bg-[#d24200] active:scale-95 transition-all shadow-xs cursor-pointer">
            📞 Call Partner
          </button>
        </div>
      </section>
    </div>
  </div>

  <!-- CALL OVERLAY (MOCK CONNECTION) -->
  <div id="call-overlay" class="hidden fixed inset-0 z-[60] bg-black/80 flex items-center justify-center p-4 backdrop-blur-xs animate-fade-in font-sans">
    <div class="bg-[#303031] text-white w-full max-w-xs rounded-3xl p-6 text-center shadow-2xl border border-gray-800 flex flex-col items-center gap-6">
      <div class="relative">
        <div class="w-24 h-24 rounded-full bg-[#a83300] flex items-center justify-center text-4xl border-4 border-white mx-auto shadow-md">🏍</div>
        <div class="absolute -bottom-1 -right-1 bg-[#00c853] p-1.5 rounded-full border-2 border-white">📞</div>
      </div>
      <div>
        <h3 class="font-black text-lg text-white" id="call-agent-name">Delivery Partner</h3>
        <p id="call-status" class="text-xs text-gray-400 mt-1 uppercase tracking-widest font-bold">Calling...</p>
      </div>
      <p id="call-detail" class="hidden text-xs text-[#00c853] font-bold">Connected Speaking line active</p>
      <button id="hangup-btn" class="bg-red-500 hover:bg-red-650 text-white w-full py-3.5 rounded-xl font-bold text-sm tracking-wider shadow-md active:scale-95 transition-transform cursor-pointer">
        HANG UP
      </button>
    </div>
  </div>

  <?php endif; // End activeOrder ?>

  <!-- ═══ ORDER HISTORY ════════════════════════════════════════ -->
  <section class="mt-5">
    <h2 class="text-2xl font-black text-[#1b1c1c] tracking-tight mb-6">
      <?= $activeOrder ? 'Past Orders History' : 'My Orders' ?>
    </h2>

    <?php if (empty($orderHistory)): ?>
    <div class="bg-white rounded-3xl border border-gray-150 p-12 text-center shadow-xs">
      <div class="text-4xl mb-4">📦</div>
      <h3 class="font-black text-lg mb-2">No orders placed yet</h3>
      <p class="text-sm text-gray-500 mb-6">Explore our curated North and South Indian menus and order now!</p>
      <a href="<?= BASE_URL ?>/index.php" class="btn-primary px-8">Browse Kitchens</a>
    </div>

    <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($orderHistory as $ord): ?>
      <div class="bg-white rounded-3xl border border-gray-150 p-5 shadow-xs flex flex-col sm:flex-row justify-between gap-4 hover:border-gray-300 transition-all">
        <div class="flex flex-col gap-1 text-left">
          <div class="flex items-center gap-3 flex-wrap">
            <span class="font-black text-[#a83300] text-sm"><?= e($ord['order_number']) ?></span>
            <span class="badge badge-<?= e($ord['order_status']) ?>"><?= e(str_replace('_', ' ', $ord['order_status'])) ?></span>
          </div>
          <p class="font-extrabold text-sm text-[#1b1c1c] mt-1"><?= e($ord['restaurant_name']) ?></p>
          <p class="text-[10px] text-gray-400 font-medium mt-0.5"><?= date('M j, Y — g:i A', strtotime($ord['created_at'])) ?></p>
        </div>
        <div class="flex flex-col items-end gap-2 shrink-0 justify-between">
          <span class="font-black text-[#a83300] text-base"><?= formatPrice($ord['total']) ?></span>
          <a href="<?= BASE_URL ?>/checkout.php?order=<?= e($ord['order_number']) ?>"
             class="text-[10px] font-bold text-gray-500 hover:text-[#a83300] hover:underline">View Receipt Invoice ➔</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

</div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
