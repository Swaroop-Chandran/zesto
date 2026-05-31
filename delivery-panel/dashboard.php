<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(ROLE_DELIVERY_PARTNER);
$userId = getCurrentUser()['id'];

$partner = db()->prepare("SELECT * FROM delivery_partners WHERE user_id=:uid LIMIT 1");
$partner->execute([':uid' => $userId]);
$dp = $partner->fetch();

$deliveries = db()->prepare("SELECT o.*, r.name AS restaurant_name FROM orders o JOIN restaurants r ON r.id=o.restaurant_id WHERE o.delivery_partner_id=:uid ORDER BY o.updated_at DESC LIMIT 20");
$deliveries->execute([':uid' => $userId]);
$deliveries = $deliveries->fetchAll();

// Pending: not yet assigned — available for pickup
$available = db()->query("SELECT o.*, r.name AS restaurant_name FROM orders o JOIN restaurants r ON r.id=o.restaurant_id WHERE o.order_status='preparing' AND o.delivery_partner_id IS NULL ORDER BY o.created_at ASC LIMIT 10")->fetchAll();

$pageTitle = 'Delivery Dashboard — Zesto';
$extraJs   = [BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'delivery'; $activePage = 'dashboard.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-2xl font-extrabold text-[#1b1c1c]">Delivery Dashboard</h1>
        <p class="text-sm text-gray-500 mt-1">Welcome, <?= e(getCurrentUser()['name']) ?>!</p>
      </div>
      <?php if ($dp): ?>
      <div class="flex items-center gap-3">
        <label class="flex items-center gap-2 cursor-pointer text-sm font-semibold">
          <span class="<?= $dp['is_available'] ? 'text-[#00c853]' : 'text-gray-400' ?>">
            <?= $dp['is_available'] ? '🟢 Available' : '🔴 Offline' ?>
          </span>
        </label>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($dp && !$dp['is_approved']): ?>
    <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-3xl p-10 border border-amber-200 text-center max-w-2xl mx-auto shadow-sm my-10 font-sans">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white text-3xl mb-6 shadow-sm">
        ⏳
      </div>
      <h2 class="text-2xl font-black text-[#1b1c1c]">Application Under Review</h2>
      <p class="text-sm text-gray-600 mt-3 leading-relaxed font-semibold">
        Thank you for applying to be a Zesto Delivery Partner! Your uploaded credentials (driving license, vehicle info, and selfie) are currently being audited by our trust and safety team.
      </p>
      <p class="text-xs text-gray-500 mt-4 font-semibold">
        We typically review and approve delivery applications within 24–48 hours. You will receive an SMS and email notification once your account is fully activated.
      </p>
    </div>
    <?php else: ?>

      <?php if ($dp): ?>
      <div class="grid grid-cols-3 gap-5 mb-8">
        <?php foreach ([['Total Deliveries', $dp['total_deliveries'], '🏍'], ['Rating', number_format($dp['rating'],1).' ★', '⭐'], ['Total Earned', formatPrice($dp['total_earnings']), '💰']] as [$l,$v,$i]): ?>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
          <div class="text-2xl mb-2"><?= $i ?></div>
          <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1"><?= $l ?></p>
          <p class="text-2xl font-extrabold text-[#1b1c1c]"><?= $v ?></p>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
   
      <!-- Available Orders -->
      <?php if (!empty($available)): ?>
      <div class="mb-8">
        <h2 class="text-lg font-bold text-[#1b1c1c] mb-4">📦 Available for Pickup (<?= count($available) ?>)</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <?php foreach ($available as $avail): ?>
          <div class="bg-white rounded-2xl border border-[#ffdbd0] p-5 shadow-sm">
            <div class="flex justify-between mb-2">
              <span class="font-bold text-[#a83300]"><?= e($avail['order_number']) ?></span>
              <span class="font-black text-sm"><?= formatPrice($avail['total']) ?></span>
            </div>
            <p class="font-semibold text-sm"><?= e($avail['restaurant_name']) ?></p>
            <p class="text-xs text-gray-500 mt-1"><?= e(substr($avail['delivery_address'],0,60)) ?></p>
            <form method="POST" action="<?= BASE_URL ?>/api/orders/accept.php" class="mt-4">
              <?= csrfField() ?>
              <input type="hidden" name="order_id" value="<?= $avail['id'] ?>">
              <button type="submit" class="btn-primary w-full text-xs py-2 justify-center">Accept Delivery</button>
            </form>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
   
      <!-- My Active Deliveries -->
      <div>
        <h2 class="text-lg font-bold text-[#1b1c1c] mb-4">🏍 My Deliveries</h2>
        <?php if (empty($deliveries)): ?>
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">No deliveries yet. Accept your first order!</div>
        <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($deliveries as $del): ?>
          <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex justify-between items-center">
            <div>
              <span class="font-bold text-[#a83300]"><?= e($del['order_number']) ?></span>
              <p class="text-sm font-semibold mt-0.5"><?= e($del['restaurant_name']) ?></p>
              <p class="text-xs text-gray-500"><?= date('M j, g:i A', strtotime($del['updated_at'])) ?></p>
            </div>
            <div class="text-right">
              <span class="badge badge-<?= e($del['order_status']) ?>"><?= e(str_replace('_',' ',$del['order_status'])) ?></span>
              <p class="font-black text-[#a83300] mt-1"><?= formatPrice($del['total']) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

    <?php endif; ?>
  </div>
</div>
<?php
$noFooter = true;
include __DIR__ . '/../includes/footer.php';
?>
