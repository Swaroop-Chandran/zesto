<?php
/**
 * Zesto — Zesto Nights Home Page
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/location_helper.php';
require_once __DIR__ . '/../../../includes/image_helper.php';

$pageTitle   = 'Zesto Nights — Delivering Your Favourite Meals, Fresh & Fast';
$description = 'Order food from top-rated restaurants in your area. Indian food, biryani, pizza, burgers and more — delivered hot to your door.';

$city    = getCurrentCity();
$locName = getCurrentLocation();

// ── Offers/Coupons ────────────────────────────────────────────
$offers = db()->query("SELECT * FROM offers WHERE is_active=1 ORDER BY id DESC")->fetchAll();

// ── Categories ────────────────────────────────────────────────
$categories = db()->query("SELECT * FROM categories WHERE is_active=1 ORDER BY display_order ASC LIMIT 8")->fetchAll();

// ── Today's Specials ──────────────────────────────────────────
$specials = db()->prepare("
    SELECT mi.*, r.name AS restaurant_name, r.slug AS restaurant_slug
    FROM menu_items mi
    JOIN restaurants r ON r.id = mi.restaurant_id
    WHERE mi.is_available=1 AND mi.is_special=1 AND r.is_active=1 AND r.city=:city
    ORDER BY mi.id DESC LIMIT 3
");
$specials->execute([':city' => $city]);
$specials = $specials->fetchAll();

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

include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/navbar.php';
?>

<main class="flex-1 bg-zesto-dark font-sans text-[#dfe2eb]">
<div class="w-full max-w-7xl mx-auto px-4 sm:px-10 py-6 space-y-12 animate-fade-in">
  
  <!-- Hero Section -->
  <section class="relative rounded-2xl overflow-hidden glass-panel py-16 sm:py-20 px-8 sm:px-12 flex flex-col justify-center min-h-[480px]">
    <!-- Background Overlay with atmospheric gas burner lanterns and steam look -->
    <div class="absolute inset-0 z-0 bg-gradient-to-r from-black via-black/85 to-transparent"></div>
    <div 
      class="absolute inset-0 z-0 opacity-25 bg-cover bg-center"
      style="background-image: url('https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&q=80&w=1200')"
    ></div>
    <div class="absolute top-10 right-10 w-48 h-48 bg-zesto-orange/15 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute bottom-10 left-10 w-36 h-36 bg-zesto-amber/10 rounded-full blur-3xl"></div>

    <!-- Content Box -->
    <div class="relative z-10 max-w-2xl space-y-6">
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-zesto-orange/15 border border-zesto-orange/30 text-xs font-semibold text-zesto-orange-glow tracking-widest uppercase animate-bounce">
        <i data-lucide="flame" class="w-3.5 h-3.5"></i>
        <span>Kerala's Legendary 2 AM Craving Cure</span>
      </div>

      <h1 class="text-4xl sm:text-6xl font-display font-extrabold text-white tracking-tight leading-tight">
        The Taste of Kerala <br />
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-zesto-orange to-zesto-amber">
          After Dark
        </span>
      </h1>

      <p class="text-base sm:text-lg text-white/70 max-w-lg leading-relaxed font-sans">
        Craving something hot and spicy at 2 AM? We deliver the authentic Thattukada experience straight to your door with smoking hot Porotta and sizzling Beef Roast.
      </p>

      <!-- Quick Actions -->
      <div class="flex flex-wrap items-center gap-4 pt-2">
        <a href="<?= BASE_URL ?>/restaurants.php" class="px-8 py-3.5 bg-zesto-orange text-white hover:bg-zesto-orange/90 active:scale-95 transition-all text-sm font-extrabold rounded-full fire-glow flex items-center gap-2 cursor-pointer no-underline">
          <span>Order Now</span>
          <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
        <a href="<?= BASE_URL ?>/restaurants.php?sort=distance" class="px-6 py-3.5 bg-white/5 hover:bg-white/10 text-white border border-white/10 rounded-full text-sm font-semibold flex items-center gap-2 transition cursor-pointer no-underline">
          <i data-lucide="compass" class="w-4 h-4 text-zesto-amber"></i>
          <span>Near Me</span>
        </a>
      </div>
    </div>

    <!-- Category Pill Floaters at standard sizing -->
    <div class="absolute bottom-6 right-6 hidden xl:flex gap-4">
      <div class="glass-panel p-2.5 rounded-xl border border-white/10 text-center flex flex-col items-center max-w-[80px]">
        <span class="text-[10px] font-bold text-white/50">Porotta</span>
        <span class="text-xs text-zesto-orange font-extrabold mt-1">₹15/pc</span>
      </div>
      <div class="glass-panel p-2.5 rounded-xl border border-white/10 text-center flex flex-col items-center max-w-[80px]">
        <span class="text-[10px] font-bold text-white/50">Beef</span>
        <span class="text-xs text-zesto-orange font-extrabold mt-1">₹140/pt</span>
      </div>
    </div>
  </section>

  <!-- Category Grid Navigation -->
  <?php if (!empty($categories)): ?>
  <section class="space-y-4">
    <h3 class="text-lg font-display font-bold text-white tracking-wide pl-1 flex items-center gap-2">
      <i data-lucide="sparkles" class="w-4 h-4 text-zesto-amber"></i>
      <span>Craving Categories</span>
    </h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <?php foreach ($categories as $cat): ?>
      <a href="<?= BASE_URL ?>/menu.php?category=<?= (int)$cat['id'] ?>" class="glass-card hover:border-zesto-orange/40 rounded-2xl p-4 flex items-center gap-4 cursor-pointer text-left focus:outline-none no-underline text-inherit group">
        <img 
          src="<?= getFoodImage($cat['image'], '', $cat['name']) ?>" 
          alt="<?= e($cat['name']) ?>" 
          class="w-14 h-14 rounded-full object-cover border border-white/10 shadow-lg group-hover:scale-105 transition-transform"
        />
        <div>
          <h4 class="text-sm font-display font-extrabold text-white group-hover:text-zesto-orange transition-colors"><?= e($cat['name']) ?></h4>
          <p class="text-[11px] text-white/50 mt-0.5">Explore authentic recipes</p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Night Specials Section -->
  <?php if (!empty($specials)): ?>
  <section class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-display font-extrabold text-white flex items-center gap-2">
          <span>Night Specials</span>
          <span class="text-zesto-orange">🔥</span>
        </h2>
        <p class="text-xs text-white/50">Steaming hot, right off the tawa</p>
      </div>
      <a href="<?= BASE_URL ?>/menu.php" class="text-xs font-semibold text-zesto-orange hover:underline flex items-center gap-1 cursor-pointer no-underline">
        See All
      </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($specials as $item): ?>
      <a href="<?= BASE_URL ?>/restaurant.php?id=<?= e($item['restaurant_slug']) ?>" class="glass-card rounded-2xl overflow-hidden flex flex-col group relative no-underline text-inherit">
        <?php if ($item['is_bestseller'] ?? true): ?>
          <span class="absolute top-3 left-3 z-10 bg-zesto-orange text-white text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider shadow-md">
            BEST SELLER
          </span>
        <?php endif; ?>
        
        <div class="relative h-44 w-full overflow-hidden">
          <img 
            src="<?= getFoodImage($item['image'], $item['name']) ?>" 
            alt="<?= e($item['name']) ?>"
            class="w-full h-full object-cover group-hover:scale-105 transition-all duration-500"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
        </div>

        <div class="p-4 flex-1 flex flex-col justify-between">
          <div>
            <div class="flex items-start justify-between gap-2">
              <h3 class="text-base font-display font-bold text-white group-hover:text-zesto-orange transition">
                <?= e($item['name']) ?>
              </h3>
              <span class="text-base font-extrabold text-zesto-amber">
                <?= formatPrice($item['price']) ?>
              </span>
            </div>
            <p class="text-xs text-white/60 mt-1.5 line-clamp-2 leading-relaxed">
              <?= e($item['description']) ?>
            </p>
          </div>

          <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between">
            <div class="flex items-center gap-1 text-[11px] text-white/40">
              <i data-lucide="flame" class="w-3.5 h-3.5 text-zesto-orange"></i>
              <span>Spice Level: 🌶️🌶️</span>
            </div>
            <button class="px-4 py-1.5 bg-white/5 hover:bg-zesto-orange hover:text-white border border-white/10 text-xs font-bold rounded-full transition-all text-white/90 active:scale-95 cursor-pointer">
              + Order
            </button>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Thattukadas Near You Section -->
  <section class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-2xl font-display font-extrabold text-white flex items-center gap-2">
          <span>Thattukadas Near You</span>
          <span class="text-zesto-amber">📍</span>
        </h3>
        <p class="text-xs text-white/50">Highly rated street counters in your area</p>
      </div>
      <a href="<?= BASE_URL ?>/restaurants.php" class="text-xs font-semibold text-zesto-orange hover:underline flex items-center gap-1 cursor-pointer no-underline">
        See All
      </a>
    </div>

    <?php if (empty($restaurants)): ?>
      <div class="glass-panel rounded-2xl p-16 text-center border border-white/10 my-8">
        <div class="text-5xl mb-4 opacity-50">🍽️</div>
        <h3 class="text-xl font-display font-black text-white mb-2">No Restaurants Open</h3>
        <p class="text-white/50 mb-6">Currently there are no registered restaurants operating in <?= e($city) ?>.</p>
        <button onclick="Zesto.modal.open('location-modal')" class="px-6 py-2.5 bg-zesto-orange text-white text-xs font-extrabold rounded-full transition-all hover:bg-zesto-orange/90 active:scale-95 cursor-pointer">Change Location</button>
      </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <?php foreach ($restaurants as $r): ?>
      <a href="<?= BASE_URL ?>/restaurant.php?id=<?= e($r['slug']) ?>" class="glass-card hover:border-zesto-amber/30 rounded-2xl overflow-hidden cursor-pointer flex flex-col justify-between no-underline text-inherit group">
        <div class="relative h-32 w-full overflow-hidden">
          <img 
            src="<?= e(getRestaurantBanner($r)) ?>" 
            alt="<?= e($r['name']) ?>"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-[#0A0A0A] via-black/40 to-transparent"></div>
          <div class="absolute bottom-2 left-3 flex items-center gap-2 text-xs font-medium text-white shadow-sm">
            <span class="bg-zesto-orange/90 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase">
              OPEN TILL <?= e($r['open_until'] ?? '3 AM') ?>
            </span>
          </div>
        </div>

        <div class="p-4 flex-1 flex flex-col justify-between">
          <div>
            <h4 class="text-sm font-display font-bold text-white line-clamp-1 group-hover:text-zesto-orange transition-colors"><?= e($r['name']) ?></h4>
            <p class="text-[11px] text-white/50 mt-1 line-clamp-1"><?= e($r['tags']) ?></p>
          </div>

          <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-xs text-white/60">
            <div class="flex items-center gap-1">
              <i data-lucide="star" class="w-3.5 h-3.5 text-zesto-amber fill-zesto-amber"></i>
              <span class="font-bold text-white"><?= number_format($r['rating'], 1) ?></span>
            </div>
            <div class="flex items-center gap-1">
              <i data-lucide="clock" class="w-3.5 h-3.5 text-white/40"></i>
              <span><?= e($r['delivery_time']) ?></span>
            </div>
            <span><?= number_format($r['distance'], 1) ?> km</span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <!-- Trending Tonight Graphic Carousel -->
  <section class="rounded-2xl glass-panel p-6 sm:p-8 border border-white/10 flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden mt-12">
    <div class="absolute top-0 right-0 w-64 h-64 bg-zesto-orange/10 rounded-full blur-3xl"></div>
    <div class="space-y-4 max-w-lg relative z-10">
      <div class="inline-flex items-center gap-1 text-xs text-zesto-orange font-bold">
        <i data-lucide="flame" class="w-4 h-4"></i>
        <span>TRENDING MIDNIGHT DUOS</span>
      </div>
      <h3 class="text-xl sm:text-2xl font-display font-extrabold text-white">
        Crispy Flaky Porotta + Spicy Red Beef Fry
      </h3>
      <p class="text-xs text-white/60 leading-relaxed">
        The legendary combination cherished by food lovers from Kasaragod to Trivandrum. Handwired flakes and smoking coconut slices. Perfect with a warm hot glass of Sulaimani tea.
      </p>
      <div class="flex items-center gap-3">
        <a href="<?= BASE_URL ?>/restaurants.php" class="px-5 py-2.5 bg-zesto-orange text-white text-xs font-extrabold rounded-full transition-all hover:bg-zesto-orange/90 active:scale-95 cursor-pointer no-underline inline-block">
          Explore Combos
        </a>
        <span class="text-xs text-white/45">Delivered within 25 mins</span>
      </div>
    </div>

    <!-- Floating food plate collage mockup -->
    <div class="flex gap-3 flex-wrap justify-center md:justify-end relative z-10">
      <img 
        src="https://images.unsplash.com/photo-1601050690597-df056fb4ce78?auto=format&fit=crop&q=80&w=150" 
        alt="Kerala Food Duo" 
        class="w-24 h-24 object-cover rounded-xl border border-white/15 shadow-2xl skew-y-3"
      />
      <img 
        src="https://images.unsplash.com/photo-1589301760014-d929f3979dbc?auto=format&fit=crop&q=80&w=150" 
        alt="Kappa Fry" 
        class="w-24 h-24 object-cover rounded-xl border border-white/15 shadow-2xl -skew-y-3 mt-4"
      />
    </div>
  </section>

</div>
</main>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
