<?php
/**
 * Zesto — Restaurants Listing Page (restaurants.php)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/location_helper.php';

$pageTitle = 'Explore Partner Kitchens — Zesto';
$description = 'Find top-rated local kitchens, dynamic bakers, and popular street vendors near you. Fast home delivery.';

$city = getCurrentCity();
$locName = getCurrentLocation();

// Fetch categories for pill selection
$categories = db()->query("SELECT * FROM categories WHERE is_active=1 ORDER BY display_order ASC")->fetchAll();

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
<div class="max-w-[1280px] mx-auto px-6 md:px-10 py-8 md:py-12 flex flex-col gap-8 font-sans">
  
  <!-- Header Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 border-b border-gray-100 pb-6">
    <div>
      <span class="text-xs font-bold text-[#a83300] uppercase tracking-widest">Discover Kitchens</span>
      <h1 class="text-2xl md:text-4xl font-black text-[#1b1c1c] tracking-tight mt-1">Restaurants in <?= e($locName) ?></h1>
      <p class="text-xs text-gray-400 mt-1">Showing <?= count($restaurants) ?> premium culinary kitchens</p>
    </div>
    
    <!-- Search Box -->
    <form method="GET" action="" class="relative max-w-sm w-full shrink-0 flex items-center bg-[#f5f3f3] rounded-full px-5 py-2.5 gap-3 border border-transparent focus-within:border-[#e5beb2] focus-within:bg-white transition-all">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] text-[#5f5e5e]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="search" placeholder="Search kitchen or tag..." value="<?= e($search) ?>"
             class="bg-transparent border-none outline-none focus:outline-none text-sm w-full text-[#1b1c1c] placeholder:text-[#5f5e5e]">
      <?php if ($category): ?>
      <input type="hidden" name="category" value="<?= e($category) ?>">
      <?php endif; ?>
      <?php if ($sortBy !== 'none'): ?>
      <input type="hidden" name="sort" value="<?= e($sortBy) ?>">
      <?php endif; ?>
    </form>
  </div>

  <!-- Filters & Sorting Row -->
  <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-gray-150 shadow-sm">
    
    <!-- Cuisine Category select pills -->
    <div class="flex gap-2 overflow-x-auto scrollbar-none py-1 -mx-2 px-2 max-w-full">
      <a href="?<?= $search ? 'search='.urlencode($search).'&' : '' ?><?= $sortBy !== 'none' ? 'sort='.urlencode($sortBy).'&' : '' ?>"
         class="px-4 py-2 border rounded-full text-xs font-bold shrink-0 transition-all cursor-pointer <?= $category === '' ? 'bg-[#ffdbd0] text-[#a83300] border-[#a83300] shadow-sm' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50' ?>">
        All Cuisines
      </a>
      <?php foreach ($categories as $cat): ?>
      <a href="?category=<?= urlencode($cat['name']) ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $sortBy !== 'none' ? '&sort='.urlencode($sortBy) : '' ?>"
         class="px-4 py-2 border rounded-full text-xs font-bold shrink-0 transition-all cursor-pointer <?= $category === $cat['name'] ? 'bg-[#ffdbd0] text-[#a83300] border-[#a83300] shadow-sm' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50' ?>">
        <?= e($cat['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Sorter -->
    <form method="GET" action="" class="flex items-center gap-2 shrink-0">
      <?php if ($search): ?>
      <input type="hidden" name="search" value="<?= e($search) ?>">
      <?php endif; ?>
      <?php if ($category): ?>
      <input type="hidden" name="category" value="<?= e($category) ?>">
      <?php endif; ?>

      <div class="relative">
        <select name="sort" onchange="this.form.submit()"
                class="pl-4 pr-8 py-2 border rounded-full text-xs font-bold cursor-pointer transition-all outline-none appearance-none <?= $sortBy !== 'none' ? 'bg-[#ffdbd0] text-[#a83300] border-[#a83300]' : 'border-gray-200 hover:bg-[#f5f3f3] bg-white text-gray-600' ?>">
          <option value="none"     <?= $sortBy === 'none'     ? 'selected' : '' ?>>⚙ Sort: Default</option>
          <option value="rating"   <?= $sortBy === 'rating'   ? 'selected' : '' ?>>⭐ Top Rated</option>
          <option value="time"     <?= $sortBy === 'time'     ? 'selected' : '' ?>>⚡ Fastest Delivery</option>
          <option value="distance" <?= $sortBy === 'distance' ? 'selected' : '' ?>>📍 Nearest First</option>
        </select>
        <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-3 w-3 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>

      <?php if ($search || $category || $sortBy !== 'none'): ?>
      <a href="<?= BASE_URL ?>/restaurants.php" class="text-xs text-gray-400 hover:text-[#a83300] font-bold px-2">Reset</a>
      <?php endif; ?>
    </form>

  </div>

  <!-- Restaurants Grid -->
  <?php if (empty($restaurants)): ?>
  <div class="bg-white rounded-3xl p-16 text-center border border-gray-150 flex flex-col items-center justify-center gap-4">
    <span class="text-5xl">🍴</span>
    <div>
      <p class="font-extrabold text-lg text-[#1b1c1c]">No Restaurants Available</p>
      <p class="text-xs text-gray-500 mt-1">We couldn't find any kitchen matches for your filter criteria. Try expanding search tags.</p>
    </div>
    <a href="<?= BASE_URL ?>/restaurants.php" class="text-xs font-bold text-white bg-[#a83300] px-6 py-3 rounded-xl hover:bg-[#d24200] transition-all shadow-sm">
      Show All Restaurants
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
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" referrerpolicy="no-referrer">
        
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
        <p class="text-xs text-gray-450 font-medium truncate"><?= e(implode(' • ', explode(',', $res['tags']))) ?></p>
      </div>

    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
