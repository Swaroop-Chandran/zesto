<?php
/**
 * Zesto — Dedicated Restaurant Details & Menu Page (restaurant.php)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/location_helper.php';

$slug = trim($_GET['id'] ?? '');

if (empty($slug)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Fetch Restaurant
$stmt = db()->prepare("SELECT * FROM restaurants WHERE slug = :slug AND is_active = 1 LIMIT 1");
$stmt->execute([':slug' => $slug]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    http_response_code(404);
    echo "<h1>Restaurant Not Found</h1>";
    exit;
}

// Fetch categories that have active foods in this restaurant
$catStmt = db()->prepare("
    SELECT DISTINCT c.* 
    FROM categories c
    JOIN menu_items mi ON mi.category_id = c.id
    WHERE mi.restaurant_id = :rid AND mi.is_available = 1
    ORDER BY c.display_order ASC
");
$catStmt->execute([':rid' => $restaurant['id']]);
$categories = $catStmt->fetchAll();

// Fetch all menu items
$menuStmt = db()->prepare("
    SELECT mi.*, c.name AS category_name
    FROM menu_items mi
    LEFT JOIN categories c ON c.id = mi.category_id
    WHERE mi.restaurant_id = :rid AND mi.is_available = 1
    ORDER BY c.display_order ASC, mi.display_order ASC, mi.id ASC
");
$menuStmt->execute([':rid' => $restaurant['id']]);
$menuItems = $menuStmt->fetchAll();

// Group items by category
$groupedMenu = [];
foreach ($menuItems as $item) {
    $catName = $item['category_name'] ?: 'Others';
    $groupedMenu[$catName][] = $item;
}

$pageTitle = $restaurant['name'] . ' — Zesto Menu';
$description = 'Order delicious meals from ' . $restaurant['name'] . ' in ' . $restaurant['city'] . '. Sizzling fast delivery.';
$extraJs = [BASE_URL . '/assets/js/cart.js'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 pb-16 md:pb-8 bg-[#fbf9f8]">
  
  <!-- ═══ RESTAURANT HEADER BRANDING ════════════════════════ -->
  <section class="relative w-full h-64 md:h-80 bg-black overflow-hidden">
    <img src="<?= $restaurant['banner_image'] ?: ($restaurant['image'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuACPe1OwcnqiSYz6mGkYPwpTwUZkoQT8Jeq336MHTLd5-szfhdGafbxKuJ3QVMBjxqcxm4UwTDipbBKsEECFSl_VHIJI58oJjjfYhQRcILi8-eedqeW9Mmlq_MJCKbX6yX6excKavJXTN1YruIGDT445j8SmCA9w4wNuJUqWrKgCGPpn5cc-E6Ph19OOcwM0Lu_vntB6rnd88Rr2jXfoBPCYqOX-gehGl-S_UIFfvPKeRPs0iP4Kc_0ZbV9KJ8H6mFYWZPD6gO7v2U') ?>" 
         alt="<?= e($restaurant['name']) ?>" class="w-full h-full object-cover opacity-60">
    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/35 to-transparent flex flex-col justify-end">
      <div class="max-w-[1280px] w-full mx-auto px-6 md:px-10 pb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 text-white">
        
        <div class="flex items-center gap-5">
          <!-- Logo -->
          <div class="w-20 h-20 md:w-24 md:h-24 bg-white rounded-2xl overflow-hidden shrink-0 border border-white/20 p-1 flex items-center justify-center shadow-lg">
            <img src="<?= $restaurant['logo_image'] ?: ($restaurant['image'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuACPe1OwcnqiSYz6mGkYPwpTwUZkoQT8Jeq336MHTLd5-szfhdGafbxKuJ3QVMBjxqcxm4UwTDipbBKsEECFSl_VHIJI58oJjjfYhQRcILi8-eedqeW9Mmlq_MJCKbX6yX6excKavJXTN1YruIGDT445j8SmCA9w4wNuJUqWrKgCGPpn5cc-E6Ph19OOcwM0Lu_vntB6rnd88Rr2jXfoBPCYqOX-gehGl-S_UIFfvPKeRPs0iP4Kc_0ZbV9KJ8H6mFYWZPD6gO7v2U') ?>" 
                 alt="Logo" class="w-full h-full object-cover rounded-xl shrink-0">
          </div>
          <div>
            <span class="bg-[#ffdbd0] text-[#a83300] text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider select-none">Zesto Premium Kitchen</span>
            <h1 class="text-2xl md:text-4xl font-black tracking-tight mt-1.5"><?= e($restaurant['name']) ?></h1>
            <p class="text-xs md:text-sm text-gray-300 mt-1"><?= e($restaurant['description'] ?: implode(' • ', explode(',', $restaurant['tags']))) ?></p>
          </div>
        </div>

        <!-- Rating & Delivery Time Info Box -->
        <div class="flex bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/10 gap-6 text-center text-xs text-gray-100">
          <div>
            <span class="block text-[#f59e0b] font-black text-sm">★ <?= number_format($restaurant['rating'], 1) ?></span>
            <span class="block text-[9px] text-gray-300 uppercase mt-0.5"><?= $restaurant['rating_count'] ?>+ reviews</span>
          </div>
          <div class="w-px bg-white/10"></div>
          <div>
            <span class="block font-extrabold text-sm"><?= e($restaurant['delivery_time']) ?></span>
            <span class="block text-[9px] text-gray-300 uppercase mt-0.5">Delivery Time</span>
          </div>
          <div class="w-px bg-white/10"></div>
          <div>
            <span class="block font-extrabold text-sm"><?= number_format($restaurant['distance'], 1) ?> km</span>
            <span class="block text-[9px] text-gray-300 uppercase mt-0.5">Distance</span>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ═══ MENU CONTENT CONTAINER ════════════════════════════ -->
  <section class="max-w-[1280px] mx-auto px-6 md:px-10 py-10 grid grid-cols-1 lg:grid-cols-12 gap-10 font-sans">
    
    <!-- LEFT SIDEBAR: STICKY MENU CATEGORIES -->
    <div class="lg:col-span-3 hidden lg:block">
      <div class="sticky top-24 bg-white rounded-2xl border border-gray-150 p-4 space-y-1 shadow-sm">
        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest px-3.5 mb-3">Categories</h4>
        <?php foreach ($categories as $cat): ?>
        <a href="#cat-section-<?= $cat['id'] ?>" 
           class="block w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold text-gray-600 hover:bg-[#ffdbd0] hover:text-[#a83300] transition-all">
          <?= e($cat['name']) ?>
        </a>
        <?php endforeach; ?>
        <?php if (isset($groupedMenu['Others'])): ?>
        <a href="#cat-section-others" 
           class="block w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-bold text-gray-600 hover:bg-[#ffdbd0] hover:text-[#a83300] transition-all">
          Others
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT: VEG TOGGLE & FOOD LISTING -->
    <div class="lg:col-span-9 space-y-8">
      
      <!-- Top Filters Menu -->
      <div class="bg-white rounded-2xl border border-gray-150 p-4 flex justify-between items-center shadow-sm">
        <div class="flex items-center gap-3">
          <span class="text-xs font-bold text-gray-500">Filter Dishes:</span>
          <!-- Veg Only Toggle Button -->
          <button id="veg-toggle-btn" onclick="ZestoRestaurant.toggleVeg()" class="px-4 py-2 border rounded-full text-xs font-bold transition-all cursor-pointer bg-white text-gray-600 border-gray-200 hover:bg-[#f5f3f3]">
            🌱 Veg Only
          </button>
        </div>
        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest"><span id="active-item-count"><?= count($menuItems) ?></span> Items Available</p>
      </div>

      <!-- grouped categories menu -->
      <div class="space-y-10">
        <?php foreach ($groupedMenu as $catName => $items): 
          $catId = 'others';
          foreach ($categories as $c) {
              if ($c['name'] === $catName) { $catId = $c['id']; break; }
          }
        ?>
        <div id="cat-section-<?= $catId ?>" class="scroll-mt-24 space-y-4">
          <h3 class="text-lg font-black text-[#1b1c1c] tracking-tight uppercase border-b border-gray-100 pb-2.5 flex items-center justify-between">
            <span><?= e($catName) ?></span>
            <span class="text-xs bg-[#ffdbd0] text-[#a83300] px-2.5 py-0.5 rounded-lg"><?= count($items) ?> items</span>
          </h3>

          <div class="divide-y divide-gray-100 bg-white rounded-2xl border border-gray-150 px-5 shadow-sm">
            <?php foreach ($items as $item): ?>
            <div class="py-5 flex gap-4 justify-between items-center menu-dish-card" data-veg="<?= $item['is_veg'] ? '1' : '0' ?>">
              
              <!-- Left side item description -->
              <div class="flex-1 space-y-2 min-w-0 pr-4">
                <div class="flex items-center gap-1.5">
                  <span class="w-4 h-4 border flex items-center justify-center rounded-sm text-[8px] font-bold shrink-0 <?= $item['is_veg'] ? 'border-green-600 text-green-600' : 'border-red-600 text-red-600' ?>">
                    <?= $item['is_veg'] ? '●' : '▲' ?>
                  </span>
                  <?php if ($item['is_special']): ?>
                  <span class="bg-[#ffdbd0] text-[#a83300] text-[8px] font-bold px-1.5 py-0.5 rounded select-none shrink-0 uppercase tracking-widest">Today's Choice</span>
                  <?php endif; ?>
                </div>
                <h4 class="font-extrabold text-sm text-[#1b1c1c] truncate"><?= e($item['name']) ?></h4>
                <p class="text-xs text-gray-500 font-medium leading-relaxed max-w-xl"><?= e($item['description'] ?: 'Delicious kitchen recipe cooked fresh daily.') ?></p>
                <p class="text-[#a83300] font-black text-sm mt-2"><?= formatPrice($item['price']) ?></p>

                <!-- Customization inputs displays -->
                <?php 
                $opts = json_decode($item['customization_options'] ?? '[]', true) ?: [];
                if (!empty($opts)): ?>
                <div class="mt-3.5 flex flex-wrap gap-1.5 items-center">
                  <span class="text-[9px] text-[#5c4037] font-bold uppercase tracking-wide mr-1">Customize:</span>
                  <?php foreach ($opts as $index => $opt): ?>
                  <button onclick="ZestoRestaurant.toggleCustomization(this, '<?= e($opt) ?>', <?= $item['id'] ?>)"
                          class="px-2.5 py-1 rounded text-[10px] font-semibold border transition-all bg-white text-gray-500 border-gray-200 hover:bg-[#ffdbd0] hover:text-[#a83300] hover:border-[#a83300] cursor-pointer">
                    <?= e($opt) ?>
                  </button>
                  <?php endforeach; ?>
                  <input type="hidden" id="custom-<?= $item['id'] ?>" value="">
                </div>
                <?php endif; ?>
              </div>

              <!-- Right side add to cart button & thumbnail -->
              <div class="flex flex-col items-center shrink-0 w-28 md:w-32 relative">
                <div class="w-24 h-24 md:w-28 md:h-28 rounded-2xl overflow-hidden border border-gray-100 shadow-sm mb-3">
                  <img src="<?= $item['image'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBZLbHo94g2948yCQi_Q1dVUSPm7BgZNWKJWBJwlPkeAxvdQXlETDOg88T30AcJwkVKeiDN3TZ3h4Uzx-ktYgh2MxBjNSgQmOdj3cR8mlX0VcaeE9AA-ynZ-cXRNbEOjFU47cUGFE9pWTrzGgqg6liFOHMYjEWhj-CyDCSeVvyO5282aXh30ZUK6uEhmx48fz-0Os880RaqVw-iUMvfgiHqI0oGi_UikGPKsXXv80RBqP2yhQQchY8YwAnkKE6NJTZJYRarOE_5lng' ?>" 
                       alt="<?= e($item['name']) ?>" class="w-full h-full object-cover">
                </div>
                <button data-add-cart="<?= $item['id'] ?>"
                        onclick="addToCart('<?= $item['id'] ?>', '<?= $restaurant['id'] ?>', '<?= $restaurant['slug'] ?>', document.getElementById('custom-<?= $item['id'] ?>') ? document.getElementById('custom-<?= $item['id'] ?>').value : '')"
                        class="absolute bottom-1 bg-white border border-gray-200 hover:bg-[#ffdbd0] hover:text-[#a83300] hover:border-[#a83300] shadow-md px-4 py-2 rounded-xl text-xs font-black text-gray-700 tracking-wide active:scale-95 transition-all flex items-center justify-center gap-1.5 w-[90px] shrink-0 cursor-pointer">
                  ADD
                </button>
              </div>

            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </section>

</main>

<script>
window.ZestoRestaurant = {
  isVegOnly: false,
  
  toggleVeg: function() {
    this.isVegOnly = !this.isVegOnly;
    const btn = document.getElementById('veg-toggle-btn');
    const cards = document.querySelectorAll('.menu-dish-card');
    
    if (this.isVegOnly) {
      btn.className = "px-4 py-2 border rounded-full text-xs font-bold transition-all cursor-pointer bg-green-600 text-white border-green-600 shadow-sm";
      let count = 0;
      cards.forEach(card => {
        if (card.dataset.veg !== '1') {
          card.classList.add('hidden');
        } else {
          card.classList.remove('hidden');
          count++;
        }
      });
      document.getElementById('active-item-count').textContent = count;
    } else {
      btn.className = "px-4 py-2 border rounded-full text-xs font-bold transition-all cursor-pointer bg-white text-gray-600 border-gray-200 hover:bg-[#f5f3f3]";
      cards.forEach(card => card.classList.remove('hidden'));
      document.getElementById('active-item-count').textContent = cards.length;
    }
  },

  toggleCustomization: function(btn, opt, id) {
    const input = document.getElementById('custom-' + id);
    if (!input) return;

    if (btn.classList.contains('active-opt')) {
      btn.classList.remove('active-opt', 'bg-[#ffdbd0]', 'text-[#a83300]', 'border-[#a83300]');
      btn.classList.add('bg-white', 'text-gray-500', 'border-gray-200');
      input.value = '';
    } else {
      // Deactivate other buttons
      const siblings = btn.parentNode.querySelectorAll('button');
      siblings.forEach(s => {
        s.classList.remove('active-opt', 'bg-[#ffdbd0]', 'text-[#a83300]', 'border-[#a83300]');
        s.classList.add('bg-white', 'text-gray-500', 'border-gray-200');
      });
      btn.classList.add('active-opt', 'bg-[#ffdbd0]', 'text-[#a83300]', 'border-[#a83300]');
      btn.classList.remove('bg-white', 'text-gray-500', 'border-gray-200');
      input.value = opt;
    }
  }
};
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
