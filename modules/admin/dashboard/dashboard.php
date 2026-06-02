<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_ADMIN);

// Core Statistics
$stats = [];
$stats['total_orders']      = db()->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$stats['total_revenue']     = db()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid'")->fetchColumn();
$stats['total_users']       = db()->query("SELECT COUNT(*) FROM users WHERE role='customer' AND account_status='active'")->fetchColumn();
$stats['total_restaurants'] = db()->query("SELECT COUNT(*) FROM restaurants WHERE is_active=1")->fetchColumn();
$stats['pending_orders']    = db()->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetchColumn();

// ── NEW DELIVERY STATS ──────────────────────────────────────────────
$stats['total_deliveries']  = db()->query("SELECT COUNT(*) FROM delivery_assignments WHERE status='completed'")->fetchColumn();
$stats['active_partners']   = db()->query("SELECT COUNT(*) FROM delivery_partners dp JOIN users u ON u.id=dp.user_id WHERE dp.is_approved=1 AND dp.is_available=1 AND u.account_status='active'")->fetchColumn();
$stats['disbursed_earnings'] = db()->query("SELECT COALESCE(SUM(total_earnings),0) FROM delivery_earnings")->fetchColumn();

// Calculate Average Confirmation Time
$avgConfSeconds = db()->query("
    SELECT AVG(TIMESTAMPDIFF(SECOND, delivered_at, confirmed_at)) 
    FROM delivery_assignments 
    WHERE status = 'completed' AND delivered_at IS NOT NULL AND confirmed_at IS NOT NULL
")->fetchColumn();

$avgConfTimeText = 'N/A';
if ($avgConfSeconds !== null && $avgConfSeconds !== false) {
    $seconds = (int)$avgConfSeconds;
    if ($seconds < 60) {
        $avgConfTimeText = $seconds . 's';
    } else {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        $avgConfTimeText = $minutes . 'm ' . $remainingSeconds . 's';
    }
}

// Recent orders
$recentOrders = db()->query("
    SELECT o.order_number, o.total, o.order_status, o.created_at, u.name AS customer, r.name AS restaurant
    FROM orders o 
    JOIN users u ON u.id=o.user_id 
    JOIN restaurants r ON r.id=o.restaurant_id
    ORDER BY o.created_at DESC LIMIT 8
")->fetchAll();

// Revenue data (last 7 days)
$revData = db()->query("
    SELECT DATE(created_at) AS day, SUM(total) AS revenue
    FROM orders WHERE payment_status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at) ORDER BY day ASC
")->fetchAll();

$chartLabels  = json_encode(array_column($revData, 'day'));
$chartRevenue = json_encode(array_map(fn($r) => round($r['revenue'], 2), $revData));

// Restaurant Performance Rank
$restaurantPerformance = db()->query("
    SELECT r.name, r.rating, COUNT(o.id) AS total_orders, COALESCE(SUM(o.total),0) AS revenue
    FROM restaurants r
    LEFT JOIN orders o ON o.restaurant_id = r.id AND o.payment_status = 'paid'
    GROUP BY r.id, r.name, r.rating
    ORDER BY revenue DESC LIMIT 5
")->fetchAll();

// Delivery Partner Performance Rank
$deliveryPerformance = db()->query("
    SELECT u.name, dp.rating, dp.total_deliveries, dp.total_earnings
    FROM delivery_partners dp
    JOIN users u ON u.id = dp.user_id
    ORDER BY dp.total_earnings DESC LIMIT 5
")->fetchAll();

$pageTitle = 'Admin Dashboard — Zesto';
$extraJs   = ['https://cdn.jsdelivr.net/npm/chart.js', BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'admin';
$activePage  = 'dashboard.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans bg-zesto-dark text-[#dfe2eb] min-h-screen flex">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>

  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-5">
      <div>
        <span class="text-xs font-bold text-zesto-orange uppercase tracking-widest">Admin Control Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-white mt-1">Admin Dashboard</h1>
        <p class="text-xs text-white/50 mt-1">Welcome back, <?= e(getCurrentUser()['name']) ?>! Real-time operations status overview.</p>
      </div>
      <span class="text-xs text-white/40 font-semibold"><?= date('l, F j, Y') ?></span>
    </div>

    <!-- Core KPI Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
      <?php
      $statCards = [
        ['Total Orders', number_format($stats['total_orders']), '📦', 'bg-blue-500/10 text-blue-400 border-blue-500/20'],
        ['Total Revenue', formatPrice($stats['total_revenue']), '💰', 'bg-green-500/10 text-green-400 border-green-500/20'],
        ['Customers', number_format($stats['total_users']), '👥', 'bg-purple-500/10 text-purple-400 border-purple-500/20'],
        ['Restaurants', number_format($stats['total_restaurants']), '🍴', 'bg-zesto-orange/10 text-zesto-orange border-zesto-orange/20'],
      ];
      foreach ($statCards as $sc): ?>
      <div class="glass-panel rounded-2xl border border-white/10 p-5 shadow-sm relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 <?= $sc[3] ?>"></div>
        <div class="flex justify-between items-start mb-2 relative z-10">
          <p class="text-[10px] text-white/40 font-bold uppercase tracking-wider"><?= $sc[0] ?></p>
          <span class="text-lg"><?= $sc[2] ?></span>
        </div>
        <p class="text-2xl font-black text-white relative z-10"><?= $sc[1] ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Delivery KPIs Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      <div class="glass-panel rounded-2xl border border-emerald-500/30 p-5 shadow-sm">
        <div class="flex justify-between items-start mb-2">
          <p class="text-[10px] text-emerald-400 font-bold uppercase tracking-wider">Completed Deliveries</p>
          <span class="text-lg">🏍</span>
        </div>
        <p class="text-2xl font-black text-emerald-400"><?= number_format($stats['total_deliveries']) ?></p>
      </div>
      <div class="glass-panel rounded-2xl border border-white/10 p-5 shadow-sm">
        <div class="flex justify-between items-start mb-2">
          <p class="text-[10px] text-white/40 font-bold uppercase tracking-wider">Active Delivery Partners</p>
          <span class="text-lg">🟢</span>
        </div>
        <p class="text-2xl font-black text-white"><?= number_format($stats['active_partners']) ?></p>
      </div>
      <div class="glass-panel rounded-2xl border border-white/10 p-5 shadow-sm">
        <div class="flex justify-between items-start mb-2">
          <p class="text-[10px] text-white/40 font-bold uppercase tracking-wider">Partner Earnings Disbursed</p>
          <span class="text-lg">💵</span>
        </div>
        <p class="text-2xl font-black text-white"><?= formatPrice($stats['disbursed_earnings']) ?></p>
      </div>
      <div class="glass-panel rounded-2xl border border-white/10 p-5 shadow-sm">
        <div class="flex justify-between items-start mb-2">
          <p class="text-[10px] text-white/40 font-bold uppercase tracking-wider">Avg Confirmation Time</p>
          <span class="text-lg">⏱️</span>
        </div>
        <p class="text-2xl font-black text-white"><?= $avgConfTimeText ?></p>
      </div>
    </div>

    <!-- Charts and Quick Stats Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <div class="lg:col-span-2 glass-panel rounded-2xl border border-white/10 p-6 shadow-sm flex flex-col">
        <h3 class="font-bold text-sm text-white mb-4 pb-2 border-b border-white/10">📈 Revenue — Last 7 Days</h3>
        <div class="h-64 flex-1">
          <canvas id="revenue-chart"></canvas>
        </div>
      </div>
      
      <div class="glass-panel rounded-2xl border border-white/10 p-6 shadow-sm flex flex-col justify-between">
        <div>
          <h3 class="font-bold text-sm text-white mb-4 pb-2 border-b border-white/10">⚡ Quick Operations Stats</h3>
          <div class="space-y-4 text-xs font-semibold">
            <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl border border-white/10">
              <span class="text-white/50">Pending Orders</span>
              <span class="font-black text-zesto-orange text-sm"><?= $stats['pending_orders'] ?></span>
            </div>
            <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl border border-white/10">
              <span class="text-white/50">Active Restaurants</span>
              <span class="font-black text-white/80 text-sm"><?= $stats['total_restaurants'] ?></span>
            </div>
            <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl border border-white/10">
              <span class="text-white/50">System Platform Fee Rate</span>
              <span class="font-black text-emerald-400 text-sm"><?= formatPrice(PLATFORM_FEE) ?></span>
            </div>
          </div>
        </div>
        <a href="<?= BASE_URL ?>/admin/delivery_settings.php" class="btn-primary justify-center bg-zesto-orange hover:bg-zesto-orange/90 text-white py-2.5 rounded-xl font-bold mt-4 text-xs flex items-center gap-2 border-none no-underline">
          Manage Delivery Rates Settings ⚙️
        </a>
      </div>
    </div>

    <!-- Performance rankings -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Restaurant Performance -->
      <div class="glass-panel rounded-2xl border border-white/10 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-white/10 bg-white/5"><h3 class="font-bold text-sm text-white">🍴 Restaurant Performance Rank</h3></div>
        <div class="overflow-x-auto flex-1">
          <table class="w-full text-xs">
            <thead class="bg-white/5 text-white/40 font-bold uppercase tracking-wider">
              <tr>
                <th class="px-4 py-3 text-left">Restaurant</th>
                <th class="px-4 py-3 text-center">Rating</th>
                <th class="px-4 py-3 text-center">Total Orders</th>
                <th class="px-4 py-3 text-right">Revenue</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10 font-semibold text-white/70">
              <?php foreach ($restaurantPerformance as $rp): ?>
              <tr>
                <td class="px-4 py-3 font-bold text-white"><?= e($rp['name']) ?></td>
                <td class="px-4 py-3 text-center text-amber-500">★ <?= number_format($rp['rating'], 1) ?></td>
                <td class="px-4 py-3 text-center"><?= $rp['total_orders'] ?> orders</td>
                <td class="px-4 py-3 text-right font-black text-zesto-orange"><?= formatPrice($rp['revenue']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Delivery Performance -->
      <div class="glass-panel rounded-2xl border border-white/10 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-white/10 bg-white/5"><h3 class="font-bold text-sm text-white">🏍 Delivery Partner Performance Rank</h3></div>
        <div class="overflow-x-auto flex-1">
          <table class="w-full text-xs">
            <thead class="bg-white/5 text-white/40 font-bold uppercase tracking-wider">
              <tr>
                <th class="px-4 py-3 text-left">Partner Executive</th>
                <th class="px-4 py-3 text-center">Rating</th>
                <th class="px-4 py-3 text-center">Deliveries</th>
                <th class="px-4 py-3 text-right">Earnings</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10 font-semibold text-white/70">
              <?php foreach ($deliveryPerformance as $dp): ?>
              <tr>
                <td class="px-4 py-3 font-bold text-white"><?= e($dp['name']) ?></td>
                <td class="px-4 py-3 text-center text-amber-500">★ <?= number_format($dp['rating'], 1) ?></td>
                <td class="px-4 py-3 text-center"><?= $dp['total_deliveries'] ?> done</td>
                <td class="px-4 py-3 text-right font-black text-emerald-400"><?= formatPrice($dp['total_earnings']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="glass-panel rounded-3xl border border-white/10 shadow-sm overflow-hidden flex flex-col">
      <div class="p-5 border-b border-white/10 flex justify-between items-center bg-white/5">
        <h3 class="font-bold text-sm text-white">Recent Global Orders</h3>
        <a href="<?= BASE_URL ?>/admin/orders.php" class="text-xs text-zesto-orange font-bold hover:underline">View All Orders →</a>
      </div>
      <div class="overflow-x-auto">
        <table id="recent-orders-table" class="w-full text-xs">
          <thead class="bg-white/5 text-white/40 font-bold uppercase tracking-wider">
            <tr>
              <th class="text-left px-5 py-3.5">Order #</th>
              <th class="text-left px-5 py-3.5">Customer</th>
              <th class="text-left px-5 py-3.5">Restaurant</th>
              <th class="text-left px-5 py-3.5">Total</th>
              <th class="text-left px-5 py-3.5">Status</th>
              <th class="text-left px-5 py-3.5">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/10 font-semibold text-white/70">
            <?php foreach ($recentOrders as $ord): ?>
            <tr class="hover:bg-white/5 transition-colors">
              <td class="px-5 py-4 font-bold text-zesto-orange"><?= e($ord['order_number']) ?></td>
              <td class="px-5 py-4 font-semibold text-white/80"><?= e($ord['customer']) ?></td>
              <td class="px-5 py-4 text-white/60"><?= e($ord['restaurant']) ?></td>
              <td class="px-5 py-4 font-bold text-white"><?= formatPrice($ord['total']) ?></td>
              <td class="px-5 py-4"><span id="order-status-<?= $ord['order_number'] ?>" class="bg-zesto-orange/10 text-zesto-orange border border-zesto-orange/20 px-2 py-1 rounded text-[10px] uppercase font-bold"><?= e(str_replace('_', ' ', $ord['order_status'])) ?></span></td>
              <td class="px-5 py-4 text-white/50 text-[10px]"><?= date('M j, g:i A', strtotime($ord['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
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
include __DIR__ . '/../../../includes/footer.php';
?>
