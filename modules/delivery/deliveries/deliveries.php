<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_DELIVERY_PARTNER);
$userId = getCurrentUser()['id'];

// Get all assignments (accepted, completed, cancelled)
$deliveries = db()->prepare("
    SELECT o.order_number, o.order_status, o.total AS order_value,
           r.name AS restaurant_name, r.address AS restaurant_address,
           da.status AS assignment_status, da.total_distance, da.earnings, da.delivered_at, da.accepted_at
    FROM delivery_assignments da
    JOIN orders o ON o.id = da.order_id
    JOIN restaurants r ON r.id = o.restaurant_id
    WHERE da.delivery_partner_id = :uid
    ORDER BY da.assigned_at DESC LIMIT 50
");
$deliveries->execute([':uid' => $userId]);
$rows = $deliveries->fetchAll();

$pageTitle = 'My Deliveries — Delivery Panel';
$extraJs   = [BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'delivery'; $activePage = 'deliveries.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-5">
      <div>
        <span class="text-xs font-bold text-[#00c853] uppercase tracking-widest">Delivery Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-white mt-1">My Deliveries Log</h1>
        <p class="text-xs text-white/60 mt-1">Detailed history of all completed and past delivery assignments</p>
      </div>
    </div>

    <div class="glass-panel rounded-3xl border border-white/10 shadow-md shadow-black/20 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead class="bg-white/5 text-white/40 font-bold uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3.5 text-left">Order #</th>
              <th class="px-5 py-3.5 text-left">Restaurant</th>
              <th class="px-5 py-3.5 text-center">Distance Travelled</th>
              <th class="px-5 py-3.5 text-right">Earning</th>
              <th class="px-5 py-3.5 text-center">Lifecycle Status</th>
              <th class="px-5 py-3.5 text-right">Delivered Date/Time</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/10 font-semibold text-white/80">
            <?php foreach ($rows as $del): ?>
            <tr class="hover:bg-gray-50/50">
              <td class="px-5 py-4 font-bold text-zesto-orange"><?= e($del['order_number']) ?></td>
              <td class="px-5 py-4">
                <p class="font-extrabold text-white/90"><?= e($del['restaurant_name']) ?></p>
                <p class="text-[10px] text-white/40 font-medium mt-0.5"><?= e(substr($del['restaurant_address'], 0, 45)) ?>...</p>
              </td>
              <td class="px-5 py-4 text-center font-mono font-bold">
                <?= $del['total_distance'] ? number_format($del['total_distance'], 1) . ' KM' : '—' ?>
              </td>
              <td class="px-5 py-4 text-right font-black text-[#00c853]">
                <?= $del['earnings'] ? formatPrice($del['earnings']) : '—' ?>
              </td>
              <td class="px-5 py-4 text-center">
                <span class="badge badge-<?= e($del['order_status']) ?>">
                  <?= e(str_replace('_', ' ', $del['order_status'])) ?>
                </span>
              </td>
              <td class="px-5 py-4 text-right text-white/40 text-[10px]">
                <?= $del['delivered_at'] ? date('M j, Y - g:i A', strtotime($del['delivered_at'])) : ($del['accepted_at'] ? 'Accepted at ' . date('g:i A', strtotime($del['accepted_at'])) : '—') ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
            <tr>
              <td colspan="6" class="px-5 py-16 text-center text-white/40 font-bold">
                🏍 You have not claimed or completed any delivery tasks yet.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
