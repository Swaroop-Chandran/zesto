<?php
/* 
  Zesto Nights - Premium Late-Night Food Delivery of Kerala
  Order Tracking Live Page (order_tracking.php) 
*/
include_once 'header.php';

// Fetch active order details by order_id url param
$order_id = htmlspecialchars($_GET['order_id'] ?? 'z-9981');

// In production, fetch current order and assigned rider details from MySQL db:
// $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
// ...

$order_info = [
  'id' => $order_id,
  'restaurant_name' => "Mani's Thattukada",
  'status' => 'cooking', // received, cooking, out_for_delivery, delivered
  'rider_name' => 'Rahul K.',
  'rider_rating' => 4.9,
  'total' => 470
];
?>

<div class="w-full max-w-7xl mx-auto px-6 sm:px-10 py-8 space-y-8 text-left">
  
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
      <h1 class="text-2xl font-display font-black text-white">Order Tracking</h1>
      <p class="text-xs text-white/50">Order ID: #<?php echo $order_info['id']; ?> • Live satellite coordinates active</p>
    </div>
    <div class="bg-white/5 border border-white/10 rounded-full px-4 py-1.5 flex items-center gap-2 text-xs font-semibold text-white/80">
      <span class="w-2.5 h-2.5 bg-green-400 rounded-full animate-ping"></span>
      <span>Arriving in 5 mins</span>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Street map layout component -->
    <div class="lg:col-span-2 rounded-2xl h-[420px] bg-[#0c1017] border border-white/10 relative overflow-hidden flex items-center justify-center">
      <div class="absolute inset-0 opacity-15">
        <svg class="w-full h-full text-white/10" xmlns="http://www.w3.org/2000/svg">
          <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" strokeWidth="0.5" />
          </pattern>
          <rect width="100%" height="100%" fill="url(#grid)" />
        </svg>
      </div>

      <!-- Glowing path vector lines -->
      <svg class="absolute inset-0 w-full h-full" pointerEvents="none">
        <path d="M 120 300 Q 320 280 380 160 T 500 100" fill="none" stroke="#ff5625" strokeWidth="3" strokeLinecap="round" strokeDasharray="6 3" />
      </svg>

      <!-- Pin indicator labels -->
      <span class="text-xs font-semibold text-white/60">🛰️ Interactive Thattukada Route Map active</span>
    </div>

    <!-- Active progress stepper panel -->
    <div class="lg:col-span-1 space-y-6">
      <div class="glass-panel rounded-2xl p-6 border border-white/10 space-y-6">
        <div>
          <h3 class="text-sm font-display font-extrabold text-white">Order Status</h3>
          <p class="text-[9px] text-white/40 uppercase">Assessing live delivery events</p>
        </div>

        <!-- Custom vertical progress lines -->
        <div class="space-y-6 relative pl-6 border-l border-white/10 text-xs">
          
          <div class="relative">
            <div class="absolute -left-[30px] top-0.5 w-3.5 h-3.5 rounded-full bg-orange-600 border border-orange-500"></div>
            <div>
              <h4 class="font-bold text-white">Order Received</h4>
              <p class="text-[9px] text-white/40 mt-0.5">Thattukada chefs accepted order recipe</p>
            </div>
          </div>

          <div class="relative">
            <div class="absolute -left-[30px] top-0.5 w-3.5 h-3.5 rounded-full bg-orange-600 border border-orange-500"></div>
            <div>
              <h4 class="font-bold text-white text-orange-500 text-sm">Cooking in progress 🔥</h4>
              <p class="text-[9px] text-white/40 mt-0.5">Porottas currently flipping on smoking hot tawa</p>
            </div>
          </div>

          <div class="relative">
            <div class="absolute -left-[30px] top-0.5 w-3.5 h-3.5 rounded-full bg-white/5 border border-white/10"></div>
            <div class="opacity-50">
              <h4 class="font-bold text-white">Out for Delivery</h4>
              <p class="text-[9px] text-white/40 mt-0.5">Rider assigns and picks up dinner hot</p>
            </div>
          </div>

          <div class="relative">
            <div class="absolute -left-[30px] top-0.5 w-3.5 h-3.5 rounded-full bg-white/5 border border-white/10"></div>
            <div class="opacity-50">
              <h4 class="font-bold text-white">Served & Feasted</h4>
              <p class="text-[9px] text-white/40 mt-0.5">Delivered cozy meal to home doorstep</p>
            </div>
          </div>

        </div>

        <!-- Assigned Rider Profile Card -->
        <div class="p-3.5 rounded-xl bg-white/3 border border-white/5 flex items-center justify-between gap-3 text-xs">
          <div class="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-orange-600/10 border border-orange-600/20 text-center flex items-center justify-center text-sm">🛵</div>
            <div>
              <h4 class="font-bold text-white"><?php echo $order_info['rider_name']; ?></h4>
              <p class="text-[9px] text-white/40 mt-0.5">Rating: ⭐ <?php echo $order_info['rider_rating']; ?></p>
            </div>
          </div>
          <a href="tel:+919876543210" class="p-2 bg-orange-600/10 text-orange-500 rounded-full hover:bg-orange-600 hover:text-white transition">
            📞 Call
          </a>
        </div>

        <!-- Bill Details -->
        <div class="bg-white/3 p-4 rounded-xl border border-white/5 text-xs text-white/70 space-y-1.5">
          <div class="flex justify-between">
            <span>Mani's Thattukada Hub</span>
            <span class="font-mono text-white/60">₹<?php echo $order_info['total'] - 70; ?></span>
          </div>
          <div class="flex justify-between font-bold text-white pt-1.5 border-t border-white/5 mt-1.5">
            <span>Billing Total Paid</span>
            <span class="text-orange-500 font-mono">₹<?php echo $order_info['total']; ?></span>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<?php 
include_once 'footer.php';
?>
