<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_DELIVERY_PARTNER);
$userId = getCurrentUser()['id'];
$dp = db()->prepare("SELECT * FROM delivery_partners WHERE user_id=:uid LIMIT 1");
$dp->execute([':uid' => $userId]);
$partner = $dp->fetch();

$monthlyEarnings = db()->prepare("
    SELECT DATE_FORMAT(o.updated_at,'%Y-%m') AS month, COUNT(o.id) AS deliveries, SUM(o.delivery_fee) AS earned
    FROM orders o WHERE o.delivery_partner_id=:uid AND o.order_status='delivered'
    GROUP BY month ORDER BY month DESC LIMIT 6
");
$monthlyEarnings->execute([':uid' => $userId]);
$earning = $monthlyEarnings->fetchAll();

$pageTitle = 'Earnings — Delivery Panel';
$sidebarType = 'delivery'; $activePage = 'earnings.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <h1 class="text-2xl font-extrabold text-[#1b1c1c] mb-8">My Earnings</h1>

    <?php if ($partner): ?>
    <div class="grid grid-cols-3 gap-5 mb-8">
      <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
        <p class="text-xs text-gray-400 font-bold uppercase mb-2">Total Earned</p>
        <p class="text-3xl font-black text-[#00c853]"><?= formatPrice($partner['total_earnings']) ?></p>
      </div>
      <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
        <p class="text-xs text-gray-400 font-bold uppercase mb-2">Total Deliveries</p>
        <p class="text-3xl font-black text-[#1b1c1c]"><?= number_format($partner['total_deliveries']) ?></p>
      </div>
      <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
        <p class="text-xs text-gray-400 font-bold uppercase mb-2">Rating</p>
        <p class="text-3xl font-black text-amber-500">★ <?= number_format($partner['rating'],1) ?></p>
      </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-gray-100"><h3 class="font-bold text-sm text-[#1b1c1c]">Monthly Earnings Breakdown</h3></div>
      <table class="w-full text-sm">
        <thead class="bg-[#f5f3f3]">
          <tr>
            <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Month</th>
            <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Deliveries</th>
            <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Earned</th>
            <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Avg/Delivery</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($earning as $e): ?>
          <tr class="hover:bg-[#f5f3f3]/50">
            <td class="px-4 py-3 font-semibold"><?= date('F Y', strtotime($e['month'].'-01')) ?></td>
            <td class="px-4 py-3"><?= $e['deliveries'] ?></td>
            <td class="px-4 py-3 font-bold text-[#00c853]"><?= formatPrice($e['earned']) ?></td>
            <td class="px-4 py-3"><?= $e['deliveries'] > 0 ? formatPrice($e['earned']/$e['deliveries']) : formatPrice(0) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($earning)): ?>
          <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No earnings data yet. Complete your first delivery!</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
