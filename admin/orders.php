<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_ADMIN);

$statusFilter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$search       = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

$sql    = "SELECT o.*, u.name AS customer_name, u.email AS customer_email, r.name AS restaurant_name FROM orders o JOIN users u ON u.id=o.user_id JOIN restaurants r ON r.id=o.restaurant_id WHERE 1=1";
$params = [];

if ($statusFilter) { $sql .= " AND o.order_status = :status"; $params[':status'] = $statusFilter; }
if ($search) { $sql .= " AND (o.order_number LIKE :q OR u.name LIKE :q2)"; $params[':q'] = "%$search%"; $params[':q2'] = "%$search%"; }
$sql .= " ORDER BY o.created_at DESC LIMIT 50";

$stmt = db()->prepare($sql); $stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Orders Management — Zesto Admin';
$extraJs   = [BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'admin'; $activePage = 'orders.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
      <h1 class="text-2xl font-extrabold text-[#1b1c1c]">Orders Management</h1>
      <form method="GET" class="flex gap-2 flex-wrap">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search order / customer..."
               class="zesto-input w-56 py-2 text-xs">
        <select name="status" onchange="this.form.submit()" class="zesto-input w-44 py-2 text-xs">
          <option value="">All Statuses</option>
          <?php foreach (['pending', 'accepted', 'preparing', 'ready_for_pickup', 'assigned_to_delivery', 'picked_up', 'out_for_delivery', 'delivered', 'cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn-primary py-2 px-4 text-xs">Filter</button>
      </form>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-[#f5f3f3]">
            <tr>
              <?php foreach(['Order #','Customer','Restaurant','Total','Payment','Status','Date','Actions'] as $h): ?>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase whitespace-nowrap"><?= $h ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($orders as $ord): ?>
            <tr class="hover:bg-[#f5f3f3]/50 transition-colors">
              <td class="px-4 py-4 font-bold text-[#a83300] whitespace-nowrap"><?= e($ord['order_number']) ?></td>
              <td class="px-4 py-4"><p class="font-semibold text-gray-800"><?= e($ord['customer_name']) ?></p><p class="text-xs text-gray-400"><?= e($ord['customer_email']) ?></p></td>
              <td class="px-4 py-4 text-gray-600"><?= e($ord['restaurant_name']) ?></td>
              <td class="px-4 py-4 font-bold"><?= formatPrice($ord['total']) ?></td>
              <td class="px-4 py-4"><span class="badge badge-<?= e($ord['payment_status']) ?>"><?= e($ord['payment_status']) ?></span></td>
              <td class="px-4 py-4"><span id="order-status-<?= $ord['id'] ?>" class="badge badge-<?= e($ord['order_status']) ?>"><?= e(str_replace('_',' ',$ord['order_status'])) ?></span></td>
              <td class="px-4 py-4 text-xs text-gray-400 whitespace-nowrap"><?= date('M j, g:i A', strtotime($ord['created_at'])) ?></td>
              <td class="px-4 py-4">
                <select id="status-select-<?= $ord['id'] ?>" data-order-id="<?= $ord['id'] ?>" data-status-select
                        class="text-xs border border-gray-200 rounded-lg px-2 py-1 outline-none cursor-pointer hover:border-[#a83300]">
                  <?php foreach(['pending', 'accepted', 'preparing', 'ready_for_pickup', 'assigned_to_delivery', 'picked_up', 'out_for_delivery', 'delivered', 'cancelled'] as $s): ?>
                  <option value="<?= $s ?>" <?= $ord['order_status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php
$noFooter = true;
include __DIR__ . '/../includes/footer.php';
?>
