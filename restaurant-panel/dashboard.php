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
if ($res) {
    // Total Orders
    $stmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = :rid");
    $stmt->execute([':rid' => $res['id']]);
    $stats['total_orders'] = $stmt->fetchColumn();

    // Today's Orders
    $stmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = :rid AND DATE(created_at) = CURDATE()");
    $stmt->execute([':rid' => $res['id']]);
    $stats['today_orders'] = $stmt->fetchColumn();

    // Total Revenue
    $stmt = db()->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE restaurant_id = :rid AND payment_status = 'paid'");
    $stmt->execute([':rid' => $res['id']]);
    $stats['total_revenue'] = $stmt->fetchColumn();

    // Pending Orders
    $stmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = :rid AND order_status = 'placed'");
    $stmt->execute([':rid' => $res['id']]);
    $stats['pending'] = $stmt->fetchColumn();

    // Recent Orders
    $stmt = db()->prepare("SELECT o.id, o.order_number, o.total, o.order_status, o.created_at, u.name AS customer FROM orders o JOIN users u ON u.id = o.user_id WHERE o.restaurant_id = :rid ORDER BY o.created_at DESC LIMIT 8");
    $stmt->execute([':rid' => $res['id']]);
    $recentOrders = $stmt->fetchAll();
} else {
    $recentOrders = [];
}

$pageTitle = 'Restaurant Dashboard — Zesto';
$extraJs   = [BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'restaurant'; $activePage = 'dashboard.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-2xl font-extrabold text-[#1b1c1c]"><?= $res ? e($res['name']) : 'Restaurant Dashboard' ?></h1>
        <p class="text-sm text-gray-500 mt-1">Manage orders and track performance</p>
      </div>
      <?php if ($res && $res['rating']): ?>
      <div class="flex items-center gap-1 bg-amber-50 px-3 py-1.5 rounded-full border border-amber-200">
        <span class="text-amber-500 font-bold">★</span>
        <span class="text-sm font-bold text-amber-700"><?= number_format($res['rating'],1) ?></span>
      </div>
      <?php endif; ?>
    </div>

    <?php if (!$res): ?>
    <div class="bg-[#ffdbd0] rounded-2xl p-8 text-center">
      <p class="font-bold text-[#a83300]">No restaurant linked to your account.</p>
      <p class="text-sm text-gray-600 mt-1">Please contact the admin to link your restaurant.</p>
    </div>
    <?php else: ?>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
      <?php foreach ([['Total Orders', number_format($stats['total_orders']),'📦'],["Today's Orders",$stats['today_orders'],'⚡'],['Total Revenue',formatPrice($stats['total_revenue']),'💰'],['Pending',$stats['pending'],'⏳']] as [$l,$v,$i]): ?>
      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <div class="text-2xl mb-2"><?= $i ?></div>
        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1"><?= $l ?></p>
        <p class="text-2xl font-extrabold text-[#1b1c1c]"><?= $v ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-gray-100"><h3 class="font-bold text-sm text-[#1b1c1c]">Recent Orders</h3></div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-[#f5f3f3]">
            <tr>
              <?php foreach(['Order #','Customer','Total','Status','Time','Update'] as $h): ?>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase"><?= $h ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($recentOrders as $ord): ?>
            <tr class="hover:bg-[#f5f3f3]/50">
              <td class="px-4 py-3 font-bold text-[#a83300]"><?= e($ord['order_number']) ?></td>
              <td class="px-4 py-3 font-semibold"><?= e($ord['customer']) ?></td>
              <td class="px-4 py-3 font-bold"><?= formatPrice($ord['total']) ?></td>
              <td class="px-4 py-3"><span id="order-status-<?= $ord['id'] ?>" class="badge badge-<?= e($ord['order_status']) ?>"><?= e(str_replace('_',' ',$ord['order_status'])) ?></span></td>
              <td class="px-4 py-3 text-xs text-gray-400"><?= date('g:i A', strtotime($ord['created_at'])) ?></td>
              <td class="px-4 py-3">
                <select id="status-select-<?= $ord['id'] ?>" data-status-select data-order-id="<?= $ord['id'] ?>" class="text-xs border border-gray-200 rounded-lg px-2 py-1 outline-none cursor-pointer hover:border-[#a83300]">
                  <?php foreach(['placed','preparing','out_for_delivery','delivered'] as $s): ?>
                  <option value="<?= $s ?>" <?= $ord['order_status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php
$noFooter = true;
include __DIR__ . '/../includes/footer.php';
?>
