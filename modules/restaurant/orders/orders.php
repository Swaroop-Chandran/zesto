<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_RESTAURANT_OWNER);
$ownerId = getCurrentUser()['id'];
$res = db()->prepare("SELECT * FROM restaurants WHERE owner_id=:oid LIMIT 1");
$res->execute([':oid' => $ownerId]);
$restaurant = $res->fetch();

$orders = [];
if ($restaurant) {
    $statusFilter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $sql = "SELECT o.*, u.name AS customer FROM orders o JOIN users u ON u.id=o.user_id WHERE o.restaurant_id=:rid";
    $params = [':rid' => $restaurant['id']];
    if ($statusFilter) { 
        $sql .= " AND o.order_status=:status"; 
        $params[':status'] = $statusFilter; 
    }
    $sql .= " ORDER BY o.created_at DESC LIMIT 50";
    $stmt = db()->prepare($sql); 
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
}

$workflow = [
    'pending' => 'Pending Acceptance',
    'accepted' => 'Accepted',
    'preparing' => 'Preparing',
    'ready_for_pickup' => 'Ready for Pickup',
    'assigned_to_delivery' => 'Partner Assigned',
    'picked_up' => 'Handed to Partner',
    'out_for_delivery' => 'Out for Delivery',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled/Rejected'
];

function renderCardStatusButtons($orderId, $status) {
    $html = '<div class="flex gap-2 w-full mt-4">';
    if ($status === 'pending') {
        $html .= '<button onclick="handleQuickStatus(' . $orderId . ', \'accepted\', this)" class="flex-1 py-3 px-4 bg-[#f59e0b] hover:bg-[#fbbf24] text-black rounded-full font-bold text-xs shadow-md cursor-pointer transition-all border-none">Accept Order</button>';
        $html .= '<button onclick="handleQuickStatus(' . $orderId . ', \'cancelled\', this)" class="py-3 px-4 border border-white/10 text-white/60 hover:bg-white/5 rounded-full font-bold text-xs cursor-pointer transition-all">Reject</button>';
    } elseif ($status === 'accepted') {
        $html .= '<button onclick="handleQuickStatus(' . $orderId . ', \'preparing\', this)" class="flex-1 py-3 px-4 bg-[#f59e0b] hover:bg-[#fbbf24] text-black rounded-full font-bold text-xs shadow-md cursor-pointer transition-all border-none">Start Preparing &amp; Cook</button>';
    } elseif ($status === 'preparing') {
        $html .= '<button onclick="handleQuickStatus(' . $orderId . ', \'ready_for_pickup\', this)" class="flex-1 py-3 px-4 bg-[#f59e0b] hover:bg-[#fbbf24] text-black rounded-full font-bold text-xs shadow-md cursor-pointer transition-all border-none">Food Ready for Pickup</button>';
    } elseif ($status === 'assigned_to_delivery') {
        $html .= '<button onclick="handleQuickStatus(' . $orderId . ', \'picked_up\', this)" class="flex-1 py-3 px-4 bg-[#f59e0b] hover:bg-[#fbbf24] text-black rounded-full font-bold text-xs shadow-md cursor-pointer transition-all border-none">Hand over to Courier</button>';
    } elseif ($status === 'ready_for_pickup') {
        $html .= '<span class="text-xs text-amber-400 bg-amber-500/10 px-4 py-3 rounded-full border border-amber-500/20 flex items-center justify-center gap-1.5 w-full font-bold"><span class="w-2 h-2 bg-amber-500 rounded-full animate-ping"></span> Waiting for Partner Acceptance...</span>';
    } elseif ($status === 'picked_up') {
        $html .= '<span class="text-xs text-blue-400 bg-blue-500/10 px-4 py-3 rounded-full border border-blue-500/20 flex items-center justify-center w-full font-bold">Courier Active (In Care)</span>';
    } elseif ($status === 'out_for_delivery') {
        $html .= '<span class="text-xs text-amber-400 bg-amber-500/10 px-4 py-3 rounded-full border border-amber-500/20 flex items-center justify-center w-full font-bold">Rider Out for Delivery</span>';
    } elseif ($status === 'delivered') {
        $html .= '<span class="text-xs text-emerald-400 bg-emerald-500/10 px-4 py-3 rounded-full border border-emerald-500/20 flex items-center justify-center gap-1 w-full font-bold">✓ Order Completed</span>';
    } else {
        $html .= '<span class="text-xs text-red-400 bg-red-500/10 px-4 py-3 rounded-full border border-red-500/20 flex items-center justify-center w-full font-bold">✗ Order Cancelled</span>';
    }
    $html .= '</div>';
    return $html;
}

$pageTitle = 'Orders — Restaurant Panel';
$extraJs   = [BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'restaurant'; $activePage = 'orders.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-5">
      <div>
        <span class="text-xs font-bold text-zesto-orange uppercase tracking-widest">Restaurant Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-white mt-1">Order Queue</h1>
        <p class="text-xs text-white/60 mt-1">Monitor real-time incoming delivery orders and update status instantly</p>
      </div>
      <form method="GET" class="flex gap-2">
        <select name="status" onchange="this.form.submit()" class="zesto-input py-2 text-xs w-48 glass-panel font-semibold">
          <option value="">All Orders</option>
          <?php foreach($workflow as $s => $label): ?>
          <option value="<?= $s ?>" <?= (filter_input(INPUT_GET,'status',FILTER_SANITIZE_SPECIAL_CHARS)??'')===$s?'selected':'' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <?php foreach ($orders as $ord): ?>
      <div class="glass-panel rounded-3xl border border-white/10 p-6 shadow-md shadow-black/20 flex flex-col justify-between hover:border-white/20 transition-all">
        <div>
          <div class="flex justify-between items-center mb-3">
            <span class="font-extrabold text-zesto-orange text-sm"><?= e($ord['order_number']) ?></span>
            <span id="order-status-<?= $ord['id'] ?>" class="badge badge-<?= e($ord['order_status']) ?>"><?= e(str_replace('_', ' ', $ord['order_status'])) ?></span>
          </div>
          <div class="text-sm">
            <p class="font-bold text-white/90 text-base"><?= e($ord['customer']) ?></p>
            <p class="text-xs text-white/60 mt-1 leading-relaxed"><?= e($ord['delivery_address']) ?></p>
          </div>
        </div>
        
        <div class="mt-4 border-t border-white/10 pt-4 flex flex-col gap-3">
          <div class="flex justify-between items-center">
            <span class="text-white/40 font-bold uppercase text-[9px]">Grand Total</span>
            <span class="font-black text-zesto-orange text-xl"><?= formatPrice($ord['total']) ?></span>
          </div>
          
          <!-- One-click status management interface -->
          <?= renderCardStatusButtons($ord['id'], $ord['order_status']) ?>
        </div>
        
        <p class="text-[10px] text-white/40 text-right mt-3 font-semibold"><?= date('M j, g:i A', strtotime($ord['created_at'])) ?></p>
      </div>
      <?php endforeach; ?>
      <?php if (empty($orders)): ?>
      <div class="col-span-2 glass-panel rounded-2xl border border-dashed border-white/20 text-center py-16 text-white/40 font-semibold text-sm">
        🏍 No orders found matching filter criteria.
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
async function handleQuickStatus(orderId, status, btn) {
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner" style="width:0.75rem;height:0.75rem;border-width:1.5px;display:inline-block;border-color:currentColor;"></span> Updating...`;
    
    try {
        const res = await fetch('<?= BASE_URL ?>/api/orders/status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrfToken() ?>' },
            body: JSON.stringify({ order_id: orderId, status: status })
        });
        const data = await res.json();
        if (data.success) {
            Zesto.toast(`Order status updated to: ${status}`, 'success');
            // Refresh order queue in 800ms
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
include __DIR__ . '/../../../includes/footer.php';
?>
