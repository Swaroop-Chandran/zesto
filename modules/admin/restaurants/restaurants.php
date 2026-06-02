<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_ADMIN);

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'toggle_restaurant') {
            $resId = (int)$_POST['restaurant_id'];
            $active = (int)$_POST['is_active'];
            db()->prepare("UPDATE restaurants SET is_active = :a WHERE id = :id")->execute([':a' => $active, ':id' => $resId]);
            setFlash('success', $active ? 'Restaurant activated successfully!' : 'Restaurant suspended/deactivated.');
        } 
        
        elseif ($action === 'update_owner_status') {
            $ownerId = (int)$_POST['owner_id'];
            $status = $_POST['status']; // 'active', 'suspended', 'deleted'
            
            if ($ownerId === (int)getCurrentUser()['id']) {
                setFlash('error', 'You cannot modify your own admin account.');
            } else {
                if ($status === 'suspended') {
                    db()->prepare("
                        UPDATE users 
                        SET is_active = 0, 
                            account_status = 'suspended', 
                            session_invalidated_at = CURRENT_TIMESTAMP 
                        WHERE id = :id
                    ")->execute([':id' => $ownerId]);
                    setFlash('success', 'Restaurant owner account suspended and sessions revoked.');
                } elseif ($status === 'active') {
                    db()->prepare("
                        UPDATE users 
                        SET is_active = 1, 
                            account_status = 'active', 
                            session_invalidated_at = NULL 
                        WHERE id = :id
                    ")->execute([':id' => $ownerId]);
                    setFlash('success', 'Restaurant owner account activated.');
                } elseif ($status === 'deleted') {
                    db()->prepare("
                        UPDATE users 
                        SET is_active = 0, 
                            account_status = 'deleted', 
                            session_invalidated_at = CURRENT_TIMESTAMP 
                        WHERE id = :id
                    ")->execute([':id' => $ownerId]);
                    setFlash('success', 'Restaurant owner account marked as deleted.');
                }
            }
        }
        
        header('Location: ' . BASE_URL . '/admin/restaurants.php');
        exit;
    }
}

// Fetch all restaurants and owner statuses
$allRestaurants = db()->query("
    SELECT r.*, u.name AS owner_name, u.account_status AS owner_status, u.id AS owner_user_id,
           (SELECT COUNT(*) FROM orders WHERE restaurant_id=r.id) AS total_orders 
    FROM restaurants r 
    LEFT JOIN users u ON u.id=r.owner_id 
    ORDER BY r.created_at DESC
")->fetchAll();

$activeRestaurants = [];
$suspendedRestaurants = [];
$deletedRestaurants = [];

foreach ($allRestaurants as $res) {
    $ownerStatus = $res['owner_status'] ?? 'active';
    
    if ($ownerStatus === 'deleted') {
        $deletedRestaurants[] = $res;
    } elseif ($res['is_active'] == 0 || $ownerStatus === 'suspended') {
        $suspendedRestaurants[] = $res;
    } else {
        $activeRestaurants[] = $res;
    }
}

$pageTitle = 'Restaurants — Zesto Admin';
$sidebarType = 'admin'; $activePage = 'restaurants.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans bg-zesto-dark text-[#dfe2eb] min-h-screen flex">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    
    <!-- Title Area -->
    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-5">
      <div>
        <span class="text-xs font-bold text-[#f59e0b] uppercase tracking-widest">Admin Control Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-white mt-1">Restaurant Management</h1>
        <p class="text-xs text-white/60 mt-1">Suspend owner accounts, invalidate sessions, and approve restaurant profiles</p>
      </div>
      <span class="text-xs text-white/40 font-semibold"><?= count($activeRestaurants) ?> active outlets</span>
    </div>

    <!-- Tabs Toggle -->
    <div class="flex gap-4 mb-6 border-b border-white/10 pb-px text-sm">
      <button onclick="switchTab('active')" id="tabBtn-active" class="px-4 py-2.5 border-b-2 border-[#f59e0b] font-bold text-white transition-all focus:outline-none cursor-pointer bg-transparent">
        Active Restaurants (<?= count($activeRestaurants) ?>)
      </button>
      <button onclick="switchTab('suspended')" id="tabBtn-suspended" class="px-4 py-2.5 border-b-2 border-transparent font-semibold text-white/50 hover:text-white transition-all focus:outline-none cursor-pointer bg-transparent">
        Suspended / Inactive (<?= count($suspendedRestaurants) ?>)
      </button>
      <button onclick="switchTab('deleted')" id="tabBtn-deleted" class="px-4 py-2.5 border-b-2 border-transparent font-semibold text-white/50 hover:text-white transition-all focus:outline-none cursor-pointer bg-transparent">
        Deleted Owners (<?= count($deletedRestaurants) ?>)
      </button>
    </div>

    <!-- TAB CONTENTS -->
    <?php
    $tabConfigs = [
        'active' => [
            'data' => $activeRestaurants,
            'emptyMsg' => '🏪 No active restaurants found.'
        ],
        'suspended' => [
            'data' => $suspendedRestaurants,
            'emptyMsg' => '🏪 No suspended or inactive restaurants found.'
        ],
        'deleted' => [
            'data' => $deletedRestaurants,
            'emptyMsg' => '🏪 No restaurants with deleted owners.'
        ]
    ];

    foreach ($tabConfigs as $tabName => $cfg):
        $isHidden = ($tabName !== 'active');
    ?>
    <div id="grid-<?= $tabName ?>" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 <?= $isHidden ? 'hidden' : '' ?>">
      <?php if (empty($cfg['data'])): ?>
      <div class="col-span-full glass-panel border rounded-3xl p-12 text-center text-white/40 text-xs">
        <?= $cfg['emptyMsg'] ?>
      </div>
      <?php else: ?>
        <?php foreach ($cfg['data'] as $res): 
          $ownerStatus = $res['owner_status'] ?? 'active';
        ?>
        <div class="glass-panel rounded-3xl border border-white/10 shadow-md shadow-black/20 overflow-hidden flex flex-col justify-between">
          <div>
            <!-- Banner Image -->
            <div class="h-36 overflow-hidden relative">
              <img src="<?= e($res['image']) ?>" alt="<?= e($res['name']) ?>" class="w-full h-full object-cover" referrerpolicy="no-referrer">
              
              <!-- Badges Overlaid -->
              <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
                <span class="px-2.5 py-1 rounded-full text-[9px] font-bold border <?= $res['is_active'] ? 'bg-emerald-500/20 border-emerald-500/30 text-emerald-400' : 'bg-red-500/20 border-red-500/30 text-red-400' ?>">
                  Outlet: <?= $res['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
                
                <?php if ($ownerStatus === 'active'): ?>
                <span class="px-2.5 py-1 rounded-full text-[9px] font-bold border bg-emerald-500/20 border-emerald-500/30 text-emerald-400">
                  Owner: Active
                </span>
                <?php elseif ($ownerStatus === 'suspended'): ?>
                <span class="px-2.5 py-1 rounded-full text-[9px] font-bold border bg-amber-500/20 border-amber-500/30 text-amber-400">
                  Owner: Suspended
                </span>
                <?php elseif ($ownerStatus === 'deleted'): ?>
                <span class="px-2.5 py-1 rounded-full text-[9px] font-bold border bg-red-500/20 border-red-500/30 text-red-400">
                  Owner: Deleted
                </span>
                <?php endif; ?>
              </div>
            </div>
            
            <!-- Details -->
            <div class="p-5">
              <h3 class="font-extrabold text-white text-base leading-tight mb-1"><?= e($res['name']) ?></h3>
              <p class="text-xs text-white/60 mb-4">
                Owner: <span class="font-bold text-white/80"><?= e($res['owner_name'] ?? 'Unassigned') ?></span>
              </p>
              
              <div class="grid grid-cols-3 gap-2 bg-white/5 border border-white/5 rounded-2xl p-3 text-center text-[10px] text-white/70">
                <div>
                  <p class="text-white/40 mb-0.5">Rating</p>
                  <p class="font-bold text-white">⭐ <?= number_format($res['rating'], 1) ?></p>
                </div>
                <div>
                  <p class="text-white/40 mb-0.5">Orders</p>
                  <p class="font-bold text-white"><?= $res['total_orders'] ?></p>
                </div>
                <div>
                  <p class="text-white/40 mb-0.5">Delivery</p>
                  <p class="font-bold text-white"><?= $res['is_free_delivery'] ? 'Free' : formatPrice($res['delivery_fee']) ?></p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Actions Container -->
          <div class="p-5 pt-0 border-t border-white/5 flex flex-col gap-2 mt-4">
            
            <!-- Restaurant Status Toggle -->
            <div class="flex justify-between items-center text-xs mt-3">
              <span class="text-white/40 font-bold">Restaurant Outlet</span>
              <form method="POST" class="inline" onsubmit="return confirm('Toggle active status for this restaurant?');">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle_restaurant">
                <input type="hidden" name="restaurant_id" value="<?= $res['id'] ?>">
                <input type="hidden" name="is_active" value="<?= $res['is_active'] ? 0 : 1 ?>">
                <button type="submit" class="text-[10px] px-3.5 py-1.5 rounded-full border border-white/20 hover:bg-white/10 font-bold text-white transition-all cursor-pointer bg-transparent">
                  <?= $res['is_active'] ? 'Deactivate Outlet' : 'Activate Outlet' ?>
                </button>
              </form>
            </div>
            
            <!-- Owner Suspension Actions -->
            <div class="flex justify-between items-center text-xs">
              <span class="text-white/40 font-bold">Owner Account</span>
              <div class="flex gap-2">
                <?php if ($ownerStatus === 'active'): ?>
                  <!-- Suspend Owner -->
                  <form method="POST" class="inline" onsubmit="return confirm('Suspend this owner account? Active sessions will be invalidated immediately.');">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_owner_status">
                    <input type="hidden" name="owner_id" value="<?= $res['owner_user_id'] ?>">
                    <input type="hidden" name="status" value="suspended">
                    <button type="submit" class="text-[10px] px-3 py-1.5 rounded-full border border-amber-500/30 text-amber-400 hover:bg-amber-500/10 font-bold transition-all cursor-pointer bg-transparent">
                      Suspend Owner
                    </button>
                  </form>
                  
                  <!-- Delete Owner -->
                  <form method="POST" class="inline" onsubmit="return confirm('Soft-delete this owner account? Historical records remain intact.');">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_owner_status">
                    <input type="hidden" name="owner_id" value="<?= $res['owner_user_id'] ?>">
                    <input type="hidden" name="status" value="deleted">
                    <button type="submit" class="text-[10px] px-3 py-1.5 rounded-full border border-red-500/30 text-red-400 hover:bg-red-500/10 font-bold transition-all cursor-pointer bg-transparent">
                      Delete
                    </button>
                  </form>
                  
                <?php elseif ($ownerStatus === 'suspended'): ?>
                  <!-- Activate Owner -->
                  <form method="POST" class="inline" onsubmit="return confirm('Activate this suspended owner account?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_owner_status">
                    <input type="hidden" name="owner_id" value="<?= $res['owner_user_id'] ?>">
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="text-[10px] px-3 py-1.5 rounded-full border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10 font-bold transition-all cursor-pointer bg-transparent">
                      Activate Owner
                    </button>
                  </form>
                  
                  <!-- Delete Owner -->
                  <form method="POST" class="inline" onsubmit="return confirm('Soft-delete this owner account?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_owner_status">
                    <input type="hidden" name="owner_id" value="<?= $res['owner_user_id'] ?>">
                    <input type="hidden" name="status" value="deleted">
                    <button type="submit" class="text-[10px] px-3 py-1.5 rounded-full border border-red-500/30 text-red-400 hover:bg-red-500/10 font-bold transition-all cursor-pointer bg-transparent">
                      Delete
                    </button>
                  </form>
                  
                <?php elseif ($ownerStatus === 'deleted'): ?>
                  <!-- Restore Owner -->
                  <form method="POST" class="inline" onsubmit="return confirm('Restore/Activate this deleted owner?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_owner_status">
                    <input type="hidden" name="owner_id" value="<?= $res['owner_user_id'] ?>">
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="text-[10px] px-3 py-1.5 rounded-full border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10 font-bold transition-all cursor-pointer bg-transparent">
                      Restore Owner
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
            
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

  </div>
</div>

<script>
function switchTab(tab) {
    const tabs = ['active', 'suspended', 'deleted'];
    tabs.forEach(t => {
        const grid = document.getElementById('grid-' + t);
        const btn = document.getElementById('tabBtn-' + t);
        if (t === tab) {
            grid.classList.remove('hidden');
            btn.classList.add('border-[#f59e0b]', 'font-bold', 'text-white');
            btn.classList.remove('border-transparent', 'text-white/50');
        } else {
            grid.classList.add('hidden');
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
