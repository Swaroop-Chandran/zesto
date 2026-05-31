<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

$orderNumber = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$order = null;
$items = [];

if ($orderNumber) {
    $stmt = db()->prepare("SELECT o.*, r.name AS restaurant_name FROM orders o JOIN restaurants r ON r.id = o.restaurant_id WHERE o.order_number = :onum LIMIT 1");
    $stmt->execute([':onum' => $orderNumber]);
    $order = $stmt->fetch();

    if ($order) {
        $iStmt = db()->prepare("SELECT * FROM order_items WHERE order_id = :oid");
        $iStmt->execute([':oid' => $order['id']]);
        $items = $iStmt->fetchAll();
    }
}

$pageTitle = 'Order Confirmed — Zesto';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<main class="flex-1 pb-16 md:pb-8">
<div class="max-w-2xl mx-auto px-6 py-16 text-center">
  <div class="w-24 h-24 bg-[#00c853]/10 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">✅</div>
  <h1 class="text-3xl font-extrabold text-[#1b1c1c] mb-3">Order Confirmed!</h1>
  <?php if ($order): ?>
  <p class="text-gray-500 mb-2">Your order <strong class="text-[#a83300]"><?= e($order['order_number']) ?></strong> has been placed.</p>
  <p class="text-gray-500 mb-8">From <strong><?= e($order['restaurant_name']) ?></strong> — Estimated arrival: <strong>25–35 min</strong></p>

  <div class="bg-white rounded-2xl border border-gray-200 p-6 text-left shadow-sm mb-6">
    <h3 class="font-bold text-sm uppercase tracking-wider text-gray-400 mb-4">Order Summary</h3>
    <div class="space-y-3">
      <?php foreach ($items as $item): ?>
      <div class="flex justify-between text-sm">
        <span><?= e($item['item_name']) ?> <span class="text-gray-400">x<?= $item['quantity'] ?></span></span>
        <span class="font-bold"><?= formatPrice($item['item_price'] * $item['quantity']) ?></span>
      </div>
      <?php endforeach; ?>
      <div class="border-t border-gray-100 pt-3 flex justify-between text-sm">
        <span>Delivery Fee</span><span class="font-bold"><?= formatPrice($order['delivery_fee']) ?></span>
      </div>
      <div class="flex justify-between text-sm">
        <span>Taxes</span><span class="font-bold"><?= formatPrice($order['taxes']) ?></span>
      </div>
      <div class="flex justify-between font-extrabold text-[#a83300] text-lg border-t border-gray-100 pt-3">
        <span>Total Paid</span><span><?= formatPrice($order['total']) ?></span>
      </div>
    </div>
  </div>
  <?php else: ?>
  <p class="text-gray-500 mb-8">Thank you for your order! You'll receive updates shortly.</p>
  <?php endif; ?>

  <div class="flex gap-4 justify-center flex-wrap">
    <a href="<?= BASE_URL ?>/orders.php" class="btn-primary">Track My Order</a>
    <a href="<?= BASE_URL ?>/index.php" class="btn-secondary">Continue Shopping</a>
  </div>
</div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
