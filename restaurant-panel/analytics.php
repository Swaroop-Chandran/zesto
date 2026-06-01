<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_RESTAURANT_OWNER);
$ownerId = getCurrentUser()['id'];
$res = db()->prepare("SELECT * FROM restaurants WHERE owner_id=:oid LIMIT 1");
$res->execute([':oid' => $ownerId]);
$restaurant = $res->fetch();

$stats = [];
$topFoods = [];
$dailyTrends = [];
$weeklyTrends = [];
$monthlyTrends = [];
$categoriesBreakdown = [];

if ($restaurant) {
    $rid = $restaurant['id'];

    // 1. Core KPIs
    $stmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = :rid");
    $stmt->execute([':rid' => $rid]);
    $stats['total_orders'] = (int)$stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COALESCE(SUM(total), 0) FROM orders WHERE restaurant_id = :rid AND payment_status = 'paid'");
    $stmt->execute([':rid' => $rid]);
    $stats['total_revenue'] = (float)$stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COALESCE(AVG(total), 0) FROM orders WHERE restaurant_id = :rid AND payment_status = 'paid'");
    $stmt->execute([':rid' => $rid]);
    $stats['avg_order_value'] = (float)$stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COUNT(DISTINCT user_id) FROM orders WHERE restaurant_id = :rid");
    $stmt->execute([':rid' => $rid]);
    $stats['customer_count'] = (int)$stmt->fetchColumn();

    // 2. Top Selling Foods
    $stmt = db()->prepare("
        SELECT oi.item_name, SUM(oi.quantity) AS total_qty, SUM(oi.quantity * oi.item_price) AS total_rev
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.restaurant_id = :rid AND o.payment_status = 'paid'
        GROUP BY oi.menu_item_id, oi.item_name
        ORDER BY total_qty DESC LIMIT 5
    ");
    $stmt->execute([':rid' => $rid]);
    $topFoods = $stmt->fetchAll();

    // 3. Trends: Daily (last 14 days)
    $stmt = db()->prepare("
        SELECT DATE(created_at) AS day, COUNT(*) AS orders, SUM(total) AS revenue
        FROM orders
        WHERE restaurant_id = :rid AND payment_status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ");
    $stmt->execute([':rid' => $rid]);
    $dailyTrends = $stmt->fetchAll();

    // 4. Trends: Weekly (last 8 weeks)
    $stmt = db()->prepare("
        SELECT DATE_FORMAT(created_at, '%X-W%V') AS week_label, COUNT(*) AS orders, SUM(total) AS revenue
        FROM orders
        WHERE restaurant_id = :rid AND payment_status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
        GROUP BY week_label
        ORDER BY week_label ASC
    ");
    $stmt->execute([':rid' => $rid]);
    $weeklyTrends = $stmt->fetchAll();

    // 5. Trends: Monthly (last 6 months)
    $stmt = db()->prepare("
        SELECT DATE_FORMAT(created_at, '%b %Y') AS month_label, DATE_FORMAT(created_at, '%Y-%m') as sort_month, COUNT(*) AS orders, SUM(total) AS revenue
        FROM orders
        WHERE restaurant_id = :rid AND payment_status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month_label, sort_month
        ORDER BY sort_month ASC
    ");
    $stmt->execute([':rid' => $rid]);
    $monthlyTrends = $stmt->fetchAll();

    // 6. Popular Categories
    $stmt = db()->prepare("
        SELECT c.name AS category_name, SUM(oi.quantity) AS total_qty
        FROM order_items oi
        JOIN menu_items mi ON mi.id = oi.menu_item_id
        JOIN categories c ON c.id = mi.category_id
        JOIN orders o ON o.id = oi.order_id
        WHERE o.restaurant_id = :rid AND o.payment_status = 'paid'
        GROUP BY c.id, c.name
        ORDER BY total_qty DESC
    ");
    $stmt->execute([':rid' => $rid]);
    $categoriesBreakdown = $stmt->fetchAll();

    // 7. Most Praised Foods
    $stmt = db()->prepare("
        SELECT oi.item_name, COUNT(oi.id) AS praise_count, SUM(oi.quantity) AS total_qty
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        JOIN order_reviews r ON r.order_id = o.id
        WHERE o.restaurant_id = :rid AND r.restaurant_rating >= 4
        GROUP BY oi.menu_item_id, oi.item_name
        ORDER BY praise_count DESC, total_qty DESC LIMIT 5
    ");
    $stmt->execute([':rid' => $rid]);
    $praisedFoods = $stmt->fetchAll();

    // 8. Recent Restaurant Reviews
    $stmt = db()->prepare("
        SELECT r.review_text, r.restaurant_rating, r.created_at, u.name AS customer_name
        FROM order_reviews r
        JOIN users u ON u.id = r.customer_id
        WHERE r.restaurant_id = :rid AND r.review_text IS NOT NULL AND r.review_text != ''
        ORDER BY r.created_at DESC LIMIT 5
    ");
    $stmt->execute([':rid' => $rid]);
    $restaurantReviews = $stmt->fetchAll();
}

// Convert trend data for Chart.js
$trendDays = [];
$trendRev = [];
$trendOrders = [];

foreach ($dailyTrends as $t) {
    $trendDays[] = date('M j', strtotime($t['day']));
    $trendRev[] = (float)$t['revenue'];
    $trendOrders[] = (int)$t['orders'];
}

$catLabels = [];
$catQty = [];
foreach ($categoriesBreakdown as $c) {
    $catLabels[] = $c['category_name'];
    $catQty[] = (int)$c['total_qty'];
}

$pageTitle = 'Analytics — Restaurant Panel';
$extraJs   = ['https://cdn.jsdelivr.net/npm/chart.js'];
$sidebarType = 'restaurant'; $activePage = 'analytics.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout font-sans">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    
    <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-5">
      <div>
        <span class="text-xs font-bold text-[#a83300] uppercase tracking-widest">Restaurant Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-[#1b1c1c] mt-1">Analytics Dashboard</h1>
        <p class="text-xs text-gray-500 mt-1">Detailed tracking of order volume, revenue streams, and product popularity</p>
      </div>
    </div>

    <?php if (!$restaurant): ?>
    <div class="bg-[#ffdbd0] rounded-2xl p-8 text-center border border-[#ffdbd0]">
      <p class="font-bold text-[#a83300]">No restaurant linked to your account.</p>
    </div>
    <?php else: ?>

    <!-- KPI Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
      <?php
      $kpis = [
        ['Total Orders', number_format($stats['total_orders']), '📦', 'bg-blue-50'],
        ['Total Revenue', formatPrice($stats['total_revenue']), '💰', 'bg-green-50'],
        ['Avg Order Value', formatPrice($stats['avg_order_value']), '📈', 'bg-amber-50'],
        ['Total Customers', number_format($stats['customer_count']), '👥', 'bg-indigo-50']
      ];
      foreach ($kpis as [$lbl, $val, $ico, $cls]): ?>
      <div class="bg-white rounded-2xl border border-gray-150 p-6 shadow-sm">
        <div class="flex justify-between mb-3 items-start">
          <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider"><?= $lbl ?></p>
          <span class="text-xl"><?= $ico ?></span>
        </div>
        <p class="text-2xl font-black text-[#1b1c1c]"><?= $val ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
      <!-- Trends Chart -->
      <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-150 p-6 shadow-sm flex flex-col">
        <h3 class="font-bold text-sm text-[#1b1c1c] mb-4 pb-2 border-b">📈 Revenue &amp; Order Trends (Last 14 Days)</h3>
        <div class="h-64 flex-1">
          <canvas id="trendsChart"></canvas>
        </div>
      </div>
      
      <!-- Category breakdown -->
      <div class="bg-white rounded-2xl border border-gray-150 p-6 shadow-sm flex flex-col">
        <h3 class="font-bold text-sm text-[#1b1c1c] mb-4 pb-2 border-b">🍕 Popular Categories</h3>
        <div class="h-64 flex-1 relative flex items-center justify-center">
          <?php if (empty($categoriesBreakdown)): ?>
          <p class="text-xs text-gray-400">No category statistics found.</p>
          <?php else: ?>
          <canvas id="categoryChart"></canvas>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Details Tables Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Top Foods table -->
      <div class="bg-white rounded-2xl border border-gray-150 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-gray-100 bg-[#fdfdfd]"><h3 class="font-bold text-sm text-[#1b1c1c]">🔥 Top Selling Dishes</h3></div>
        <div class="overflow-x-auto flex-1">
          <table class="w-full text-xs">
            <thead class="bg-[#f5f3f3] text-gray-400 font-bold uppercase tracking-wider">
              <tr>
                <th class="text-left px-4 py-3">Dish Name</th>
                <th class="text-center px-4 py-3">Quantity Sold</th>
                <th class="text-right px-4 py-3">Total Sales Revenue</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
              <?php foreach ($topFoods as $tf): ?>
              <tr>
                <td class="px-4 py-3 font-bold text-[#1b1c1c]"><?= e($tf['item_name']) ?></td>
                <td class="px-4 py-3 text-center"><?= $tf['total_qty'] ?> units</td>
                <td class="px-4 py-3 text-right font-black text-[#a83300]"><?= formatPrice($tf['total_rev']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($topFoods)): ?>
              <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">No dishes sold yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Historical Aggregation -->
      <div class="bg-white rounded-2xl border border-gray-150 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-gray-100 bg-[#fdfdfd]"><h3 class="font-bold text-sm text-[#1b1c1c]">📅 Monthly &amp; Weekly Aggregates</h3></div>
        <div class="overflow-x-auto flex-1">
          <table class="w-full text-xs">
            <thead class="bg-[#f5f3f3] text-gray-400 font-bold uppercase tracking-wider">
              <tr>
                <th class="text-left px-4 py-3">Period</th>
                <th class="text-center px-4 py-3">Orders</th>
                <th class="text-right px-4 py-3">Sales Revenue</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
              <!-- Months -->
              <?php foreach ($monthlyTrends as $m): ?>
              <tr class="bg-gray-50/20 font-bold">
                <td class="px-4 py-3 text-gray-800">📅 <?= e($m['month_label']) ?></td>
                <td class="px-4 py-3 text-center"><?= $m['orders'] ?></td>
                <td class="px-4 py-3 text-right text-emerald-600"><?= formatPrice($m['revenue']) ?></td>
              </tr>
              <?php endforeach; ?>
              
              <!-- Weeks -->
              <?php foreach ($weeklyTrends as $w): ?>
              <tr>
                <td class="px-4 py-3 text-gray-600">⚡ Week <?= substr($w['week_label'], -3) ?></td>
                <td class="px-4 py-3 text-center"><?= $w['orders'] ?></td>
                <td class="px-4 py-3 text-right font-bold"><?= formatPrice($w['revenue']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
    </div>

    <!-- Praised Foods & Customer Feedback Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8 mb-8">
      
      <!-- Most Praised Foods -->
      <div class="bg-white rounded-2xl border border-gray-150 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-gray-100 bg-[#fdfdfd] flex justify-between items-center">
          <h3 class="font-bold text-sm text-[#1b1c1c] flex items-center gap-1.5">👍 Most Praised Dishes</h3>
          <span class="text-[10px] text-gray-400 font-bold uppercase">Rated 4★ or above</span>
        </div>
        <div class="overflow-x-auto flex-1">
          <table class="w-full text-xs">
            <thead class="bg-[#f5f3f3] text-gray-400 font-bold uppercase tracking-wider">
              <tr>
                <th class="text-left px-4 py-3">Dish Item</th>
                <th class="text-center px-4 py-3">5★ Commendations</th>
                <th class="text-right px-4 py-3">Volume Ordered</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
              <?php foreach ($praisedFoods as $pf): ?>
              <tr>
                <td class="px-4 py-4 font-bold text-gray-800"><?= e($pf['item_name']) ?></td>
                <td class="px-4 py-4 text-center text-amber-500">★ <?= $pf['praise_count'] ?> times</td>
                <td class="px-4 py-4 text-right text-gray-500"><?= $pf['total_qty'] ?> units</td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($praisedFoods)): ?>
              <tr>
                <td colspan="3" class="px-4 py-10 text-center text-gray-400">No dishes rated highly yet. Excellent quality builds praise!</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Customer Feedback & Reviews -->
      <div class="bg-white rounded-2xl border border-gray-150 shadow-sm p-6 flex flex-col gap-4">
        <h3 class="font-bold text-sm text-[#1b1c1c] pb-2 border-b">💬 Customer Reviews &amp; Feedback</h3>
        <?php if (empty($restaurantReviews)): ?>
        <div class="flex-1 flex items-center justify-center text-center text-gray-400 text-xs py-8">
          No customer review comments left yet for this kitchen.
        </div>
        <?php else: ?>
        <div class="space-y-4 max-h-96 overflow-y-auto pr-1">
          <?php foreach ($restaurantReviews as $rev): ?>
          <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 flex flex-col gap-1 text-xs">
            <div class="flex justify-between items-center font-bold">
              <span class="text-gray-850 font-extrabold"><?= e($rev['customer_name']) ?></span>
              <span class="text-amber-500">★ <?= number_format($rev['restaurant_rating'], 1) ?></span>
            </div>
            <p class="text-gray-600 italic mt-1 font-semibold">"<?= e($rev['review_text']) ?>"</p>
            <span class="text-[9px] text-gray-400 font-bold block mt-1 uppercase"><?= date('M j, Y - g:i A', strtotime($rev['created_at'])) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

    </div>

    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Line Chart for daily trends
    const trendsCtx = document.getElementById('trendsChart');
    if (trendsCtx) {
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($trendDays) ?>,
                datasets: [
                    {
                        label: 'Revenue (₹)',
                        data: <?= json_encode($trendRev) ?>,
                        borderColor: '#a83300',
                        backgroundColor: 'rgba(168, 51, 0, 0.05)',
                        borderWidth: 3,
                        pointBackgroundColor: '#a83300',
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders Count',
                        data: <?= json_encode($trendOrders) ?>,
                        borderColor: '#00c853',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        pointBackgroundColor: '#00c853',
                        borderDash: [5, 5],
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { font: { family: 'Plus Jakarta Sans', size: 10 } } }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        position: 'left',
                        title: { display: true, text: 'Revenue (₹)', font: { weight: 'bold' } },
                        ticks: { callback: v => '₹' + v }
                    },
                    y1: {
                        position: 'right',
                        title: { display: true, text: 'Orders Count', font: { weight: 'bold' } },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    // 2. Popular Categories Doughnut Chart
    const catCtx = document.getElementById('categoryChart');
    if (catCtx) {
        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($catLabels) ?>,
                datasets: [{
                    data: <?= json_encode($catQty) ?>,
                    backgroundColor: ['#a83300', '#00c853', '#e59e0b', '#0284c7', '#7c3aed', '#ec4899', '#f97316'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { family: 'Plus Jakarta Sans', size: 10 }, boxWidth: 10 }
                    }
                }
            }
        });
    }
});
</script>
<?php
$noFooter = true;
include __DIR__ . '/../includes/footer.php';
?>
