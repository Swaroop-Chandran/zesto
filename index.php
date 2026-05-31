<?php
/**
 * Zesto — Swiggy-Style Homepage v2.0
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/location_helper.php';
require_once __DIR__ . '/includes/image_helper.php';

$pageTitle   = 'Zesto — Delivering Your Favourite Meals, Fresh & Fast';
$description = 'Order food from top-rated restaurants in your area. Indian food, biryani, pizza, burgers and more — delivered hot to your door.';

$city    = getCurrentCity();
$locName = getCurrentLocation();

// ── Offers/Coupons ────────────────────────────────────────────
$offers = db()->query("SELECT * FROM offers WHERE is_active=1 ORDER BY id DESC")->fetchAll();

// ── Categories ────────────────────────────────────────────────
$categories = db()->query("SELECT * FROM categories WHERE is_active=1 ORDER BY display_order ASC LIMIT 12")->fetchAll();

// ── Today's Specials ──────────────────────────────────────────
$specials = db()->prepare("
    SELECT mi.*, r.name AS restaurant_name, r.slug AS restaurant_slug
    FROM menu_items mi
    JOIN restaurants r ON r.id = mi.restaurant_id
    WHERE mi.is_available=1 AND mi.is_special=1 AND r.is_active=1 AND r.city=:city
    ORDER BY mi.id DESC LIMIT 8
");
$specials->execute([':city' => $city]);
$specials = $specials->fetchAll();

// ── Trending Foods ────────────────────────────────────────────
$trending = db()->prepare("
    SELECT mi.*, r.name AS restaurant_name, r.slug AS restaurant_slug
    FROM menu_items mi
    JOIN restaurants r ON r.id = mi.restaurant_id
    WHERE mi.is_available=1 AND mi.is_trending=1 AND r.is_active=1 AND r.city=:city
    ORDER BY mi.id ASC LIMIT 8
");
$trending->execute([':city' => $city]);
$trendingFoods = $trending->fetchAll();

// ── Search & General Restaurant List ─────────────────────────
$search = trim($_GET['search'] ?? '');
$catTag = trim($_GET['category'] ?? '');
$sortBy = $_GET['sort'] ?? 'none';

$sql    = "SELECT * FROM restaurants WHERE is_active=1 AND city=:city";
$params = [':city' => $city];

if ($search !== '') {
    $sql .= " AND (name LIKE :s OR tags LIKE :s2)";
    $params[':s'] = "%$search%"; $params[':s2'] = "%$search%";
}
if ($catTag !== '') {
    $sql .= " AND tags LIKE :cat";
    $params[':cat'] = "%$catTag%";
}
$validSorts = [
    'rating'   => 'ORDER BY rating DESC',
    'time'     => 'ORDER BY delivery_time_value ASC',
    'distance' => 'ORDER BY distance ASC',
    'none'     => 'ORDER BY is_featured DESC, rating DESC',
];
$sql .= ' ' . ($validSorts[$sortBy] ?? $validSorts['none']);
$stmt = db()->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll();

// ── Popular Near You (subset) ─────────────────────────────────
$popular = array_filter($restaurants, fn($r) => $r['is_popular']);
$popular = array_slice(array_values($popular), 0, 8);

// ── Top Rated ─────────────────────────────────────────────────
$topRated = array_filter($restaurants, fn($r) => $r['is_best_rated']);
$topRated = array_slice(array_values($topRated), 0, 8);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 bg-white pb-mobile-nav font-sans">
<div class="max-w-[1200px] mx-auto px-5 md:px-8 lg:px-10 py-6 md:py-10 space-y-12 bg-white">

  <!-- ═══ FOOD CATEGORIES ("What's on your mind?") ════════════════ -->
  <?php if (!empty($categories)): ?>
  <section class="border-b border-gray-100 pb-8">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl md:text-2xl font-black text-[#1b1c1c] tracking-tight">What's on your mind?</h2>
      <!-- Carousel navigation controls -->
      <div class="flex gap-2">
        <button onclick="document.getElementById('categories-scroll').scrollBy({left: -320, behavior: 'smooth'})" 
                class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors flex items-center justify-center text-[#1b1c1c] font-black cursor-pointer shadow-sm border-none">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button onclick="document.getElementById('categories-scroll').scrollBy({left: 320, behavior: 'smooth'})" 
                class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors flex items-center justify-center text-[#1b1c1c] font-black cursor-pointer shadow-sm border-none">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>
    
    <div id="categories-scroll" class="flex gap-6 md:gap-8 overflow-x-auto scrollbar-none pb-2 scroll-smooth">
      <?php foreach ($categories as $cat): ?>
      <a href="<?= BASE_URL ?>/menu.php?category=<?= (int)$cat['id'] ?>" class="flex flex-col items-center gap-3 group no-underline shrink-0 text-center w-24 md:w-28 cursor-pointer">
        <div class="w-20 h-20 md:w-24 md:h-24 rounded-full overflow-hidden bg-gray-50 flex items-center justify-center relative shadow-sm border border-gray-100/60">
          <img src="<?= getFoodImage($cat['image'], '', $cat['name']) ?>" alt="<?= e($cat['name']) ?>" 
               class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
        </div>
        <span class="text-xs md:text-sm font-bold text-gray-700 group-hover:text-[#a83300] transition-colors tracking-tight"><?= e($cat['name']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ TOP RESTAURANT CHAINS (Horizontal Carousel) ════════════ -->
  <?php if (!empty($restaurants)): ?>
  <section class="border-b border-gray-100 pb-10">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl md:text-2xl font-black text-[#1b1c1c] tracking-tight">Top restaurant chains in <?= e($city) ?></h2>
      <!-- Carousel controls -->
      <div class="flex gap-2">
        <button onclick="document.getElementById('chains-scroll').scrollBy({left: -350, behavior: 'smooth'})" 
                class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors flex items-center justify-center text-[#1b1c1c] font-black cursor-pointer shadow-sm border-none">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button onclick="document.getElementById('chains-scroll').scrollBy({left: 350, behavior: 'smooth'})" 
                class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors flex items-center justify-center text-[#1b1c1c] font-black cursor-pointer shadow-sm border-none">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>

    <div id="chains-scroll" class="flex gap-6 overflow-x-auto scrollbar-none pb-4 scroll-smooth">
      <?php foreach ($popular as $r): ?>
      <a href="<?= BASE_URL ?>/restaurant.php?id=<?= e($r['slug']) ?>" class="restaurant-card group shrink-0 w-60 no-underline text-inherit">
        <div class="relative w-full aspect-[16/10] rounded-2xl overflow-hidden shadow-sm bg-gray-100">
          <img src="<?= e(getRestaurantBanner($r)) ?>" alt="<?= e($r['name']) ?>"
               class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
          <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent pt-12 pb-3 px-4 flex items-end">
            <span class="text-white text-sm md:text-base font-black tracking-tighter uppercase">
              <?= $r['discount'] ? e($r['discount']) : 'ITEMS AT ₹129' ?>
            </span>
          </div>
        </div>
        <div class="restaurant-card-body px-1 pt-3">
          <h3 class="font-extrabold text-sm md:text-base text-[#1b1c1c] truncate leading-tight group-hover:text-[#a83300] transition-colors"><?= e($r['name']) ?></h3>
          <div class="flex items-center gap-1.5 mt-1 font-bold text-xs text-gray-700">
            <span class="flex items-center justify-center w-4 h-4 rounded-full bg-[#16a34a] text-white text-[9px] font-black leading-none shrink-0">★</span>
            <span><?= number_format($r['rating'], 1) ?></span>
            <span class="text-gray-300 font-normal">•</span>
            <span><?= e($r['delivery_time']) ?></span>
          </div>
          <p class="text-xs text-gray-400 font-medium truncate mt-1"><?= e($r['tags']) ?></p>
          <p class="text-xs text-gray-400 font-light truncate mt-0.5"><?= e($r['address'] ?: ($r['city'] . ', West')) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ TODAY'S SPECIALS ════════════════════════════════════════ -->
  <?php if (!empty($specials)): ?>
  <section class="border-b border-gray-100 pb-10">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl md:text-2xl font-black text-[#1b1c1c] tracking-tight">Today's Specials</h2>
      <a href="<?= BASE_URL ?>/menu.php" class="text-sm font-bold text-[#a83300] hover:underline flex items-center gap-1 no-underline">
        See all specials
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </a>
    </div>
    
    <div class="flex gap-5 overflow-x-auto scrollbar-none pb-4 scroll-smooth">
      <?php foreach ($specials as $item): ?>
      <a href="<?= BASE_URL ?>/restaurant.php?id=<?= e($item['restaurant_slug']) ?>"
         class="flex-shrink-0 w-44 md:w-52 group block no-underline" style="text-decoration:none;">
        <div class="bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-lg transition-all">
          <div class="h-32 md:h-40 overflow-hidden bg-gray-50 relative">
            <img src="<?= getFoodImage($item['image'], $item['name']) ?>"
                 alt="<?= e($item['name']) ?>"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            <span class="absolute top-2 left-2 flex items-center gap-1 bg-[#16a34a] text-white text-[8px] font-black px-2 py-0.5 rounded-full uppercase">
              <span class="inline-block w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> Specials
            </span>
          </div>
          <div class="p-3.5">
            <div class="flex items-center gap-1.5 mb-1.5">
              <div class="veg-dot <?= $item['is_veg'] ? 'veg' : 'nonveg' ?>"></div>
              <span class="text-[9px] font-extrabold uppercase tracking-wide text-gray-400 truncate"><?= e($item['restaurant_name']) ?></span>
            </div>
            <h3 class="font-extrabold text-sm text-[#1b1c1c] line-clamp-2 leading-snug"><?= e($item['name']) ?></h3>
            <div class="flex items-center justify-between mt-2.5">
              <span class="font-black text-[#1b1c1c] text-sm"><?= formatPrice($item['price']) ?></span>
              <span class="text-[10px] font-bold text-[#a83300] bg-[#ffdbd0] px-2 py-0.5 rounded">Order Now</span>
            </div>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ VERTICAL RESTAURANT GRID (Online Food Delivery) ════════ -->
  <section id="all-restaurants" class="pt-2">
    <h2 class="text-xl md:text-2xl font-black text-[#1b1c1c] tracking-tight mb-6">Restaurants with online food delivery in <?= e($city) ?></h2>

    <!-- Real-time Filter Pills -->
    <div class="flex items-center justify-between flex-wrap gap-4 mb-8">
      <div class="flex items-center gap-3 flex-wrap">
        <button onclick="toggleFilter('rating', this)" class="bg-white text-gray-700 border border-gray-200 hover:bg-gray-50/70 transition-all font-bold text-xs py-2.5 px-4 rounded-full flex items-center gap-1.5 cursor-pointer shadow-sm">
          ⭐ Ratings 4.0+
        </button>
        <button onclick="toggleFilter('veg', this)" class="bg-white text-gray-700 border border-gray-200 hover:bg-gray-50/70 transition-all font-bold text-xs py-2.5 px-4 rounded-full flex items-center gap-1.5 cursor-pointer shadow-sm">
          🌱 Pure Veg
        </button>
        <button onclick="toggleFilter('fast', this)" class="bg-white text-gray-700 border border-gray-200 hover:bg-gray-50/70 transition-all font-bold text-xs py-2.5 px-4 rounded-full flex items-center gap-1.5 cursor-pointer shadow-sm">
          ⚡ Fast Delivery
        </button>
        <button onclick="toggleFilter('offers', this)" class="bg-white text-gray-700 border border-gray-200 hover:bg-gray-50/70 transition-all font-bold text-xs py-2.5 px-4 rounded-full flex items-center gap-1.5 cursor-pointer shadow-sm">
          🏷️ Offers & Deals
        </button>
      </div>

      <!-- Quick Search input next to filters -->
      <div class="relative shrink-0 w-full sm:w-64">
        <form method="GET" action="#all-restaurants">
          <input type="text" name="search" placeholder="Search within results..." value="<?= e($search) ?>"
                 class="w-full text-xs font-semibold py-2.5 pl-8 pr-4 bg-gray-50 border border-gray-200 rounded-full focus:outline-none focus:border-[#a83300] focus:bg-white transition-all">
          <svg class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </form>
      </div>
    </div>

    <!-- Empty filtered results state -->
    <div id="no-filtered-results" class="hidden bg-white rounded-2xl p-16 text-center border border-gray-100 my-8">
      <div class="text-5xl mb-4">🍽️</div>
      <h3 class="text-lg font-black text-[#1b1c1c] mb-2">No Matching Restaurants Found</h3>
      <p class="text-xs text-gray-400 max-w-sm mx-auto mb-6">Try clearing your filters or resetting the search to discover all available food spots.</p>
      <button onclick="resetFilters()" class="btn-primary py-2 px-6 text-xs">Reset All Filters</button>
    </div>

    <?php if (empty($restaurants)): ?>
    <div class="bg-white rounded-2xl p-16 text-center border border-[#ece9e6]">
      <div class="text-5xl mb-4">🍽️</div>
      <h3 class="text-xl font-black text-[#1b1c1c] mb-2">No Restaurants Open</h3>
      <p class="text-gray-500 mb-6">Currently there are no registered restaurants operating in <?= e($city) ?>.</p>
      <button onclick="Zesto.modal.open('location-modal')" class="btn-primary">Change Location</button>
    </div>
    <?php else: ?>
    <!-- Grid elements -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 md:gap-x-6 md:gap-y-10">
      <?php foreach ($restaurants as $r): ?>
      <?php
        $isVeg = (str_contains(strtolower($r['tags']), 'veg') && !str_contains(strtolower($r['tags']), 'non-veg')) ? '1' : '0';
      ?>
      <div class="grid-restaurant-card" 
           data-rating="<?= $r['rating'] ?>"
           data-time="<?= $r['delivery_time_value'] ?>"
           data-veg="<?= $isVeg ?>"
           data-discount="<?= $r['discount'] ? '1' : '0' ?>">
        <a href="<?= BASE_URL ?>/restaurant.php?id=<?= e($r['slug']) ?>" class="restaurant-card group block no-underline text-inherit">
          <div class="relative w-full aspect-[16/10] rounded-2xl overflow-hidden shadow-sm bg-gray-50">
            <img src="<?= e(getRestaurantBanner($r)) ?>" alt="<?= e($r['name']) ?>"
                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent pt-12 pb-3 px-4 flex items-end">
              <span class="text-white text-base md:text-lg font-black tracking-tighter leading-none uppercase">
                <?= $r['discount'] ? e($r['discount']) : 'ITEMS AT ₹129' ?>
              </span>
            </div>
            <?php if ($r['is_free_delivery']): ?>
            <div class="absolute top-3 right-3 bg-[#00c853] text-white text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-wider shadow-sm">Free Delivery</div>
            <?php endif; ?>
          </div>
          <div class="restaurant-card-body px-1 pt-3">
            <h3 class="font-extrabold text-base text-[#1b1c1c] tracking-tight truncate group-hover:text-[#a83300] transition-colors leading-tight"><?= e($r['name']) ?></h3>
            <div class="flex items-center gap-1.5 font-bold text-sm text-gray-700 mt-1">
              <span class="flex items-center justify-center w-4.5 h-4.5 rounded-full bg-[#16a34a] text-white text-[9px] font-black leading-none shadow-sm shrink-0">★</span>
              <span><?= number_format($r['rating'], 1) ?></span>
              <span class="text-gray-300 font-normal">•</span>
              <span><?= e($r['delivery_time']) ?></span>
              <span class="text-gray-300 font-normal">•</span>
              <span><?= number_format($r['distance'], 1) ?> km</span>
            </div>
            <p class="text-xs text-gray-500 font-semibold truncate mt-1.5"><?= e($r['tags']) ?></p>
            <p class="text-xs text-gray-400 font-medium truncate mt-0.5"><?= e($r['address'] ?: ($r['city'] . ', West')) ?></p>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <!-- ═══ SWIGGY-STYLE BOTTOM EXPLORE GRIDS ═══════════════════════ -->
  <section class="border-t border-gray-100 pt-10">
    <h2 class="text-lg md:text-xl font-black text-[#1b1c1c] tracking-tight mb-6">Best Places to Eat Across Cities</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
      <?php
      $citiesGrid = [
        'Bangalore', 'Pune', 'Mumbai', 'Delhi', 
        'Hyderabad', 'Chennai', 'Kolkata', 'Ahmedabad',
        'Jaipur', 'Nagpur', 'Bhubaneswar', 'Kochi'
      ];
      foreach ($citiesGrid as $cityName):
      ?>
      <div onclick="Zesto.toast('Explore restaurants in <?= $cityName ?>','info')"
           class="bg-white border border-gray-200 rounded-xl py-3 px-4 flex items-center justify-between hover:bg-gray-50/50 cursor-pointer shadow-sm group transition-all">
        <span class="text-xs font-bold text-gray-700 group-hover:text-[#a83300]">Best Restaurants in <?= $cityName ?></span>
        <svg class="h-3.5 w-3.5 text-gray-400 group-hover:text-[#a83300] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="border-t border-gray-100 pt-10 pb-6">
    <h2 class="text-lg md:text-xl font-black text-[#1b1c1c] tracking-tight mb-6">Explore Cuisines Near Me</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
      <?php
      $cuisinesGrid = [
        'Biryani', 'Pizza', 'Burger', 'Chinese',
        'South Indian', 'Dessert', 'North Indian', 'Bakery',
        'Mughlai', 'Fast Food', 'Street Food', 'Healthy Food'
      ];
      foreach ($cuisinesGrid as $cuisineName):
      ?>
      <a href="<?= BASE_URL ?>/menu.php?search=<?= urlencode($cuisineName) ?>"
         class="bg-white border border-gray-200 rounded-xl py-3 px-4 flex items-center justify-between hover:bg-gray-50/50 cursor-pointer no-underline text-inherit shadow-sm group transition-all">
        <span class="text-xs font-bold text-gray-700 group-hover:text-[#a83300]"><?= $cuisineName ?> Restaurants Near Me</span>
        <svg class="h-3.5 w-3.5 text-gray-400 group-hover:text-[#a83300] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

</div>
</main>

<!-- JS Client-side filter functionality -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('.grid-restaurant-card');
  const filters = {
    fast: false,
    rating: false,
    veg: false,
    offers: false
  };

  window.toggleFilter = function(filterName, btn) {
    filters[filterName] = !filters[filterName];
    
    // Toggle active styles on the button to match brand colors
    if (filters[filterName]) {
      btn.classList.remove('bg-white', 'text-gray-700', 'border-gray-200');
      btn.classList.add('bg-[#a83300]', 'text-white', 'border-transparent', 'shadow-md');
    } else {
      btn.classList.remove('bg-[#a83300]', 'text-white', 'border-transparent', 'shadow-md');
      btn.classList.add('bg-white', 'text-gray-700', 'border-gray-200');
    }
    
    applyFilters();
  };

  window.resetFilters = function() {
    // Reset state
    filters.fast = false;
    filters.rating = false;
    filters.veg = false;
    filters.offers = false;
    
    // Reset button designs
    const buttons = document.querySelectorAll('.nav-pill');
    buttons.forEach(btn => {
      btn.classList.remove('bg-[#a83300]', 'text-white', 'border-transparent', 'shadow-md');
      btn.classList.add('bg-white', 'text-gray-700', 'border-gray-200');
    });
    
    applyFilters();
  };

  function applyFilters() {
    let visibleCount = 0;
    cards.forEach(card => {
      let show = true;
      
      if (filters.fast && parseInt(card.dataset.time || '99') > 30) {
        show = false;
      }
      if (filters.rating && parseFloat(card.dataset.rating || '0') < 4.0) {
        show = false;
      }
      if (filters.veg && card.dataset.veg !== '1') {
        show = false;
      }
      if (filters.offers && card.dataset.discount !== '1') {
        show = false;
      }
      
      if (show) {
        card.style.display = 'block';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });
    
    const noResults = document.getElementById('no-filtered-results');
    if (visibleCount === 0) {
      noResults.classList.remove('hidden');
    } else {
      noResults.classList.add('hidden');
    }
  }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
