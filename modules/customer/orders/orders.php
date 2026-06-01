<?php
/**
 * Zesto — Orders History Page
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

$pageTitle   = 'My Orders — Zesto Nights';
$description = 'View your complete night feast history.';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Order history
$orderHistory = [];
$uid = getCurrentUser()['id'];
$histStmt = db()->prepare("
    SELECT o.*, r.name AS restaurant_name
    FROM orders o
    JOIN restaurants r ON r.id = o.restaurant_id
    WHERE o.user_id = :uid
    ORDER BY o.created_at DESC LIMIT 20
");
$histStmt->execute([':uid' => $uid]);
$orderHistory = $histStmt->fetchAll();

// Fetch items for each order to show the summary
$orderIds = array_column($orderHistory, 'id');
$orderItemsMap = [];
if (!empty($orderIds)) {
    $inQuery = implode(',', array_fill(0, count($orderIds), '?'));
    $itemsStmt = db()->prepare("SELECT order_id, item_name, quantity, item_price FROM order_items WHERE order_id IN ($inQuery)");
    $itemsStmt->execute($orderIds);
    $allItems = $itemsStmt->fetchAll();
    foreach ($allItems as $it) {
        $orderItemsMap[$it['order_id']][] = $it;
    }
}

include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/navbar.php';
?>

<main class="flex-1 bg-zesto-dark font-sans text-[#dfe2eb]">
<div class="w-full max-w-4xl mx-auto px-4 sm:px-10 py-6 space-y-8 animate-fade-in text-left pb-20">
  
  <div>
    <h1 class="text-3xl font-display font-black text-white">Your Night Feast Log</h1>
    <p class="text-sm text-white/50 mt-1">Review prior midnight meals delivered warm to your home</p>
  </div>

  <?php if (empty($orderHistory)): ?>
    <div class="text-center py-20 bg-white/5 rounded-2xl border border-white/5 space-y-4">
      <span class="text-4xl text-zesto-orange">📜</span>
      <h3 class="text-xl font-display font-bold text-white">No orders placed yet</h3>
      <p class="text-xs text-white/50 max-w-xs mx-auto">
        Your feast log is empty. Explore our curated late night menus and order now!
      </p>
      <a href="<?= BASE_URL ?>/index.php" class="inline-block mt-4 px-6 py-2.5 bg-zesto-orange text-white text-xs font-bold rounded-lg no-underline hover:bg-zesto-orange/90 transition">
        Browse Thattukadas
      </a>
    </div>
  <?php else: ?>
    <div class="space-y-6">
      <?php foreach ($orderHistory as $order): 
        $items = $orderItemsMap[$order['id']] ?? [];
        $statusStr = str_replace('_', ' ', $order['order_status']);
        $isActive = !in_array($order['order_status'], ['completed', 'delivered', 'cancelled', 'delivery_issue']);
      ?>
        <div class="p-5 sm:p-6 rounded-2xl bg-zesto-charcoal/30 border border-white/5 flex flex-col md:flex-row justify-between gap-6 hover:border-white/10 transition">
          
          <!-- Order Brief Info -->
          <div class="space-y-4 flex-1">
            <div class="flex flex-wrap items-center gap-3">
              <span class="text-xs font-bold text-zesto-orange">#<?= e($order['order_number']) ?></span>
              <span class="text-white/20">|</span>
              <span class="text-xs text-white/55 flex items-center gap-1">
                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                <?= date('M j, Y — g:i A', strtotime($order['created_at'])) ?>
              </span>
              <span class="text-white/20">|</span>
              <span class="<?= $isActive ? 'bg-zesto-orange/20 text-zesto-orange border-zesto-orange/40 animate-pulse' : 'bg-green-400/10 text-green-400 border-green-400/20' ?> border text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-wider">
                <?= e($statusStr) ?>
              </span>
            </div>

            <!-- Restaurant Headline -->
            <div>
              <h3 class="text-lg font-display font-bold text-white leading-none"><?= e($order['restaurant_name']) ?></h3>
            </div>

            <!-- Item Previews list -->
            <div class="space-y-2 border-l border-white/10 pl-4">
              <?php foreach ($items as $item): ?>
                <div class="text-xs text-white/70 flex justify-between max-w-sm">
                  <span><?= e($item['item_name']) ?> <span class="text-white/40">x<?= $item['quantity'] ?></span></span>
                  <span class="text-white/50 font-mono">₹<?= $item['item_price'] * $item['quantity'] ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Price details and Action buttons -->
          <div class="flex flex-row md:flex-col justify-between md:justify-center items-end gap-4 border-t md:border-t-0 border-white/5 pt-4 md:pt-0 min-w-[150px]">
            <div class="text-left md:text-right">
              <span class="text-[10px] text-white/40 block">Bill Amount:</span>
              <span class="text-xl font-display font-black text-zesto-amber">₹<?= $order['total'] ?></span>
              <span class="text-[8px] text-white/30 block mt-0.5">Via <?= e($order['payment_method']) ?></span>
            </div>

            <?php if ($isActive): ?>
              <a href="<?= BASE_URL ?>/checkout.php?order=<?= e($order['order_number']) ?>" class="px-4 py-2.5 bg-zesto-orange text-white font-bold text-xs rounded-full transition flex items-center justify-center gap-1.5 cursor-pointer no-underline text-center border-none hover:bg-zesto-orange/90 shadow-lg shadow-zesto-orange/20">
                <i data-lucide="navigation" class="w-3.5 h-3.5"></i>
                <span>Track Live</span>
              </a>
            <?php else: ?>
              <a href="<?= BASE_URL ?>/checkout.php?order=<?= e($order['order_number']) ?>" class="px-4 py-2.5 bg-zesto-orange/10 hover:bg-zesto-orange text-zesto-orange hover:text-white border border-zesto-orange/20 text-xs font-bold rounded-full transition flex items-center justify-center gap-1.5 cursor-pointer text-center no-underline">
                <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                <span>View Receipt</span>
              </a>
            <?php endif; ?>
          </div>

        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
