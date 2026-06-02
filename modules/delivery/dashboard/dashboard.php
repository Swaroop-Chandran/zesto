<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_DELIVERY_PARTNER);
$userId = getCurrentUser()['id'];

// Get delivery partner profile
$partner = db()->prepare("SELECT * FROM delivery_partners WHERE user_id=:uid LIMIT 1");
$partner->execute([':uid' => $userId]);
$dp = $partner->fetch();

// Calculate delivery partner stats
$totRatingsStmt = db()->prepare("SELECT COUNT(*) FROM order_reviews WHERE delivery_partner_id = :pid");
$totRatingsStmt->execute([':pid' => $userId]);
$totalRatings = (int)$totRatingsStmt->fetchColumn();

$compCount = db()->prepare("SELECT COUNT(*) FROM delivery_assignments WHERE delivery_partner_id = :pid AND status = 'completed'");
$compCount->execute([':pid' => $userId]);
$completedCount = (int)$compCount->fetchColumn();

$dispCount = db()->prepare("SELECT COUNT(*) FROM orders WHERE delivery_partner_id = :pid AND order_status IN ('cancelled', 'delivery_issue')");
$dispCount->execute([':pid' => $userId]);
$disputeCount = (int)$dispCount->fetchColumn();

$successRate = ($completedCount + $disputeCount > 0) 
    ? round(($completedCount / ($completedCount + $disputeCount)) * 100, 1) 
    : 100.0;

// Fetch recent delivery partner reviews
$revsStmt = db()->prepare("
    SELECT r.review_text, r.delivery_rating, r.created_at, u.name AS customer_name
    FROM order_reviews r
    JOIN users u ON u.id = r.customer_id
    WHERE r.delivery_partner_id = :pid AND r.review_text IS NOT NULL AND r.review_text != ''
    ORDER BY r.created_at DESC LIMIT 3
");
$revsStmt->execute([':pid' => $userId]);
$recentReviews = $revsStmt->fetchAll();

// 1. Available Deliveries (where assignment status is 'assigned')
$available = db()->prepare("
    SELECT o.id AS order_id, o.order_number, o.total AS order_value, o.created_at, o.delivery_address,
           r.name AS restaurant_name, r.address AS restaurant_address,
           da.id AS assignment_id, da.distance_to_restaurant, da.total_distance, da.earnings
    FROM delivery_assignments da
    JOIN orders o ON o.id = da.order_id
    JOIN restaurants r ON r.id = o.restaurant_id
    WHERE da.delivery_partner_id = :pid AND da.status = 'assigned' AND o.order_status = 'ready_for_pickup'
    ORDER BY o.created_at ASC
");
$available->execute([':pid' => $userId]);
$availableList = $available->fetchAll();

// 2. Active Deliveries (accepted but not completed/cancelled)
$active = db()->prepare("
    SELECT o.id AS order_id, o.order_number, o.total AS order_value, o.order_status, o.delivery_address,
           r.name AS restaurant_name, r.address AS restaurant_address,
           da.id AS assignment_id, da.distance_to_restaurant, da.total_distance, da.earnings
    FROM delivery_assignments da
    JOIN orders o ON o.id = da.order_id
    JOIN restaurants r ON r.id = o.restaurant_id
    WHERE da.delivery_partner_id = :pid AND da.status = 'accepted' AND o.order_status NOT IN ('completed', 'cancelled')
    LIMIT 1
");
$active->execute([':pid' => $userId]);
$activeDelivery = $active->fetch() ?: null;

// 3. Last 5 Completed Deliveries
$completed = db()->prepare("
    SELECT o.order_number, o.updated_at,
           r.name AS restaurant_name,
           da.total_distance, da.earnings
    FROM delivery_assignments da
    JOIN orders o ON o.id = da.order_id
    JOIN restaurants r ON r.id = o.restaurant_id
    WHERE da.delivery_partner_id = :pid AND da.status = 'completed'
    ORDER BY da.delivered_at DESC LIMIT 5
");
$completed->execute([':pid' => $userId]);
$completedList = $completed->fetchAll();

$pageTitle = 'Delivery Dashboard — Zesto';
$extraJs   = [BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'delivery'; $activePage = 'dashboard.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-5">
      <div>
        <span class="text-xs font-bold text-[#f59e0b] uppercase tracking-widest">Delivery Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-white mt-1">Partner Dashboard</h1>
        <p class="text-xs text-white/60 mt-1">Welcome back, <?= e(getCurrentUser()['name']) ?>! Keep moving, keep earning.</p>
      </div>
      <?php if ($dp): ?>
      <div class="flex items-center gap-3">
        <span class="text-xs font-black px-3.5 py-1.5 rounded-full border bg-amber-500/10 border-amber-500/20 text-amber-400">
          🟢 <?= $dp['is_available'] ? 'Online & Available' : 'Offline' ?>
        </span>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($dp && !$dp['is_approved']): ?>
    <div class="glass-panel rounded-3xl p-10 border border-amber-500/30 text-center max-w-2xl mx-auto shadow-md shadow-black/20 my-10 bg-amber-500/5">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-full glass-panel text-3xl mb-6 shadow-md shadow-black/20 border border-amber-500/20">⏳</div>
      <h2 class="text-2xl font-black text-[#f59e0b]">Application Under Review</h2>
      <p class="text-sm text-white/70 mt-3 leading-relaxed font-semibold">
        Your onboarding documents and vehicle license details are currently being reviewed by the Zesto safety team. Accounts are usually activated within 24 hours.
      </p>
    </div>
    <?php else: ?>

    <!-- Partner KPIs -->
    <?php if ($dp): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
      <?php foreach ([
          ['Total Deliveries', number_format($dp['total_deliveries']), '🏍'],
          ['Average Rating', number_format($dp['rating'], 1).' ★ ('.$totalRatings.')', '⭐'],
          ['Success Rate', $successRate.'%', '📈'],
          ['Total Earnings', formatPrice($dp['total_earnings']), '💰']
      ] as [$lbl, $val, $ico]): ?>
      <div class="glass-panel rounded-2xl border border-white/10 p-5 shadow-md shadow-black/20">
        <div class="flex justify-between items-start mb-2">
          <p class="text-[10px] text-white/40 font-bold uppercase tracking-wider"><?= $lbl ?></p>
          <span class="text-xl"><?= $ico ?></span>
        </div>
        <p class="text-xl md:text-2xl font-black text-white"><?= $val ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ACTIVE SWIGGY-STYLE WORKFLOW CARD -->
    <?php if ($activeDelivery): 
        $oStatus = $activeDelivery['order_status'];
        
        // Parse customer area
        $addrParts = explode(',', $activeDelivery['delivery_address']);
        $custArea = trim($addrParts[0]);
        if (count($addrParts) > 1) {
            $custArea .= ', ' . trim($addrParts[1]);
        }
    ?>
    <div class="glass-panel rounded-3xl border-2 border-amber-500/50 p-6 md:p-8 mb-8 shadow-md shadow-amber-500/5">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-white/10 pb-5 mb-5">
        <div>
          <span class="text-[10px] font-bold text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded-full uppercase tracking-wider">Active Delivery Tasks</span>
          <h2 class="text-xl font-black text-white mt-2">Order #<?= e($activeDelivery['order_number']) ?></h2>
          
          <div class="mt-3 space-y-2 text-xs font-semibold text-white/80">
            <p>🍴 Restaurant: <strong class="text-white"><?= e($activeDelivery['restaurant_name']) ?></strong></p>
            <p>📍 Pickup Address: <span class="text-white/60 font-medium"><?= e($activeDelivery['restaurant_address']) ?></span></p>
            <p>🏠 Dropoff Area: <span class="text-white/60 font-medium"><?= e($custArea) ?></span></p>
          </div>
        </div>
        
        <div class="text-right">
          <p class="text-[10px] text-white/40 font-bold uppercase">Estimated Earnings</p>
          <p class="text-3xl font-black text-[#f59e0b] mt-1"><?= formatPrice($activeDelivery['earnings']) ?></p>
          <span class="text-[10px] text-white/40 font-bold mt-1 block">Order Value: <?= formatPrice($activeDelivery['order_value']) ?></span>
          <span class="text-[10px] text-[#f59e0b] font-bold block">Distance: <?= number_format($activeDelivery['total_distance'], 1) ?> KM</span>
        </div>
      </div>

      <!-- Progressive timeline indicators -->
      <div class="grid grid-cols-4 gap-2 mb-8">
        <?php 
        $workflowStages = ['assigned_to_delivery', 'picked_up', 'out_for_delivery', 'awaiting_customer_confirmation'];
        $activeIdx = array_search($oStatus, $workflowStages);
        if ($activeIdx === false) {
            if ($oStatus === 'delivery_issue') $activeIdx = 3;
            else $activeIdx = 0;
        }
        
        $stageLabels = [
            'Navigate to Restaurant',
            'Picked Up Food',
            'Out for Delivery',
            'Delivered Successfully'
        ];
        
        foreach ($workflowStages as $idx => $stage): 
            $done = $idx <= $activeIdx;
            $current = $idx === $activeIdx;
        ?>
        <div class="flex flex-col items-center">
          <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shadow-md shadow-black/20
                      <?= $done ? 'bg-[#f59e0b] text-black' : 'bg-white/10 text-white/40' ?>">
            <?= $idx + 1 ?>
          </div>
          <span class="text-[9px] font-bold text-center mt-2 <?= $current ? 'text-[#f59e0b]' : ($done ? 'text-white/70' : 'text-white/40') ?>">
            <?= $stageLabels[$idx] ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Swiggy-Style Workflow Buttons -->
      <?php 
      $isWaiting = ($oStatus === 'awaiting_customer_confirmation');
      $hasIssue  = ($oStatus === 'delivery_issue');
      $isDisabled = ($isWaiting || $hasIssue);
      ?>
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 bg-white/5 rounded-2xl p-5 border border-white/10">
        <!-- Navigate To Restaurant -->
        <?php if ($oStatus === 'assigned_to_delivery'): ?>
        <button onclick="transitionActiveDelivery(<?= $activeDelivery['order_id'] ?>, 'assigned_to_delivery', this)" 
                class="py-3 px-4 rounded-full font-bold text-xs shadow-md shadow-black/20 transition-all cursor-pointer text-center bg-[#f59e0b] text-black hover:bg-[#fbbf24]">
          Navigate to Restaurant
        </button>
        <?php elseif ($activeIdx > 0): ?>
        <button disabled class="py-3 px-4 rounded-full font-bold text-xs border bg-amber-500/20 border-amber-500/30 text-amber-400 text-center cursor-default">
          Navigated to Restaurant ✓
        </button>
        <?php else: ?>
        <button disabled class="py-3 px-4 rounded-full font-bold text-xs border bg-white/5 border-white/10 text-white/30 text-center cursor-not-allowed">
          Navigate to Restaurant
        </button>
        <?php endif; ?>
        
        <!-- Picked Up -->
        <?php if ($oStatus === 'picked_up'): ?>
        <button onclick="transitionActiveDelivery(<?= $activeDelivery['order_id'] ?>, 'picked_up', this)" 
                class="py-3 px-4 rounded-full font-bold text-xs shadow-md shadow-black/20 transition-all cursor-pointer text-center bg-[#f59e0b] text-black hover:bg-[#fbbf24]">
          Picked Up Food
        </button>
        <?php elseif ($oStatus === 'assigned_to_delivery'): ?>
        <button onclick="transitionActiveDelivery(<?= $activeDelivery['order_id'] ?>, 'picked_up', this)" 
                class="py-3 px-4 rounded-full font-bold text-xs border border-[#f59e0b] text-[#f59e0b] hover:bg-[#f59e0b]/5 transition-all cursor-pointer text-center">
          Picked Up Food
        </button>
        <?php elseif ($activeIdx > 1): ?>
        <button disabled class="py-3 px-4 rounded-full font-bold text-xs border bg-amber-500/20 border-amber-500/30 text-amber-400 text-center cursor-default">
          Picked Up Food ✓
        </button>
        <?php else: ?>
        <button disabled class="py-3 px-4 rounded-full font-bold text-xs border bg-white/5 border-white/10 text-white/30 text-center cursor-not-allowed">
          Picked Up Food
        </button>
        <?php endif; ?>
        
        <!-- Out For Delivery -->
        <?php if ($oStatus === 'out_for_delivery'): ?>
        <button onclick="transitionActiveDelivery(<?= $activeDelivery['order_id'] ?>, 'out_for_delivery', this)" 
                class="py-3 px-4 rounded-full font-bold text-xs shadow-md shadow-black/20 transition-all cursor-pointer text-center bg-[#f59e0b] text-black hover:bg-[#fbbf24]">
          Out for Delivery
        </button>
        <?php elseif ($oStatus === 'picked_up'): ?>
        <button onclick="transitionActiveDelivery(<?= $activeDelivery['order_id'] ?>, 'out_for_delivery', this)" 
                class="py-3 px-4 rounded-full font-bold text-xs border border-[#f59e0b] text-[#f59e0b] hover:bg-[#f59e0b]/5 transition-all cursor-pointer text-center">
          Out for Delivery
        </button>
        <?php elseif ($activeIdx > 2): ?>
        <button disabled class="py-3 px-4 rounded-full font-bold text-xs border bg-amber-500/20 border-amber-500/30 text-amber-400 text-center cursor-default">
          Out for Delivery ✓
        </button>
        <?php else: ?>
        <button disabled class="py-3 px-4 rounded-full font-bold text-xs border bg-white/5 border-white/10 text-white/30 text-center cursor-not-allowed">
          Out for Delivery
        </button>
        <?php endif; ?>
        
        <!-- Delivered / Waiting Confirmation -->
        <?php if ($isWaiting): ?>
        <button disabled class="py-3 px-4 rounded-full font-bold text-xs border bg-amber-500/20 border-amber-500/30 text-amber-400 text-center cursor-not-allowed">
          Waiting for Customer...
        </button>
        <?php elseif ($hasIssue): ?>
        <button disabled class="py-3 px-4 rounded-full font-bold text-xs border bg-red-500/20 border-red-500/30 text-red-400 text-center cursor-not-allowed">
          Dispute Opened ⚠️
        </button>
        <?php elseif ($oStatus === 'out_for_delivery'): ?>
        <button onclick="transitionActiveDelivery(<?= $activeDelivery['order_id'] ?>, 'delivered', this)" 
                class="py-3 px-4 rounded-full font-bold text-xs shadow-md shadow-black/20 transition-all cursor-pointer text-center bg-[#f59e0b] text-black hover:bg-[#fbbf24]">
          Delivered
        </button>
        <?php else: ?>
        <button disabled class="py-3 px-4 rounded-full font-bold text-xs border bg-white/5 border-white/10 text-white/30 text-center cursor-not-allowed">
          Delivered
        </button>
        <?php endif; ?>
      </div>

      <?php if ($isWaiting): ?>
      <div class="mt-4 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center gap-3 text-xs text-amber-400 font-bold">
        <span class="text-lg">⏳</span>
        <div>
          <p>Order marked as Delivered. Courier earnings will be released automatically once the customer confirms receipt.</p>
        </div>
      </div>
      <?php elseif ($hasIssue): ?>
      <div class="mt-4 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center gap-3 text-xs text-red-400 font-bold animate-pulse">
        <span class="text-lg">⚠️</span>
        <div>
          <p>The customer reported an issue with this delivery. Payout has been held. Support team and kitchen have been notified.</p>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- AVAILABLE DELIVERIES IN AREA -->
    <div class="mb-10">
      <h2 class="text-xl font-black text-white mb-5 flex items-center gap-2">
        📦 Available Deliveries (<?= count($availableList) ?>)
        <span class="w-2.5 h-2.5 rounded-full bg-[#f59e0b] animate-pulse"></span>
      </h2>

      <?php if (empty($availableList)): ?>
      <div class="glass-panel rounded-3xl border border-white/10 p-12 text-center text-white/40 font-bold text-sm">
        🏍 No delivery tasks dispatched in your immediate area yet. Keep this panel open to receive real-time notifications!
      </div>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <?php foreach ($availableList as $avail): ?>
        <?php 
            $availAddrParts = explode(',', $avail['delivery_address']);
            $availArea = trim($availAddrParts[0]);
            if (count($availAddrParts) > 1) {
                $availArea .= ', ' . trim($availAddrParts[1]);
            }
        ?>
        <div class="glass-panel rounded-3xl border border-white/10 p-6 shadow-md shadow-black/20 hover:border-[#f59e0b]/50 transition-all flex flex-col gap-4">
          <div class="flex justify-between items-start">
            <div>
              <span class="font-extrabold text-zesto-orange text-sm"><?= e($avail['order_number']) ?></span>
              <h3 class="font-black text-white text-base mt-1.5"><?= e($avail['restaurant_name']) ?></h3>
              <p class="text-xs text-white/40 font-medium mt-0.5"><?= e($avail['restaurant_address']) ?></p>
            </div>
            <div class="text-right">
              <span class="text-[9px] font-bold text-white/40 uppercase tracking-wider block">Est. Earnings</span>
              <span class="text-2xl font-black text-[#f59e0b]"><?= formatPrice($avail['earnings']) ?></span>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-2 bg-white/5 rounded-xl p-3 border border-white/5 text-[10px] text-center font-bold text-white/70">
            <div>
              <p class="text-white/40 text-[8px] uppercase font-bold">Total Distance</p>
              <p class="text-xs text-white/90 font-extrabold mt-0.5"><?= number_format($avail['total_distance'], 1) ?> KM</p>
            </div>
            <div>
              <p class="text-white/40 text-[8px] uppercase font-bold">Customer Drop</p>
              <p class="text-xs text-white/90 font-extrabold mt-0.5 truncate max-w-[80px] mx-auto" title="<?= e($availArea) ?>"><?= e($availArea) ?></p>
            </div>
            <div>
              <p class="text-white/40 text-[8px] uppercase font-bold">Est. Earnings</p>
              <p class="text-xs text-[#f59e0b] font-extrabold mt-0.5"><?= formatPrice($avail['earnings']) ?></p>
            </div>
          </div>

          <div class="flex gap-2">
            <button onclick="handleDeliveryAccept(<?= $avail['order_id'] ?>, 'accepted', this)" 
                    class="flex-1 justify-center py-3 bg-[#f59e0b] hover:bg-[#fbbf24] text-black font-bold rounded-full tracking-wide text-xs cursor-pointer shadow-md shadow-black/20 transition-all">
              Accept Delivery
            </button>
            <button onclick="handleDeliveryAccept(<?= $avail['order_id'] ?>, 'rejected', this)" 
                    class="bg-white/10 hover:bg-white/20 text-white border border-white/10 px-6 py-3 rounded-full font-bold text-xs cursor-pointer transition-all">
              Reject
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- COMPLETED DELIVERIES SUMMARY -->
    <div class="glass-panel rounded-3xl border border-white/10 shadow-md shadow-black/20 overflow-hidden mb-8 flex flex-col">
      <div class="p-5 border-b border-white/10 bg-white/5 flex justify-between items-center">
        <h3 class="font-bold text-sm text-white">🏍 Completed Deliveries (Recent)</h3>
        <a href="<?= BASE_URL ?>/delivery-panel/deliveries.php" class="text-xs text-[#f59e0b] font-bold hover:underline">View History →</a>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead class="bg-white/5 text-white/40 font-bold uppercase tracking-wider">
            <tr>
              <th class="text-left px-5 py-3.5">Order #</th>
              <th class="text-left px-5 py-3.5">Restaurant</th>
              <th class="text-center px-5 py-3.5">Distance</th>
              <th class="text-right px-5 py-3.5">Earnings</th>
              <th class="text-right px-5 py-3.5">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/10 font-semibold text-white/80">
            <?php foreach ($completedList as $c): ?>
            <tr>
              <td class="px-5 py-3 font-bold text-zesto-orange"><?= e($c['order_number']) ?></td>
              <td class="px-5 py-3 font-semibold"><?= e($c['restaurant_name']) ?></td>
              <td class="px-5 py-3 text-center"><?= number_format($c['total_distance'], 1) ?> KM</td>
              <td class="px-5 py-3 text-right font-black text-[#f59e0b]"><?= formatPrice($c['earnings']) ?></td>
              <td class="px-5 py-3 text-right text-[10px] text-white/40"><?= date('M j, Y - g:i A', strtotime($c['updated_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($completedList)): ?>
            <tr>
              <td colspan="5" class="px-5 py-8 text-center text-white/40 font-bold">No completed deliveries logged in your log yet.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- RECENT CUSTOMER REVIEWS -->
    <div class="glass-panel rounded-3xl border border-white/10 shadow-md shadow-black/20 p-6 mb-8 flex flex-col gap-4">
      <h3 class="font-bold text-sm text-white pb-2 border-b">⭐ Recent Customer Feedback &amp; Reviews</h3>
      <?php if (empty($recentReviews)): ?>
      <div class="text-center text-white/40 font-bold text-xs py-6">
        No rated reviews received yet from customers. Excellent service builds higher ratings!
      </div>
      <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($recentReviews as $rev): ?>
        <div class="p-4 bg-white/5 rounded-2xl border border-white/10 flex flex-col gap-1 text-xs font-semibold">
          <div class="flex justify-between items-center font-extrabold">
            <span class="text-white/90"><?= e($rev['customer_name']) ?></span>
            <span class="text-amber-500">★ <?= number_format((float)($rev['delivery_rating'] ?? 0), 1) ?> rating</span>
          </div>
          <p class="text-white/70 italic mt-1">"<?= e($rev['review_text']) ?>"</p>
          <span class="text-[9px] text-white/40 font-bold block mt-1 uppercase"><?= date('M j, Y - g:i A', strtotime($rev['created_at'])) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php endif; ?>
  </div>
</div>

<!-- Custom delivery scripts -->
<script>
// Transition delivery step
async function transitionActiveDelivery(orderId, status, btn) {
    if (!confirm('Are you sure you want to transition this delivery to the next stage?')) return;
    
    let original = '';
    if (btn) {
        original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner" style="width:0.75rem;height:0.75rem;border-width:1.5px;display:inline-block;border-color:currentColor;"></span>`;
    }

    try {
        const res = await fetch('<?= BASE_URL ?>/api/delivery/status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrfToken() ?>' },
            body: JSON.stringify({ order_id: orderId, status: status })
        });
        const data = await res.json();
        if (data.success) {
            Zesto.toast(`Delivery updated: ${status.replace(/_/g, ' ')}`, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            Zesto.toast(data.message || 'Transition failed.', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = original; }
        }
    } catch (e) {
        Zesto.toast('Network error occurred.', 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = original; }
    }
}

// Accept or reject available order
async function handleDeliveryAccept(orderId, status, btn) {
    let original = '';
    if (btn) {
        original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner" style="width:0.75rem;height:0.75rem;border-width:1.5px;display:inline-block;border-color:currentColor;"></span>`;
    }

    try {
        const res = await fetch('<?= BASE_URL ?>/api/delivery/status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrfToken() ?>' },
            body: JSON.stringify({ order_id: orderId, status: status })
        });
        const data = await res.json();
        if (data.success) {
            Zesto.toast(`Delivery ${status === 'accepted' ? 'accepted!' : 'rejected successfully.'}`, 'success');
            setTimeout(() => location.reload(), 850);
        } else {
            Zesto.toast(data.message || 'Action failed.', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = original; }
        }
    } catch (e) {
        Zesto.toast('Network error occurred.', 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = original; }
    }
}

// Polling for incoming order notifications in real time
let lastUnreadCount = 0;
function startNotificationPoll() {
    setInterval(async () => {
        try {
            const res = await fetch('<?= BASE_URL ?>/api/delivery/notifications.php');
            const data = await res.json();
            if (data.success && data.unread_count > lastUnreadCount) {
                lastUnreadCount = data.unread_count;
                Zesto.toast('🔔 New available delivery alert! Check Available Deliveries.', 'success');
                // Play simple click sound or refresh
                setTimeout(() => location.reload(), 2000);
            }
        } catch (e) {}
    }, 5000); // Poll every 5s
}

document.addEventListener('DOMContentLoaded', startNotificationPoll);
</script>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
