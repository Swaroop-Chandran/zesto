<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_ADMIN);

// Stats
$stats = [];
$stats['total_orders']     = db()->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$stats['total_revenue']    = db()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid'")->fetchColumn();
$stats['total_users']      = db()->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$stats['total_restaurants']= db()->query("SELECT COUNT(*) FROM restaurants WHERE is_active=1")->fetchColumn();
$stats['pending_orders']   = db()->query("SELECT COUNT(*) FROM orders WHERE order_status='placed'")->fetchColumn();

// Recent orders
$recentOrders = db()->query("
    SELECT o.order_number, o.total, o.order_status, o.created_at, u.name AS customer, r.name AS restaurant
    FROM orders o JOIN users u ON u.id=o.user_id JOIN restaurants r ON r.id=o.restaurant_id
    ORDER BY o.created_at DESC LIMIT 10
")->fetchAll();

// Revenue data (last 7 days)
$revData = db()->query("
    SELECT DATE(created_at) AS day, SUM(total) AS revenue
    FROM orders WHERE payment_status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at) ORDER BY day ASC
")->fetchAll();

$chartLabels  = json_encode(array_column($revData, 'day'));
$chartRevenue = json_encode(array_map(fn($r) => round($r['revenue'], 2), $revData));

$pageTitle = 'Admin Dashboard — Zesto';
$extraJs   = ['https://cdn.jsdelivr.net/npm/chart.js', BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'admin';
$activePage  = 'dashboard.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="flex-1 overflow-auto">
    <div class="p-6 md:p-10 max-w-7xl">
      <!-- Header -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-2xl font-extrabold text-[#1b1c1c]">Admin Dashboard</h1>
          <p class="text-sm text-gray-500 mt-1">Welcome back, <?= e(getCurrentUser()['name']) ?>! Here's what's happening today.</p>
        </div>
        <span class="text-xs text-gray-400"><?= date('l, F j, Y') ?></span>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-10">
        <?php
        $statCards = [
          ['label'=>'Total Orders',    'value'=> number_format($stats['total_orders']),               'icon'=>'📦', 'color'=>'bg-blue-50    text-blue-600'],
          ['label'=>'Total Revenue',   'value'=>formatPrice($stats['total_revenue']),                  'icon'=>'💰', 'color'=>'bg-green-50   text-green-600'],
          ['label'=>'Customers',       'value'=> number_format($stats['total_users']),                 'icon'=>'👥', 'color'=>'bg-purple-50  text-purple-600'],
          ['label'=>'Restaurants',     'value'=> number_format($stats['total_restaurants']),           'icon'=>'🍴', 'color'=>'bg-orange-50  text-orange-600'],
        ];
        foreach ($statCards as $sc): ?>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
          <div class="flex items-center gap-3 mb-3">
            <div class="text-2xl"><?= $sc['icon'] ?></div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider"><?= $sc['label'] ?></p>
          </div>
          <p class="text-2xl font-extrabold text-[#1b1c1c]"><?= $sc['value'] ?></p>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Revenue Chart -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
          <h3 class="font-bold text-sm text-[#1b1c1c] mb-4">Revenue — Last 7 Days</h3>
          <div class="h-56">
            <canvas id="revenue-chart"></canvas>
          </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
          <h3 class="font-bold text-sm text-[#1b1c1c] mb-4">Quick Stats</h3>
          <div class="space-y-4">
            <div class="flex justify-between items-center p-3 bg-[#f5f3f3] rounded-xl">
              <span class="text-sm font-semibold text-gray-600">Pending Orders</span>
              <span class="font-extrabold text-[#a83300]"><?= $stats['pending_orders'] ?></span>
            </div>
            <div class="flex justify-between items-center p-3 bg-[#f5f3f3] rounded-xl">
              <span class="text-sm font-semibold text-gray-600">Active Restaurants</span>
              <span class="font-extrabold text-[#a83300]"><?= $stats['total_restaurants'] ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Orders Table -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center">
          <h3 class="font-bold text-sm text-[#1b1c1c]">Recent Orders</h3>
          <a href="<?= BASE_URL ?>/admin/orders.php" class="text-xs text-[#a83300] font-bold hover:underline">View All →</a>
        </div>
        <div class="overflow-x-auto">
          <table id="recent-orders-table" class="w-full text-sm">
            <thead class="bg-[#f5f3f3]">
              <tr>
                <th class="text-left px-5 py-3 text-xs font-bold text-gray-400 uppercase">Order #</th>
                <th class="text-left px-5 py-3 text-xs font-bold text-gray-400 uppercase">Customer</th>
                <th class="text-left px-5 py-3 text-xs font-bold text-gray-400 uppercase">Restaurant</th>
                <th class="text-left px-5 py-3 text-xs font-bold text-gray-400 uppercase">Total</th>
                <th class="text-left px-5 py-3 text-xs font-bold text-gray-400 uppercase">Status</th>
                <th class="text-left px-5 py-3 text-xs font-bold text-gray-400 uppercase">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php foreach ($recentOrders as $ord): ?>
              <tr class="hover:bg-[#f5f3f3]/50 transition-colors">
                <td class="px-5 py-4 font-bold text-[#a83300]"><?= e($ord['order_number']) ?></td>
                <td class="px-5 py-4 font-semibold text-gray-800"><?= e($ord['customer']) ?></td>
                <td class="px-5 py-4 text-gray-600"><?= e($ord['restaurant']) ?></td>
                <td class="px-5 py-4 font-bold"><?= formatPrice($ord['total']) ?></td>
                <td class="px-5 py-4"><span id="order-status-<?= $ord['order_number'] ?>" class="badge badge-<?= e($ord['order_status']) ?>"><?= e(str_replace('_', ' ', $ord['order_status'])) ?></span></td>
                <td class="px-5 py-4 text-gray-500 text-xs"><?= date('M j, g:i A', strtotime($ord['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  initRevenueChart(<?= $chartLabels ?>, <?= $chartRevenue ?>);
});
</script>
<?php
$noFooter = true;
include __DIR__ . '/../includes/footer.php';
?>
