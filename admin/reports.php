<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_ADMIN);

$from = filter_input(INPUT_GET, 'from', FILTER_SANITIZE_SPECIAL_CHARS) ?? date('Y-m-01');
$to   = filter_input(INPUT_GET, 'to',   FILTER_SANITIZE_SPECIAL_CHARS) ?? date('Y-m-d');

$summary = db()->prepare("SELECT COUNT(*) AS total_orders, COALESCE(SUM(total),0) AS revenue, COALESCE(SUM(delivery_fee),0) AS delivery_revenue, COUNT(DISTINCT user_id) AS unique_customers FROM orders WHERE payment_status='paid' AND DATE(created_at) BETWEEN :from AND :to");
$summary->execute([':from' => $from, ':to' => $to]);
$sum = $summary->fetch();

$byRestaurant = db()->prepare("SELECT r.name, COUNT(o.id) AS orders, COALESCE(SUM(o.total),0) AS revenue FROM orders o JOIN restaurants r ON r.id=o.restaurant_id WHERE o.payment_status='paid' AND DATE(o.created_at) BETWEEN :from AND :to GROUP BY r.id ORDER BY revenue DESC");
$byRestaurant->execute([':from' => $from, ':to' => $to]);
$restData = $byRestaurant->fetchAll();

$pageTitle = 'Reports — Zesto Admin';
$extraJs   = ['https://cdn.jsdelivr.net/npm/chart.js', BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'admin'; $activePage = 'reports.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
      <h1 class="text-2xl font-extrabold text-[#1b1c1c]">Reports &amp; Analytics</h1>
      <form id="report-filter-form" method="GET" class="flex gap-2 items-center flex-wrap">
        <input type="date" name="from" value="<?= e($from) ?>" class="zesto-input py-2 text-xs w-40">
        <input type="date" name="to"   value="<?= e($to) ?>"   class="zesto-input py-2 text-xs w-40">
        <button class="btn-primary py-2 px-4 text-xs">Apply</button>
        <button type="button" onclick="exportTableCSV('rest-table','zesto-report.csv')" class="btn-secondary py-2 px-4 text-xs">Export CSV</button>
      </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
      <?php $cards = [['Total Orders',$sum['total_orders'],'📦'],['Revenue',formatPrice($sum['revenue']),'💰'],['Unique Customers',$sum['unique_customers'],'👥'],['Delivery Revenue',formatPrice($sum['delivery_revenue']),'🚚']];
      foreach ($cards as [$label, $val, $icon]): ?>
      <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <div class="text-2xl mb-2"><?= $icon ?></div>
        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1"><?= $label ?></p>
        <p class="text-2xl font-extrabold text-[#1b1c1c]"><?= $val ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- By Restaurant -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-gray-100"><h3 class="font-bold text-sm text-[#1b1c1c]">Revenue by Restaurant</h3></div>
      <table id="rest-table" class="w-full text-sm">
        <thead class="bg-[#f5f3f3]">
          <tr>
            <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Restaurant</th>
            <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Orders</th>
            <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Revenue</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($restData as $r): ?>
          <tr class="hover:bg-[#f5f3f3]/50">
            <td class="px-4 py-3 font-semibold"><?= e($r['name']) ?></td>
            <td class="px-4 py-3"><?= $r['orders'] ?></td>
            <td class="px-4 py-3 font-bold text-[#a83300]"><?= formatPrice($r['revenue']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($restData)): ?>
          <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">No data for selected period</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
$noFooter = true;
include __DIR__ . '/../includes/footer.php';
?>
