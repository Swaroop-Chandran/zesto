<?php
/**
 * Zesto — Modernized Swiggy-Style Homepage (index.php)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/location_helper.php';

$pageTitle   = 'Zesto — Sizzling Hot Food Delivery';
$description = 'Order fresh, delicious meals from top-rated restaurants in your location. Sizzling fast delivery.';

$city = getCurrentCity();
$locName = getCurrentLocation();

// ── Fetch Offers/Coupons for Carousel ───────────────────────
$offers = db()->query("SELECT * FROM offers WHERE is_active=1 ORDER BY id DESC")->fetchAll();

// ── Fetch Categories ──────────────────────────────────────────
$categories = db()->query("SELECT * FROM categories WHERE is_active=1 ORDER BY display_order ASC")->fetchAll();

// ── Fetch Featured Restaurants in City ────────────────────────
$featuredStmt = db()->prepare("SELECT * FROM restaurants WHERE is_active=1 AND city=:city AND is_featured=1 ORDER BY rating DESC LIMIT 6");
$featuredStmt->execute([':city' => $city]);
$featuredRestaurants = $featuredStmt->fetchAll();

// ── Fetch Popular Restaurants in City ─────────────────────────
$popularStmt = db()->prepare("SELECT * FROM restaurants WHERE is_active=1 AND city=:city AND is_popular=1 ORDER BY id DESC LIMIT 6");
$popularStmt->execute([':city' => $city]);
$popularRestaurants = $popularStmt->fetchAll();

// ── Fetch Best Rated Restaurants in City ──────────────────────
$bestRatedStmt = db()->prepare("SELECT * FROM restaurants WHERE is_active=1 AND city=:city AND is_best_rated=1 ORDER BY rating DESC LIMIT 6");
$bestRatedStmt->execute([':city' => $city]);
$bestRatedRestaurants = $bestRatedStmt->fetchAll();

// ── Fetch Today's Specials in City ────────────────────────────
$specialsStmt = db()->prepare("
    SELECT mi.*, r.name AS restaurant_name, r.slug AS restaurant_slug
    FROM menu_items mi
    JOIN restaurants r ON r.id = mi.restaurant_id
    WHERE mi.is_available=1 AND mi.is_special=1 AND r.city=:city
    ORDER BY mi.id DESC LIMIT 6
");
$specialsStmt->execute([':city' => $city]);
$specials = $specialsStmt->fetchAll();

// ── Fetch Trending Foods in City ──────────────────────────────
$trendingStmt = db()->prepare("
    SELECT mi.*, r.name AS restaurant_name, r.slug AS restaurant_slug
    FROM menu_items mi
    JOIN restaurants r ON r.id = mi.restaurant_id
    WHERE mi.is_available=1 AND mi.is_trending=1 AND r.city=:city
    ORDER BY mi.id ASC LIMIT 6
");
$trendingStmt->execute([':city' => $city]);
$trendingFoods = $trendingStmt->fetchAll();

// ── Search & General Filters list ─────────────────────────────
$search   = trim($_GET['search']   ?? '');
$category = trim($_GET['category'] ?? '');
$sortBy   = $_GET['sort']          ?? 'none';

$sql = "SELECT * FROM restaurants WHERE is_active = 1 AND city = :city";
$params = [':city' => $city];

if ($search !== '') {
    $sql .= " AND (name LIKE :search OR tags LIKE :search2)";
    $params[':search']  = "%$search%";
    $params[':search2'] = "%$search%";
}
if ($category !== '') {
    $sql .= " AND (tags LIKE :cat)";
    $params[':cat'] = "%$category%";
}

$validSorts = [
    'rating'   => "ORDER BY rating DESC",
    'time'     => "ORDER BY delivery_time_value ASC",
    'distance' => "ORDER BY distance ASC",
    'none'     => "ORDER BY id ASC",
];
$sql .= ' ' . ($validSorts[$sortBy] ?? $validSorts['none']);

$stmt = db()->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 pb-16 md:pb-8 bg-[#fbf9f8]">
<div class="relative font-sans text-[#1b1c1c] max-w-[1280px] mx-auto px-6 md:px-10 py-6 md:py-10 flex flex-col gap-10 md:gap-14">

  <!-- ═══ SWIGGY LOCATION BANNER ═══════════════════════════════ -->
  <section class="bg-gradient-to-r from-[#a83300] to-[#d24200] text-white rounded-3xl p-6 md:p-8 shadow-xl flex flex-col sm:flex-row justify-between items-center gap-6">
    <div>
      <span class="bg-white/20 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider select-none">Current Zone</span>
      <h1 class="text-xl md:text-3xl font-black mt-2">Delivering in <?= e($locName) ?></h1>
      <p class="text-xs md:text-sm text-orange-100 mt-1">Discover <?= count($restaurants) ?> verified partner kitchens bringing sizzling hot meals to your doorstep.</p>
    </div>
    <button onclick="Zesto.modal.open('location-modal')" class="shrink-0 bg-white text-[#a83300] hover:bg-[#ffdbd0] hover:text-[#d24200] font-bold text-xs px-6 py-3.5 rounded-xl shadow-md transition-all active:scale-95 duration-200">
      📍 Change Location
    </button>
  </section>

  <!-- ═══ OFFERS CAROUSEL ══════════════════════════════════════ -->
  <?php if (!empty($offers)): ?>
  <section class="relative">
    <div class="flex justify-between items-end mb-5">
      <div>
        <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Today's Hot Promotions</h2>
        <p class="text-xs text-gray-500 mt-0.5">Exclusive discounts and delicious deals just for you</p>
      </div>
      <div class="flex gap-2">
        <button id="offer-scroll-left" class="p-2.5 rounded-full border border-gray-200 bg-white hover:bg-gray-50 transition-colors shadow-sm active:scale-90 cursor-pointer">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button id="offer-scroll-right" class="p-2.5 rounded-full border border-gray-200 bg-white hover:bg-gray-50 transition-colors shadow-sm active:scale-90 cursor-pointer">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>
    
    <div id="offers-track" class="flex gap-6 overflow-x-auto scrollbar-none pb-2 snap-x -mx-4 px-4 scroll-smooth">
      <?php foreach ($offers as $off): ?>
      <div class="flex-shrink-0 w-[290px] md:w-[460px] h-[160px] md:h-[200px] rounded-2xl overflow-hidden relative shadow-md snap-start group border border-gray-100">
        <img src="<?= $off['image'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD55EC6JU2Ccf8bZ_lQOIPcFj3kSuFakZ7Wxt-W0OpE6gHvNfyT49MPBvMPCJY1c2BABZdhUorBcsCBsRdjIi1hV8MN-qBhNvVfkFOGkwJwTBRXvQ5-xFFM-_YeWvKSO-ulKag_cSMFrCnyQzpDYMhwhkWuDXcHjGmocSq_VjOGID-zDA7slW4ITIXHdCFsr9Wqgp-iceaeLkg2slVfKC4jUW5I10I1LweWGGOkKZfWvhPdb4otIKn2F-2Gq6JwTISCzYme5748y0M' ?>"
             alt="<?= e($off['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-black/10 flex flex-col justify-end p-5 text-white">
          <span class="bg-[#a83300] text-white text-[9px] font-bold px-2 py-0.5 rounded w-fit uppercase tracking-wider mb-1">Coupon code: <?= e($off['code']) ?></span>
          <h3 class="font-black text-sm md:text-lg"><?= e($off['title']) ?></h3>
          <p class="text-[10px] md:text-xs text-orange-50/90 mt-1 line-clamp-2 leading-relaxed"><?= e($off['description']) ?></p>
          
          <button onclick="navigator.clipboard.writeText('<?= e($off['code']) ?>'); Zesto.toast('🎉 Code <?= e($off['code']) ?> copied! Paste at checkout.','success')"
                  class="mt-3 bg-white text-black hover:bg-[#ffdbd0] hover:text-[#a83300] font-bold text-[10px] px-3.5 py-1.5 rounded-lg w-fit transition-all duration-200">
            Copy Coupon
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <script>
    document.getElementById('offer-scroll-left')?.addEventListener('click', () => document.getElementById('offers-track').scrollBy({ left: -350, behavior: 'smooth' }));
    document.getElementById('offer-scroll-right')?.addEventListener('click', () => document.getElementById('offers-track').scrollBy({ left: 350, behavior: 'smooth' }));
  </script>
  <?php endif; ?>

  <!-- ═══ CUISINES / CATEGORIES ════════════════════════════════ -->
  <section class="relative">
    <div class="flex justify-between items-end mb-6">
      <div>
        <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">What's on your mind?</h2>
        <p class="text-xs text-gray-500 mt-0.5">Explore curated dishes and categories</p>
      </div>
      <div class="flex gap-2">
        <button id="cat-scroll-left" class="p-2.5 rounded-full border border-gray-200 bg-white hover:bg-gray-50 transition-colors shadow-sm active:scale-90 cursor-pointer">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button id="cat-scroll-right" class="p-2.5 rounded-full border border-gray-200 bg-white hover:bg-gray-50 transition-colors shadow-sm active:scale-90 cursor-pointer">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>

    <div id="category-track" class="flex gap-8 overflow-x-auto scrollbar-none pb-4 snap-x -mx-4 px-4 scroll-smooth">
      <a href="<?= BASE_URL ?>/index.php"
         class="flex-shrink-0 flex flex-col items-center gap-3 cursor-pointer select-none snap-start">
        <div class="w-20 h-20 md:w-24 md:h-24 rounded-full overflow-hidden border-2 flex items-center justify-center transition-all shadow-sm <?= $category === '' ? 'border-[#a83300] bg-[#ffdbd0]' : 'border-gray-100 bg-white hover:border-[#a83300]' ?>">
          <span class="text-2xl">✨</span>
        </div>
        <span class="text-xs font-bold tracking-tight transition-colors <?= $category === '' ? 'text-[#a83300]' : 'text-gray-500' ?>">All Categories</span>
      </a>

      <?php foreach ($categories as $cat): ?>
      <a href="<?= BASE_URL ?>/menu.php?category=<?= $cat['id'] ?>"
         class="flex-shrink-0 flex flex-col items-center gap-3 cursor-pointer select-none snap-start group">
        <div class="w-20 h-20 md:w-24 md:h-24 rounded-full overflow-hidden border-2 p-1.5 transition-all shadow-sm <?= $category === $cat['name'] ? 'border-[#a83300] bg-[#ffdbd0]' : 'border-gray-100 bg-white group-hover:border-[#a83300]' ?>">
          <img src="<?= e($cat['image']) ?>" alt="<?= e($cat['name']) ?>"
               class="w-full h-full object-cover rounded-full" referrerpolicy="no-referrer">
        </div>
        <span class="text-xs font-bold tracking-tight transition-colors group-hover:text-[#a83300] text-gray-500">
          <?= e($cat['name']) ?>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ═══ TODAY'S SPECIALS ═════════════════════════════════════ -->
  <?php if (!empty($specials)): ?>
  <section class="bg-white rounded-3xl p-6 md:p-8 border border-gray-100 shadow-sm">
    <div class="mb-6 flex justify-between items-end">
      <div>
        <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Today's Specials 🌟</h2>
        <p class="text-xs text-gray-500 mt-0.5">Chef-curated gourmet picks and discounted foods in <?= e($city) ?></p>
      </div>
      <a href="<?= BASE_URL ?>/menu.php?special=1" class="text-xs font-bold text-[#a83300] hover:underline flex items-center gap-1">
        <span>View all specials</span> ➔
      </a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($specials as $spec): ?>
      <div class="bg-gray-50/50 rounded-2xl p-4 flex gap-4 border border-gray-100 hover:border-[#e5beb2] transition-colors relative">
        <img src="<?= $spec['image'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBZLbHo94g2948yCQi_Q1dVUSPm7BgZNWKJWBJwlPkeAxvdQXlETDOg88T30AcJwkVKeiDN3TZ3h4Uzx-ktYgh2MxBjNSgQmOdj3cR8mlX0VcaeE9AA-ynZ-cXRNbEOjFU47cUGFE9pWTrzGgqg6liFOHMYjEWhj-CyDCSeVvyO5282aXh30ZUK6uEhmx48fz-0Os880RaqVw-iUMvfgiHqI0oGi_UikGPKsXXv80RBqP2yhQQchY8YwAnkKE6NJTZJYRarOE_5lng' ?>" 
             alt="<?= e($spec['name']) ?>" class="w-20 h-20 rounded-xl object-cover shrink-0 border border-gray-100">
        <div class="flex-1 flex flex-col justify-between min-w-0">
          <div>
            <div class="flex items-center gap-1.5">
              <span class="w-3.5 h-3.5 border flex items-center justify-center rounded-sm text-[8px] font-bold shrink-0 <?= $spec['is_veg'] ? 'border-green-600 text-green-600' : 'border-red-600 text-red-600' ?>">
                <?= $spec['is_veg'] ? '●' : '▲' ?>
              </span>
              <h4 class="font-extrabold text-xs text-[#1b1c1c] truncate"><?= e($spec['name']) ?></h4>
            </div>
            <p class="text-[10px] text-gray-500 truncate mt-0.5">from <a href="<?= BASE_URL ?>/restaurant.php?id=<?= $spec['restaurant_slug'] ?>" class="font-semibold text-gray-700 hover:underline"><?= e($spec['restaurant_name']) ?></a></p>
            <p class="text-[10px] text-gray-400 mt-1 line-clamp-1"><?= e($spec['description']) ?></p>
          </div>
          <div class="flex justify-between items-center mt-2">
            <span class="text-[#a83300] font-black text-sm"><?= formatPrice($spec['price']) ?></span>
            <a href="<?= BASE_URL ?>/restaurant.php?id=<?= $spec['restaurant_slug'] ?>" 
               class="px-3.5 py-1 bg-white hover:bg-[#ffdbd0] hover:text-[#a83300] text-gray-700 border border-gray-200 hover:border-[#a83300] text-[10px] font-bold rounded-lg transition-all shadow-sm">
              View Menu
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ FEATURED RESTAURANTS ═════════════════════════════════ -->
  <?php if (!empty($featuredRestaurants)): ?>
  <section class="relative">
    <div class="flex justify-between items-end mb-6">
      <div>
        <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Featured Restaurants ⭐</h2>
        <p class="text-xs text-gray-500 mt-0.5">Premium curated kitchens highly recommended in <?= e($city) ?></p>
      </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php foreach ($featuredRestaurants as $res): ?>
      <div onclick="window.location.href='<?= BASE_URL ?>/restaurant.php?id=<?= e($res['slug']) ?>'"
           class="group cursor-pointer flex flex-col bg-white rounded-2xl overflow-hidden hover:shadow-md transition-all duration-300 border border-gray-150 relative">
        <div class="relative h-48 md:h-52 overflow-hidden shadow-sm shrink-0">
          <img src="<?= e($res['image']) ?>" alt="<?= e($res['name']) ?>"
               class="w-full h-full object-cover group-hover:scale-105 duration-700" referrerpolicy="no-referrer">
          
          <div class="absolute top-4 right-4 bg-white/95 backdrop-blur-sm px-2.5 py-1 rounded-lg flex items-center gap-1 shadow-sm border border-gray-100">
            <span class="text-amber-500 text-sm">★</span>
            <span class="text-xs font-extrabold text-[#1b1c1c]"><?= number_format($res['rating'], 1) ?></span>
          </div>

          <div class="absolute bottom-4 left-4 flex gap-2">
            <span class="bg-[#a83300] text-white font-bold px-3 py-1 rounded-full text-[10px] tracking-wide shadow-sm">FEATURED</span>
            <?php if ($res['discount']): ?>
            <span class="bg-amber-500 text-white font-bold px-3 py-1 rounded-full text-[10px] tracking-wide shadow-sm"><?= e($res['discount']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        
        <div class="p-5 flex flex-col justify-between flex-1 gap-2.5 bg-white">
          <div class="flex justify-between items-start gap-2">
            <h4 class="text-sm md:text-base font-extrabold text-[#1b1c1c] tracking-tight truncate group-hover:text-[#a83300] transition-colors">
              <?= e($res['name']) ?>
            </h4>
            <div class="text-right shrink-0">
              <span class="text-xs font-bold text-[#1b1c1c] block"><?= e($res['delivery_time']) ?></span>
              <span class="text-[10px] text-gray-400 block mt-0.5"><?= number_format($res['distance'], 1) ?> km</span>
            </div>
          </div>
          <p class="text-xs text-gray-400 font-medium truncate"><?= e(implode(' • ', explode(',', $res['tags']))) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ POPULAR NEAR YOU ═════════════════════════════════════ -->
  <?php if (!empty($popularRestaurants)): ?>
  <section class="relative">
    <div class="flex justify-between items-end mb-6">
      <div>
        <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Popular Near You 🔥</h2>
        <p class="text-xs text-gray-500 mt-0.5">Most ordered-from kitchens in your region right now</p>
      </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php foreach ($popularRestaurants as $res): ?>
      <div onclick="window.location.href='<?= BASE_URL ?>/restaurant.php?id=<?= e($res['slug']) ?>'"
           class="group cursor-pointer p-4 bg-white rounded-2xl hover:shadow-md transition-all duration-200 border border-gray-150 flex gap-4">
        <img src="<?= e($res['image']) ?>" alt="<?= e($res['name']) ?>"
             class="w-20 h-20 rounded-xl object-cover shrink-0 border border-gray-100">
        <div class="flex-1 flex flex-col justify-between min-w-0">
          <div>
            <h4 class="font-extrabold text-xs text-[#1b1c1c] truncate group-hover:text-[#a83300] mt-0.5"><?= e($res['name']) ?></h4>
            <p class="text-[10px] text-gray-400 truncate mt-0.5"><?= e(implode(', ', array_slice(explode(',', $res['tags']), 0, 2))) ?></p>
          </div>
          <div class="flex items-center justify-between text-[10px] font-bold text-gray-500">
            <span class="flex items-center gap-0.5 text-amber-500">★ <?= number_format($res['rating'], 1) ?></span>
            <span>• <?= e($res['delivery_time']) ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ TRENDING FOODS ═══════════════════════════════════════ -->
  <?php if (!empty($trendingFoods)): ?>
  <section class="relative">
    <div class="flex justify-between items-end mb-6">
      <div>
        <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Trending Foods 📈</h2>
        <p class="text-xs text-gray-500 mt-0.5">Top trending choices and viral dishes today in <?= e($city) ?></p>
      </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
      <?php foreach ($trendingFoods as $trend): ?>
      <div onclick="window.location.href='<?= BASE_URL ?>/restaurant.php?id=<?= $trend['restaurant_slug'] ?>'"
           class="group cursor-pointer bg-white rounded-2xl border border-gray-100 p-3 hover:shadow-md transition-all text-center flex flex-col justify-between">
        <div class="relative shrink-0 aspect-square rounded-xl overflow-hidden mb-3 border border-gray-50">
          <img src="<?= $trend['image'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDgyfyP7rZ5dmAORInBqrp6VhdNNjTUc3kJb-uGys1DXHWggV9aJfUPMwEIDyBuchzQlSz2_H-GhgK4CPrHMHDdT9XcXzk0tjfAafyZhNbgMUYIhKMJFY_T6Lkiyv7bLzAcf_LH9yFedLNmQWqOU9FEplVgB2QNXItgaSM0PngufbViGwnLmgTG6zXQ_giH7ILTd1-Wvircw50sDHB3PrwG3ug70sfg4ydbThZMLuJ8BQqJ5NOQ4kOyZ6ntA2f5zDfwXWqenvjvpPQ' ?>" 
               alt="<?= e($trend['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
          <span class="absolute top-2 left-2 w-3.5 h-3.5 border flex items-center justify-center rounded-sm text-[8px] bg-white font-bold <?= $trend['is_veg'] ? 'border-green-600 text-green-600' : 'border-red-600 text-red-600' ?>">
            <?= $trend['is_veg'] ? '●' : '▲' ?>
          </span>
        </div>
        <div>
          <h4 class="font-extrabold text-xs text-[#1b1c1c] truncate line-clamp-1"><?= e($trend['name']) ?></h4>
          <p class="text-[9px] text-gray-400 truncate mt-0.5"><?= e($trend['restaurant_name']) ?></p>
        </div>
        <p class="text-[#a83300] font-black text-xs mt-2"><?= formatPrice($trend['price']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ ALL RESTAURANTS SECTION ══════════════════════════════ -->
  <section id="restaurants-section" class="scroll-mt-24 pt-6 border-t border-gray-100">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
      <div>
        <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">
          <?= $category ? e($category) . ' Kitchens' : 'All Nearby Restaurants' ?>
        </h2>
        <p class="text-xs text-gray-400 mt-0.5">Showing <?= count($restaurants) ?> verified partner kitchens near you</p>
      </div>

      <!-- General Filters Bar -->
      <form id="filter-form" method="GET" action="" class="flex gap-3 relative flex-wrap md:flex-nowrap items-center">
        <?php if ($search): ?>
        <input type="hidden" name="search" value="<?= e($search) ?>">
        <?php endif; ?>
        <?php if ($category): ?>
        <input type="hidden" name="category" value="<?= e($category) ?>">
        <?php endif; ?>

        <div class="relative">
          <select name="sort" onchange="this.form.submit()"
                  class="pl-4 pr-8 py-2 border rounded-full text-xs font-semibold cursor-pointer transition-all outline-none appearance-none <?= $sortBy !== 'none' ? 'bg-[#ffdbd0] text-[#a83300] border-[#a83300]' : 'border-gray-200 hover:bg-[#f5f3f3] bg-white text-gray-600' ?>">
            <option value="none"     <?= $sortBy === 'none'     ? 'selected' : '' ?>>⚙ Sort: Default</option>
            <option value="rating"   <?= $sortBy === 'rating'   ? 'selected' : '' ?>>⭐ Top Rated</option>
            <option value="time"     <?= $sortBy === 'time'     ? 'selected' : '' ?>>⚡ Fastest Delivery</option>
            <option value="distance" <?= $sortBy === 'distance' ? 'selected' : '' ?>>📍 Nearest First</option>
          </select>
          <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-3 w-3 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        <?php if ($search || $category || $sortBy !== 'none'): ?>
        <a href="<?= BASE_URL ?>/index.php" class="text-xs text-gray-500 hover:text-[#a83300] font-semibold px-3">× Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Restaurant Grid -->
    <?php if (empty($restaurants)): ?>
    <div class="bg-gray-100 rounded-3xl p-12 text-center flex flex-col items-center justify-center gap-4">
      <span class="text-4xl">🔍</span>
      <div>
        <p class="font-extrabold text-sm text-gray-800">No kitchens found in your location</p>
        <p class="text-xs text-gray-550 mt-1">Try switching locations or clearing search queries.</p>
      </div>
      <a href="<?= BASE_URL ?>/index.php" class="text-xs font-bold text-white bg-[#a83300] px-5 py-2.5 rounded-xl hover:bg-[#d24200] transition-colors">
        Reset Location View
      </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
      <?php foreach ($restaurants as $res): ?>
      <div onclick="window.location.href='<?= BASE_URL ?>/restaurant.php?id=<?= e($res['slug']) ?>'"
           class="group cursor-pointer flex flex-col bg-white rounded-2xl overflow-hidden hover:shadow-md transition-all duration-300 border border-gray-150">
        <!-- Thumbnail -->
        <div class="relative h-56 md:h-60 overflow-hidden shadow-sm shrink-0">
          <img src="<?= e($res['image']) ?>" alt="<?= e($res['name']) ?>"
               class="w-full h-full object-cover group-hover:scale-105 duration-700" referrerpolicy="no-referrer">
          <!-- Rating -->
          <div class="absolute top-4 right-4 bg-white/95 backdrop-blur-sm px-2.5 py-1 rounded-lg flex items-center gap-1 shadow-sm border border-gray-100">
            <span class="text-amber-500 text-sm">★</span>
            <span class="text-xs font-extrabold text-[#1b1c1c]"><?= number_format($res['rating'], 1) ?></span>
          </div>
          <!-- Tags -->
          <div class="absolute bottom-4 left-4 flex gap-2">
            <?php if ($res['is_free_delivery']): ?>
            <span class="bg-[#ffdbd0] text-[#a83300] font-sans font-bold px-3 py-1 rounded-full text-[10px] tracking-wide shadow-sm border border-[#e5beb2]">Free Delivery</span>
            <?php endif; ?>
            <?php if ($res['discount']): ?>
            <span class="bg-[#a83300] text-white font-sans font-bold px-3 py-1 rounded-full text-[10px] tracking-wide shadow-sm"><?= e($res['discount']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <!-- Info -->
        <div class="p-5 flex flex-col justify-between flex-1 gap-2.5 bg-white">
          <div class="flex justify-between items-start gap-2">
            <h4 class="text-sm md:text-base font-extrabold text-[#1b1c1c] tracking-tight truncate line-clamp-1 group-hover:text-[#a83300] transition-colors">
              <?= e($res['name']) ?>
            </h4>
            <div class="text-right shrink-0">
              <span class="text-xs font-bold text-[#1b1c1c] block"><?= e($res['delivery_time']) ?></span>
              <span class="text-[10px] text-gray-400 block mt-0.5"><?= number_format($res['distance'], 1) ?> km</span>
            </div>
          </div>
          <p class="text-xs text-gray-405 font-medium truncate"><?= e(implode(' • ', explode(',', $res['tags']))) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

</div><!-- /.max-w container -->
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
