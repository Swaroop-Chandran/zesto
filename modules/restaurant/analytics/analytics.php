<?php
/**
 * Zesto — Restaurant Performance Dashboard
 * Displays premium, real-time metrics and charts using native database queries.
 */

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
$charts = [];
$extended = [];

if ($restaurant) {
    $rid = (int)$restaurant['id'];

    // Fetch dynamic database metrics
    $revenue   = AnalyticsService::getRevenueMetrics($rid);
    $orders    = AnalyticsService::getOrderMetrics($rid);
    $food      = AnalyticsService::getFoodAnalytics($rid);
    $customers = AnalyticsService::getCustomerAnalytics($rid);
    $charts    = AnalyticsService::getChartsData($rid);
    $extended  = AnalyticsService::getExtendedMetrics($rid);
}

$pageTitle = 'Restaurant Analytics — Zesto';
$extraJs   = ['https://cdn.jsdelivr.net/npm/chart.js'];
$sidebarType = 'restaurant'; $activePage = 'analytics.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans text-white bg-[#050505]">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    
    <!-- Dashboard Header -->
    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-5">
      <div>
        <span class="text-xs font-bold text-[#f59e0b] uppercase tracking-widest">Analytics Studio</span>
        <h1 class="text-2xl md:text-3xl font-black text-white mt-1">Performance Dashboard</h1>
        <p class="text-xs text-white/60 mt-1">Real-time indicators of your kitchen\'s sales trends, retention, and fulfillment quality</p>
      </div>
      <span class="text-xs text-white/40 font-semibold uppercase tracking-wider"><?= date('l, M j, Y') ?></span>
    </div>

    <?php if (!$restaurant): ?>
    <div class="glass-panel rounded-3xl p-10 border border-amber-500/30 text-center max-w-2xl mx-auto shadow-md shadow-black/20 my-10 bg-amber-500/5">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-full glass-panel text-3xl mb-6 shadow-md shadow-black/20 border border-amber-500/20">🍳</div>
      <h2 class="text-2xl font-black text-[#f59e0b]">Complete Your Kitchen Setup</h2>
      <p class="text-sm text-white/70 mt-3 leading-relaxed font-semibold">
        Create your kitchen profile before you can access the performance dashboard.
      </p>
      <div class="mt-8 flex justify-center">
        <a href="<?= BASE_URL ?>/restaurant-panel/onboard.php" class="btn-primary font-bold px-8 py-3.5 rounded-full shadow-md text-xs border-none">
          Create Profile 🚀
        </a>
      </div>
    </div>
    <?php else: ?>

    <!-- High-Fidelity Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      
      <!-- Revenue KPI -->
      <div class="glass-panel rounded-2xl border border-white/10 p-6 shadow-md shadow-black/20 relative overflow-hidden">
        <div class="flex justify-between items-start mb-2">
          <p class="text-[10px] text-white/40 font-bold uppercase tracking-wider">Total Revenue</p>
          <span class="text-lg">💰</span>
        </div>
        <p class="text-2xl font-black text-white"><?= formatPrice($revenue['total']) ?></p>
        <span class="text-[9px] text-[#f59e0b] font-bold block mt-1">Today: <?= formatPrice($revenue['today']) ?></span>
      </div>

      <!-- Total Orders KPI -->
      <div class="glass-panel rounded-2xl border border-white/10 p-6 shadow-md shadow-black/20">
        <div class="flex justify-between items-start mb-2">
          <p class="text-[10px] text-white/40 font-bold uppercase tracking-wider">Total Orders</p>
          <span class="text-lg">📦</span>
        </div>
        <p class="text-2xl font-black text-white"><?= number_format($orders['total']) ?></p>
        <span class="text-[9px] text-white/40 font-bold block mt-1">Delivered: <?= $orders['delivered'] ?> • Cancelled: <?= $orders['cancelled'] ?></span>
      </div>

      <!-- Average Order Value KPI -->
      <div class="glass-panel rounded-2xl border border-white/10 p-6 shadow-md shadow-black/20">
        <div class="flex justify-between items-start mb-2">
          <p class="text-[10px] text-white/40 font-bold uppercase tracking-wider">Avg Order Value</p>
          <span class="text-lg">📈</span>
        </div>
        <p class="text-2xl font-black text-white"><?= formatPrice($extended['aov']) ?></p>
        <span class="text-[9px] text-white/40 font-bold block mt-1">Calculated from paid purchases</span>
      </div>

      <!-- Peak Order Hours KPI -->
      <div class="glass-panel rounded-2xl border border-white/10 p-6 shadow-md shadow-black/20">
        <div class="flex justify-between items-start mb-2">
          <p class="text-[10px] text-white/40 font-bold uppercase tracking-wider">Peak Order Hours</p>
          <span class="text-lg">⏱️</span>
        </div>
        <p class="text-sm font-black text-white mt-1.5 truncate" title="<?= e($extended['peak_hour']) ?>"><?= e($extended['peak_hour']) ?></p>
        <span class="text-[9px] text-white/40 font-bold block mt-2">Highest sales throughput time window</span>
      </div>

    </div>

    <!-- Second Row: Order Timing Velocity & Retention Widget -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      
      <!-- Order Timing Velocity -->
      <div class="glass-panel rounded-3xl border border-white/10 p-6 shadow-md shadow-black/20 flex flex-col justify-between">
        <h4 class="font-extrabold text-sm text-white pb-3 border-b border-white/5 mb-4 flex items-center gap-2">🛒 Order Velocity Breakdown</h4>
        <div class="space-y-4 flex-1 flex flex-col justify-center">
          <div class="flex justify-between items-center text-xs font-semibold">
            <span class="text-white/60">Daily Orders:</span>
            <span class="font-bold text-[#f59e0b]"><?= $extended['daily_orders'] ?> orders</span>
          </div>
          <div class="flex justify-between items-center text-xs font-semibold">
            <span class="text-white/60">Weekly Orders:</span>
            <span class="font-bold text-white/90"><?= $extended['weekly_orders'] ?> orders</span>
          </div>
          <div class="flex justify-between items-center text-xs font-semibold">
            <span class="text-white/60">Monthly Orders:</span>
            <span class="font-bold text-white/90"><?= $extended['monthly_orders'] ?> orders</span>
          </div>
        </div>
      </div>

      <!-- Execution Quality Metrics -->
      <div class="glass-panel rounded-3xl border border-white/10 p-6 shadow-md shadow-black/20 flex flex-col justify-between">
        <h4 class="font-extrabold text-sm text-white pb-3 border-b border-white/5 mb-4 flex items-center gap-2">🎯 Fulfillment Success Quality</h4>
        <div class="space-y-4 flex-1 flex flex-col justify-center">
          
          <div class="space-y-1.5">
            <div class="flex justify-between text-[10px] font-bold text-white/50">
              <span>Order Completion Rate</span>
              <span class="text-emerald-400"><?= $extended['completion_rate'] ?>%</span>
            </div>
            <div class="w-full bg-white/10 rounded-full h-1.5 overflow-hidden">
              <div class="bg-emerald-500 h-full rounded-full" style="width: <?= $extended['completion_rate'] ?>%"></div>
            </div>
          </div>

          <div class="space-y-1.5">
            <div class="flex justify-between text-[10px] font-bold text-white/50">
              <span>Cancellation Rate</span>
              <span class="text-red-400"><?= $extended['cancellation_rate'] ?>%</span>
            </div>
            <div class="w-full bg-white/10 rounded-full h-1.5 overflow-hidden">
              <div class="bg-red-500 h-full rounded-full" style="width: <?= $extended['cancellation_rate'] ?>%"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Customer Retention Premium Widget -->
      <div class="glass-panel rounded-3xl border border-white/10 p-6 shadow-md shadow-black/20 flex flex-col items-center justify-center text-center">
        <h4 class="font-extrabold text-sm text-white pb-3 border-b border-white/5 mb-2 w-full text-left flex items-center gap-2">🔄 Customer Retention</h4>
        
        <div class="relative flex items-center justify-center my-3">
          <svg class="w-24 h-24 transform -rotate-90">
            <circle class="text-white/5" stroke-width="7" stroke="currentColor" fill="transparent" r="38" cx="48" cy="48" />
            <circle class="text-[#f59e0b] filter drop-shadow-[0_0_6px_rgba(245,158,11,0.4)]" stroke-dasharray="238.76" stroke-dashoffset="<?= 238.76 - (238.76 * $extended['repeat_pct'] / 100) ?>" stroke-width="7" stroke-linecap="round" stroke="currentColor" fill="transparent" r="38" cx="48" cy="48" />
          </svg>
          <div class="absolute text-xl font-black text-white"><?= $extended['repeat_pct'] ?>%</div>
        </div>

        <p class="text-xs font-bold text-white/80">Repeat Customer Percentage</p>
        <p class="text-[9px] text-white/40 mt-1 max-w-[200px]">Percentage of active users making 2+ purchases from your kitchen.</p>
      </div>

    </div>

    <!-- Charts Visualization Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      
      <!-- Revenue Trend Line Graph -->
      <div class="glass-panel rounded-3xl border border-white/10 p-6 shadow-md shadow-black/20 flex flex-col min-h-[320px]">
        <h4 class="font-bold text-sm text-white mb-4 pb-2 border-b border-white/5 flex items-center gap-1.5">📈 Revenue Trend (Last 14 Days)</h4>
        <div class="flex-1 relative min-h-[220px]">
          <?php if (empty($charts['trend_days'])): ?>
          <div class="w-full h-full flex items-center justify-center text-center font-bold text-white/40 text-xs py-8">
            No sales data recorded in the last 14 days.
          </div>
          <?php else: ?>
          <canvas id="revenueTrendChart"></canvas>
          <?php endif; ?>
        </div>
      </div>

      <!-- Orders Trend Bar Chart -->
      <div class="glass-panel rounded-3xl border border-white/10 p-6 shadow-md shadow-black/20 flex flex-col min-h-[320px]">
        <h4 class="font-bold text-sm text-white mb-4 pb-2 border-b border-white/5 flex items-center gap-1.5">📊 Orders Trend (Last 14 Days)</h4>
        <div class="flex-1 relative min-h-[220px]">
          <?php if (empty($charts['trend_days'])): ?>
          <div class="w-full h-full flex items-center justify-center text-center font-bold text-white/40 text-xs py-8">
            No orders placed in the last 14 days.
          </div>
          <?php else: ?>
          <canvas id="ordersTrendChart"></canvas>
          <?php endif; ?>
        </div>
      </div>

      <!-- Top Selling Dishes Bar Chart -->
      <div class="glass-panel rounded-3xl border border-white/10 p-6 shadow-md shadow-black/20 flex flex-col min-h-[320px]">
        <h4 class="font-bold text-sm text-white mb-4 pb-2 border-b border-white/5 flex items-center gap-1.5">🍔 Top Dishes by Revenue Contribution</h4>
        <div class="flex-1 relative min-h-[220px]">
          <?php if (empty($charts['top_dishes'])): ?>
          <div class="w-full h-full flex items-center justify-center text-center font-bold text-white/40 text-xs py-8">
            No items sold yet.
          </div>
          <?php else: ?>
          <canvas id="topDishesChart"></canvas>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- Detailed Sales List: Most Ordered Items -->
    <div class="glass-panel rounded-3xl border border-white/10 shadow-md shadow-black/20 overflow-hidden mb-8">
      <div class="p-5 border-b border-white/10 bg-white/5">
        <h4 class="font-bold text-sm text-white flex items-center gap-1.5">🔥 Most Ordered Items &amp; Revenue Breakdown</h4>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="bg-white/5 text-white/40 font-bold uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3">Dish Name</th>
              <th class="px-5 py-3 text-center">Total Quantity Sold</th>
              <th class="px-5 py-3 text-right">Revenue Generated</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/10 font-semibold text-white/80">
            <?php foreach ($food['top_selling'] as $ts): ?>
            <tr class="hover:bg-white/5">
              <td class="px-5 py-4 font-bold text-white/90"><?= e($ts['item_name']) ?></td>
              <td class="px-5 py-4 text-center text-white/60"><?= $ts['total_qty'] ?> units</td>
              <td class="px-5 py-4 text-right font-black text-[#f59e0b]"><?= formatPrice($ts['total_rev']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($food['top_selling'])): ?>
            <tr>
              <td colspan="3" class="px-5 py-12 text-center text-white/40 font-bold">No sales records logged in history.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php endif; // End restaurant check ?>
  </div>
</div>

<!-- Chart Script Rendering -->
<?php if ($restaurant && !empty($charts['trend_days'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Revenue Trend Line Chart
    const revCtx = document.getElementById('revenueTrendChart');
    if (revCtx) {
        new Chart(revCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($charts['trend_days'] ?: []) ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?= json_encode($charts['trend_revenue'] ?: []) ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.05)',
                    borderWidth: 3,
                    pointBackgroundColor: '#f59e0b',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { 
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: 'rgba(255, 255, 255, 0.6)', font: { size: 9 } }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: 'rgba(255, 255, 255, 0.6)', font: { size: 9 }, callback: v => '₹' + v }
                    }
                }
            }
        });
    }

    // 2. Orders Trend Bar Chart
    const ordersCtx = document.getElementById('ordersTrendChart');
    if (ordersCtx) {
        new Chart(ordersCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($charts['trend_days'] ?: []) ?>,
                datasets: [{
                    label: 'Orders Count',
                    data: <?= json_encode($charts['trend_orders'] ?: []) ?>,
                    backgroundColor: 'rgba(245, 158, 11, 0.25)',
                    borderColor: '#f59e0b',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { 
                        grid: { display: false },
                        ticks: { color: 'rgba(255, 255, 255, 0.6)', font: { size: 9 } }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: 'rgba(255, 255, 255, 0.6)', font: { size: 9 }, stepSize: 1 }
                    }
                }
            }
        });
    }

    // 3. Top Dishes Horizontal Bar Chart
    const dishesCtx = document.getElementById('topDishesChart');
    if (dishesCtx) {
        new Chart(dishesCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($charts['top_dishes'] ?: []) ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?= json_encode($charts['top_dish_revenue'] ?: []) ?>,
                    backgroundColor: ['#f59e0b', '#fbbf24', '#d97706', '#92400e', '#451a03'],
                    borderWidth: 0,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { 
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: 'rgba(255, 255, 255, 0.6)', font: { size: 9 } }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: 'rgba(255, 255, 255, 0.6)', font: { size: 9 } }
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
