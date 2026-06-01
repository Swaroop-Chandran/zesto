<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_ADMIN);

// Handle toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    verifyCsrf();
    db()->prepare("UPDATE restaurants SET is_active=:a WHERE id=:id")->execute([':a'=>(int)$_POST['active'],':id'=>(int)$_POST['toggle_id']]);
    header('Location: '.BASE_URL.'/admin/restaurants.php'); exit;
}

$restaurants = db()->query("SELECT r.*, u.name AS owner_name, (SELECT COUNT(*) FROM orders WHERE restaurant_id=r.id) AS total_orders FROM restaurants r LEFT JOIN users u ON u.id=r.owner_id ORDER BY r.created_at DESC")->fetchAll();

$pageTitle = 'Restaurants — Zesto Admin';
$sidebarType = 'admin'; $activePage = 'restaurants.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-2xl font-extrabold text-[#1b1c1c]">Restaurants</h1>
      <span class="text-sm text-gray-500"><?= count($restaurants) ?> total</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($restaurants as $res): ?>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="h-36 overflow-hidden">
          <img src="<?= e($res['image']) ?>" alt="<?= e($res['name']) ?>" class="w-full h-full object-cover" referrerpolicy="no-referrer">
        </div>
        <div class="p-4">
          <div class="flex justify-between items-start mb-2">
            <h3 class="font-bold text-sm text-[#1b1c1c]"><?= e($res['name']) ?></h3>
            <span class="<?= $res['is_active'] ? 'badge-delivered' : 'badge-cancelled' ?> badge text-[10px]"><?= $res['is_active'] ? 'Active' : 'Inactive' ?></span>
          </div>
          <p class="text-xs text-gray-500 mb-3">Owner: <?= e($res['owner_name'] ?? 'Unassigned') ?></p>
          <div class="flex justify-between text-xs text-gray-600">
            <span>★ <?= number_format($res['rating'], 1) ?></span>
            <span><?= $res['total_orders'] ?> orders</span>
            <span><?= $res['is_free_delivery'] ? '🆓 Free' : formatPrice($res['delivery_fee']) ?></span>
          </div>
          <div class="flex gap-2 mt-4">
            <form method="POST" action="">
              <?= csrfField() ?>
              <input type="hidden" name="toggle_id" value="<?= $res['id'] ?>">
              <input type="hidden" name="active" value="<?= $res['is_active'] ? 0 : 1 ?>">
              <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 font-semibold">
                <?= $res['is_active'] ? 'Suspend' : 'Activate' ?>
              </button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
