<?php
/**
 * Zesto — Restaurants Listing Page v2.0
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/location_helper.php';
require_once __DIR__ . '/../../../includes/image_helper.php';

$city    = getCurrentCity();
$locName = getCurrentLocation();

$search    = trim($_GET['search'] ?? '');
$catFilter = trim($_GET['category'] ?? '');
$vegOnly   = isset($_GET['veg']) && $_GET['veg'] === '1';
$sortBy    = $_GET['sort'] ?? 'recommended';

$sql    = "SELECT * FROM restaurants WHERE is_active=1 AND city=:city";
$params = [':city' => $city];

if ($search !== '') {
    $sql .= " AND (name LIKE :s OR tags LIKE :s2 OR description LIKE :s3)";
    $params[':s'] = "%$search%"; $params[':s2'] = "%$search%"; $params[':s3'] = "%$search%";
}
if ($catFilter !== '') {
    $sql .= " AND tags LIKE :cat";
    $params[':cat'] = "%$catFilter%";
}

$sorts = [
    'recommended' => 'ORDER BY is_featured DESC, rating DESC',
    'rating'      => 'ORDER BY rating DESC',
    'time'        => 'ORDER BY delivery_time_value ASC',
    'distance'    => 'ORDER BY distance ASC',
    'free'        => 'ORDER BY is_free_delivery DESC, rating DESC',
];
$sql .= ' ' . ($sorts[$sortBy] ?? $sorts['recommended']);
$stmt = db()->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll();

$categories = db()->query("SELECT * FROM categories WHERE is_active=1 ORDER BY display_order ASC LIMIT 12")->fetchAll();

$pageTitle   = 'All Restaurants in ' . $city . ' — Zesto';
$description = 'Browse all restaurants in ' . $city . '. Filter by cuisine, sort by rating, delivery time and more.';
include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/navbar.php';
?>

<main class="flex-1 bg-white pb-mobile-nav">
<div class="max-w-[1280px] mx-auto px-4 md:px-8 lg:px-10 py-6 md:py-10 bg-white">

  <!-- ── Header ─────────────────────────────────────────────── -->
  <div class="mb-6 md:mb-8">
    <p class="section-label">📍 <?= e($city) ?></p>
    <h1 class="section-title">All Restaurants</h1>
    <p class="text-gray-500 text-sm mt-1"><?= count($restaurants) ?> restaurants available</p>
  </div>

  <!-- ── Search Bar ─────────────────────────────────────────── -->
  <form method="GET" class="mb-6">
    <div class="hero-search-bar max-w-2xl">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="search" placeholder="Search restaurants or cuisines..." value="<?= e($search) ?>"
             class="hero-search-input" autocomplete="off">
      <?php if ($sortBy !== 'recommended'): ?><input type="hidden" name="sort" value="<?= e($sortBy) ?>"><?php endif; ?>
      <button type="submit" class="btn-primary py-2 px-4 text-sm">Search</button>
    </div>
  </form>

  <!-- ── Sort Filters ────────────────────────────────────────── -->
  <div class="flex items-center gap-2 flex-wrap mb-6">
    <?php
    $filterBtns = [
      ['sort' => 'recommended', 'label' => 'Recommended'],
      ['sort' => 'rating',      'label' => '⭐ Rating'],
      ['sort' => 'time',        'label' => '⚡ Fastest'],
      ['sort' => 'distance',    'label' => '📍 Nearest'],
      ['sort' => 'free',        'label' => '🎉 Free Delivery'],
    ];
    foreach ($filterBtns as $btn):
      $active = $sortBy === $btn['sort'];
      $href = '?sort=' . $btn['sort'] . ($search ? '&search=' . urlencode($search) : '') . ($catFilter ? '&category=' . urlencode($catFilter) : '');
    ?>
    <a href="<?= $href ?>"
       class="nav-pill <?= $active ? 'bg-[#a83300] text-white shadow-md' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' ?>">
      <?= $btn['label'] ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- ── Category Pills ─────────────────────────────────────── -->
  <?php if (!empty($categories)): ?>
  <div class="flex gap-2 overflow-x-auto scrollbar-none pb-2 mb-6">
    <a href="?sort=<?= e($sortBy) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
       class="nav-pill shrink-0 <?= !$catFilter ? 'bg-[#a83300] text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
      All
    </a>
    <?php foreach ($categories as $cat): ?>
    <?php $active = stripos($catFilter, $cat['name']) !== false; ?>
    <a href="?category=<?= urlencode($cat['name']) ?>&sort=<?= e($sortBy) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
       class="nav-pill shrink-0 <?= $active ? 'bg-[#a83300] text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
      <?= getCategoryEmoji($cat['name']) ?> <?= e($cat['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── Restaurant Grid ────────────────────────────────────── -->
  <?php if (empty($restaurants)): ?>
  <div class="bg-white rounded-2xl p-16 text-center border border-[#ece9e6]">
    <div class="text-5xl mb-4">🍽️</div>
    <h2 class="text-xl font-black text-[#1b1c1c] mb-2">No Restaurants Found</h2>
    <p class="text-gray-500 mb-6">Try a different search term or change your location.</p>
    <div class="flex flex-wrap gap-3 justify-center">
      <a href="?" class="btn-secondary">Clear Filters</a>
      <button onclick="Zesto.modal.open('location-modal')" class="btn-primary">Change Location</button>
    </div>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php foreach ($restaurants as $r): ?>
    <a href="<?= BASE_URL ?>/restaurant.php?id=<?= e($r['slug']) ?>" class="restaurant-card group">
      <div class="relative w-full aspect-[4/3] rounded-2xl overflow-hidden shadow-sm bg-gray-50">
        <img src="<?= e(getRestaurantBanner($r)) ?>" alt="<?= e($r['name']) ?>"
             class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent pt-12 pb-3 px-4 flex items-end">
          <span class="text-white text-sm md:text-base font-extrabold tracking-tight uppercase">
            <?= $r['discount'] ? e($r['discount']) : 'ITEMS AT ₹129' ?>
          </span>
        </div>
        <?php if ($r['is_free_delivery']): ?>
        <div class="absolute top-3 right-3 bg-[#00c853] text-white text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider shadow-sm">Free Delivery</div>
        <?php endif; ?>
      </div>
      <div class="restaurant-card-body px-1">
        <h3 class="font-extrabold text-[#1b1c1c] text-base leading-tight tracking-tight truncate group-hover:text-[#a83300] transition-colors"><?= e($r['name']) ?></h3>
        <div class="flex items-center gap-1.5 font-bold text-xs text-gray-700 mt-1">
          <span class="flex items-center justify-center w-4.5 h-4.5 rounded-full bg-[#00c853] text-white text-[9px] font-black leading-none shadow-sm shrink-0">★</span>
          <span><?= number_format($r['rating'], 1) ?></span>
          <span class="text-gray-300 font-normal">•</span>
          <span><?= e($r['delivery_time']) ?></span>
          <span class="text-gray-300 font-normal">•</span>
          <span><?= number_format($r['distance'], 1) ?> km</span>
        </div>
        <p class="text-xs text-gray-500 font-semibold truncate mt-1"><?= e($r['tags']) ?></p>
        <p class="text-xs text-gray-400 font-medium truncate mt-0.5"><?= e($r['address'] ?: ($r['city'] . ', West')) ?></p>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
