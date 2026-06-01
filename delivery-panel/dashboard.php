<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_DELIVERY_PARTNER);
$userId = getCurrentUser()['id'];

// Get delivery partner profile
$partner = db()->prepare("SELECT * FROM delivery_partners WHERE user_id=:uid LIMIT 1");
$partner->execute([':uid' => $userId]);
$dp = $partner->fetch();

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
    WHERE da.delivery_partner_id = :pid AND da.status = 'accepted' AND o.order_status NOT IN ('delivered', 'cancelled')
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
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout font-sans">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-5">
      <div>
        <span class="text-xs font-bold text-[#00c853] uppercase tracking-widest">Delivery Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-[#1b1c1c] mt-1">Partner Dashboard</h1>
        <p class="text-xs text-gray-500 mt-1">Welcome back, <?= e(getCurrentUser()['name']) ?>! Keep moving, keep earning.</p>
      </div>
      <?php if ($dp): ?>
      <div class="flex items-center gap-3">
        <span class="text-xs font-black px-3.5 py-1.5 rounded-full border bg-emerald-50 border-emerald-200 text-emerald-700">
          🟢 <?= $dp['is_available'] ? 'Online & Available' : 'Offline' ?>
        </span>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($dp && !$dp['is_approved']): ?>
    <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-3xl p-10 border border-amber-200 text-center max-w-2xl mx-auto shadow-sm my-10">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white text-3xl mb-6 shadow-sm">⏳</div>
      <h2 class="text-2xl font-black text-[#1b1c1c]">Application Under Review</h2>
      <p class="text-sm text-gray-650 mt-3 leading-relaxed font-semibold">
        Your onboarding documents and vehicle license details are currently being reviewed by the Zesto safety team. Accounts are usually activated within 24 hours.
      </p>
    </div>
    <?php else: ?>

    <!-- Partner KPIs -->
    <?php if ($dp): ?>
    <div class="grid grid-cols-3 gap-5 mb-8">
      <?php foreach ([['Total Deliveries', number_format($dp['total_deliveries']), '🏍'], ['Average Rating', number_format($dp['rating'], 1).' ★', '⭐'], ['Total Earnings', formatPrice($dp['total_earnings']), '💰']] as [$lbl, $val, $ico]): ?>
      <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-sm">
        <div class="flex justify-between items-start mb-2">
          <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider"><?= $lbl ?></p>
          <span class="text-xl"><?= $ico ?></span>
        </div>
        <p class="text-2xl font-black text-[#1b1c1c]"><?= $val ?></p>
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
    <div class="bg-white rounded-3xl border-2 border-emerald-500 p-6 md:p-8 mb-8 shadow-md">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-gray-100 pb-5 mb-5">
        <div>
          <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full uppercase tracking-wider">Active Delivery Tasks</span>
          <h2 class="text-xl font-black text-[#1b1c1c] mt-2">Order #<?= e($activeDelivery['order_number']) ?></h2>
          
          <div class="mt-3 space-y-2 text-xs font-semibold text-gray-700">
            <p>🍴 Restaurant: <strong class="text-gray-900"><?= e($activeDelivery['restaurant_name']) ?></strong></p>
            <p>📍 Pickup Address: <span class="text-gray-500 font-medium"><?= e($activeDelivery['restaurant_address']) ?></span></p>
            <p>🏠 Dropoff Area: <span class="text-gray-500 font-medium"><?= e($custArea) ?></span></p>
          </div>
        </div>
        
        <div class="text-right">
          <p class="text-[10px] text-gray-400 font-bold uppercase">Estimated Earnings</p>
          <p class="text-3xl font-black text-[#00c853] mt-1"><?= formatPrice($activeDelivery['earnings']) ?></p>
          <span class="text-[10px] text-gray-400 font-bold mt-1 block">Order Value: <?= formatPrice($activeDelivery['order_value']) ?></span>
          <span class="text-[10px] text-[#00c853] font-bold block">Distance: <?= number_format($activeDelivery['total_distance'], 1) ?> KM</span>
        </div>
      </div>

      <!-- Progressive timeline indicators -->
      <div class="grid grid-cols-4 gap-2 mb-8">
        <?php 
        $workflowStages = ['assigned_to_delivery', 'picked_up', 'out_for_delivery', 'delivered'];
        $activeIdx = array_search($oStatus, $workflowStages);
        if ($activeIdx === false) $activeIdx = 0;
        
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
          <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shadow-sm
                      <?= $done ? 'bg-[#00c853] text-white' : 'bg-gray-100 text-gray-400' ?>">
            <?= $idx + 1 ?>
          </div>
          <span class="text-[9px] font-bold text-center mt-2 <?= $current ? 'text-[#00c853]' : ($done ? 'text-gray-600' : 'text-gray-400') ?>">
            <?= $stageLabels[$idx] ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Swiggy-Style Workflow Buttons -->
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 bg-gray-50 rounded-2xl p-5 border border-gray-100">
        <!-- Navigate To Restaurant -->
        <button onclick="transitionActiveDelivery(<?= $activeDelivery['order_id'] ?>, 'assigned_to_delivery', this)" 
                class="py-3 px-4 rounded-xl font-bold text-xs shadow-sm transition-all cursor-pointer text-center
                       <?= $oStatus === 'assigned_to_delivery' ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-200/50 text-gray-400 border border-gray-100' ?>">
          Navigate to Restaurant
        </button>
        
        <!-- Picked Up -->
        <button onclick="transitionActiveDelivery(<?= $activeDelivery['order_id'] ?>, 'picked_up', this)" 
                class="py-3 px-4 rounded-xl font-bold text-xs shadow-sm transition-all cursor-pointer text-center
                       <?= $oStatus === 'picked_up' ? 'bg-amber-500 text-white hover:bg-amber-600' : ($oStatus === 'assigned_to_delivery' ? 'bg-white border border-[#00c853] text-[#00c853] hover:bg-[#00c853]/5' : 'bg-gray-100 text-gray-400') ?>"
                <?= ($oStatus !== 'assigned_to_delivery' && $oStatus !== 'picked_up') ? 'disabled' : '' ?>>
          Picked Up Food
        </button>
        
        <!-- Out For Delivery -->
        <button onclick="transitionActiveDelivery(<?= $activeDelivery['order_id'] ?>, 'out_for_delivery', this)" 
                class="py-3 px-4 rounded-xl font-bold text-xs shadow-sm transition-all cursor-pointer text-center
                       <?= $oStatus === 'out_for_delivery' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : ($oStatus === 'picked_up' ? 'bg-white border border-[#00c853] text-[#00c853] hover:bg-[#00c853]/5' : 'bg-gray-100 text-gray-400') ?>"
                <?= ($oStatus !== 'picked_up' && $oStatus !== 'out_for_delivery') ? 'disabled' : '' ?>>
          Out for Delivery
        </button>
        
        <!-- Delivered -->
        <button onclick="transitionActiveDelivery(<?= $activeDelivery['order_id'] ?>, 'delivered', this)" 
                class="py-3 px-4 rounded-xl font-bold text-xs shadow-sm transition-all cursor-pointer text-center
                       <?= ($oStatus === 'out_for_delivery') ? 'bg-[#00c853] text-white hover:bg-[#00b047]' : 'bg-gray-100 text-gray-400' ?>"
                <?= ($oStatus !== 'out_for_delivery') ? 'disabled' : '' ?>>
          Delivered
        </button>
      </div>
    </div>
    <?php endif; ?>

    <!-- AVAILABLE DELIVERIES IN AREA -->
    <div class="mb-10">
      <h2 class="text-xl font-black text-[#1b1c1c] mb-5 flex items-center gap-2">
        📦 Available Deliveries (<?= count($availableList) ?>)
        <span class="w-2.5 h-2.5 rounded-full bg-[#00c853] animate-pulse"></span>
      </h2>

      <?php if (empty($availableList)): ?>
      <div class="bg-white rounded-3xl border border-gray-150 p-12 text-center text-gray-400 font-bold text-sm">
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
        <div class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm hover:border-[#00c853] transition-all flex flex-col gap-4">
          <div class="flex justify-between items-start">
            <div>
              <span class="font-extrabold text-[#a83300] text-sm"><?= e($avail['order_number']) ?></span>
              <h3 class="font-black text-[#1b1c1c] text-base mt-1.5"><?= e($avail['restaurant_name']) ?></h3>
              <p class="text-xs text-gray-400 font-medium mt-0.5"><?= e($avail['restaurant_address']) ?></p>
            </div>
            <div class="text-right">
              <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">Est. Earnings</span>
              <span class="text-2xl font-black text-[#00c853]"><?= formatPrice($avail['earnings']) ?></span>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-2 bg-[#f5f3f3]/50 rounded-xl p-3 border border-gray-50 text-[10px] text-center font-bold text-gray-650">
            <div>
              <p class="text-gray-400 text-[8px] uppercase font-bold">Total Distance</p>
              <p class="text-xs text-gray-800 font-extrabold mt-0.5"><?= number_format($avail['total_distance'], 1) ?> KM</p>
            </div>
            <div>
              <p class="text-gray-400 text-[8px] uppercase font-bold">Customer Drop</p>
              <p class="text-xs text-gray-800 font-extrabold mt-0.5 truncate max-w-[80px] mx-auto" title="<?= e($availArea) ?>"><?= e($availArea) ?></p>
            </div>
            <div>
              <p class="text-gray-400 text-[8px] uppercase font-bold">Est. Earnings</p>
              <p class="text-xs text-[#00c853] font-extrabold mt-0.5"><?= formatPrice($avail['earnings']) ?></p>
            </div>
          </div>

          <div class="flex gap-2">
            <button onclick="handleDeliveryAccept(<?= $avail['order_id'] ?>, 'accepted', this)" 
                    class="btn-primary bg-[#00c853] hover:bg-[#00b047] text-white flex-1 justify-center py-3 rounded-xl font-bold tracking-wide text-xs cursor-pointer shadow-sm">
              Accept Delivery
            </button>
            <button onclick="handleDeliveryAccept(<?= $avail['order_id'] ?>, 'rejected', this)" 
                    class="btn-secondary border-gray-200 text-gray-500 hover:bg-gray-50 px-4 py-3 rounded-xl font-bold text-xs cursor-pointer">
              Reject
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- COMPLETED DELIVERIES SUMMARY -->
    <div class="bg-white rounded-3xl border border-gray-150 shadow-sm overflow-hidden mb-8 flex flex-col">
      <div class="p-5 border-b border-gray-100 bg-[#fdfdfd] flex justify-between items-center">
        <h3 class="font-bold text-sm text-[#1b1c1c]">🏍 Completed Deliveries (Recent)</h3>
        <a href="<?= BASE_URL ?>/delivery-panel/deliveries.php" class="text-xs text-[#00c853] font-bold hover:underline">View History →</a>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead class="bg-[#f5f3f3] text-gray-400 font-bold uppercase tracking-wider">
            <tr>
              <th class="text-left px-5 py-3.5">Order #</th>
              <th class="text-left px-5 py-3.5">Restaurant</th>
              <th class="text-center px-5 py-3.5">Distance</th>
              <th class="text-right px-5 py-3.5">Earnings</th>
              <th class="text-right px-5 py-3.5">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
            <?php foreach ($completedList as $c): ?>
            <tr>
              <td class="px-5 py-3 font-bold text-[#a83300]"><?= e($c['order_number']) ?></td>
              <td class="px-5 py-3 font-semibold"><?= e($c['restaurant_name']) ?></td>
              <td class="px-5 py-3 text-center"><?= number_format($c['total_distance'], 1) ?> KM</td>
              <td class="px-5 py-3 text-right font-black text-[#00c853]"><?= formatPrice($c['earnings']) ?></td>
              <td class="px-5 py-3 text-right text-[10px] text-gray-400"><?= date('M j, Y - g:i A', strtotime($c['updated_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($completedList)): ?>
            <tr>
              <td colspan="5" class="px-5 py-8 text-center text-gray-400 font-bold">No completed deliveries found in your log yet.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
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
include __DIR__ . '/../includes/footer.php';
?>
