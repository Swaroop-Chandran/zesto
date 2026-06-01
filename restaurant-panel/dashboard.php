<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_RESTAURANT_OWNER);
$ownerId = getCurrentUser()['id'];
$restaurant = db()->prepare("SELECT * FROM restaurants WHERE owner_id = :oid LIMIT 1");
$restaurant->execute([':oid' => $ownerId]);
$res = $restaurant->fetch();

$stats = [];
$topItems = [];
$recentOrders = [];

if ($res) {
    $rid = $res['id'];

    // Today's Orders
    $stmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = :rid AND DATE(created_at) = CURDATE()");
    $stmt->execute([':rid' => $rid]);
    $stats['today_orders'] = $stmt->fetchColumn();

    // Today's Revenue
    $stmt = db()->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE restaurant_id = :rid AND payment_status = 'paid' AND DATE(created_at) = CURDATE()");
    $stmt->execute([':rid' => $rid]);
    $stats['today_revenue'] = $stmt->fetchColumn();

    // Pending Orders
    $stmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = :rid AND order_status = 'pending'");
    $stmt->execute([':rid' => $rid]);
    $stats['pending'] = $stmt->fetchColumn();

    // Preparing Orders
    $stmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = :rid AND order_status = 'preparing'");
    $stmt->execute([':rid' => $rid]);
    $stats['preparing'] = $stmt->fetchColumn();

    // Ready for Pickup Orders
    $stmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = :rid AND order_status = 'ready_for_pickup'");
    $stmt->execute([':rid' => $rid]);
    $stats['ready_for_pickup'] = $stmt->fetchColumn();

    // Top Selling Items
    $stmt = db()->prepare("
        SELECT oi.item_name, SUM(oi.quantity) AS total_qty, SUM(oi.quantity * oi.item_price) AS total_rev
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.restaurant_id = :rid AND o.payment_status = 'paid'
        GROUP BY oi.menu_item_id, oi.item_name
        ORDER BY total_qty DESC LIMIT 5
    ");
    $stmt->execute([':rid' => $rid]);
    $topItems = $stmt->fetchAll();

    // Recent Orders
    $stmt = db()->prepare("
        SELECT o.id, o.order_number, o.total, o.order_status, o.created_at, u.name AS customer 
        FROM orders o 
        JOIN users u ON u.id = o.user_id 
        WHERE o.restaurant_id = :rid 
        ORDER BY o.created_at DESC LIMIT 10
    ");
    $stmt->execute([':rid' => $rid]);
    $recentOrders = $stmt->fetchAll();
}

function renderTableStatusButtons($orderId, $status) {
    if ($status === 'pending') {
        return '<div class="flex gap-1.5 justify-center">
            <button onclick="handleQuickStatus(' . $orderId . ', \'accepted\', this)" class="py-1.5 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold text-[10px] shadow-xs cursor-pointer transition-all">Accept</button>
            <button onclick="handleQuickStatus(' . $orderId . ', \'cancelled\', this)" class="py-1.5 px-2.5 border border-red-200 text-red-500 hover:bg-red-555 hover:bg-red-50 rounded-lg font-bold text-[10px] cursor-pointer transition-all">Reject</button>
        </div>';
    } elseif ($status === 'accepted') {
        return '<button onclick="handleQuickStatus(' . $orderId . ', \'preparing\', this)" class="py-1.5 px-3 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-bold text-[10px] shadow-xs cursor-pointer transition-all w-full">Start Preparing</button>';
    } elseif ($status === 'preparing') {
        return '<button onclick="handleQuickStatus(' . $orderId . ', \'ready_for_pickup\', this)" class="py-1.5 px-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold text-[10px] shadow-xs cursor-pointer transition-all w-full">Ready for Pickup</button>';
    } elseif ($status === 'assigned_to_delivery') {
        return '<button onclick="handleQuickStatus(' . $orderId . ', \'picked_up\', this)" class="py-1.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-[10px] shadow-xs cursor-pointer transition-all w-full">Hand to Partner</button>';
    } elseif ($status === 'ready_for_pickup') {
        return '<span class="text-[9px] text-purple-650 font-extrabold bg-purple-50 px-2 py-1 rounded border border-purple-150 flex items-center justify-center gap-1 w-full"><span class="w-1.5 h-1.5 bg-purple-500 rounded-full animate-ping"></span> Waiting Dispatch</span>';
    } elseif ($status === 'picked_up') {
        return '<span class="text-[9px] text-blue-600 font-extrabold bg-blue-50 px-2 py-1 rounded border border-blue-150 flex items-center justify-center w-full">In Courier Care</span>';
    } elseif ($status === 'out_for_delivery') {
        return '<span class="text-[9px] text-emerald-600 font-extrabold bg-emerald-50 px-2 py-1 rounded border border-emerald-150 flex items-center justify-center w-full">Out for Delivery</span>';
    } elseif ($status === 'delivered') {
        return '<span class="text-[9px] text-emerald-800 font-extrabold bg-emerald-50/50 px-2 py-1 rounded border border-emerald-200 flex items-center justify-center w-full">✓ Completed</span>';
    } else {
        return '<span class="text-[9px] text-red-650 font-extrabold bg-red-50 px-2 py-1 rounded border border-red-150 flex items-center justify-center w-full">✗ Cancelled</span>';
    }
}

$pageTitle = 'Restaurant Dashboard — Zesto';
$extraJs   = [BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'restaurant'; $activePage = 'dashboard.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout font-sans">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-5">
      <div>
        <span class="text-xs font-bold text-[#a83300] uppercase tracking-widest">Restaurant Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-[#1b1c1c] mt-1"><?= $res ? e($res['name']) : 'Dashboard' ?></h1>
        <p class="text-xs text-gray-500 mt-1">Manage orders, update menu and track store performance</p>
      </div>
      <?php if ($res && $res['rating']): ?>
      <div class="flex items-center gap-1.5 bg-amber-50 px-3.5 py-1.5 rounded-full border border-amber-200">
        <span class="text-amber-500 font-extrabold text-sm">★</span>
        <span class="text-xs font-black text-amber-700"><?= number_format($res['rating'], 1) ?> (<?= $res['rating_count'] ?> reviews)</span>
      </div>
      <?php endif; ?>
    </div>

    <?php if (!$res): ?>
    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-3xl p-10 border border-orange-200 text-center max-w-2xl mx-auto shadow-sm my-10">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white text-3xl mb-6 shadow-sm">🍳</div>
      <h2 class="text-2xl font-black text-[#1b1c1c]">Welcome to Zesto Kitchens!</h2>
      <p class="text-sm text-gray-600 mt-3 leading-relaxed font-semibold">
        To start managing orders, selling your delicious dishes, and tracking your business, you need to create your restaurant profile first.
      </p>
      <div class="mt-8 flex justify-center">
        <a href="<?= BASE_URL ?>/restaurant-panel/onboard.php" class="btn-primary flex items-center gap-2 font-bold px-8 py-3 rounded-2xl bg-gradient-to-r from-orange-500 to-red-600 text-white shadow-md hover:scale-[1.02] active:scale-95 transition-all text-xs">
          Setup Your Restaurant Profile 🚀
        </a>
      </div>
    </div>
    <?php else: ?>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-5 mb-8">
      <?php
      $cards = [
        ["Today's Orders", $stats['today_orders'], '⚡', 'bg-blue-50 text-blue-600'],
        ["Today's Revenue", formatPrice($stats['today_revenue']), '💰', 'bg-green-50 text-green-600'],
        ['Pending Orders', $stats['pending'], '⏳', 'bg-amber-50 text-amber-600'],
        ['Preparing Orders', $stats['preparing'], '👨‍🍳', 'bg-indigo-50 text-indigo-600'],
        ['Ready for Pickup', $stats['ready_for_pickup'], '🏍', 'bg-emerald-50 text-emerald-600']
      ];
      foreach ($cards as [$l, $v, $i, $c]): ?>
      <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-sm">
        <div class="flex justify-between items-start mb-2">
          <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider"><?= $l ?></p>
          <span class="text-lg"><?= $i ?></span>
        </div>
        <p class="text-2xl font-extrabold text-[#1b1c1c]"><?= $v ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Recent Orders Queue -->
      <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-150 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-[#fdfdfd]">
          <h3 class="font-bold text-sm text-[#1b1c1c]">Recent Order Queue</h3>
          <a href="<?= BASE_URL ?>/restaurant-panel/orders.php" class="text-xs text-[#a83300] font-bold hover:underline">View All Queue →</a>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead class="bg-[#f5f3f3] text-gray-400 font-bold uppercase tracking-wider">
              <tr>
                <th class="text-left px-4 py-3.5">Order #</th>
                <th class="text-left px-4 py-3.5">Customer</th>
                <th class="text-left px-4 py-3.5">Total</th>
                <th class="text-left px-4 py-3.5">Status</th>
                <th class="text-left px-4 py-3.5">Time</th>
                <th class="text-center px-4 py-3.5" style="width: 170px;">One-Click Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
              <?php foreach ($recentOrders as $ord): ?>
              <tr class="hover:bg-gray-50/50">
                <td class="px-4 py-4 font-bold text-[#a83300]"><?= e($ord['order_number']) ?></td>
                <td class="px-4 py-4 font-semibold"><?= e($ord['customer']) ?></td>
                <td class="px-4 py-4 font-extrabold"><?= formatPrice($ord['total']) ?></td>
                <td class="px-4 py-4">
                  <span id="order-status-<?= $ord['id'] ?>" class="badge badge-<?= e($ord['order_status']) ?>">
                    <?= e(str_replace('_',' ',$ord['order_status'])) ?>
                  </span>
                </td>
                <td class="px-4 py-4 text-[10px] text-gray-400"><?= date('g:i A', strtotime($ord['created_at'])) ?></td>
                <td class="px-4 py-4 text-center">
                  <?= renderTableStatusButtons($ord['id'], $ord['order_status']) ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($recentOrders)): ?>
              <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-400">No orders received yet. Make sure your menu is active!</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top Selling Foods -->
      <div class="bg-white rounded-2xl border border-gray-150 shadow-sm p-6 flex flex-col">
        <h3 class="font-bold text-sm text-[#1b1c1c] mb-4 pb-2 border-b">🔥 Top Selling Dishes</h3>
        <?php if (empty($topItems)): ?>
        <div class="flex-1 flex items-center justify-center text-center text-gray-400 text-xs py-8">
          No item sales recorded yet.
        </div>
        <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($topItems as $item): ?>
          <div class="flex justify-between items-center text-xs font-semibold">
            <div>
              <p class="font-bold text-gray-800 text-sm"><?= e($item['item_name']) ?></p>
              <p class="text-[10px] text-gray-400 mt-0.5"><?= $item['total_qty'] ?> units sold</p>
            </div>
            <span class="font-black text-[#a83300] text-sm"><?= formatPrice($item['total_rev']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php endif; ?>
  </div>
</div>

<script>
async function handleQuickStatus(orderId, status, btn) {
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner" style="width:0.75rem;height:0.75rem;border-width:1.5px;display:inline-block;border-color:currentColor;"></span>`;
    
    try {
        const res = await fetch('<?= BASE_URL ?>/api/orders/status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrfToken() ?>' },
            body: JSON.stringify({ order_id: orderId, status: status })
        });
        const data = await res.json();
        if (data.success) {
            Zesto.toast(`Order status updated to: ${status}`, 'success');
            // Refresh dashboard in 800ms
            setTimeout(() => location.reload(), 800);
        } else {
            Zesto.toast(data.message || 'Update failed.', 'error');
            btn.disabled = false;
            btn.innerHTML = original;
        }
    } catch (e) {
        Zesto.toast('Network error occurred.', 'error');
        btn.disabled = false;
        btn.innerHTML = original;
    }
}
</script>
<?php
$noFooter = true;
include __DIR__ . '/../includes/footer.php';
?>
