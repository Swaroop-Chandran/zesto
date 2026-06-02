<?php
/**
 * Zesto — Admin Delivery Confirmations & Dispute Management Control Panel
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_ADMIN);

// 1. Fetch pending confirmation orders (awaiting_customer_confirmation)
$pendingConfirmations = db()->query("
    SELECT o.id, o.order_number, o.created_at, o.updated_at, o.total,
           u.name AS customer_name, u.email AS customer_email,
           r.name AS restaurant_name,
           dp.name AS partner_name
    FROM orders o
    JOIN users u ON u.id = o.user_id
    JOIN restaurants r ON r.id = o.restaurant_id
    LEFT JOIN users dp ON dp.id = o.delivery_partner_id
    WHERE o.order_status = 'awaiting_customer_confirmation'
    ORDER BY o.updated_at DESC
")->fetchAll();

// 2. Fetch disputed orders (delivery_issue)
$disputedOrders = db()->query("
    SELECT o.id, o.order_number, o.created_at, o.updated_at, o.total,
           u.name AS customer_name, u.email AS customer_email,
           r.name AS restaurant_name,
           dp.name AS partner_name,
           (SELECT details FROM delivery_audit_logs WHERE order_id = o.id AND action_name = 'delivery_issue' ORDER BY id DESC LIMIT 1) AS dispute_reason
    FROM orders o
    JOIN users u ON u.id = o.user_id
    JOIN restaurants r ON r.id = o.restaurant_id
    LEFT JOIN users dp ON dp.id = o.delivery_partner_id
    WHERE o.order_status = 'delivery_issue'
    ORDER BY o.updated_at DESC
")->fetchAll();

$pageTitle = 'Delivery Confirmations & Disputes — Zesto Admin';
$extraJs   = [BASE_URL . '/assets/js/admin.js'];
$sidebarType = 'admin'; $activePage = 'confirmations.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans text-white">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>

  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-5">
      <div>
        <span class="text-xs font-bold text-red-600 uppercase tracking-widest">System Operations</span>
        <h1 class="text-2xl md:text-3xl font-black text-white mt-1">Confirmations &amp; Disputes</h1>
        <p class="text-xs text-white/60 mt-1">Oversee pending customer confirmations and resolve delivery partner payout disputes.</p>
      </div>
      <span class="text-xs text-white/40 font-semibold"><?= date('l, F j, Y') ?></span>
    </div>

    <!-- TABS -->
    <div class="mb-8">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- PENDING CONFIRMATIONS PANEL -->
        <div class="glass-panel rounded-3xl border border-white/10 shadow-md shadow-black/20 overflow-hidden flex flex-col">
          <div class="p-5 border-b border-white/10 bg-white/5 flex justify-between items-center">
            <h3 class="font-extrabold text-sm text-white flex items-center gap-2">
              ⏳ Pending Customer Confirmations 
              <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-black"><?= count($pendingConfirmations) ?></span>
            </h3>
          </div>
          <div class="overflow-x-auto flex-1">
            <table class="w-full text-xs text-left">
              <thead class="bg-white/5 text-white/40 font-bold uppercase tracking-wider">
                <tr>
                  <th class="px-4 py-3">Order / Customer</th>
                  <th class="px-4 py-3">Restaurant</th>
                  <th class="px-4 py-3">Courier</th>
                  <th class="px-4 py-3">Delivery Time</th>
                  <th class="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/10 font-semibold text-white/80">
                <?php foreach ($pendingConfirmations as $pc): ?>
                <tr class="hover:bg-gray-50/50">
                  <td class="px-4 py-3.5">
                    <p class="font-bold text-zesto-orange"><?= e($pc['order_number']) ?></p>
                    <p class="text-[10px] text-white/40 mt-0.5"><?= e($pc['customer_name']) ?></p>
                  </td>
                  <td class="px-4 py-3.5"><?= e($pc['restaurant_name']) ?></td>
                  <td class="px-4 py-3.5 text-emerald-700 font-bold">🏍 <?= e($pc['partner_name'] ?: 'Marcus') ?></td>
                  <td class="px-4 py-3.5 text-white/40 text-[10px]"><?= date('g:i A', strtotime($pc['updated_at'])) ?></td>
                  <td class="px-4 py-3.5 text-right">
                    <button onclick="resolveDispute(<?= $pc['id'] ?>, 'release')" class="py-1.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-[10px] shadow-xs cursor-pointer">
                      Release Payout ✓
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pendingConfirmations)): ?>
                <tr>
                  <td colspan="5" class="px-4 py-10 text-center text-white/40 font-bold">No orders currently awaiting customer confirmation.</td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- DISPUTED DELIVERIES PANEL -->
        <div class="glass-panel rounded-3xl border border-red-200 shadow-md shadow-black/20 overflow-hidden flex flex-col">
          <div class="p-5 border-b border-red-100 bg-red-50/50 flex justify-between items-center">
            <h3 class="font-extrabold text-sm text-red-800 flex items-center gap-2">
              ⚠️ Active Disputes &amp; Issues
              <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-[10px] font-black animate-pulse"><?= count($disputedOrders) ?></span>
            </h3>
          </div>
          <div class="overflow-x-auto flex-1">
            <table class="w-full text-xs text-left">
              <thead class="bg-red-50/30 text-red-800 font-bold uppercase tracking-wider">
                <tr>
                  <th class="px-4 py-3">Order / Customer</th>
                  <th class="px-4 py-3">Rider / Issue Reason</th>
                  <th class="px-4 py-3 text-right" style="width: 190px;">Operational Resolution</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-red-100 font-semibold text-white/80">
                <?php foreach ($disputedOrders as $do): ?>
                <tr class="hover:bg-red-50/10">
                  <td class="px-4 py-3.5">
                    <p class="font-bold text-red-700"><?= e($do['order_number']) ?></p>
                    <p class="text-[10px] text-white/40 mt-0.5"><?= e($do['customer_name']) ?></p>
                    <p class="text-[10px] font-bold text-white/70 mt-1">🍴 <?= e($do['restaurant_name']) ?></p>
                  </td>
                  <td class="px-4 py-3.5">
                    <p class="text-emerald-700 font-extrabold">🏍 <?= e($do['partner_name'] ?: 'Marcus') ?></p>
                    <p class="text-[10px] text-red-600 font-bold mt-1 bg-red-50 p-2 rounded-lg border border-red-100 leading-normal max-w-xs" title="<?= e($do['dispute_reason']) ?>">
                      <?= e($do['dispute_reason'] ?: 'No specific reason reported.') ?>
                    </p>
                  </td>
                  <td class="px-4 py-3.5 text-right flex flex-col gap-1.5 justify-center items-end">
                    <button onclick="resolveDispute(<?= $do['id'] ?>, 'release')" class="w-full py-1.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-[10px] shadow-xs cursor-pointer">
                      Release Earnings
                    </button>
                    <button onclick="resolveDispute(<?= $do['id'] ?>, 'refund')" class="w-full py-1.5 px-3 border border-red-300 text-red-650 hover:bg-red-50 rounded-lg font-bold text-[10px] cursor-pointer">
                      Refund Customer
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($disputedOrders)): ?>
                <tr>
                  <td colspan="3" class="px-4 py-10 text-center text-white/40 font-bold">No active delivery partner disputes open. Great job!</td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<script>
async function resolveDispute(orderId, actionType) {
    let confirmMsg = '';
    if (actionType === 'release') {
        confirmMsg = 'Are you sure you want to release partner earnings and mark this order completed?';
    } else {
        confirmMsg = 'Are you sure you want to refund the customer, cancel rider payouts, and close this dispute?';
    }

    if (!confirm(confirmMsg)) return;

    try {
        const res = await fetch('<?= BASE_URL ?>/api/admin/disputes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrfToken() ?>' },
            body: JSON.stringify({ order_id: orderId, action: actionType })
        });
        const data = await res.json();
        if (data.success) {
            Zesto.toast(`Operational dispute resolved: ${actionType === 'release' ? 'Released Earnings' : 'Customer Refunded'}`, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            Zesto.toast(data.message || 'Dispute resolution failed.', 'error');
        }
    } catch (e) {
        Zesto.toast('Network error occurred.', 'error');
    }
}
</script>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
