<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_DELIVERY_PARTNER);
$userId = getCurrentUser()['id'];

$stats = [
    'total_deliveries' => 0,
    'total_distance' => 0.0,
    'total_earnings' => 0.0,
    'today_earnings' => 0.0,
    'weekly_earnings' => 0.0,
    'monthly_earnings' => 0.0,
    'avg_earnings' => 0.0,
    'active_deliveries' => 0
];
$dailyTrend = [];

try {
    // 1. Core KPIs
    $stmt = db()->prepare("SELECT COUNT(*) FROM delivery_assignments WHERE delivery_partner_id = :pid AND status = 'completed'");
    $stmt->execute([':pid' => $userId]);
    $stats['total_deliveries'] = (int)$stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COALESCE(SUM(distance_travelled), 0) FROM delivery_earnings WHERE delivery_partner_id = :pid");
    $stmt->execute([':pid' => $userId]);
    $stats['total_distance'] = (float)$stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COALESCE(SUM(total_earnings), 0) FROM delivery_earnings WHERE delivery_partner_id = :pid");
    $stmt->execute([':pid' => $userId]);
    $stats['total_earnings'] = (float)$stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COALESCE(SUM(total_earnings), 0) FROM delivery_earnings WHERE delivery_partner_id = :pid AND DATE(created_at) = CURDATE()");
    $stmt->execute([':pid' => $userId]);
    $stats['today_earnings'] = (float)$stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COALESCE(SUM(total_earnings), 0) FROM delivery_earnings WHERE delivery_partner_id = :pid AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute([':pid' => $userId]);
    $stats['weekly_earnings'] = (float)$stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COALESCE(SUM(total_earnings), 0) FROM delivery_earnings WHERE delivery_partner_id = :pid AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stmt->execute([':pid' => $userId]);
    $stats['monthly_earnings'] = (float)$stats['total_earnings']; // Or last 30 days dynamically: (float)$stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COALESCE(AVG(total_earnings), 0) FROM delivery_earnings WHERE delivery_partner_id = :pid");
    $stmt->execute([':pid' => $userId]);
    $stats['avg_earnings'] = (float)$stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COUNT(*) FROM delivery_assignments WHERE delivery_partner_id = :pid AND status = 'accepted'");
    $stmt->execute([':pid' => $userId]);
    $stats['active_deliveries'] = (int)$stmt->fetchColumn();

    // Daily trends (last 10 days)
    $stmt = db()->prepare("
        SELECT DATE(created_at) AS day, SUM(total_earnings) AS earnings, COUNT(*) AS count
        FROM delivery_earnings
        WHERE delivery_partner_id = :pid AND created_at >= DATE_SUB(CURDATE(), INTERVAL 10 DAY)
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ");
    $stmt->execute([':pid' => $userId]);
    $dailyTrend = $stmt->fetchAll();

} catch (Exception $e) {
    // Fail silently or log
}

$chartLabels = [];
$chartData = [];
$chartCount = [];
foreach ($dailyTrend as $t) {
    $chartLabels[] = date('M j', strtotime($t['day']));
    $chartData[] = (float)$t['earnings'];
    $chartCount[] = (int)$t['count'];
}

$pageTitle = 'Partner Analytics — Delivery Panel';
$extraJs   = ['https://cdn.jsdelivr.net/npm/chart.js'];
$sidebarType = 'delivery'; $activePage = 'analytics.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    
    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-5">
      <div>
        <span class="text-xs font-bold text-[#00c853] uppercase tracking-widest">Delivery Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-white mt-1">Earnings Analytics</h1>
        <p class="text-xs text-white/60 mt-1">Detailed breakdowns of your deliveries log, distance calculations, and payout milestones</p>
      </div>
    </div>

    <!-- Core KPI Dashboard -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
      <?php
      $kpis = [
        ['Today\'s Payout', formatPrice($stats['today_earnings']), '⚡', 'bg-emerald-50 text-[#00c853]'],
        ['Avg per Delivery', formatPrice($stats['avg_earnings']), '📈', 'bg-blue-50 text-blue-600'],
        ['Total Distance', number_format($stats['total_distance'], 1) . ' KM', '🏍', 'bg-amber-50 text-amber-600'],
        ['Lifetime Payouts', formatPrice($stats['total_earnings']), '💰', 'bg-indigo-50 text-indigo-600']
      ];
      foreach ($kpis as [$lbl, $val, $ico, $cls]): ?>
      <div class="glass-panel rounded-2xl border border-white/10 p-6 shadow-md shadow-black/20">
        <div class="flex justify-between items-start mb-2">
          <p class="text-[10px] text-white/40 font-bold uppercase tracking-wider"><?= $lbl ?></p>
          <span class="text-lg"><?= $ico ?></span>
        </div>
        <p class="text-2xl font-black text-white"><?= $val ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Statistics widgets -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
      <div class="glass-panel rounded-2xl border border-white/10 p-5 shadow-md shadow-black/20">
        <h4 class="text-xs font-bold text-white/40 uppercase mb-3">Weekly Milestones</h4>
        <p class="text-xl font-bold text-white/90"><?= formatPrice($stats['weekly_earnings']) ?></p>
        <span class="text-[10px] text-white/40 block mt-1">Payout aggregated for the last 7 active days</span>
      </div>
      <div class="glass-panel rounded-2xl border border-white/10 p-5 shadow-md shadow-black/20">
        <h4 class="text-xs font-bold text-white/40 uppercase mb-3">Monthly Aggregation</h4>
        <p class="text-xl font-bold text-white/90"><?= formatPrice($stats['monthly_earnings']) ?></p>
        <span class="text-[10px] text-white/40 block mt-1">Total earnings for current billing period</span>
      </div>
      <div class="glass-panel rounded-2xl border border-white/10 p-5 shadow-md shadow-black/20">
        <h4 class="text-xs font-bold text-white/40 uppercase mb-3">Workflow Queue</h4>
        <div class="flex justify-between text-xs font-semibold mt-2 text-white/80">
          <p>Active Tasks:</p>
          <p class="font-extrabold text-emerald-600"><?= $stats['active_deliveries'] ?></p>
        </div>
        <div class="flex justify-between text-xs font-semibold mt-1.5 text-white/80">
          <p>Completed Log:</p>
          <p class="font-extrabold text-white"><?= $stats['total_deliveries'] ?></p>
        </div>
      </div>
    </div>

    <!-- Interactive Earnings trend graph -->
    <div class="glass-panel rounded-3xl border border-white/10 p-6 shadow-md shadow-black/20 flex flex-col mb-8">
      <h3 class="font-bold text-sm text-white mb-4 pb-2 border-b">📈 Earnings &amp; Deliveries Trend (Last 10 Days)</h3>
      <div class="h-80 flex-1">
        <?php if (empty($dailyTrend)): ?>
        <div class="w-full h-full flex items-center justify-center text-center text-white/40 font-bold text-xs">
          No delivery earnings logged in the last 10 days. Complete a delivery to start recording statistics!
        </div>
        <?php else: ?>
        <canvas id="earningsChart"></canvas>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('earningsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [
                    {
                        label: 'Earnings (₹)',
                        type: 'line',
                        data: <?= json_encode($chartData) ?>,
                        borderColor: '#00c853',
                        backgroundColor: 'rgba(0, 200, 83, 0.05)',
                        borderWidth: 3,
                        pointBackgroundColor: '#00c853',
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Deliveries Made',
                        type: 'bar',
                        data: <?= json_encode($chartCount) ?>,
                        backgroundColor: '#ffdbd0',
                        yAxisID: 'y1',
                        borderRadius: 6
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
                        title: { display: true, text: 'Earnings (₹)', font: { weight: 'bold' } },
                        ticks: { callback: v => '₹' + v }
                    },
                    y1: {
                        position: 'right',
                        title: { display: true, text: 'Deliveries Made', font: { weight: 'bold' } },
                        grid: { drawOnChartArea: false },
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
});
</script>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
