<?php
/**
 * Zesto — Order Tracking / Confirmation Page
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

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

$pageTitle = 'Order Tracking — Zesto Nights';
include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/navbar.php';
?>

<main class="flex-1 bg-zesto-dark font-sans text-[#dfe2eb]">
<div class="w-full max-w-7xl mx-auto px-4 sm:px-10 py-6 space-y-8 animate-fade-in pb-20">

  <?php if (!$order): ?>
    <div class="text-center py-20 space-y-4">
      <span class="text-4xl text-zesto-orange animate-bounce inline-block">🚚</span>
      <h2 class="text-xl font-display font-bold text-white">No active orders being traced</h2>
      <p class="text-xs text-white/50 max-w-sm mx-auto">
        You don't have any meals cooking on the tawa right now. Place an order to start live delivery tracking!
      </p>
      <a href="<?= BASE_URL ?>/index.php" class="inline-block px-6 py-2.5 bg-zesto-orange text-white text-xs font-bold rounded-full hover:bg-zesto-orange/90 transition cursor-pointer no-underline">
        Browse Thattukadas
      </a>
    </div>
  <?php else: ?>

    <!-- Route Header Info bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-3xl font-display font-extrabold text-white">Order Tracking</h1>
        <p class="text-xs text-white/50 mt-1">Order Ref: <span class="font-bold text-white/80">#<?= e($order['order_number']) ?></span> • Authenticating live satellite positions</p>
      </div>
      <div class="bg-white/5 border border-white/10 rounded-full px-4 py-1.5 flex items-center gap-2">
        <span class="w-2.5 h-2.5 bg-green-400 rounded-full animate-ping"></span>
        <span class="text-xs font-bold text-white/90">Est. Arrival: <?= e($order['delivery_time'] ?: '30-45 min') ?></span>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- Left Side Large Map Grid -->
      <div class="lg:col-span-2 relative h-[450px] bg-[#0c1017] rounded-2xl overflow-hidden border border-white/10 shadow-lg">
        
        <!-- Street Map Background Grid -->
        <div class="absolute inset-0 z-0 opacity-40">
          <svg class="w-full h-full text-white/10" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="0.5" />
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />
          </svg>
          
          <div class="absolute h-4 w-full top-1/4 bg-white/5 -skew-y-3"></div>
          <div class="absolute h-4 w-full top-2/3 bg-white/5 skew-y-6"></div>
          <div class="absolute w-4 h-full left-1/4 bg-white/5 -skew-x-6"></div>
          <div class="absolute w-4 h-full left-2/3 bg-white/5 skew-x-3"></div>
          
          <!-- Ambient street gas-burner lamp posts -->
          <div class="absolute text-center" style="top: 15%; left: 12%;">
            <div class="w-1.5 h-1.5 rounded-full bg-zesto-amber/90"></div>
            <div class="w-8 h-8 rounded-full bg-zesto-amber/25 blur-sm -mt-4 -ml-3 animate-pulse"></div>
          </div>
          <div class="absolute text-center" style="top: 22%; left: 40%;">
            <div class="w-1.5 h-1.5 rounded-full bg-zesto-amber/90"></div>
            <div class="w-8 h-8 rounded-full bg-zesto-amber/25 blur-sm -mt-4 -ml-3 animate-pulse"></div>
          </div>
          <div class="absolute text-center" style="top: 65%; left: 18%;">
            <div class="w-1.5 h-1.5 rounded-full bg-zesto-amber/90"></div>
            <div class="w-8 h-8 rounded-full bg-zesto-amber/25 blur-sm -mt-4 -ml-3 animate-pulse"></div>
          </div>
          <div class="absolute text-center" style="top: 55%; left: 88%;">
            <div class="w-1.5 h-1.5 rounded-full bg-zesto-amber/90"></div>
            <div class="w-8 h-8 rounded-full bg-zesto-amber/25 blur-sm -mt-4 -ml-3 animate-pulse"></div>
          </div>
        </div>

        <!-- Sizzling warm path -->
        <svg class="absolute inset-0 w-full h-full z-10" pointer-events="none">
          <path 
            d="M 120 320 Q 350 300 400 180 T 520 120" 
            fill="none" 
            stroke="rgba(245, 158, 11, 0.22)" 
            stroke-width="6" 
            stroke-linecap="round" 
          />
          <path 
            id="delivery-path"
            d="M 120 320 Q 350 300 400 180 T 520 120" 
            fill="none" 
            stroke="var(--color-zesto-orange)" 
            stroke-width="3.5" 
            stroke-linecap="round" 
            stroke-dasharray="8 4"
            class="fire-glow"
          />
        </svg>

        <!-- Glowing Static Thattukada Stand representing initial point -->
        <div class="absolute left-[80px] top-[290px] z-20 flex flex-col items-center">
          <div class="w-10 h-10 rounded-full border border-zesto-orange/40 bg-[#1c2026]/95 backdrop-blur-md flex items-center justify-center fire-glow">
            <span class="text-sm">🏮</span>
          </div>
          <span class="bg-[#1c2026] text-[8px] font-black text-zesto-orange border border-white/10 px-1.5 py-0.5 rounded-full mt-1.5 tracking-wider uppercase">
            <?= e($order['restaurant_name']) ?>
          </span>
        </div>

        <!-- Glowing dynamic Home pin representing order coordinates -->
        <div class="absolute left-[490px] top-[75px] z-20 flex flex-col items-center hidden sm:flex">
          <div class="bg-zesto-orange text-[#402d00] text-[9px] font-black font-mono px-2.5 py-1 rounded-lg border border-white/10 shadow-2xl relative mb-1 text-white select-none">
            ETA <?= e($order['delivery_time'] ?: '30 min') ?>
            <div class="absolute w-2 h-2 bg-zesto-orange rotate-45 left-[40%] bottom-[-4px]"></div>
          </div>
          <div class="w-10 h-10 rounded-full border border-zesto-amber/40 bg-[#10141a]/95 backdrop-blur flex items-center justify-center animate-pulse">
            <i data-lucide="map-pin" class="w-4 h-4 text-zesto-amber fill-zesto-amber"></i>
          </div>
        </div>

        <!-- Sizzling rider motion mockup -->
        <div class="absolute z-30 transition-all duration-300 transform -translate-x-1/2 -translate-y-1/2 left-[300px] top-[230px]">
          <div class="w-8 h-8 rounded-full bg-zesto-orange flex items-center justify-center border-2 border-white cursor-pointer fire-glow">
            <span class="text-xs">🛵</span>
          </div>
          <span class="bg-[#0c1017] text-[7px] font-bold text-white px-1.5 py-0.5 rounded border border-white/15 shadow whitespace-nowrap mt-1 block select-none">
            Rahul K.
          </span>
        </div>

        <!-- Dynamic tracker status tag overlay -->
        <div class="absolute bottom-4 left-4 z-20 glass-panel-heavy p-3 rounded-xl border border-white/15 max-w-xs space-y-1">
          <span class="text-[10px] uppercase font-black text-zesto-amber tracking-widest block flex items-center gap-1">
            <i data-lucide="sparkles" class="w-3 h-3"></i> Live Event State
          </span>
          <p class="text-xs text-white font-mono font-bold uppercase">
            <?php 
            $status = $order['order_status'];
            if ($status == 'pending' || $status == 'accepted') echo 'Order Received • Waiting for counter chefs';
            elseif ($status == 'preparing' || $status == 'ready_for_pickup') echo 'On the Heat • Porottas cooking on hot tawa';
            elseif ($status == 'picked_up' || $status == 'out_for_delivery') echo 'On Delivery Transit • Rahul K riding hot speed';
            elseif ($status == 'delivered') echo 'Served Warm • Enjoy dinner owl!';
            else echo 'Order Placed';
            ?>
          </p>
        </div>

      </div>

      <!-- Right Side: Step Progress panel -->
      <div class="lg:col-span-1 space-y-6">
        <div class="glass-panel rounded-2xl p-6 border border-white/10 space-y-6">
          
          <div>
            <h3 class="text-base font-display font-extrabold text-white">Order Status</h3>
            <p class="text-[10px] text-white/50">Tracking late night delivery stages</p>
          </div>

          <!-- Timelines -->
          <div class="space-y-6 relative pl-6 border-l border-white/10">
            <?php
            $steps = [
              ['id' => 'received', 'title' => 'Order Received', 'desc' => 'Sizzling chefs acknowledged recipe'],
              ['id' => 'cooking', 'title' => 'Cooking in progress', 'desc' => 'Porottas flipping on smoking tawa'],
              ['id' => 'out_for_delivery', 'title' => 'Out for Delivery', 'desc' => 'Assigned rider picked up dinner hot'],
              ['id' => 'delivered', 'title' => 'Served & Feasted', 'desc' => 'Warm delivery successfully completed'],
            ];
            $statusOrder = [
              'pending' => 0, 'accepted' => 0,
              'preparing' => 1, 'ready_for_pickup' => 1,
              'assigned_to_delivery' => 2, 'picked_up' => 2, 'out_for_delivery' => 2,
              'delivered' => 3, 'cancelled' => 3
            ];
            $currentStepIdx = $statusOrder[$order['order_status']] ?? 0;
            
            foreach ($steps as $idx => $stage):
              $isPast = $idx <= $currentStepIdx;
              $isActive = $idx == $currentStepIdx;
              
              if ($isPast) {
                $dotClass = 'bg-zesto-orange text-white border-zesto-orange glow-zesto-orange';
                $textClass = 'text-white';
              } else {
                $dotClass = 'bg-white/5 text-white/30 border-white/10';
                $textClass = 'text-white/40';
              }
            ?>
              <div class="relative text-left">
                <!-- Circle marker tag -->
                <div class="absolute -left-[35px] top-0 w-4.5 h-4.5 rounded-full flex items-center justify-center text-[8px] font-black border transition-all <?= $dotClass ?>">
                  ✓
                </div>
                <div>
                  <h4 class="text-xs font-bold leading-none <?= $textClass ?> <?= $isActive ? 'text-zesto-orange font-black text-sm' : '' ?>">
                    <?= $stage['title'] ?>
                  </h4>
                  <p class="text-[10px] text-white/40 mt-1 leading-relaxed">
                    <?= $stage['desc'] ?>
                  </p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Delivery Partner profile card -->
          <?php if (in_array($order['order_status'], ['assigned_to_delivery','picked_up','out_for_delivery','delivered'])): ?>
            <div class="p-4 rounded-xl bg-white/5 border border-white/5 flex items-center justify-between gap-3">
              <div class="flex items-center gap-3">
                <div class="relative">
                  <div class="w-12 h-12 rounded-full border border-white/15 bg-white/10 flex items-center justify-center text-xl">🛵</div>
                  <div class="absolute -bottom-0.5 -right-0.5 bg-zesto-cyan w-3 h-3 rounded-full border-2 border-zesto-charcoal"></div>
                </div>
                <div class="text-left font-sans">
                  <h4 class="text-xs font-bold text-white leading-none">Rahul K.</h4>
                  <span class="text-[10px] text-white/40 mt-1.5 block">Rating: ⭐ 4.9 (Late Hour Pro)</span>
                </div>
              </div>

              <div class="flex gap-1.5">
                <button
                  onclick="alert('Calling Rahul K at +91 98765 00000')"
                  class="p-2 rounded-full bg-zesto-orange/15 hover:bg-zesto-orange text-zesto-orange hover:text-white transition cursor-pointer border-none"
                >
                  <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                </button>
                <button
                  onclick="alert('Connecting securely to Rahul K\'s satellite dashboard')"
                  class="p-2 rounded-full bg-white/5 hover:bg-white/10 text-white transition cursor-pointer border-none"
                >
                  <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                </button>
              </div>
            </div>
          <?php endif; ?>

          <!-- Dynamic Items Ordered list -->
          <div>
            <span class="text-[10px] font-bold text-white/40 block mb-3 uppercase tracking-widest">Dinner Ordered:</span>
            <div class="space-y-2.5">
              <?php foreach ($items as $cartItem): ?>
                <div class="flex justify-between items-center text-xs text-white/85">
                  <span><?= e($cartItem['item_name']) ?> <span class="text-white/40">x<?= $cartItem['quantity'] ?></span></span>
                  <span class="font-mono text-white/60">₹<?= $cartItem['item_price'] * $cartItem['quantity'] ?></span>
                </div>
              <?php endforeach; ?>
              
              <div class="border-t border-white/10 mt-3 pt-2 space-y-1">
                <div class="flex justify-between items-center text-[10px] text-white/50">
                  <span>Item Total</span>
                  <span class="font-mono text-white/60">₹<?= formatPrice($order['subtotal']) ?></span>
                </div>
                <div class="flex justify-between items-center text-[10px] text-white/50">
                  <span>Delivery & Taxes</span>
                  <span class="font-mono text-white/60">₹<?= formatPrice($order['delivery_fee'] + $order['taxes']) ?></span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                <div class="flex justify-between items-center text-[10px] text-zesto-cyan font-bold">
                  <span>Discount</span>
                  <span class="font-mono">-₹<?= formatPrice($order['discount_amount']) ?></span>
                </div>
                <?php endif; ?>
              </div>
              
              <div class="border-t border-white/10 pt-2 mt-2 flex justify-between font-bold text-xs text-white">
                <span>Grand Total Paid</span>
                <span class="text-zesto-orange font-mono">₹<?= $order['total'] ?></span>
              </div>
            </div>
          </div>
          
          <div class="flex gap-2">
            <a href="<?= BASE_URL ?>/orders.php" class="flex-1 text-center py-2 bg-white/5 hover:bg-white/10 text-white text-xs font-bold rounded-lg transition border-none no-underline cursor-pointer">
              All Orders
            </a>
            <a href="<?= BASE_URL ?>/index.php" class="flex-1 text-center py-2 bg-zesto-orange hover:bg-zesto-orange/90 text-white text-xs font-bold rounded-lg transition border-none no-underline cursor-pointer">
              Shop More
            </a>
          </div>

        </div>
      </div>

    </div>

  <?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
