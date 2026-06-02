<?php
/**
 * Zesto — Admin Delivery Partners Onboarding Approval Dashboard
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_ADMIN);

// Handle Approve / Suspend Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['partner_id'])) {
    verifyCsrf();
    $partnerId = (int)$_POST['partner_id'];
    $userId    = (int)$_POST['user_id'];
    $status    = (int)$_POST['status']; // 1 = Approve, 0 = Suspend

    db()->prepare("UPDATE delivery_partners SET is_approved = :status WHERE id = :pid")->execute([':status' => $status, ':pid' => $partnerId]);
    db()->prepare("UPDATE users SET is_active = :status WHERE id = :uid")->execute([':status' => $status, ':uid' => $userId]);

    setFlash('success', $status ? 'Delivery Partner application approved & activated! 🎉' : 'Delivery Partner suspended.');
    header('Location: ' . BASE_URL . '/admin/delivery.php');
    exit;
}

// Fetch all partners
$partners = db()->query("
    SELECT dp.*, u.name, u.email, u.phone, u.is_active
    FROM delivery_partners dp
    JOIN users u ON u.id = dp.user_id
    ORDER BY dp.id DESC
")->fetchAll();

$pageTitle = 'Delivery Partners Approval — Zesto Admin';
$sidebarType = 'admin'; $activePage = 'delivery.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    
    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-5">
      <div>
        <span class="text-xs font-bold text-zesto-orange uppercase tracking-widest">Admin Control Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-white mt-1">Delivery Partners Onboarding</h1>
      </div>
      <span class="text-xs font-bold bg-zesto-orange/20 text-zesto-orange px-3.5 py-1 rounded-full uppercase tracking-wider"><?= count($partners) ?> Applications</span>
    </div>

    <?php if (empty($partners)): ?>
    <div class="glass-panel border rounded-3xl p-12 text-center text-white/60 text-xs">
      🏍 No delivery partner applications found.
    </div>
    <?php else: ?>
    <div class="glass-panel rounded-3xl border border-white/10 shadow-md shadow-black/20 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="bg-white/5 text-white/40 font-bold uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3.5">Partner Details</th>
              <th class="px-5 py-3.5">Vehicle Type / Plate</th>
              <th class="px-5 py-3.5">License Number / Docs</th>
              <th class="px-5 py-3.5">Bank transfer</th>
              <th class="px-5 py-3.5 text-center">Status</th>
              <th class="px-5 py-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/10 font-semibold text-white/80">
            <?php foreach ($partners as $p): 
              $bank = json_decode($p['bank_details'] ?? '{}', true) ?: [];
            ?>
            <tr class="hover:bg-gray-50/50">
              
              <!-- Partner Profile -->
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl overflow-hidden bg-gray-105 border shrink-0">
                    <img src="<?= $p['selfie_image'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB8N79NnzBkNjJlH_j1MDJj1is3ZLHe5zf4QNLtwbxOQq9elB6w1-Eq8iK8QPl_kecxA_EkIvhpm8OgWEd4ajqCgMGzg6E-hsv5yjY-_3geRtReum09madJb2FykVO-iBptXyO0aAo7QKeUi_6V3VGDQHR_-IvGjgdbC6S_F7Nn3-nU5Cndtj6Oa_nD4-OhDUQvWly8lPVokasK_Cutr4tewAcOGRELa8H2W5TiFlz-XitgBe7vNL4pPys9N4ZRNJm_xGddPR209i4' ?>" 
                         class="w-full h-full object-cover rounded-lg" alt="selfie">
                  </div>
                  <div>
                    <h4 class="font-extrabold text-white text-sm"><?= e($p['name']) ?></h4>
                    <p class="text-[10px] text-white/40 mt-0.5"><?= e($p['email']) ?> • <?= e($p['phone']) ?></p>
                    <p class="text-[9px] text-white/40 mt-0.5 max-w-[180px] truncate"><?= e($p['address'] ?? 'No address') ?></p>
                  </div>
                </div>
              </td>

              <!-- Vehicle Details -->
              <td class="px-5 py-4">
                <span class="inline-block px-2 py-0.5 bg-white/10 text-white/80 text-[9px] rounded uppercase font-extrabold"><?= e($p['vehicle_type']) ?></span>
                <p class="font-mono font-bold mt-1 text-white uppercase"><?= e($p['vehicle_number'] ?: '—') ?></p>
              </td>

              <!-- License Details -->
              <td class="px-5 py-4">
                <p class="font-mono text-white/90"><?= e($p['driving_license_number'] ?: '—') ?></p>
                <?php if ($p['driving_license_image']): ?>
                <a href="<?= e($p['driving_license_image']) ?>" target="_blank" class="text-[9px] font-bold text-zesto-orange hover:underline mt-1 block">View License Image ➔</a>
                <?php else: ?>
                <span class="text-[9px] text-white/40 block mt-1">No Doc File</span>
                <?php endif; ?>
              </td>

              <!-- Bank Transfer Info -->
              <td class="px-5 py-4 text-[10px]">
                <?php if (!empty($bank)): ?>
                <p><span class="text-white/40">Acc:</span> <span class="font-mono font-bold"><?= e($bank['account_number'] ?? '—') ?></span></p>
                <p class="mt-0.5"><span class="text-white/40">Bank:</span> <?= e($bank['bank_name'] ?? '—') ?></p>
                <p class="mt-0.5"><span class="text-white/40">IFSC:</span> <span class="font-mono"><?= e($bank['ifsc_code'] ?? '—') ?></span></p>
                <?php else: ?>
                <span class="text-white/40">No bank credentials</span>
                <?php endif; ?>
              </td>

              <!-- Status Badge -->
              <td class="px-5 py-4 text-center">
                <span class="badge <?= $p['is_approved'] ? 'badge-delivered' : 'badge-cancelled' ?>">
                  <?= $p['is_approved'] ? 'Approved' : 'Pending' ?>
                </span>
              </td>

              <!-- Actions -->
              <td class="px-5 py-4 text-right">
                <form method="POST" class="inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="partner_id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="user_id" value="<?= $p['user_id'] ?>">
                  <input type="hidden" name="status" value="<?= $p['is_approved'] ? 0 : 1 ?>">
                  <button type="submit" 
                          data-confirm="<?= $p['is_approved'] ? 'Suspend this partner?' : 'Approve and activate this partner application?' ?>" 
                          class="px-4 py-2 border rounded-xl text-[10px] font-bold transition-all shadow-md shadow-black/20 cursor-pointer <?= $p['is_approved'] ? 'border-red-100 text-red-500 hover:bg-red-555 hover:bg-red-50' : 'border-green-150 text-green-600 hover:bg-green-50' ?>">
                    <?= $p['is_approved'] ? 'Suspend' : 'Approve Application' ?>
                  </button>
                </form>
              </td>

            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
