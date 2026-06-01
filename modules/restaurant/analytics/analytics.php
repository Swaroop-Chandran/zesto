<?php
/**
 * Zesto — Restaurant Panel Analytics Dashboard
 * Displays high-fidelity KPIs, revenue metrics, food analytics, and delivery charts.
 * Leverages the AnalyticsService for secure, dry, and centralized database calculations.
 */

// Enable error reporting for debug safety
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../services/AnalyticsService.php';

requireRole(ROLE_RESTAURANT_OWNER);
$ownerId = getCurrentUser()['id'];

// Get restaurant profile
$res = db()->prepare("SELECT * FROM restaurants WHERE owner_id = :oid LIMIT 1");
$res->execute([':oid' => $ownerId]);
$restaurant = $res->fetch();

$revenue = [];
$orders = [];
$food = [];
$customers = [];
$delivery = [];
$charts = [];
$recentReviews = [];

if ($restaurant) {
    $rid = (int)$restaurant['id'];

    // 1. Fetch metrics from AnalyticsService
    $revenue   = AnalyticsService::getRevenueMetrics($rid);
    $orders    = AnalyticsService::getOrderMetrics($rid);
    $food      = AnalyticsService::getFoodAnalytics($rid);
    $customers = AnalyticsService::getCustomerAnalytics($rid);
    $delivery  = AnalyticsService::getDeliveryAnalytics($rid);
    $charts    = AnalyticsService::getChartsData($rid);

    // 2. Fetch Recent Reviews
    $stmt = db()->prepare("
        SELECT r.review_text, r.restaurant_rating, r.created_at, u.name AS customer_name
        FROM order_reviews r
        JOIN users u ON u.id = r.customer_id
        WHERE r.restaurant_id = :rid AND r.review_text IS NOT NULL AND r.review_text != ''
        ORDER BY r.created_at DESC LIMIT 5
    ");
    $stmt->execute([':rid' => $rid]);
    $recentReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Analytics — Restaurant Panel';
$extraJs   = ['https://cdn.jsdelivr.net/npm/chart.js'];
$sidebarType = 'restaurant'; $activePage = 'analytics.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans text-[#1b1c1c]">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    
    <!-- Top Header -->
    <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-5">
      <div>
        <span class="text-xs font-bold text-[#a83300] uppercase tracking-widest">Restaurant Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-[#1b1c1c] mt-1">Analytics Dashboard</h1>
        <p class="text-xs text-gray-500 mt-1">Real-time statistics on revenue, orders, customers, and delivery performance</p>
      </div>
      <span class="text-xs text-gray-400 font-semibold"><?= date('l, F j, Y') ?></span>
    </div>

    <?php if (!$restaurant): ?>
    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-3xl p-10 border border-orange-200 text-center max-w-2xl mx-auto shadow-sm my-10 font-sans">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white text-3xl mb-6 shadow-sm">🍳</div>
      <h2 class="text-2xl font-black text-[#1b1c1c]">Setup Your Profile First</h2>
      <p class="text-sm text-gray-650 mt-3 leading-relaxed font-semibold">
        Create your kitchen profile before you can access sales reports and analytics.
      </p>
      <div class="mt-8 flex justify-center">
        <a href="<?= BASE_URL ?>/restaurant-panel/onboard.php" class="btn-primary flex items-center gap-2 font-bold px-8 py-3 rounded-2xl bg-gradient-to-r from-orange-500 to-red-600 text-white shadow-md hover:scale-[1.02] active:scale-95 transition-all text-xs">
          Create Profile 🚀
        </a>
      </div>
    </div>
    <?php else: ?>

    <!-- ─── REVENUE & SALES METRICS ────────────────────────────────────────── -->
    <div class="mb-10">
      <h3 class="font-bold text-sm text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">💰 Revenue &amp; Sales Metrics</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
        <?php foreach ([
            ['Today\'s Revenue', formatPrice($revenue['today']), '⚡', 'bg-blue-50 text-blue-600'],
            ['Weekly Revenue', formatPrice($revenue['weekly']), '📅', 'bg-emerald-50 text-emerald-600'],
            ['Monthly Revenue', formatPrice($revenue['monthly']), '📈', 'bg-indigo-50 text-indigo-600'],
            ['Total Revenue', formatPrice($revenue['total']), '💳', 'bg-orange-50 text-orange-600']
        ] as [$lbl, $val, $ico, $cls]): ?>
        <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-sm hover:border-brand/20 transition-all">
          <div class="flex justify-between items-start mb-2">
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider"><?= $lbl ?></p>
            <span class="text-lg"><?= $ico ?></span>
          </div>
          <p class="text-xl md:text-2xl font-black text-[#1b1c1c]"><?= $val ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ─── ORDER VOLUME METRICS ──────────────────────────────────────────── -->
    <div class="mb-10">
      <h3 class="font-bold text-sm text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">📦 Order Volume Metrics</h3>
      <div class="grid grid-cols-2 md:grid-cols-5 gap-5">
        <?php foreach ([
            ['Total Orders', $orders['total'], '📋', 'text-gray-600'],
            ['Pending Orders', $orders['pending'], '⏳', 'text-amber-500'],
            ['Preparing Orders', $orders['preparing'], '👨‍🍳', 'text-indigo-500'],
            ['Delivered Orders', $orders['delivered'], '✓', 'text-emerald-600'],
            ['Cancelled Orders', $orders['cancelled'], '✗', 'text-red-500']
        ] as [$lbl, $val, $ico, $col]): ?>
        <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-sm hover:border-brand/20 transition-all">
          <div class="flex justify-between items-start mb-2">
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider"><?= $lbl ?></p>
            <span class="text-sm font-bold <?= $col ?>"><?= $ico ?></span>
          </div>
          <p class="text-xl md:text-2xl font-black text-[#1b1c1c]"><?= $val ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ─── CUSTOMER & DELIVERY METRICS ───────────────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
      
      <!-- Customer Segments -->
      <div class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm">
        <h4 class="font-extrabold text-sm text-[#1b1c1c] pb-3 border-b mb-4 flex items-center gap-2">👥 Customer Segments Analytics</h4>
        <div class="grid grid-cols-3 gap-4 text-center">
          <div class="p-4 bg-gray-50 rounded-2xl border">
            <p class="text-[9px] font-bold text-gray-400 uppercase">Total Customers</p>
            <p class="text-xl md:text-2xl font-black mt-1.5 text-gray-800"><?= $customers['total'] ?></p>
          </div>
          <div class="p-4 bg-emerald-55/10 bg-emerald-50 rounded-2xl border border-emerald-100">
            <p class="text-[9px] font-bold text-emerald-600 uppercase">Returning Segment</p>
            <p class="text-xl md:text-2xl font-black mt-1.5 text-emerald-700"><?= $customers['returning'] ?></p>
          </div>
          <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100">
            <p class="text-[9px] font-bold text-blue-600 uppercase">New Segment</p>
            <p class="text-xl md:text-2xl font-black mt-1.5 text-blue-700"><?= $customers['new'] ?></p>
          </div>
        </div>
      </div>

      <!-- Delivery Performance -->
      <div class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm">
        <h4 class="font-extrabold text-sm text-[#1b1c1c] pb-3 border-b mb-4 flex items-center gap-2">🏍 Delivery Execution Analytics</h4>
        <div class="grid grid-cols-3 gap-4 text-center">
          <div class="p-4 bg-gray-50 rounded-2xl border">
            <p class="text-[9px] font-bold text-gray-400 uppercase">Completed Dropoffs</p>
            <p class="text-xl md:text-2xl font-black mt-1.5 text-gray-800"><?= $delivery['completed'] ?></p>
          </div>
          <div class="p-4 bg-orange-50 rounded-2xl border border-orange-100">
            <p class="text-[9px] font-bold text-orange-600 uppercase">Avg Delivery Time</p>
            <p class="text-xl md:text-2xl font-black mt-1.5 text-orange-700"><?= $delivery['avg_time'] ?> min</p>
          </div>
          <div class="p-4 bg-red-50 rounded-2xl border border-red-100">
            <p class="text-[9px] font-bold text-red-600 uppercase">Delayed (>40 min)</p>
            <p class="text-xl md:text-2xl font-black mt-1.5 text-red-700"><?= $delivery['delayed'] ?></p>
          </div>
        </div>
      </div>

    </div>

    <!-- ─── CHARTS DATA DISPLAY ───────────────────────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      
      <!-- Daily Revenue & Orders Trend -->
      <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-150 p-6 shadow-sm flex flex-col min-h-[360px]">
        <h4 class="font-bold text-sm text-[#1b1c1c] mb-4 pb-2 border-b flex items-center gap-1.5">📈 Revenue &amp; Orders Trend (Last 14 Days)</h4>
        <div class="flex-1 flex items-center justify-center relative min-h-[260px]">
          <?php if (empty($charts['trend_days'])): ?>
          <div class="text-center font-bold text-gray-400 text-xs py-8">
            <span class="text-2xl block mb-2">📊</span>
            No Data Available
          </div>
          <?php else: ?>
          <canvas id="revenueTrendChart" class="w-full h-full"></canvas>
          <?php endif; ?>
        </div>
      </div>

      <!-- Categories Performance Doughnut -->
      <div class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm flex flex-col min-h-[360px]">
        <h4 class="font-bold text-sm text-[#1b1c1c] mb-4 pb-2 border-b flex items-center gap-1.5">🍕 Popular Categories</h4>
        <div class="flex-1 flex items-center justify-center relative min-h-[260px]">
          <?php if (empty($charts['categories'])): ?>
          <div class="text-center font-bold text-gray-400 text-xs py-8">
            <span class="text-2xl block mb-2">🍽️</span>
            No Data Available
          </div>
          <?php else: ?>
          <canvas id="categoryShareChart" class="w-full h-full"></canvas>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- ─── DISH ANALYSIS TABLES ──────────────────────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      
      <!-- Top Selling Foods -->
      <div class="bg-white rounded-3xl border border-gray-150 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-gray-100 bg-[#fdfdfd] flex justify-between items-center">
          <h4 class="font-bold text-sm text-[#1b1c1c] flex items-center gap-1.5">🔥 Top Selling Dishes</h4>
        </div>
        <div class="overflow-x-auto flex-1">
          <table class="w-full text-xs text-left">
            <thead class="bg-[#f5f3f3] text-gray-400 font-bold uppercase tracking-wider">
              <tr>
                <th class="px-5 py-3">Dish Name</th>
                <th class="px-5 py-3 text-center">Quantity Sold</th>
                <th class="px-5 py-3 text-right">Revenue Generated</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
              <?php foreach ($food['top_selling'] as $ts): ?>
              <tr class="hover:bg-gray-50/50">
                <td class="px-5 py-4 font-bold text-gray-800"><?= e($ts['item_name']) ?></td>
                <td class="px-5 py-4 text-center text-gray-500"><?= $ts['total_qty'] ?> units</td>
                <td class="px-5 py-4 text-right font-black text-brand"><?= formatPrice($ts['total_rev']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($food['top_selling'])): ?>
              <tr>
                <td colspan="3" class="px-5 py-10 text-center text-gray-400">No Data Available</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Low Performing Foods -->
      <div class="bg-white rounded-3xl border border-gray-150 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-gray-100 bg-[#fdfdfd] flex justify-between items-center">
          <h4 class="font-bold text-sm text-gray-700 flex items-center gap-1.5">💤 Low Performing Dishes</h4>
        </div>
        <div class="overflow-x-auto flex-1">
          <table class="w-full text-xs text-left">
            <thead class="bg-[#f5f3f3] text-gray-450 font-bold uppercase tracking-wider">
              <tr>
                <th class="px-5 py-3">Dish Name</th>
                <th class="px-5 py-3 text-center">Quantity Sold</th>
                <th class="px-5 py-3 text-right">Revenue Generated</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
              <?php foreach ($food['low_performing'] as $lp): ?>
              <tr class="hover:bg-gray-50/50">
                <td class="px-5 py-4 font-bold text-gray-800"><?= e($lp['item_name']) ?></td>
                <td class="px-5 py-4 text-center text-gray-500"><?= $lp['total_qty'] ?> units</td>
                <td class="px-5 py-4 text-right font-black text-gray-650"><?= formatPrice($lp['total_rev']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($food['low_performing'])): ?>
              <tr>
                <td colspan="3" class="px-5 py-10 text-center text-gray-400">No Data Available</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- ─── REVIEWS AND PRAISE FEEDBACK ───────────────────────────────────── -->
    <div class="bg-white rounded-3xl border border-gray-150 shadow-sm p-6 mb-8 flex flex-col gap-4">
      <h3 class="font-bold text-sm text-[#1b1c1c] pb-2 border-b">💬 Customer Reviews &amp; Praise Feedback</h3>
      <?php if (empty($recentReviews)): ?>
      <div class="text-center text-gray-400 font-bold text-xs py-8">
        No rated customer reviews or text comments logged yet.
      </div>
      <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($recentReviews as $rev): ?>
        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 flex flex-col gap-1 text-xs">
          <div class="flex justify-between items-center font-bold">
            <span class="text-gray-850 font-extrabold"><?= e($rev['customer_name']) ?></span>
            <span class="text-amber-500">★ <?= number_format((float)($rev['restaurant_rating'] ?? 0), 1) ?> rating</span>
          </div>
          <p class="text-gray-600 italic mt-1 font-semibold">"<?= e($rev['review_text']) ?>"</p>
          <span class="text-[9px] text-gray-400 font-bold block mt-1 uppercase"><?= date('M j, Y - g:i A', strtotime($rev['created_at'])) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php endif; // End restaurant check ?>
  </div>
</div>

<!-- Chart Script Rendering -->
<?php if ($restaurant && !empty($charts['trend_days'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Daily Sales & Orders Trend Line Chart
    const trendCtx = document.getElementById('revenueTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($charts['trend_days']) ?>,
                datasets: [
                    {
                        label: 'Revenue (₹)',
                        data: <?= json_encode($charts['trend_revenue']) ?>,
                        borderColor: '#a83300',
                        backgroundColor: 'rgba(168, 51, 0, 0.05)',
                        borderWidth: 3,
                        pointBackgroundColor: '#a83300',
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders Count',
                        data: <?= json_encode($charts['trend_orders']) ?>,
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

    // 2. Popular Categories Share Chart (Doughnut)
    const categoryCtx = document.getElementById('categoryShareChart');
    if (categoryCtx && <?= json_encode(!empty($charts['categories'])) ?>) {
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($charts['categories']) ?>,
                datasets: [{
                    data: <?= json_encode($charts['category_quantities']) ?>,
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
<?php endif; ?>

<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
