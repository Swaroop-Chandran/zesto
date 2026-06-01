<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_ADMIN);

// Handle toggle user status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user'])) {
    verifyCsrf();
    db()->prepare("UPDATE users SET is_active=:a WHERE id=:id")->execute([':a'=>(int)$_POST['active'],':id'=>(int)$_POST['toggle_user']]);
    header('Location: '.BASE_URL.'/admin/customers.php'); exit;
}

$customers = db()->query("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) AS order_count, (SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=u.id AND payment_status='paid') AS total_spent FROM users u WHERE u.role='customer' ORDER BY u.created_at DESC LIMIT 100")->fetchAll();

$pageTitle = 'Customers — Zesto Admin';
$sidebarType = 'admin'; $activePage = 'customers.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-2xl font-extrabold text-[#1b1c1c]">Customers</h1>
      <span class="text-sm text-gray-500"><?= count($customers) ?> registered</span>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-[#f5f3f3]">
            <tr>
              <?php foreach(['#','Name','Email','Phone','Orders','Spent','Status','Joined','Actions'] as $h): ?>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase"><?= $h ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($customers as $i => $c): ?>
            <tr class="hover:bg-[#f5f3f3]/50">
              <td class="px-4 py-3 text-gray-400 text-xs"><?= $i+1 ?></td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-[#ffdbd0] flex items-center justify-center text-[#a83300] font-bold text-xs shrink-0"><?= strtoupper(substr($c['name'],0,1)) ?></div>
                  <span class="font-semibold"><?= e($c['name']) ?></span>
                </div>
              </td>
              <td class="px-4 py-3 text-gray-600"><?= e($c['email']) ?></td>
              <td class="px-4 py-3 text-gray-600"><?= e($c['phone'] ?? '—') ?></td>
              <td class="px-4 py-3 font-bold"><?= $c['order_count'] ?></td>
              <td class="px-4 py-3 font-bold text-[#a83300]"><?= formatPrice($c['total_spent']) ?></td>
              <td class="px-4 py-3"><span class="badge <?= $c['is_active'] ? 'badge-delivered' : 'badge-cancelled' ?>"><?= $c['is_active'] ? 'Active' : 'Suspended' ?></span></td>
              <td class="px-4 py-3 text-xs text-gray-400"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
              <td class="px-4 py-3">
                <form method="POST" class="inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="toggle_user" value="<?= $c['id'] ?>">
                  <input type="hidden" name="active" value="<?= $c['is_active'] ? 0 : 1 ?>">
                  <button data-confirm="<?= $c['is_active'] ? 'Suspend this customer?' : 'Activate this customer?' ?>"
                          class="text-xs px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 font-medium">
                    <?= $c['is_active'] ? 'Suspend' : 'Activate' ?>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
