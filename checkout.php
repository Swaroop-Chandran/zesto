<?php
/**
 * Zesto — Order Confirmation Page v2.0 (Swiggy-style)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

$orderNumber = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$order = null; $items = [];

if ($orderNumber) {
    $stmt = db()->prepare("
        SELECT o.*, r.name AS restaurant_name, r.slug AS restaurant_slug, r.delivery_time
        FROM orders o
        JOIN restaurants r ON r.id = o.restaurant_id
        WHERE o.order_number=:onum LIMIT 1
    ");
    $stmt->execute([':onum' => $orderNumber]);
    $order = $stmt->fetch();
    if ($order) {
        $iStmt = db()->prepare("SELECT * FROM order_items WHERE order_id=:oid");
        $iStmt->execute([':oid' => $order['id']]);
        $items = $iStmt->fetchAll();
    }
}

$pageTitle = 'Order Confirmed 🎉 — Zesto';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 bg-[#f5f5f0] pb-mobile-nav">
<div class="max-w-2xl mx-auto px-4 md:px-8 py-10 md:py-16">

  <!-- Success Banner -->
  <div class="text-center mb-8 animate-slide-up">
    <div class="relative inline-block mb-6">
      <div class="w-24 h-24 bg-green-500 rounded-full flex items-center justify-center mx-auto shadow-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <div class="absolute -top-1 -right-1 w-8 h-8 bg-[#f59e0b] rounded-full flex items-center justify-center text-sm animate-bounce">🎉</div>
    </div>
    <h1 class="text-3xl md:text-4xl font-black text-[#1b1c1c] mb-2">Order Confirmed!</h1>
    <?php if ($order): ?>
    <p class="text-gray-500 text-base">
      Your order <strong class="text-[#a83300]"><?= e($order['order_number']) ?></strong> is being prepared by <strong class="text-[#1b1c1c]"><?= e($order['restaurant_name']) ?></strong>
    </p>
    <div class="inline-flex items-center gap-2 mt-3 bg-[#ffdbd0] text-[#a83300] px-4 py-2 rounded-full text-sm font-bold">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Arriving in <?= e($order['delivery_time'] ?: '30–45 min') ?>
    </div>
    <?php else: ?>
    <p class="text-gray-500">Thank you for your order! Check your inbox for updates.</p>
    <?php endif; ?>
  </div>

  <?php if ($order): ?>
  <!-- Order Progress -->
  <div class="bg-white rounded-2xl border border-[#ece9e6] p-6 mb-5 shadow-sm">
    <h2 class="text-base font-black text-[#1b1c1c] mb-5">Order Status</h2>
    <div class="flex items-center gap-0">
      <?php
      $steps = [
        ['label' => 'Placed',    'icon' => '📋'],
        ['label' => 'Preparing', 'icon' => '👨‍🍳'],
        ['label' => 'On the way','icon' => '🛵'],
        ['label' => 'Delivered', 'icon' => '✅'],
      ];
      $statusOrder = [
        'pending' => 0,
        'accepted' => 0,
        'preparing' => 1,
        'ready_for_pickup' => 1,
        'assigned_to_delivery' => 2,
        'picked_up' => 2,
        'out_for_delivery' => 2,
        'delivered' => 3,
        'cancelled' => 3
      ];
      $currentStep = $statusOrder[$order['order_status']] ?? 0;
      ?>
      <?php foreach ($steps as $i => $step): ?>
      <?php $done = $i <= $currentStep; $active = $i === $currentStep; ?>
      <div class="flex items-center <?= $i < count($steps)-1 ? 'flex-1' : '' ?>">
        <div class="flex flex-col items-center">
          <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg
                      <?= $done ? 'bg-[#a83300] text-white shadow-md' : 'bg-gray-100 text-gray-400' ?>"><?= $step['icon'] ?></div>
          <span class="text-[10px] font-bold mt-1.5 <?= $done ? 'text-[#a83300]' : 'text-gray-400' ?> text-center"><?= $step['label'] ?></span>
        </div>
        <?php if ($i < count($steps)-1): ?>
        <div class="flex-1 h-1 mx-1 rounded <?= $i < $currentStep ? 'bg-[#a83300]' : 'bg-gray-200' ?>"></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Order Summary -->
  <div class="bg-white rounded-2xl border border-[#ece9e6] p-6 mb-5 shadow-sm">
    <h2 class="text-base font-black text-[#1b1c1c] mb-4">Order Summary</h2>
    <div class="space-y-3">
      <?php foreach ($items as $item): ?>
      <div class="flex justify-between items-center">
        <div class="flex items-center gap-2">
          <span class="bg-[#a83300] text-white text-xs font-bold w-6 h-6 rounded flex items-center justify-center"><?= $item['quantity'] ?></span>
          <span class="text-sm font-semibold text-[#1b1c1c]"><?= e($item['item_name']) ?></span>
        </div>
        <span class="text-sm font-bold text-[#1b1c1c]"><?= formatPrice($item['item_price'] * $item['quantity']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    
    <div class="border-t border-dashed border-gray-200 mt-4 pt-4 space-y-2">
      <div class="flex justify-between text-sm text-gray-500">
        <span>Item total</span>
        <span class="font-semibold"><?= formatPrice($order['subtotal']) ?></span>
      </div>
      <div class="flex justify-between text-sm text-gray-500">
        <span>Delivery fee</span>
        <span class="font-semibold"><?= $order['delivery_fee'] > 0 ? formatPrice($order['delivery_fee']) : '🎉 FREE' ?></span>
      </div>
      <div class="flex justify-between text-sm text-gray-500">
        <span>Platform fee</span>
        <span class="font-semibold"><?= formatPrice($order['taxes']) ?></span>
      </div>
      <div class="border-t border-gray-200 pt-2 flex justify-between font-black text-[#1b1c1c] text-lg">
        <span>Total Paid</span>
        <span class="text-[#a83300]"><?= formatPrice($order['total']) ?></span>
      </div>
    </div>
  </div>

  <!-- Delivery Address -->
  <div class="bg-white rounded-2xl border border-[#ece9e6] p-6 mb-5 shadow-sm">
    <div class="flex items-start gap-3">
      <div class="w-8 h-8 bg-[#ffdbd0] rounded-lg flex items-center justify-center shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#a83300]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      </div>
      <div>
        <h3 class="text-sm font-black text-[#1b1c1c] mb-1">Delivery Address</h3>
        <p class="text-sm text-gray-500 leading-relaxed"><?= e($order['delivery_address']) ?></p>
      </div>
    </div>
  </div>
  
  <!-- Payment Method -->
  <div class="bg-white rounded-2xl border border-[#ece9e6] p-6 mb-8 shadow-sm">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 bg-[#ffdbd0] rounded-lg flex items-center justify-center shrink-0">💳</div>
      <div>
        <h3 class="text-sm font-black text-[#1b1c1c]">Payment</h3>
        <p class="text-sm text-gray-500 capitalize"><?= e($order['payment_method'] ?: 'online') ?> — <span class="text-green-600 font-bold">Paid ✓</span></p>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Action Buttons -->
  <div class="flex flex-col sm:flex-row gap-3">
    <a href="<?= BASE_URL ?>/orders.php" class="btn-primary flex-1 justify-center py-4 text-base">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Track My Order
    </a>
    <a href="<?= BASE_URL ?>/index.php" class="btn-secondary flex-1 justify-center py-4 text-base">
      Continue Shopping
    </a>
  </div>

</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
