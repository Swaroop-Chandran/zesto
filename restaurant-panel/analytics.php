<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_RESTAURANT_OWNER);
$ownerId = getCurrentUser()['id'];
$res = db()->prepare("SELECT * FROM restaurants WHERE owner_id=:oid LIMIT 1");
$res->execute([':oid' => $ownerId]);
$restaurant = $res->fetch();

$monthlyData = [];
if ($restaurant) {
    $stmt = db()->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS orders, SUM(total) AS revenue FROM orders WHERE restaurant_id=:rid AND payment_status='paid' GROUP BY month ORDER BY month DESC LIMIT 6");
    $stmt->execute([':rid' => $restaurant['id']]);
    $monthlyData = $stmt->fetchAll();
}

$pageTitle = 'Analytics — Restaurant Panel';
$sidebarType = 'restaurant'; $activePage = 'analytics.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <h1 class="text-2xl font-extrabold text-[#1b1c1c] mb-8">Analytics</h1>

    <?php if (!$restaurant): ?>
    <div class="bg-[#ffdbd0] rounded-2xl p-8 text-center">
      <p class="font-bold text-[#a83300]">No restaurant linked to your account.</p>
    </div>
    <?php else: ?>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-8">
      <div class="p-5 border-b border-gray-100"><h3 class="font-bold text-sm text-[#1b1c1c]">Monthly Performance</h3></div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-[#f5f3f3]">
            <tr>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Month</th>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Orders</th>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Revenue</th>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Avg Order Value</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($monthlyData as $row): ?>
            <tr class="hover:bg-[#f5f3f3]/50">
              <td class="px-4 py-3 font-semibold"><?= date('F Y', strtotime($row['month'].'-01')) ?></td>
              <td class="px-4 py-3"><?= $row['orders'] ?></td>
              <td class="px-4 py-3 font-bold text-[#a83300]"><?= formatPrice($row['revenue']) ?></td>
              <td class="px-4 py-3"><?= $row['orders'] > 0 ? formatPrice($row['revenue']/$row['orders']) : formatPrice(0) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($monthlyData)): ?>
            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No order data available yet.</td></tr>
            <?php endif; ?>
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
