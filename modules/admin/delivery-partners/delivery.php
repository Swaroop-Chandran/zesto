<?php
/**
 * Zesto — Admin Delivery Partners Onboarding Approval Dashboard
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_ADMIN);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $userId = (int)$_POST['user_id'];
        
        if ($userId === (int)getCurrentUser()['id']) {
            setFlash('error', 'You cannot modify your own account.');
        } else {
            if ($action === 'approve') {
                $partnerId = (int)$_POST['partner_id'];
                db()->prepare("UPDATE delivery_partners SET is_approved = 1 WHERE id = :pid")->execute([':pid' => $partnerId]);
                db()->prepare("UPDATE users SET is_active = 1, account_status = 'active' WHERE id = :uid")->execute([':uid' => $userId]);
                setFlash('success', 'Delivery Partner application approved & activated! 🎉');
            } elseif ($action === 'suspend') {
                db()->prepare("UPDATE users SET is_active = 0, account_status = 'suspended', session_invalidated_at = CURRENT_TIMESTAMP WHERE id = :uid")->execute([':uid' => $userId]);
                setFlash('success', 'Delivery Partner suspended and session invalidated.');
            } elseif ($action === 'activate') {
                db()->prepare("UPDATE users SET is_active = 1, account_status = 'active', session_invalidated_at = NULL WHERE id = :uid")->execute([':uid' => $userId]);
                setFlash('success', 'Delivery Partner activated.');
            } elseif ($action === 'soft_delete') {
                db()->prepare("UPDATE users SET is_active = 0, account_status = 'deleted', session_invalidated_at = CURRENT_TIMESTAMP WHERE id = :uid")->execute([':uid' => $userId]);
                setFlash('success', 'Delivery Partner marked as deleted.');
            }
        }
        header('Location: ' . BASE_URL . '/admin/delivery.php');
        exit;
    }
}

// Fetch Active Partners (where account_status is 'active')
$activePartners = db()->query("
    SELECT dp.*, u.name, u.email, u.phone, u.is_active, u.account_status
    FROM delivery_partners dp
    JOIN users u ON u.id = dp.user_id
    WHERE u.account_status = 'active'
    ORDER BY dp.id DESC
")->fetchAll();

// Fetch Suspended Partners
$suspendedPartners = db()->query("
    SELECT dp.*, u.name, u.email, u.phone, u.is_active, u.account_status
    FROM delivery_partners dp
    JOIN users u ON u.id = dp.user_id
    WHERE u.account_status = 'suspended'
    ORDER BY u.updated_at DESC
")->fetchAll();

// Fetch Deleted Partners
$deletedPartners = db()->query("
    SELECT dp.*, u.name, u.email, u.phone, u.is_active, u.account_status
    FROM delivery_partners dp
    JOIN users u ON u.id = dp.user_id
    WHERE u.account_status = 'deleted'
    ORDER BY u.updated_at DESC
")->fetchAll();

$pageTitle = 'Delivery Partners Onboarding — Zesto Admin';
$sidebarType = 'admin'; $activePage = 'delivery.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans bg-zesto-dark text-[#dfe2eb] min-h-screen flex">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    
    <!-- Title Area -->
    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-5">
      <div>
        <span class="text-xs font-bold text-[#f59e0b] uppercase tracking-widest">Admin Control Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-white mt-1">Delivery Partners Onboarding</h1>
        <p class="text-xs text-white/60 mt-1">Manage applications, suspend/activate delivery accounts, or view archives</p>
      </div>
      <span class="text-xs text-white/40 font-semibold"><?= count($activePartners) ?> Active / Pending</span>
    </div>

    <!-- Tabs Toggle -->
    <div class="flex gap-4 mb-6 border-b border-white/10 pb-px text-sm">
      <button onclick="switchTab('active')" id="tabBtn-active" class="px-4 py-2.5 border-b-2 border-[#f59e0b] font-bold text-white transition-all focus:outline-none cursor-pointer bg-transparent">
        Active & Pending (<?= count($activePartners) ?>)
      </button>
      <button onclick="switchTab('suspended')" id="tabBtn-suspended" class="px-4 py-2.5 border-b-2 border-transparent font-semibold text-white/50 hover:text-white transition-all focus:outline-none cursor-pointer bg-transparent">
        Suspended (<?= count($suspendedPartners) ?>)
      </button>
      <button onclick="switchTab('deleted')" id="tabBtn-deleted" class="px-4 py-2.5 border-b-2 border-transparent font-semibold text-white/50 hover:text-white transition-all focus:outline-none cursor-pointer bg-transparent">
        Deleted Archive (<?= count($deletedPartners) ?>)
      </button>
    </div>

    <!-- Tab Contents Helper -->
    <?php
    $tabConfigs = [
        'active' => [
            'data' => $activePartners,
            'emptyMsg' => '🏍 No active or pending delivery partners found.',
            'isHidden' => false
        ],
        'suspended' => [
            'data' => $suspendedPartners,
            'emptyMsg' => '🏍 No suspended delivery partners found.',
            'isHidden' => true
        ],
        'deleted' => [
            'data' => $deletedPartners,
            'emptyMsg' => '🏍 No soft-deleted delivery partners in history.',
            'isHidden' => true
        ]
    ];

    foreach ($tabConfigs as $tabName => $cfg):
    ?>
    <div id="table-<?= $tabName ?>" class="glass-panel rounded-3xl border border-white/10 shadow-md shadow-black/20 overflow-hidden <?= $cfg['isHidden'] ? 'hidden' : '' ?>">
      <?php if (empty($cfg['data'])): ?>
      <div class="p-12 text-center text-white/40 text-xs">
        <?= $cfg['emptyMsg'] ?>
      </div>
      <?php else: ?>
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
            <?php foreach ($cfg['data'] as $p): 
              $bank = json_decode($p['bank_details'] ?? '{}', true) ?: [];
              $avatarLetter = strtoupper(substr($p['name'] ?? 'D', 0, 1));
            ?>
            <tr class="hover:bg-white/5">
              
              <!-- Partner Profile -->
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl overflow-hidden bg-white/5 border border-white/10 shrink-0">
                    <img src="<?= $p['selfie_image'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB8N79NnzBkNjJlH_j1MDJj1is3ZLHe5zf4QNLtwbxOQq9elB6w1-Eq8iK8QPl_kecxA_EkIvhpm8OgWEd4ajqCgMGzg6E-hsv5yjY-_3geRtReum09madJb2FykVO-iBptXyO0aAo7QKeUi_6V3VGDQHR_-IvGjgdbC6S_F7Nn3-nU5Cndtj6Oa_nD4-OhDUQvWly8lPVokasK_Cutr4tewAcOGRELa8H2W5TiFlz-XitgBe7vNL4pPys9N4ZRNJm_xGddPR209i4' ?>" 
                         class="w-full h-full object-cover rounded-lg" alt="selfie" referrerpolicy="no-referrer">
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
                <a href="<?= e($p['driving_license_image']) ?>" target="_blank" class="text-[9px] font-bold text-[#f59e0b] hover:underline mt-1 block">View License Image ➔</a>
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
                <?php if ($p['account_status'] === 'suspended'): ?>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border bg-amber-500/10 border-amber-500/20 text-amber-400">Suspended</span>
                <?php elseif ($p['account_status'] === 'deleted'): ?>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border bg-red-500/10 border-red-500/20 text-red-400">Deleted</span>
                <?php else: ?>
                  <?php if ($p['is_approved']): ?>
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border bg-emerald-500/10 border-emerald-500/20 text-emerald-400">Approved</span>
                  <?php else: ?>
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border bg-amber-500/10 border-amber-500/20 text-amber-400">Pending</span>
                  <?php endif; ?>
                <?php endif; ?>
              </td>

              <!-- Actions -->
              <td class="px-5 py-4 text-right">
                <div class="flex justify-end gap-2">
                  <?php if ($p['account_status'] === 'active'): ?>
                    <?php if (!$p['is_approved']): ?>
                    <form method="POST" onsubmit="return confirm('Approve this partner application?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="user_id" value="<?= $p['user_id'] ?>">
                      <input type="hidden" name="partner_id" value="<?= $p['id'] ?>">
                      <input type="hidden" name="action" value="approve">
                      <button type="submit" class="text-[10px] px-3.5 py-1.5 rounded-full bg-[#f59e0b] hover:bg-[#fbbf24] text-black font-extrabold transition-all cursor-pointer border-none shadow-md shadow-black/20">
                        Approve Application
                      </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" onsubmit="return confirm('Suspend this delivery partner? This will invalidate their current session.');">
                      <?= csrfField() ?>
                      <input type="hidden" name="user_id" value="<?= $p['user_id'] ?>">
                      <input type="hidden" name="action" value="suspend">
                      <button type="submit" class="text-[10px] px-3 py-1.5 rounded-full border border-amber-500/30 text-amber-400 hover:bg-amber-500/10 font-bold transition-all cursor-pointer bg-transparent">
                        Suspend
                      </button>
                    </form>
                    <?php endif; ?>
                    
                    <form method="POST" onsubmit="return confirm('Soft-delete this partner? historical records remain.');">
                      <?= csrfField() ?>
                      <input type="hidden" name="user_id" value="<?= $p['user_id'] ?>">
                      <input type="hidden" name="action" value="soft_delete">
                      <button type="submit" class="text-[10px] px-3 py-1.5 rounded-full border border-red-500/30 text-red-400 hover:bg-red-500/10 font-bold transition-all cursor-pointer bg-transparent">
                        Delete
                      </button>
                    </form>

                  <?php elseif ($p['account_status'] === 'suspended'): ?>
                    <form method="POST" onsubmit="return confirm('Restore/Activate this partner account?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="user_id" value="<?= $p['user_id'] ?>">
                      <input type="hidden" name="action" value="activate">
                      <button type="submit" class="text-[10px] px-3 py-1.5 rounded-full border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10 font-bold transition-all cursor-pointer bg-transparent">
                        Activate
                      </button>
                    </form>
                    
                    <form method="POST" onsubmit="return confirm('Soft-delete this suspended partner?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="user_id" value="<?= $p['user_id'] ?>">
                      <input type="hidden" name="action" value="soft_delete">
                      <button type="submit" class="text-[10px] px-3 py-1.5 rounded-full border border-red-500/30 text-red-400 hover:bg-red-500/10 font-bold transition-all cursor-pointer bg-transparent">
                        Delete
                      </button>
                    </form>

                  <?php elseif ($p['account_status'] === 'deleted'): ?>
                    <form method="POST" onsubmit="return confirm('Restore/Activate this deleted partner?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="user_id" value="<?= $p['user_id'] ?>">
                      <input type="hidden" name="action" value="activate">
                      <button type="submit" class="text-[10px] px-3 py-1.5 rounded-full border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10 font-bold transition-all cursor-pointer bg-transparent">
                        Restore / Activate
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>

            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

  </div>
</div>

<script>
function switchTab(tab) {
    const tabs = ['active', 'suspended', 'deleted'];
    tabs.forEach(t => {
        const table = document.getElementById('table-' + t);
        const btn = document.getElementById('tabBtn-' + t);
        if (t === tab) {
            table.classList.remove('hidden');
            btn.classList.add('border-[#f59e0b]', 'font-bold', 'text-white');
            btn.classList.remove('border-transparent', 'text-white/50');
        } else {
            table.classList.add('hidden');
            btn.classList.remove('border-[#f59e0b]', 'font-bold', 'text-white');
            btn.classList.add('border-transparent', 'text-white/50');
        }
    });
}
</script>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
