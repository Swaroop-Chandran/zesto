<?php
/**
 * Zesto — Dynamic Offers & Coupons Wall (offers.php)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/location_helper.php';

$pageTitle = 'Hot Coupons & Today\'s Offers — Zesto';
$description = 'Unlock 50% discount codes, weekend feasts, and sizzling food promotions. Save big with Rupee offers.';

$city = getCurrentCity();

// Fetch coupons
$coupons = db()->query("SELECT * FROM coupons WHERE is_active=1 ORDER BY id ASC")->fetchAll();

// Fetch banner offers
$offers = db()->query("SELECT * FROM offers WHERE is_active=1 ORDER BY id DESC")->fetchAll();

// Fetch trending food dishes
$trendingStmt = db()->prepare("
    SELECT mi.*, r.name AS restaurant_name, r.slug AS restaurant_slug
    FROM menu_items mi
    JOIN restaurants r ON r.id = mi.restaurant_id
    WHERE mi.is_available=1 AND mi.is_trending=1 AND r.city=:city
    ORDER BY mi.id ASC LIMIT 4
");
$trendingStmt->execute([':city' => $city]);
$trendingFoods = $trendingStmt->fetchAll();

// Fetch restaurant specific offers (which have discount text)
$resOffersStmt = db()->prepare("
    SELECT id, name, slug, discount, image, rating
    FROM restaurants
    WHERE is_active=1 AND city=:city AND discount IS NOT NULL AND discount != ''
    ORDER BY rating DESC LIMIT 6
");
$resOffersStmt->execute([':city' => $city]);
$restaurantOffers = $resOffersStmt->fetchAll();

$extraJs = [BASE_URL . '/assets/js/cart.js'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 pb-16 md:pb-8 bg-[#fbf9f8]">
<div class="max-w-[1280px] mx-auto px-6 md:px-10 py-8 md:py-12 flex flex-col gap-10 md:gap-14 font-sans">
  
  <!-- Header Title -->
  <div class="border-b border-gray-100 pb-6">
    <span class="text-xs font-bold text-[#a83300] uppercase tracking-widest">Coupons & Promos</span>
    <h1 class="text-2xl md:text-4xl font-black text-[#1b1c1c] tracking-tight mt-1">Offers & Deals for You</h1>
    <p class="text-xs text-gray-400 mt-1">Sizzling deals and coupon savings running right now in <?= e($city) ?></p>
  </div>

  <!-- ═══ ACTIVE COUPON CARDS ══════════════════════════════════ -->
  <section class="space-y-6">
    <h2 class="text-lg font-black text-[#1b1c1c] tracking-tight uppercase border-l-4 border-[#a83300] pl-3">Active Coupon Codes</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php foreach ($coupons as $coup): ?>
      <div class="bg-white rounded-2xl border border-gray-150 p-6 flex flex-col justify-between shadow-sm relative overflow-hidden group">
        <!-- background dynamic pattern -->
        <div class="absolute -right-4 -top-4 w-16 h-16 rounded-full bg-[#ffdbd0]/30 group-hover:scale-110 transition-transform"></div>
        
        <div class="space-y-3">
          <div class="flex items-center gap-2">
            <span class="text-xl">🏷️</span>
            <span class="bg-[#ffdbd0] text-[#a83300] text-xs font-black px-3 py-1 rounded-lg font-mono"><?= e($coup['code']) ?></span>
          </div>
          <div>
            <h3 class="font-extrabold text-sm text-gray-800">Flat <?= number_format($coup['discount_percentage'], 0) ?>% OFF</h3>
            <p class="text-[11px] text-gray-500 mt-1">Minimum order of <?= formatPrice($coup['min_order_value']) ?>. Max discount <?= formatPrice($coup['max_discount']) ?>.</p>
          </div>
        </div>

        <button onclick="navigator.clipboard.writeText('<?= e($coup['code']) ?>'); Zesto.toast('🎉 Code <?= e($coup['code']) ?> copied! Paste at checkout.','success')"
                class="mt-6 w-full py-2.5 bg-gray-50 hover:bg-[#a83300] hover:text-white border border-gray-200 hover:border-[#a83300] rounded-xl text-xs font-bold text-gray-700 transition-all active:scale-[0.98] cursor-pointer">
          Copy Coupon Code
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ═══ BANNER PROMO OFFERS ══════════════════════════════════ -->
  <?php if (!empty($offers)): ?>
  <section class="space-y-6">
    <h2 class="text-lg font-black text-[#1b1c1c] tracking-tight uppercase border-l-4 border-[#a83300] pl-3">Featured Banners</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <?php foreach ($offers as $off): ?>
      <div class="rounded-2xl overflow-hidden shadow-sm h-48 relative border border-gray-150 group">
        <img src="<?= $off['image'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD55EC6JU2Ccf8bZ_lQOIPcFj3kSuFakZ7Wxt-W0OpE6gHvNfyT49MPBvMPCJY1c2BABZdhUorBcsCBsRdjIi1hV8MN-qBhNvVfkFOGkwJwTBRXvQ5-xFFM-_YeWvKSO-ulKag_cSMFrCnyQzpDYMhwhkWuDXcHjGmocSq_VjOGID-zDA7slW4ITIXHdCFsr9Wqgp-iceaeLkg2slVfKC4jUW5I10I1LweWGGOkKZfWvhPdb4otIKn2F-2Gq6JwTISCzYme5748y0M' ?>"
             alt="<?= e($off['title']) ?>" class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-500">
        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/10 p-6 flex flex-col justify-end text-white">
          <span class="bg-[#a83300] text-white text-[8px] font-bold px-2 py-0.5 rounded w-fit uppercase tracking-widest mb-1.5"><?= e($off['code'] ? 'Code: '.$off['code'] : 'Deal Alert') ?></span>
          <h3 class="font-extrabold text-base md:text-lg"><?= e($off['title']) ?></h3>
          <p class="text-xs text-orange-50/90 mt-1 max-w-sm line-clamp-2 leading-relaxed"><?= e($off['description']) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ RESTAURANT DISCOUNTS ═════════════════════════════════ -->
  <?php if (!empty($restaurantOffers)): ?>
  <section class="space-y-6">
    <h2 class="text-lg font-black text-[#1b1c1c] tracking-tight uppercase border-l-4 border-[#a83300] pl-3">Kitchen Specific Offers</h2>
    <div class="grid grid-cols-2 md:grid-cols-6 gap-6">
      <?php foreach ($restaurantOffers as $res): ?>
      <div onclick="window.location.href='<?= BASE_URL ?>/restaurant.php?id=<?= e($res['slug']) ?>'"
           class="group cursor-pointer bg-white rounded-2xl border border-gray-150 p-3 hover:shadow-md transition-all text-center flex flex-col justify-between">
        <div class="relative shrink-0 aspect-square rounded-xl overflow-hidden mb-3 border border-gray-50">
          <img src="<?= e($res['image']) ?>" alt="<?= e($res['name']) ?>" 
               class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-505">
          <span class="absolute top-2 left-2 bg-[#a83300] text-white text-[8px] font-black px-1.5 py-0.5 rounded shadow-sm">
            <?= e($res['discount']) ?>
          </span>
        </div>
        <div>
          <h4 class="font-extrabold text-xs text-[#1b1c1c] truncate line-clamp-1 group-hover:text-[#a83300]"><?= e($res['name']) ?></h4>
          <p class="text-[9px] text-amber-500 font-bold mt-0.5">★ <?= number_format($res['rating'], 1) ?> rating</p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ TRENDING DISHES ══════════════════════════════════════ -->
  <?php if (!empty($trendingFoods)): ?>
  <section class="space-y-6 pb-6">
    <h2 class="text-lg font-black text-[#1b1c1c] tracking-tight uppercase border-l-4 border-[#a83300] pl-3">Popular & Trending Foods</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <?php foreach ($trendingFoods as $trend): ?>
      <div onclick="window.location.href='<?= BASE_URL ?>/restaurant.php?id=<?= $trend['restaurant_slug'] ?>'"
           class="group cursor-pointer bg-white rounded-2xl border border-gray-150 p-4 hover:shadow-sm transition-all flex gap-4">
        <img src="<?= $trend['image'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDgyfyP7rZ5dmAORInBqrp6VhdNNjTUc3kJb-uGys1DXHWggV9aJfUPMwEIDyBuchzQlSz2_H-GhgK4CPrHMHDdT9XcXzk0tjfAafyZhNbgMUYIhKMJFY_T6Lkiyv7bLzAcf_LH9yFedLNmQWqOU9FEplVgB2QNXItgaSM0PngufbViGwnLmgTG6zXQ_giH7ILTd1-Wvircw50sDHB3PrwG3ug70sfg4ydbThZMLuJ8BQqJ5NOQ4kOyZ6ntA2f5zDfwXWqenvjvpPQ' ?>"
             class="w-16 h-16 rounded-xl object-cover shrink-0 border border-gray-100 shadow-sm" alt="dish">
        <div class="flex-1 flex flex-col justify-between min-w-0">
          <div>
            <div class="flex items-center gap-1">
              <span class="w-3 h-3 border flex items-center justify-center rounded-sm text-[6px] font-bold shrink-0 <?= $trend['is_veg'] ? 'border-green-600 text-green-600' : 'border-red-600 text-red-600' ?>">
                <?= $trend['is_veg'] ? '●' : '▲' ?>
              </span>
              <h4 class="font-extrabold text-[11px] text-[#1b1c1c] truncate mt-0.5"><?= e($trend['name']) ?></h4>
            </div>
            <p class="text-[9px] text-gray-400 mt-0.5 truncate"><?= e($trend['restaurant_name']) ?></p>
          </div>
          <span class="text-[#a83300] font-black text-xs"><?= formatPrice($trend['price']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
