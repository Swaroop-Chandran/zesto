<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_DELIVERY_PARTNER);
$userId = getCurrentUser()['id'];

$deliveries = db()->prepare("SELECT o.*, r.name AS restaurant_name FROM orders o JOIN restaurants r ON r.id=o.restaurant_id WHERE o.delivery_partner_id=:uid ORDER BY o.updated_at DESC LIMIT 50");
$deliveries->execute([':uid' => $userId]);
$rows = $deliveries->fetchAll();

$pageTitle = 'My Deliveries — Delivery Panel';
$extraJs   = [BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'delivery'; $activePage = 'deliveries.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <h1 class="text-2xl font-extrabold text-[#1b1c1c] mb-8">My Deliveries</h1>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-[#f5f3f3]">
            <tr>
              <?php foreach(['Order #','Restaurant','Address','Total','Status','Date','Update'] as $h): ?>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase"><?= $h ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($rows as $del): ?>
            <tr class="hover:bg-[#f5f3f3]/50">
              <td class="px-4 py-3 font-bold text-[#a83300]"><?= e($del['order_number']) ?></td>
              <td class="px-4 py-3 font-semibold"><?= e($del['restaurant_name']) ?></td>
              <td class="px-4 py-3 text-gray-600 max-w-[200px] truncate"><?= e($del['delivery_address']) ?></td>
              <td class="px-4 py-3 font-bold"><?= formatPrice($del['total']) ?></td>
              <td class="px-4 py-3"><span id="order-status-<?= $del['id'] ?>" class="badge badge-<?= e($del['order_status']) ?>"><?= e(str_replace('_',' ',$del['order_status'])) ?></span></td>
              <td class="px-4 py-3 text-xs text-gray-400"><?= date('M j, g:i A', strtotime($del['updated_at'])) ?></td>
              <td class="px-4 py-3">
                <?php if (!in_array($del['order_status'],['delivered','cancelled'])): ?>
                <select data-status-select data-order-id="<?= $del['id'] ?>" class="text-xs border border-gray-200 rounded-lg px-2 py-1 outline-none cursor-pointer hover:border-[#a83300]">
                  <?php foreach(['out_for_delivery','delivered'] as $s): ?>
                  <option value="<?= $s ?>" <?= $del['order_status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                  <?php endforeach; ?>
                </select>
                <?php else: ?>
                <span class="text-xs text-gray-400">Completed</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">No deliveries found.</td></tr>
            <?php endif; ?>
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
