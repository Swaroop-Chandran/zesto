<?php
/**
 * Zesto — Order Tracking / Orders History Page
 * Also serves as the live order tracking screen (PHP port of React OrderTracking)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

$pageTitle   = 'My Orders — Zesto';
$description = 'Track your current order in real-time and view your complete order history.';
$extraJs     = [BASE_URL . '/assets/js/tracking.js'];

// Fetch latest order for tracking view
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
<!-- Auto-open auth drawer for unauthenticated visitors -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof ZestoAuth !== 'undefined') {
    ZestoAuth.open();
  }
});
</script>
<main class="flex-1 pb-16 md:pb-8">
<div class="max-w-2xl mx-auto px-6 py-24 text-center flex flex-col items-center gap-6">
  <div class="w-20 h-20 bg-[#ffdbd0]/70 rounded-full flex items-center justify-center text-3xl">📦</div>
  <h1 class="text-2xl font-black text-[#1b1c1c]">Sign in to view your orders</h1>
  <p class="text-sm text-gray-500 max-w-sm">Track your deliveries and view order history after signing in.</p>
  <button onclick="ZestoAuth.open()" class="btn-primary px-8">
    Sign In to Continue
  </button>
  <a href="<?= BASE_URL ?>/index.php" class="text-xs text-gray-400 hover:text-[#a83300] font-semibold">← Back to Home</a>
</div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php exit; ?>
<?php endif; ?>


<main class="flex-1 pb-16 md:pb-8">
<div class="max-w-[1280px] mx-auto px-4 md:px-10 py-6 md:py-10 font-sans text-[#1b1c1c]">

  <!-- ═══ LIVE TRACKING (if active order) ══════════════════════ -->
  <?php if ($activeOrder): ?>

  <!-- Simulation Banner -->
  <div class="mb-8 bg-[#ffdbd0]/50 border border-[#e5beb2] rounded-2xl p-4 flex flex-col sm:flex-row justify-between items-center gap-4">
    <div class="flex items-center gap-3">
      <span class="relative flex h-3 w-3 shrink-0">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 bg-[#a83300]"></span>
      </span>
      <div>
        <p class="text-xs font-bold text-[#a83300] uppercase tracking-wider">Live Order Tracking</p>
        <p class="text-xs text-gray-600 mt-0.5">Order #<?= e($activeOrder['order_number']) ?> from <?= e($activeOrder['restaurant_name']) ?></p>
      </div>
    </div>
    <div class="flex gap-2">
      <button id="sim-run-btn"
              class="bg-[#a83300] hover:bg-[#d24200] text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 shadow-sm">
        ▶ Run Simulation
      </button>
      <button id="sim-reset-btn"
              class="bg-white border border-gray-200 text-[#1b1c1c] px-3 py-2 rounded-lg text-xs font-bold hover:bg-gray-50 flex items-center justify-center">
        ↺
      </button>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 md:gap-12 mb-16">
    <!-- LEFT: Map + Chat -->
    <div class="lg:col-span-7 flex flex-col gap-6">

      <!-- Tracking Map -->
      <div class="tracking-map">
        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuD1V3Mwcf8elc7rZfVYmG1TSPjt2mMmwlNKVX3matjNgYAQZH3zBHB5vY_E8NhEZxDdR7mzwnYTemwa-w0xtV5lqc5hn3PBJMw6GZPxlLE-SREzFwjJFdkxOVkPWg2hMAlPIcLBKsQ1iMFVp8ArK060U_Dby1SIx3o7qfBiXDpLyOMnP1MV5o2vgyOW40P8YDQWU81QgFwNmixN74U6yqwwH8PHeLT5bci4_4wUd9jO3CmFveYKXNJaoAxcfV2A8WI4TI2FvI-0rvk"
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
            <span id="agent-badge">8 mins away</span>
          </div>
        </div>
      </div>

      <!-- Chat -->
      <section class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm flex flex-col gap-4">
        <div class="flex justify-between items-center border-b border-gray-50 pb-3">
          <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#a83300]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <h3 class="font-bold text-sm text-[#1b1c1c]">Secure Chat with Marcus Rodriguez</h3>
          </div>
          <span class="w-1.5 h-1.5 bg-[#00c853] rounded-full"></span>
        </div>
        <div id="chat-messages" class="h-48 overflow-y-auto px-1 space-y-3.5 text-xs text-left"></div>
        <form id="chat-form" class="flex gap-2 border-t border-gray-50 pt-3">
          <input id="chat-input" type="text" placeholder="Type message to Marcus..."
                 class="bg-[#f5f3f3] focus:bg-white text-sm px-4 py-2.5 rounded-xl flex-1 outline-none focus:ring-1 focus:ring-[#a83300] border-none text-gray-800">
          <button type="submit" class="bg-[#a83300] text-white p-2.5 rounded-xl hover:bg-[#d24200] active:scale-95 transition-transform">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          </button>
        </form>
      </section>
    </div>

    <!-- RIGHT: Timeline + Agent Card + Summary -->
    <div class="lg:col-span-5 flex flex-col gap-8">

      <!-- ETA & Status -->
      <section class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
        <div class="flex justify-between items-start mb-6 border-b border-gray-100 pb-4">
          <div>
            <p class="text-xs text-gray-500 font-medium">Estimated Arrival</p>
            <h2 id="tracking-eta" class="text-2xl font-extrabold text-[#1b1c1c] tracking-tight mt-0.5">12:45 PM</h2>
          </div>
          <span id="tracking-status" class="px-3 py-1 rounded-full text-xs font-extrabold bg-[#ffdbd0] text-[#a83300]">On Track</span>
        </div>

        <!-- Timeline Steps -->
        <div class="relative pl-8 space-y-6">
          <div class="relative">
            <div class="absolute -left-8 top-1.5 w-6 h-6 rounded-full bg-[#00c853] flex items-center justify-center text-white z-10 border-4 border-white">✓</div>
            <div class="absolute -left-5 top-7 w-[2px] bg-[#00c853] h-10"></div>
            <div><h4 class="font-bold text-sm text-[#1b1c1c]">Order Placed</h4><p class="text-xs text-gray-500 mt-1">12:10 PM • Payment Verified</p></div>
          </div>
          <div class="relative">
            <div class="absolute -left-8 top-1.5 w-6 h-6 rounded-full bg-[#00c853] flex items-center justify-center text-white z-10 border-4 border-white">✓</div>
            <div class="absolute -left-5 top-7 w-[2px] bg-[#00c853] h-10"></div>
            <div><h4 class="font-bold text-sm text-[#1b1c1c]">Preparing your meal</h4><p class="text-xs text-gray-500 mt-1">12:15 PM • Fresh culinary craft</p></div>
          </div>
          <div class="relative">
            <div class="absolute -left-8 top-1.5 w-6 h-6 rounded-full bg-[#a83300] flex items-center justify-center z-10 border-4 border-white">
              <div class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></div>
            </div>
            <div class="absolute -left-5 top-7 w-[2px] bg-gray-200 h-10"></div>
            <div>
              <h4 class="font-bold text-sm text-[#a83300]">Out for Delivery</h4>
              <p id="step-out-for-delivery" class="text-xs text-gray-500 mt-1">Delivery Agent is 8 mins away</p>
            </div>
          </div>
          <div class="relative">
            <div id="step4-dot" class="absolute -left-8 top-1.5 w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center z-10 border-4 border-white">✓</div>
            <div><h4 id="step4-label" class="font-bold text-sm text-gray-400">Order Delivered</h4><p class="text-xs text-gray-400 mt-1">Expected dropoff by 12:45 PM</p></div>
          </div>
        </div>
      </section>

      <!-- Agent Card -->
      <section class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
        <div class="flex gap-4 items-center">
          <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuB8N79NnzBkNjJlH_j1MDJj1is3ZLHe5zf4QNLtwbxOQq9elB6w1-Eq8iK8QPl_kecxA_EkIvhpm8OgWEd4ajqCgMGzg6E-hsv5yjY-_3geRtReum09madJb2FykVO-iBptXyO0aAo7QKeUi_6V3VGDQHR_-IvGjgdbC6S_F7Nn3-nU5Cndtj6Oa_nD4-OhDUQvWly8lPVokasK_Cutr4tewAcOGRELa8H2W5TiFlz-XitgBe7vNL4pPys9N4ZRNJm_xGddPR209i4"
               alt="Marcus Rodriguez" class="w-14 h-14 rounded-full object-cover border-2 border-[#ffdbd0] shrink-0" referrerpolicy="no-referrer">
          <div class="flex-1">
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Assigned Agent</p>
            <h4 class="font-bold text-sm text-[#1b1c1c]">Marcus Rodriguez</h4>
            <p class="text-[11px] font-bold text-gray-800 mt-0.5">★ 4.9 (2k+ deliveries)</p>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 mt-5">
          <button class="px-4 py-3 rounded-xl border border-gray-200 bg-[#f5f3f3] text-gray-800 flex items-center justify-center gap-2 text-xs font-bold hover:bg-gray-100 active:scale-95 transition-transform">
            💬 Query Message
          </button>
          <button id="call-driver-btn"
                  class="px-4 py-3 rounded-xl bg-[#a83300] text-white flex items-center justify-center gap-2 text-xs font-bold hover:bg-[#d24200] active:scale-95 transition-transform shadow-sm">
            📞 Call Marcus
          </button>
        </div>
      </section>
    </div>
  </div>

  <!-- CALL OVERLAY -->
  <div id="call-overlay" class="hidden fixed inset-0 z-[60] bg-black/84 flex items-center justify-center p-4 backdrop-blur-sm animate-fade-in font-sans">
    <div class="bg-[#303031] text-white w-full max-w-xs rounded-2xl p-6 text-center shadow-2xl border border-gray-800 flex flex-col items-center gap-6 animate-scale-up">
      <div class="relative">
        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuB8N79NnzBkNjJlH_j1MDJj1is3ZLHe5zf4QNLtwbxOQq9elB6w1-Eq8iK8QPl_kecxA_EkIvhpm8OgWEd4ajqCgMGzg6E-hsv5yjY-_3geRtReum09madJb2FykVO-iBptXyO0aAo7QKeUi_6V3VGDQHR_-IvGjgdbC6S_F7Nn3-nU5Cndtj6Oa_nD4-OhDUQvWly8lPVokasK_Cutr4tewAcOGRELa8H2W5TiFlz-XitgBe7vNL4pPys9N4ZRNJm_xGddPR209i4"
             alt="Marcus Rodriguez" class="w-24 h-24 rounded-full object-cover border-4 border-[#a83300]" referrerpolicy="no-referrer">
        <div class="absolute -bottom-1 -right-1 bg-[#00c853] p-1.5 rounded-full border-2 border-white">📞</div>
      </div>
      <div>
        <h3 class="font-black text-lg text-white">Marcus Rodriguez</h3>
        <p id="call-status" class="text-xs text-gray-400 mt-1 uppercase tracking-widest font-bold">Calling...</p>
      </div>
      <p id="call-detail" class="hidden text-xs text-[#00c853] font-bold">Connected speaking line active</p>
      <button id="hangup-btn" class="bg-red-500 hover:bg-red-600 text-white w-full py-3.5 rounded-xl font-bold text-sm tracking-wider shadow-md active:scale-95 transition-transform">
        HANG UP
      </button>
    </div>
  </div>

  <?php endif; // End activeOrder ?>

  <!-- ═══ ORDER HISTORY ════════════════════════════════════════ -->
  <section>
    <h2 class="text-2xl font-bold text-[#1b1c1c] tracking-tight mb-6">
      <?= $activeOrder ? 'Order History' : 'My Orders' ?>
    </h2>

    <?php if (!isLoggedIn()): ?>
    <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
      <div class="text-4xl mb-4">🔐</div>
      <h3 class="font-bold text-lg mb-2">Login to see your orders</h3>
      <p class="text-sm text-gray-500 mb-6">Sign in to track deliveries and view your order history.</p>
      <a href="<?= BASE_URL ?>/login.php" class="btn-primary">Sign In</a>
    </div>

    <?php elseif (empty($orderHistory)): ?>
    <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
      <div class="text-4xl mb-4">📦</div>
      <h3 class="font-bold text-lg mb-2">No orders yet</h3>
      <p class="text-sm text-gray-500 mb-6">Start exploring restaurants and place your first order!</p>
      <a href="<?= BASE_URL ?>/index.php" class="btn-primary">Browse Restaurants</a>
    </div>

    <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($orderHistory as $ord): ?>
      <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm flex flex-col sm:flex-row justify-between gap-4">
        <div class="flex flex-col gap-1">
          <div class="flex items-center gap-3">
            <span class="font-black text-[#a83300] text-sm"><?= e($ord['order_number']) ?></span>
            <span class="badge badge-<?= e($ord['order_status']) ?>"><?= e(str_replace('_', ' ', $ord['order_status'])) ?></span>
          </div>
          <p class="font-bold text-sm text-[#1b1c1c]"><?= e($ord['restaurant_name']) ?></p>
          <p class="text-xs text-gray-400"><?= date('M j, Y — g:i A', strtotime($ord['created_at'])) ?></p>
        </div>
        <div class="flex flex-col items-end gap-2 shrink-0 justify-between">
          <span class="font-black text-[#a83300] text-base"><?= formatPrice($ord['total']) ?></span>
          <a href="<?= BASE_URL ?>/checkout.php?order=<?= e($ord['order_number']) ?>"
             class="text-[10px] font-bold text-gray-500 hover:text-[#a83300]">View Receipt ➔</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

</div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
