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
<div class="admin-layout font-sans bg-zesto-dark text-[#dfe2eb] min-h-screen flex">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <h1 class="text-2xl font-extrabold text-white mb-8">My Earnings</h1>

    <?php if ($partner): ?>
    <div class="grid grid-cols-3 gap-5 mb-8">
      <div class="glass-panel rounded-2xl border border-white/10 p-6 shadow-md shadow-black/20">
        <p class="text-xs text-white/40 font-bold uppercase mb-2">Total Earned</p>
        <p class="text-3xl font-black text-[#f59e0b]"><?= formatPrice($partner['total_earnings']) ?></p>
      </div>
      <div class="glass-panel rounded-2xl border border-white/10 p-6 shadow-md shadow-black/20">
        <p class="text-xs text-white/40 font-bold uppercase mb-2">Total Deliveries</p>
        <p class="text-3xl font-black text-white"><?= number_format($partner['total_deliveries']) ?></p>
      </div>
      <div class="glass-panel rounded-2xl border border-white/10 p-6 shadow-md shadow-black/20">
        <p class="text-xs text-white/40 font-bold uppercase mb-2">Rating</p>
        <p class="text-3xl font-black text-amber-500">★ <?= number_format($partner['rating'],1) ?></p>
      </div>
    </div>
    <?php endif; ?>

    <div class="glass-panel rounded-2xl border border-white/10 shadow-md shadow-black/20 overflow-hidden">
      <div class="p-5 border-b border-white/10"><h3 class="font-bold text-sm text-white">Monthly Earnings Breakdown</h3></div>
      <table class="w-full text-sm">
        <thead class="bg-white/5">
          <tr>
            <th class="text-left px-4 py-3 text-xs font-bold text-white/40 uppercase">Month</th>
            <th class="text-left px-4 py-3 text-xs font-bold text-white/40 uppercase">Deliveries</th>
            <th class="text-left px-4 py-3 text-xs font-bold text-white/40 uppercase">Earned</th>
            <th class="text-left px-4 py-3 text-xs font-bold text-white/40 uppercase">Avg/Delivery</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
          <?php foreach ($earning as $e): ?>
          <tr class="hover:bg-white/5">
            <td class="px-4 py-3 font-semibold"><?= date('F Y', strtotime($e['month'].'-01')) ?></td>
            <td class="px-4 py-3"><?= $e['deliveries'] ?></td>
            <td class="px-4 py-3 font-bold text-[#f59e0b]"><?= formatPrice($e['earned']) ?></td>
            <td class="px-4 py-3"><?= $e['deliveries'] > 0 ? formatPrice($e['earned']/$e['deliveries']) : formatPrice(0) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($earning)): ?>
          <tr><td colspan="4" class="px-4 py-8 text-center text-white/40">No earnings data yet. Complete your first delivery!</td></tr>
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
