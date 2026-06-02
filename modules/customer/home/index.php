<?php
/**
 * Zesto — Swiggy-Style Homepage v2.0
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/location_helper.php';
require_once __DIR__ . '/../../../includes/image_helper.php';

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

$validSorts = [
    'rating'   => 'rating DESC',
    'time'     => 'delivery_time_value ASC',
    'distance' => 'distance ASC',
    'none'     => 'is_featured DESC, rating DESC',
];
$orderClause = $validSorts[$sortBy] ?? $validSorts['none'];

if ($search !== '') {
    // Search restaurants by name/tags OR by menu item name/description
    $sql = "
        SELECT DISTINCT r.* FROM restaurants r
        WHERE r.is_active = 1 AND r.city = :city
          AND (r.name LIKE :s OR r.tags LIKE :s2)
        UNION
        SELECT DISTINCT r.* FROM restaurants r
        INNER JOIN menu_items mi ON mi.restaurant_id = r.id
        WHERE r.is_active = 1 AND r.city = :city2
          AND mi.is_available = 1
          AND (mi.name LIKE :s3 OR mi.description LIKE :s4)
        ORDER BY $orderClause
    ";
    $params = [
        ':city'  => $city,
        ':s'     => "%$search%",
        ':s2'    => "%$search%",
        ':city2' => $city,
        ':s3'    => "%$search%",
        ':s4'    => "%$search%",
    ];
} else {
    $sql    = "SELECT * FROM restaurants WHERE is_active=1 AND city=:city";
    $params = [':city' => $city];

    if ($catTag !== '') {
        $sql .= " AND tags LIKE :cat";
        $params[':cat'] = "%$catTag%";
    }
    $sql .= ' ORDER BY ' . $orderClause;
}

$stmt = db()->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll();
$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

$topRated = array_filter($restaurants, fn($r) => $r['is_best_rated']);
$topRated = array_slice(array_values($topRated), 0, 8);

// Fetch Trending Combo Item
$comboItem = db()->prepare("
    SELECT mi.*, r.slug AS restaurant_slug
    FROM menu_items mi
    JOIN restaurants r ON r.id = mi.restaurant_id
    WHERE mi.name = 'Crispy Flaky Porotta + Spicy Red Beef Fry' AND r.city = :city AND r.is_active = 1
    LIMIT 1
");
$comboItem->execute([':city' => $city]);
$comboInfo = $comboItem->fetch();

include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/navbar.php';
?>

<main class="flex-1 pb-mobile-nav font-sans bg-[#050505] text-[#dfe2eb]">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-12">

  <!-- ═══ HERO BANNER ═════════════════════════════════════════════ -->
  <section class="hero-banner-kerala rounded-3xl overflow-hidden py-16 px-8 md:px-12 lg:px-16 flex flex-col justify-center min-h-[420px] border border-white/5 shadow-2xl relative">
    <div class="relative z-10 max-w-2xl space-y-6 text-left">
      <span class="inline-flex items-center gap-1.5 bg-amber-500/10 border border-amber-500/25 text-[#fbbf24] text-[10px] font-black uppercase tracking-widest px-3.5 py-1.5 rounded-full shadow-inner">
        📍 KOCHI'S LEGENDARY 2 AM CRAVING CURE
      </span>
      <h1 class="text-4xl sm:text-5xl md:text-6xl font-display font-extrabold text-white tracking-tight leading-none">
        The Taste of Kerala <br>
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#fbbf24] to-[#f59e0b] premium-glow-text">
          After Dark
        </span>
      </h1>
      <p class="text-sm md:text-base text-zinc-300 leading-relaxed font-sans max-w-xl">
        Craving something hot and spicy at 2 AM? We deliver the authentic Thattukada experience straight to your door with smoking hot Porotta and sizzling Beef Roast.
      </p>
      
      <div class="flex flex-wrap items-center gap-4 pt-2">
        <a href="#all-restaurants" class="px-8 py-3.5 bg-gradient-to-r from-[#fbbf24] to-[#f59e0b] text-zinc-950 text-xs font-black uppercase tracking-wider rounded-full hover:opacity-90 transition shadow-lg shadow-amber-500/10 no-underline flex items-center gap-1.5 cursor-pointer">
          <span>Order Now</span>
          <svg class="w-3.5 h-3.5 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <a href="#all-restaurants" class="px-6 py-3.5 bg-zinc-900/60 hover:bg-zinc-800/80 text-white border border-white/5 rounded-full text-xs font-bold transition flex items-center gap-2 no-underline">
          <i data-lucide="compass" class="w-4 h-4 text-[#fbbf24]"></i>
          <span>Near Me</span>
        </a>
      </div>
    </div>
  </section>

  <!-- ═══ CRAVING CATEGORIES ══════════════════════════════════════ -->
  <?php if (!empty($categories)): ?>
  <section class="space-y-6">
    <div class="flex items-center gap-2 text-left">
      <i data-lucide="sparkles" class="w-4 h-4 text-[#fbbf24]"></i>
      <h2 class="text-lg md:text-xl font-extrabold text-white tracking-tight uppercase" style="font-family: 'Georgia', serif; color: #fbbf24;">Craving Categories</h2>
    </div>
    
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
      <?php foreach ($categories as $cat): ?>
      <a href="<?= BASE_URL ?>/menu.php?category=<?= (int)$cat['id'] ?>" class="category-horizontal-card p-3.5 flex items-center gap-4 group no-underline">
        <img src="<?= getFoodImage($cat['image'], '', $cat['name']) ?>" alt="<?= e($cat['name']) ?>" 
             class="w-12 h-12 rounded-xl object-cover shadow-lg border border-white/10 group-hover:scale-105 transition-transform duration-300 shrink-0">
        <div class="text-left min-w-0">
          <h4 class="text-xs font-bold text-white group-hover:text-[#fbbf24] transition-colors truncate"><?= e($cat['name']) ?></h4>
          <p class="text-[9px] text-zinc-500 font-medium mt-0.5 truncate">Explore authentic recipes</p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ NIGHT SPECIALS ══════════════════════════════════════════ -->
  <?php if (!empty($specials)): ?>
  <section class="space-y-6">
    <div class="flex items-center justify-between">
      <div class="text-left flex items-center gap-2">
        <h2 class="text-lg md:text-xl font-extrabold text-white tracking-tight uppercase" style="font-family: 'Georgia', serif; color: #fbbf24;">Night Specials 🔥</h2>
      </div>
      <a href="<?= BASE_URL ?>/menu.php" class="text-xs font-black text-[#fbbf24] hover:underline no-underline tracking-wider uppercase">
        See All
      </a>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <?php foreach ($specials as $item): ?>
      <div class="premium-special-card flex flex-col justify-between p-4 space-y-4">
        <div class="relative h-44 overflow-hidden rounded-xl bg-white/5">
          <img src="<?= getFoodImage($item['image'], $item['name']) ?>"
               alt="<?= e($item['name']) ?>"
               class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-300">
          <span class="absolute top-3 left-3 bg-[#f59e0b] text-zinc-950 text-[9px] font-black px-2.5 py-0.5 rounded uppercase tracking-wider shadow-md">
            Best Seller
          </span>
        </div>
        
        <div class="space-y-2 text-left">
          <div class="flex justify-between items-start gap-4">
            <h3 class="text-sm font-extrabold text-white line-clamp-1 leading-snug group-hover:text-[#fbbf24] transition-colors"><?= e($item['name']) ?></h3>
            <span class="text-sm font-black text-[#fbbf24] shrink-0"><?= formatPrice($item['price']) ?></span>
          </div>
          <p class="text-xs text-zinc-400 line-clamp-2 leading-relaxed"><?= e($item['description']) ?></p>
        </div>
        
        <div class="flex items-center justify-between border-t border-white/5 pt-3.5 mt-2">
          <div class="flex items-center gap-1.5 text-[10px] text-zinc-500 font-bold">
            <i data-lucide="flame" class="w-3.5 h-3.5 text-zinc-500 fill-zinc-500"></i>
            <span>Spice Level 🌶️🌶️🌶️</span>
          </div>
          
          <?php
            $qtyInCart = 0;
            foreach ($cartItems as $cItem) {
                if ($cItem['menu_item_id'] == $item['id']) {
                    $qtyInCart = $cItem['quantity'];
                    break;
                }
            }
          ?>
          <div id="wrap-<?= $item['id'] ?>" class="flex-shrink-0 min-w-[100px]" data-theme="dark">
            <?php if ($qtyInCart > 0): ?>
              <div class="qty-stepper w-full mt-auto">
                <button class="qty-stepper-btn" onclick="event.preventDefault(); event.stopPropagation(); cartDecrement(<?= $item['id'] ?>, <?= $item['restaurant_id'] ?>, '<?= e($item['restaurant_slug']) ?>')">−</button>
                <span class="qty-stepper-count"><?= $qtyInCart ?></span>
                <button class="qty-stepper-btn" onclick="event.preventDefault(); event.stopPropagation(); cartIncrement(<?= $item['id'] ?>, <?= $item['restaurant_id'] ?>, '<?= e($item['restaurant_slug']) ?>')">+</button>
              </div>
            <?php else: ?>
              <button onclick="event.preventDefault(); event.stopPropagation(); cartAdd(<?= $item['id'] ?>, <?= $item['restaurant_id'] ?>, '<?= e($item['restaurant_slug']) ?>')" 
                      class="zesto-add-btn w-full">
                <span>+ Add</span>
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ═══ THATTUKADAS NEAR YOU ════════════════════════════════════ -->
  <section id="all-restaurants" class="space-y-6">
    <div class="flex items-center justify-between">
      <div class="text-left flex items-center gap-2">
        <h2 class="text-lg md:text-xl font-extrabold text-white tracking-tight uppercase" style="font-family: 'Georgia', serif; color: #fbbf24;">Thattukadas Near You 📍</h2>
      </div>
      <a href="<?= BASE_URL ?>/restaurants.php" class="text-xs font-black text-[#fbbf24] hover:underline no-underline tracking-wider uppercase">
        See All
      </a>
    </div>

    <!-- Real-time Filter Pills -->
    <div class="flex items-center justify-between flex-wrap gap-4 bg-zinc-950/40 p-3 rounded-2xl border border-white/5">
      <div class="flex items-center gap-2.5 flex-wrap">
        <button onclick="toggleFilter('rating', this)" class="bg-zinc-900 border border-white/5 text-zinc-300 hover:bg-zinc-800 transition-all font-bold text-xs py-2 px-4 rounded-full flex items-center gap-1.5 cursor-pointer shadow-md shadow-black/20">
          ⭐ Ratings 4.0+
        </button>
        <button onclick="toggleFilter('veg', this)" class="bg-zinc-900 border border-white/5 text-zinc-300 hover:bg-zinc-800 transition-all font-bold text-xs py-2 px-4 rounded-full flex items-center gap-1.5 cursor-pointer shadow-md shadow-black/20">
          🌱 Pure Veg
        </button>
        <button onclick="toggleFilter('fast', this)" class="bg-zinc-900 border border-white/5 text-zinc-300 hover:bg-zinc-800 transition-all font-bold text-xs py-2 px-4 rounded-full flex items-center gap-1.5 cursor-pointer shadow-md shadow-black/20">
          ⚡ Fast Delivery
        </button>
        <button onclick="toggleFilter('offers', this)" class="bg-zinc-900 border border-white/5 text-zinc-300 hover:bg-zinc-800 transition-all font-bold text-xs py-2 px-4 rounded-full flex items-center gap-1.5 cursor-pointer shadow-md shadow-black/20">
          🏷️ Offers & Deals
        </button>
      </div>

      <!-- Quick Search input next to filters -->
      <div class="relative shrink-0 w-full sm:w-64">
        <form method="GET" action="#all-restaurants">
          <input type="text" name="search" placeholder="Search within results..." value="<?= e($search) ?>"
                 class="w-full text-xs font-bold py-2 pl-8 pr-4 bg-zinc-900/50 border border-white/10 rounded-full focus:outline-none focus:border-[#fbbf24]/50 transition-all placeholder:text-zinc-600 text-white">
          <svg class="h-4.5 w-4.5 text-zinc-600 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </form>
      </div>
    </div>

    <!-- Empty filtered results state -->
    <div id="no-filtered-results" class="hidden bg-zinc-950/40 rounded-3xl p-16 text-center border border-white/5 my-8">
      <div class="text-5xl mb-4">🍽️</div>
      <h3 class="text-lg font-black text-white mb-2">No Matching Restaurants Found</h3>
      <p class="text-xs text-zinc-500 max-w-sm mx-auto mb-6">Try clearing your filters or resetting the search to discover all available food spots.</p>
      <button onclick="resetFilters()" class="btn-primary py-2 px-6 text-xs bg-[#fbbf24] text-zinc-950">Reset All Filters</button>
    </div>

    <?php if ($search !== ''): ?>
    <div class="flex items-center gap-3 bg-amber-400/5 border border-amber-400/20 rounded-2xl px-5 py-3 text-sm">
      <i data-lucide="search" class="w-4 h-4 text-amber-400 shrink-0"></i>
      <span class="text-zinc-400">Showing restaurants for <strong class="text-white">"<?= e($search) ?>"</strong></span>
      <a href="<?= BASE_URL ?>/menu.php?search=<?= urlencode($search) ?>" class="ml-auto text-amber-400 font-bold text-xs hover:underline shrink-0 no-underline">
        See matching dishes →
      </a>
    </div>
    <?php endif; ?>

    <?php if (empty($restaurants)): ?>
    <div class="bg-zinc-950/40 rounded-3xl p-16 text-center border border-white/5">
      <div class="text-5xl mb-4">🍽️</div>
      <?php if ($search !== ''): ?>
      <h3 class="text-xl font-black text-white mb-2">No restaurants found for "<?= e($search) ?>"</h3>
      <p class="text-zinc-500 mb-6">Try searching by a dish name, cuisine type, or restaurant name.</p>
      <div class="flex flex-wrap gap-3 justify-center">
        <a href="<?= BASE_URL ?>/menu.php?search=<?= urlencode($search) ?>" class="btn-primary bg-[#fbbf24] text-zinc-950 font-bold rounded-full px-6 py-2.5 no-underline">Browse Matching Dishes</a>
        <a href="<?= BASE_URL ?>/" class="btn-secondary font-bold rounded-full px-6 py-2.5 no-underline">View All Restaurants</a>
      </div>
      <?php else: ?>
      <h3 class="text-xl font-black text-white mb-2">No Restaurants Open</h3>
      <p class="text-zinc-500 mb-6">Currently there are no registered restaurants operating in <?= e($city) ?>.</p>
      <button onclick="Zesto.modal.open('location-modal')" class="btn-primary bg-[#fbbf24] text-zinc-950 font-bold rounded-full px-6 py-2.5">Change Location</button>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Grid elements -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <?php foreach ($restaurants as $r): ?>
      <?php
        $isVeg = (str_contains(strtolower($r['tags']), 'veg') && !str_contains(strtolower($r['tags']), 'non-veg')) ? '1' : '0';
      ?>
      <div class="grid-restaurant-card" 
           data-rating="<?= $r['rating'] ?>"
           data-time="<?= $r['delivery_time_value'] ?>"
           data-veg="<?= $isVeg ?>"
           data-discount="<?= $r['discount'] ? '1' : '0' ?>">
        <a href="<?= BASE_URL ?>/restaurant.php?id=<?= e($r['slug']) ?>" class="premium-special-card p-4 flex flex-col justify-between transition text-left group no-underline text-inherit cursor-pointer">
          <div class="relative w-full aspect-[16/10] rounded-xl overflow-hidden bg-white/5 shadow-inner">
            <img src="<?= e(getRestaurantBanner($r)) ?>" alt="<?= e($r['name']) ?>"
                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-102">
            
            <div class="absolute bottom-3 left-3 bg-[#000000]/70 border border-amber-500/20 text-[#fbbf24] text-[8px] font-extrabold px-2.5 py-1 rounded shadow-md uppercase tracking-wider">
              OPEN TILL <?= e($r['operating_hours'] ? explode('-', $r['operating_hours'])[1] ?? '4 AM' : '4 AM') ?>
            </div>
            
            <?php if ($r['is_free_delivery']): ?>
            <div class="absolute top-3 right-3 bg-[#16a34a] text-white text-[9px] font-black px-2.5 py-1 rounded shadow-md uppercase tracking-wider">Free Delivery</div>
            <?php endif; ?>
          </div>
          <div class="px-0.5 pt-4 space-y-2">
            <h3 class="font-extrabold text-sm text-white truncate leading-tight group-hover:text-[#fbbf24] transition-colors"><?= e($r['name']) ?></h3>
            <p class="text-[10px] text-zinc-500 font-semibold truncate leading-none"><?= e($r['tags']) ?></p>
            
            <div class="flex items-center justify-between border-t border-white/5 pt-3.5 mt-2 text-[10px] text-zinc-400 font-bold">
              <span class="flex items-center gap-1 text-[#fbbf24]"><i data-lucide="star" class="w-3.5 h-3.5 fill-[#fbbf24] text-[#fbbf24]"></i><?= number_format($r['rating'], 1) ?></span>
              <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3.5 h-3.5 text-zinc-500"></i><?= e($r['delivery_time']) ?></span>
              <span class="flex items-center gap-1"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-zinc-500"></i><?= number_format($r['distance'], 1) ?> km</span>
            </div>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <!-- ═══ TRENDING COMBO SECTION ══════════════════════════════════ -->
  <?php if ($comboInfo): ?>
  <section class="trending-combo-card p-6 md:p-8 flex flex-col md:flex-row items-center justify-between gap-6 overflow-hidden">
    <div class="relative z-10 space-y-3 md:max-w-xl text-left">
      <span class="text-[10px] font-black text-[#fbbf24] uppercase tracking-widest flex items-center gap-1.5">
        🔥 TRENDING MIDNIGHT COMBO
      </span>
      <h2 class="text-2xl sm:text-3xl font-display font-extrabold text-white leading-tight">
        <?= e($comboInfo['name']) ?>
      </h2>
      <p class="text-xs text-zinc-400 leading-relaxed font-sans">
        <?= e($comboInfo['description']) ?>
      </p>
      
      <div class="flex items-center gap-4 pt-4">
        <div id="wrap-<?= $comboInfo['id'] ?>" data-theme="dark">
          <?php
            $comboQty = 0;
            foreach ($cartItems as $cItem) {
                if ($cItem['menu_item_id'] == $comboInfo['id']) {
                    $comboQty = $cItem['quantity'];
                    break;
                }
            }
          ?>
          <?php if ($comboQty > 0): ?>
            <div class="flex items-center gap-2 bg-zinc-900 border border-white/10 rounded-full px-3 py-1.5 shadow-inner">
              <button class="w-6 h-6 rounded-full bg-white/5 flex items-center justify-center text-white/70 hover:bg-white/20 hover:text-white border-none cursor-pointer font-bold" onclick="event.preventDefault(); cartDecrement(<?= $comboInfo['id'] ?>, <?= $comboInfo['restaurant_id'] ?>, '<?= e($comboInfo['restaurant_slug']) ?>')">−</button>
              <span class="text-white text-xs font-black"><?= $comboQty ?></span>
              <button class="w-6 h-6 rounded-full bg-white/5 flex items-center justify-center text-white/70 hover:bg-white/20 hover:text-white border-none cursor-pointer font-bold" onclick="event.preventDefault(); cartIncrement(<?= $comboInfo['id'] ?>, <?= $comboInfo['restaurant_id'] ?>, '<?= e($comboInfo['restaurant_slug']) ?>')">+</button>
            </div>
          <?php else: ?>
            <button onclick="event.preventDefault(); cartAdd(<?= $comboInfo['id'] ?>, <?= $comboInfo['restaurant_id'] ?>, '<?= e($comboInfo['restaurant_slug']) ?>')" 
                    class="px-6 py-2.5 bg-gradient-to-r from-[#fbbf24] to-[#f59e0b] text-zinc-950 text-xs font-black uppercase tracking-wider rounded-full hover:opacity-90 transition shadow-lg shadow-amber-500/10 cursor-pointer border-none">
              Order Combo (₹<?= number_format($comboInfo['price'], 0) ?>)
            </button>
          <?php endif; ?>
        </div>
        <span class="text-[10px] font-semibold text-zinc-500 uppercase tracking-wider">
          Delivered within 20 mins
        </span>
      </div>
    </div>
    
    <div class="relative z-10 flex gap-4 mt-4 md:mt-0 flex-shrink-0">
      <img src="https://images.unsplash.com/photo-1603360946369-dc9bb6258143?auto=format&fit=crop&q=80&w=200" class="w-24 h-24 sm:w-28 sm:h-28 object-cover rounded-xl shadow-2xl border border-white/10" alt="Beef Fry">
      <img src="https://images.unsplash.com/photo-1626132647523-66f5bf380027?auto=format&fit=crop&q=80&w=200" class="w-24 h-24 sm:w-28 sm:h-28 object-cover rounded-xl shadow-2xl border border-white/10" alt="Porotta">
    </div>
  </section>
  <?php endif; ?>

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
      btn.classList.remove('bg-zinc-900', 'text-zinc-300', 'border-white/5');
      btn.classList.add('bg-[#f59e0b]', 'text-zinc-950', 'border-transparent', 'shadow-md');
    } else {
      btn.classList.remove('bg-[#f59e0b]', 'text-zinc-950', 'border-transparent', 'shadow-md');
      btn.classList.add('bg-zinc-900', 'text-zinc-300', 'border-white/5');
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
      btn.classList.remove('bg-[#f59e0b]', 'text-zinc-950', 'border-transparent', 'shadow-md');
      btn.classList.add('bg-zinc-900', 'text-zinc-300', 'border-white/5');
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
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
