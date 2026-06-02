<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_ADMIN);

// Fetch Active Customers
$activeCustomers = db()->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM orders WHERE user_id=u.id) AS order_count, 
           (SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=u.id AND payment_status='paid') AS total_spent 
    FROM users u 
    WHERE u.role='customer' AND u.account_status = 'active' 
    ORDER BY u.created_at DESC 
    LIMIT 100
")->fetchAll();

// Fetch Suspended Customers
$suspendedCustomers = db()->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM orders WHERE user_id=u.id) AS order_count, 
           (SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=u.id AND payment_status='paid') AS total_spent 
    FROM users u 
    WHERE u.role='customer' AND u.account_status = 'suspended' 
    ORDER BY u.updated_at DESC 
    LIMIT 100
")->fetchAll();

// Fetch Soft-Deleted Customers
$deletedCustomers = db()->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM orders WHERE user_id=u.id) AS order_count, 
           (SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=u.id AND payment_status='paid') AS total_spent 
    FROM users u 
    WHERE u.role='customer' AND u.account_status = 'deleted' 
    ORDER BY u.updated_at DESC 
    LIMIT 100
")->fetchAll();

$pageTitle = 'Customers — Zesto Admin';
$sidebarType = 'admin'; $activePage = 'customers.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans bg-zesto-dark text-[#dfe2eb] min-h-screen flex">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    
    <!-- Title Area -->
    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-5">
      <div>
        <span class="text-xs font-bold text-[#f59e0b] uppercase tracking-widest">Admin Control</span>
        <h1 class="text-2xl md:text-3xl font-black text-white mt-1">Customer Management</h1>
        <p class="text-xs text-white/60 mt-1">Manage active logins, suspend abusive users, or view deleted profiles</p>
      </div>
      <span class="text-xs text-white/40 font-semibold"><?= count($activeCustomers) ?> active registered</span>
    </div>

    <!-- Tabs Toggle -->
    <div class="flex gap-4 mb-6 border-b border-white/10 pb-px text-sm">
      <button onclick="switchTab('active')" id="tabBtn-active" class="px-4 py-2.5 border-b-2 border-[#f59e0b] font-bold text-white transition-all focus:outline-none cursor-pointer">
        Active Customers
      </button>
      <button onclick="switchTab('suspended')" id="tabBtn-suspended" class="px-4 py-2.5 border-b-2 border-transparent font-semibold text-white/50 hover:text-white transition-all focus:outline-none cursor-pointer">
        Suspended Archive (<?= count($suspendedCustomers) ?>)
      </button>
      <button onclick="switchTab('deleted')" id="tabBtn-deleted" class="px-4 py-2.5 border-b-2 border-transparent font-semibold text-white/50 hover:text-white transition-all focus:outline-none cursor-pointer">
        Deleted Archive (<?= count($deletedCustomers) ?>)
      </button>
    </div>

    <!-- ACTIVE TABLE -->
    <div id="table-active" class="glass-panel rounded-3xl border border-white/10 shadow-md shadow-black/20 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="bg-white/5 text-white/40 font-bold uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3.5">#</th>
              <th class="px-5 py-3.5">Name</th>
              <th class="px-5 py-3.5">Email</th>
              <th class="px-5 py-3.5 text-center">Orders</th>
              <th class="px-5 py-3.5 text-right">Spent</th>
              <th class="px-5 py-3.5 text-center">Status</th>
              <th class="px-5 py-3.5">Joined Date</th>
              <th class="px-5 py-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/10 font-semibold text-white/80">
            <?php foreach ($activeCustomers as $i => $c): ?>
            <tr class="hover:bg-white/5">
              <td class="px-5 py-4 text-white/40 font-mono"><?= $i+1 ?></td>
              <td class="px-5 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-zesto-orange/20 flex items-center justify-center text-zesto-orange font-bold text-xs shrink-0"><?= strtoupper(substr($c['name'],0,1)) ?></div>
                  <span class="font-extrabold text-white/95"><?= e($c['name']) ?></span>
                </div>
              </td>
              <td class="px-5 py-4 text-white/70"><?= e($c['email']) ?></td>
              <td class="px-5 py-4 text-center font-bold"><?= $c['order_count'] ?></td>
              <td class="px-5 py-4 text-right font-black text-[#f59e0b]"><?= formatPrice($c['total_spent']) ?></td>
              <td class="px-5 py-4 text-center">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border bg-emerald-500/10 border-emerald-500/20 text-emerald-400">Active</span>
              </td>
              <td class="px-5 py-4 text-white/40 text-[10px]"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
              <td class="px-5 py-4 text-right flex justify-end gap-2">
                <button onclick="showConfirmModal(<?= $c['id'] ?>, '<?= e($c['name']) ?>', 'suspend')"
                        class="text-[10px] px-3 py-1.5 rounded-full border border-amber-500/30 text-amber-400 hover:bg-amber-500/10 font-bold transition-all cursor-pointer">
                  Suspend
                </button>
                <button onclick="showConfirmModal(<?= $c['id'] ?>, '<?= e($c['name']) ?>', 'soft_delete')"
                        class="text-[10px] px-3 py-1.5 rounded-full border border-red-500/30 text-red-400 hover:bg-red-500/10 font-bold transition-all cursor-pointer">
                  Delete
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($activeCustomers)): ?>
            <tr>
              <td colspan="8" class="px-5 py-12 text-center text-white/40 font-bold">No active customers found.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- SUSPENDED TABLE -->
    <div id="table-suspended" class="glass-panel rounded-3xl border border-white/10 shadow-md shadow-black/20 overflow-hidden hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="bg-white/5 text-white/40 font-bold uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3.5">#</th>
              <th class="px-5 py-3.5">Name</th>
              <th class="px-5 py-3.5">Email</th>
              <th class="px-5 py-3.5 text-center">Orders</th>
              <th class="px-5 py-3.5 text-right">Spent</th>
              <th class="px-5 py-3.5 text-center">Status</th>
              <th class="px-5 py-3.5">Joined Date</th>
              <th class="px-5 py-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/10 font-semibold text-white/80">
            <?php foreach ($suspendedCustomers as $i => $c): ?>
            <tr class="hover:bg-white/5">
              <td class="px-5 py-4 text-white/40 font-mono"><?= $i+1 ?></td>
              <td class="px-5 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-amber-500/20 flex items-center justify-center text-amber-400 font-bold text-xs shrink-0"><?= strtoupper(substr($c['name'],0,1)) ?></div>
                  <span class="font-extrabold text-white/95"><?= e($c['name']) ?></span>
                </div>
              </td>
              <td class="px-5 py-4 text-white/70"><?= e($c['email']) ?></td>
              <td class="px-5 py-4 text-center font-bold"><?= $c['order_count'] ?></td>
              <td class="px-5 py-4 text-right font-black text-[#f59e0b]"><?= formatPrice($c['total_spent']) ?></td>
              <td class="px-5 py-4 text-center">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border bg-amber-500/10 border-amber-500/20 text-amber-400">Suspended</span>
              </td>
              <td class="px-5 py-4 text-white/40 text-[10px]"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
              <td class="px-5 py-4 text-right flex justify-end gap-2">
                <button onclick="showConfirmModal(<?= $c['id'] ?>, '<?= e($c['name']) ?>', 'activate')"
                        class="text-[10px] px-3 py-1.5 rounded-full border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10 font-bold transition-all cursor-pointer">
                  Activate
                </button>
                <button onclick="showConfirmModal(<?= $c['id'] ?>, '<?= e($c['name']) ?>', 'soft_delete')"
                        class="text-[10px] px-3 py-1.5 rounded-full border border-red-500/30 text-red-400 hover:bg-red-500/10 font-bold transition-all cursor-pointer">
                  Delete
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($suspendedCustomers)): ?>
            <tr>
              <td colspan="8" class="px-5 py-12 text-center text-white/40 font-bold">No suspended customers found.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DELETED ARCHIVE TABLE -->
    <div id="table-deleted" class="glass-panel rounded-3xl border border-white/10 shadow-md shadow-black/20 overflow-hidden hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="bg-white/5 text-white/40 font-bold uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3.5">#</th>
              <th class="px-5 py-3.5">Name</th>
              <th class="px-5 py-3.5">Email</th>
              <th class="px-5 py-3.5 text-center">Orders</th>
              <th class="px-5 py-3.5 text-right">Spent</th>
              <th class="px-5 py-3.5 text-center">Status</th>
              <th class="px-5 py-3.5">Joined Date</th>
              <th class="px-5 py-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/10 font-semibold text-white/80">
            <?php foreach ($deletedCustomers as $i => $c): ?>
            <tr class="hover:bg-white/5">
              <td class="px-5 py-4 text-white/40 font-mono"><?= $i+1 ?></td>
              <td class="px-5 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-red-50/20 flex items-center justify-center text-red-400 font-bold text-xs shrink-0"><?= strtoupper(substr($c['name'],0,1)) ?></div>
                  <span class="font-extrabold text-white/95"><?= e($c['name']) ?></span>
                </div>
              </td>
              <td class="px-5 py-4 text-white/70"><?= e($c['email']) ?></td>
              <td class="px-5 py-4 text-center font-bold"><?= $c['order_count'] ?></td>
              <td class="px-5 py-4 text-right font-black text-white/50"><?= formatPrice($c['total_spent']) ?></td>
              <td class="px-5 py-4 text-center">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border bg-red-500/10 border-red-500/20 text-red-400">Deleted</span>
              </td>
              <td class="px-5 py-4 text-white/40 text-[10px]"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
              <td class="px-5 py-4 text-right">
                <button onclick="showConfirmModal(<?= $c['id'] ?>, '<?= e($c['name']) ?>', 'activate')"
                        class="text-[10px] px-3 py-1.5 rounded-full border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10 font-bold transition-all cursor-pointer">
                  Restore / Activate
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($deletedCustomers)): ?>
            <tr>
              <td colspan="8" class="px-5 py-12 text-center text-white/40 font-bold">No deleted customers in database history.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Confirmation Modal Overlay -->
<div id="confirmModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
  <div class="glass-panel border border-white/10 rounded-3xl max-w-md w-full p-6 shadow-2xl shadow-black/80 bg-[#0c0d0f]">
    <h3 id="modalTitle" class="text-lg font-black text-white">Confirm Action</h3>
    <p id="modalDescription" class="text-xs text-white/60 mt-2 leading-relaxed">Are you sure you want to modify this account?</p>
    
    <div class="mt-4" id="reasonContainer">
      <label class="block text-[10px] text-white/40 font-bold uppercase tracking-wider mb-2">Reason for Action (Optional)</label>
      <input type="text" id="actionReason" placeholder="e.g. Repeated chargeback requests" 
             class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-[#f59e0b]">
    </div>

    <div class="flex justify-end gap-3 mt-6">
      <button onclick="closeConfirmModal()" class="px-4 py-2 rounded-full font-bold text-xs text-white/60 hover:bg-white/5 transition-all cursor-pointer">
        Cancel
      </button>
      <button id="modalConfirmBtn" class="px-5 py-2.5 bg-[#f59e0b] hover:bg-[#fbbf24] text-black font-bold rounded-full text-xs shadow-md shadow-black/20 transition-all cursor-pointer border-none">
        Confirm
      </button>
    </div>
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

function showConfirmModal(userId, userName, action) {
    let title = '';
    let desc = '';
    let showReason = false;
    
    if (action === 'suspend') {
        title = `Suspend Customer: ${userName}`;
        desc = `Suspending this customer will log them out of all active devices immediately and block future login attempts.`;
        showReason = true;
    } else if (action === 'activate') {
        title = `Activate Customer: ${userName}`;
        desc = `Activating this customer will restore their login access and set their account to Active status immediately.`;
    } else if (action === 'soft_delete') {
        title = `Delete Customer: ${userName}`;
        desc = `Soft-deleting this customer will remove them from Andheri list and move them to Deleted tab Archive.`;
        showReason = true;
    }

    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalDescription').textContent = desc;
    document.getElementById('reasonContainer').style.display = showReason ? 'block' : 'none';
    document.getElementById('actionReason').value = '';

    document.getElementById('modalConfirmBtn').onclick = async function() {
        const reason = document.getElementById('actionReason').value;
        await executeUserAction(userId, action, reason);
    };

    document.getElementById('confirmModal').classList.remove('hidden');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
}

async function executeUserAction(userId, action, reason) {
    try {
        const res = await fetch('<?= BASE_URL ?>/api/admin/users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= csrfToken() ?>'
            },
            body: JSON.stringify({
                user_id: userId,
                action: action,
                reason: reason
            })
        });
        const data = await res.json();
        if (data.success) {
            Zesto.toast(data.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            Zesto.toast(data.message || 'Action failed.', 'error');
        }
    } catch (e) {
        Zesto.toast('Network error occurred.', 'error');
    }
    closeConfirmModal();
}
</script>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
