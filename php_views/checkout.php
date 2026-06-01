<?php
/* 
  Zesto Nights - Premium Late-Night Food Delivery of Kerala
  Checkout Page View (checkout.php) 
*/
include_once 'header.php';

// Session variables representation
$user_addresses = [
  [
    'id' => 1,
    'label' => 'Home',
    'address_line' => 'Home - 123 Street',
    'details' => '1000 Home, Address',
    'is_selected' => 1
  ],
  [
    'id' => 2,
    'label' => 'Work',
    'address_line' => 'Work - 456 Avenue',
    'details' => '123 Street, Avenue',
    'is_selected' => 0
  ]
];

// Mock Session Cart representation
$cart_items = [
  ['name' => 'Beef Roast & Porotta', 'qty' => 1, 'price' => 180, 'image' => 'https://images.unsplash.com/photo-1603360946369-dc9bb6258143?auto=format&fit=crop&q=80&w=150'],
  ['name' => 'Chicken Fry', 'qty' => 1, 'price' => 220, 'image' => 'https://images.unsplash.com/photo-1563379971899-660589a01cc3?auto=format&fit=crop&q=80&w=150']
];

$subtotal = 400;
$delivery_fee = 50;
$taxes = 20;
$total = $subtotal + $delivery_fee + $taxes;
?>

<div class="w-full max-w-7xl mx-auto px-6 sm:px-10 py-8 text-left">
  <h1 class="text-2xl font-display font-black text-white mb-8">Checkout</h1>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
    
    <!-- Left Steps Column -->
    <div class="lg:col-span-2 space-y-6">
      
      <!-- 1. Delivery Address Card -->
      <div class="glass-panel rounded-2xl p-6 border border-white/10 space-y-5">
        <h3 class="text-sm font-display font-extrabold text-white">1. Delivery Address</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          
          <!-- Radio Selectors -->
          <div class="space-y-3">
            <?php foreach ($user_addresses as $addr): ?>
              <div class="p-4 rounded-xl border <?php echo $addr['is_selected'] ? 'bg-zinc-900 border-amber-300' : 'bg-white/5 border-white/5'; ?> flex items-start gap-3">
                <input type="radio" name="address_id" value="<?php echo $addr['id']; ?>" <?php echo $addr['is_selected'] ? 'checked' : ''; ?> class="mt-1">
                <div>
                  <h4 class="text-xs font-bold text-white"><?php echo $addr['label']; ?></h4>
                  <p class="text-[11px] text-white/70 mt-1"><?php echo $addr['address_line']; ?></p>
                  <p class="text-[9px] text-white/40 mt-0.5"><?php echo $addr['details']; ?></p>
                </div>
              </div>
            <?php endforeach; ?>

            <a href="address_add.php" class="block w-full py-2.5 text-center border border-dashed border-white/10 rounded-xl text-[10px] font-bold text-white/60 hover:text-white transition">
              + Add New Address
            </a>
          </div>

          <!-- Dark map element -->
          <div class="rounded-xl h-44 border border-white/10 bg-[#0d1117] flex items-center justify-center relative overflow-hidden">
            <span class="text-xs text-white/50 z-10 font-bold">📍 Kochi Delivery Zone Map</span>
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]"></div>
          </div>

        </div>
      </div>

      <!-- 2. Payment Method Card -->
      <div class="glass-panel rounded-2xl p-6 border border-white/10 space-y-4">
        <h3 class="text-sm font-display font-extrabold text-white">2. Payment Method</h3>
        
        <div class="space-y-3">
          <label class="p-4 rounded-xl bg-white/3 border border-white/5 flex items-center justify-between cursor-pointer">
            <div class="flex items-center gap-3">
              <input type="radio" name="payment" value="UPI" checked>
              <span class="text-xs font-semibold text-white">UPI (GooglePay, PhonePe, Paytm)</span>
            </div>
            <span class="text-xs tracking-widest font-black text-transparent bg-clip-text bg-gradient-to-r from-teal-400 to-indigo-400">UPI ▷</span>
          </label>

          <label class="p-4 rounded-xl bg-white/3 border border-white/5 flex items-center justify-between cursor-pointer">
            <div class="flex items-center gap-3">
              <input type="radio" name="payment" value="Card">
              <span class="text-xs font-semibold text-white">Credit / Debit Card</span>
            </div>
            <span class="text-[10px] text-white/40 font-bold uppercase border border-white/10 px-1 py-0.5 rounded">Visa / Master</span>
          </label>

          <label class="p-4 rounded-xl bg-white/3 border border-white/5 flex items-center gap-3 cursor-pointer">
            <input type="radio" name="payment" value="COD">
            <span class="text-xs font-semibold text-white">Cash on Delivery</span>
          </label>
        </div>
      </div>

    </div>

    <!-- Right Summary Card -->
    <div class="lg:col-span-1">
      <div class="glass-panel rounded-2xl p-5 border border-white/10 space-y-5">
        <div>
          <h3 class="text-sm font-display font-extrabold text-white">Order Summary</h3>
          <p class="text-[9px] text-white/40 block mt-0.5 uppercase tracking-wide">Your item from cart</p>
        </div>

        <div class="space-y-3.5 max-h-[220px] overflow-y-auto">
          <?php foreach ($cart_items as $item): ?>
            <div class="flex justify-between items-start text-xs border-b border-white/5 pb-2.5">
              <div class="flex gap-2">
                <img src="<?php echo $item['image']; ?>" class="w-8 h-8 rounded-lg object-cover" alt="">
                <div>
                  <h4 class="font-bold text-white"><?php echo $item['name']; ?></h4>
                  <span class="text-[9px] text-white/40">x<?php echo $item['qty']; ?></span>
                </div>
              </div>
              <span class="font-bold text-white">₹<?php echo $item['price'] * $item['qty']; ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="space-y-2 text-xs pt-2">
          <div class="flex justify-between text-white/60">
            <span>Subtotal</span>
            <span>₹<?php echo $subtotal; ?></span>
          </div>
          <div class="flex justify-between text-white/60">
            <span>Late Night Delivery Fee</span>
            <span>₹<?php echo $delivery_fee; ?></span>
          </div>
          <div class="flex justify-between text-white/60">
            <span>Taxes</span>
            <span>₹<?php echo $taxes; ?></span>
          </div>
          <div class="border-t border-white/10 my-1"></div>
          <div class="flex justify-between text-sm font-black text-white">
            <span>Total to Pay</span>
            <span class="text-orange-500">₹<?php echo $total; ?></span>
          </div>
        </div>

        <form action="place_order.php" method="POST">
          <input type="hidden" name="total" value="<?php echo $total; ?>">
          <button type="submit" class="w-full py-3.5 text-center bg-gradient-to-r from-orange-500 to-amber-300 rounded-full text-white text-xs font-extrabold hover:opacity-95 transition shadow shadow-orange-500/25">
            Place Order
          </button>
        </form>
      </div>
    </div>

  </div>
</div>

<?php 
include_once 'footer.php';
?>
