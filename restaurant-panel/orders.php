<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

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
    if ($statusFilter) { $sql .= " AND o.order_status=:status"; $params[':status'] = $statusFilter; }
    $sql .= " ORDER BY o.created_at DESC LIMIT 50";
    $stmt = db()->prepare($sql); $stmt->execute($params);
    $orders = $stmt->fetchAll();
}

$pageTitle = 'Orders — Restaurant Panel';
$extraJs   = [BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'restaurant'; $activePage = 'orders.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-2xl font-extrabold text-[#1b1c1c]">Order Queue</h1>
      <form method="GET" class="flex gap-2">
        <select name="status" onchange="this.form.submit()" class="zesto-input py-2 text-xs w-44">
          <option value="">All Orders</option>
          <?php foreach(['placed','preparing','out_for_delivery','delivered','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= (filter_input(INPUT_GET,'status',FILTER_SANITIZE_SPECIAL_CHARS)??'')===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <?php foreach ($orders as $ord): ?>
      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex flex-col gap-3">
        <div class="flex justify-between">
          <span class="font-bold text-[#a83300]"><?= e($ord['order_number']) ?></span>
          <span id="order-status-<?= $ord['id'] ?>" class="badge badge-<?= e($ord['order_status']) ?>"><?= e(str_replace('_',' ',$ord['order_status'])) ?></span>
        </div>
        <div class="text-sm">
          <p class="font-semibold"><?= e($ord['customer']) ?></p>
          <p class="text-xs text-gray-400 mt-0.5"><?= e(substr($ord['delivery_address'],0,60)) ?>...</p>
        </div>
        <div class="flex justify-between items-center">
          <span class="font-black text-[#a83300] text-lg"><?= formatPrice($ord['total']) ?></span>
          <select data-status-select data-order-id="<?= $ord['id'] ?>" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 outline-none cursor-pointer hover:border-[#a83300]">
            <?php foreach(['placed','preparing','out_for_delivery','delivered'] as $s): ?>
            <option value="<?= $s ?>" <?= $ord['order_status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <p class="text-xs text-gray-400"><?= date('M j, g:i A', strtotime($ord['created_at'])) ?></p>
      </div>
      <?php endforeach; ?>
      <?php if (empty($orders)): ?>
      <div class="col-span-2 text-center py-12 text-gray-400">No orders found.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php
$noFooter = true;
include __DIR__ . '/../includes/footer.php';
?>
