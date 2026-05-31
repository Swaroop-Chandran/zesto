<?php
/**
 * Zesto — Global Foods Menu Exploration Page (menu.php)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/location_helper.php';

$pageTitle = 'Explore Sizzling Food Dishes — Zesto';
$description = 'Browse thousands of fresh, hot dishes. Filter by category, Veg/Non-Veg, and sort by prices and ratings.';

$city = getCurrentCity();
$locName = getCurrentLocation();

// Fetch categories for filtering
$categories = db()->query("SELECT * FROM categories WHERE is_active=1 ORDER BY display_order ASC")->fetchAll();

// Read query inputs
$search      = trim($_GET['search']      ?? '');
$catId       = (int)($_GET['category']   ?? 0);
$vegFilter   = $_GET['veg']              ?? 'all'; // all, veg, nonveg
$sortBy      = $_GET['sort']             ?? 'none'; // none, price_asc, price_desc, rating, popularity

// Build SQL
$sql = "
    SELECT mi.*, r.name AS restaurant_name, r.slug AS restaurant_slug, r.rating AS restaurant_rating
    FROM menu_items mi
    JOIN restaurants r ON r.id = mi.restaurant_id
    WHERE mi.is_available = 1 AND r.is_active = 1 AND r.city = :city
";
$params = [':city' => $city];

if ($search !== '') {
    $sql .= " AND (mi.name LIKE :search OR mi.description LIKE :search2)";
    $params[':search']  = "%$search%";
    $params[':search2'] = "%$search%";
}

if ($catId > 0) {
    $sql .= " AND mi.category_id = :cat_id";
    $params[':cat_id'] = $catId;
}

if ($vegFilter === 'veg') {
    $sql .= " AND mi.is_veg = 1";
} elseif ($vegFilter === 'nonveg') {
    $sql .= " AND mi.is_veg = 0";
}

// Sorting rules
$validSorts = [
    'price_asc'  => "ORDER BY mi.price ASC",
    'price_desc' => "ORDER BY mi.price DESC",
    'rating'     => "ORDER BY r.rating DESC, mi.id DESC",
    'popularity' => "ORDER BY mi.is_popular DESC, mi.id ASC",
    'none'       => "ORDER BY mi.id DESC",
];
$sql .= ' ' . ($validSorts[$sortBy] ?? $validSorts['none']);

$stmt = db()->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$extraJs = [BASE_URL . '/assets/js/cart.js'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 pb-16 md:pb-8 bg-[#fbf9f8]">
<div class="max-w-[1280px] mx-auto px-6 md:px-10 py-8 md:py-12 flex flex-col gap-8 font-sans">
  
  <!-- Header Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 border-b border-gray-100 pb-6">
    <div>
      <span class="text-xs font-bold text-[#a83300] uppercase tracking-widest">Global Food Menu</span>
      <h1 class="text-2xl md:text-4xl font-black text-[#1b1c1c] tracking-tight mt-1">Browse Dishes in <?= e($city) ?></h1>
      <p class="text-xs text-gray-400 mt-1">Found <?= count($items) ?> mouthwatering choices in your location</p>
    </div>

    <!-- Search Form -->
    <form method="GET" action="" class="relative max-w-sm w-full shrink-0 flex items-center bg-[#f5f3f3] rounded-full px-5 py-2.5 gap-3 border border-transparent focus-within:border-[#e5beb2] focus-within:bg-white transition-all">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] text-[#5f5e5e]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="search" placeholder="Search delicious foods..." value="<?= e($search) ?>"
             class="bg-transparent border-none outline-none focus:outline-none text-sm w-full text-[#1b1c1c] placeholder:text-[#5f5e5e]">
      <?php if ($catId > 0): ?><input type="hidden" name="category" value="<?= $catId ?>"><?php endif; ?>
      <?php if ($vegFilter !== 'all'): ?><input type="hidden" name="veg" value="<?= e($vegFilter) ?>"><?php endif; ?>
      <?php if ($sortBy !== 'none'): ?><input type="hidden" name="sort" value="<?= e($sortBy) ?>"><?php endif; ?>
    </form>
  </div>

  <!-- Filter & Sorting Panel -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center bg-white p-4 rounded-2xl border border-gray-150 shadow-sm">
    
    <!-- Category pills scroll track -->
    <div class="lg:col-span-6 flex gap-2 overflow-x-auto scrollbar-none py-1 max-w-full">
      <a href="?<?= $search ? 'search='.urlencode($search).'&' : '' ?><?= $vegFilter !== 'all' ? 'veg='.urlencode($vegFilter).'&' : '' ?><?= $sortBy !== 'none' ? 'sort='.urlencode($sortBy).'&' : '' ?>"
         class="px-4 py-2 border rounded-full text-xs font-bold shrink-0 transition-all cursor-pointer <?= $catId === 0 ? 'bg-[#ffdbd0] text-[#a83300] border-[#a83300] shadow-sm' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50' ?>">
        All Categories
      </a>
      <?php foreach ($categories as $cat): ?>
      <a href="?category=<?= $cat['id'] ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $vegFilter !== 'all' ? '&veg='.urlencode($vegFilter) : '' ?><?= $sortBy !== 'none' ? '&sort='.urlencode($sortBy) : '' ?>"
         class="px-4 py-2 border rounded-full text-xs font-bold shrink-0 transition-all cursor-pointer <?= $catId === (int)$cat['id'] ? 'bg-[#ffdbd0] text-[#a83300] border-[#a83300] shadow-sm' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50' ?>">
        <?= e($cat['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Veg/Non-Veg Switches -->
    <div class="lg:col-span-3 flex justify-center gap-1.5 bg-gray-100 p-1.5 rounded-xl">
      <a href="?veg=all<?= $search ? '&search='.urlencode($search) : '' ?><?= $catId > 0 ? '&category='.$catId : '' ?><?= $sortBy !== 'none' ? '&sort='.urlencode($sortBy) : '' ?>"
         class="flex-1 py-1.5 text-center text-[10px] font-bold rounded-lg transition-all <?= $vegFilter === 'all' ? 'bg-white text-black shadow-sm' : 'text-gray-505' ?>">
        All Dishes
      </a>
      <a href="?veg=veg<?= $search ? '&search='.urlencode($search) : '' ?><?= $catId > 0 ? '&category='.$catId : '' ?><?= $sortBy !== 'none' ? '&sort='.urlencode($sortBy) : '' ?>"
         class="flex-1 py-1.5 text-center text-[10px] font-bold rounded-lg transition-all <?= $vegFilter === 'veg' ? 'bg-green-600 text-white shadow-sm' : 'text-green-600' ?>">
        🌱 Veg
      </a>
      <a href="?veg=nonveg<?= $search ? '&search='.urlencode($search) : '' ?><?= $catId > 0 ? '&category='.$catId : '' ?><?= $sortBy !== 'none' ? '&sort='.urlencode($sortBy) : '' ?>"
         class="flex-1 py-1.5 text-center text-[10px] font-bold rounded-lg transition-all <?= $vegFilter === 'nonveg' ? 'bg-red-600 text-white shadow-sm' : 'text-red-600' ?>">
        🔺 Non-Veg
      </a>
    </div>

    <!-- Sorter Dropdown -->
    <div class="lg:col-span-3 flex justify-end">
      <form method="GET" action="" class="w-full">
        <?php if ($search): ?><input type="hidden" name="search" value="<?= e($search) ?>"><?php endif; ?>
        <?php if ($catId > 0): ?><input type="hidden" name="category" value="<?= $catId ?>"><?php endif; ?>
        <?php if ($vegFilter !== 'all'): ?><input type="hidden" name="veg" value="<?= e($vegFilter) ?>"><?php endif; ?>
        
        <div class="relative w-full">
          <select name="sort" onchange="this.form.submit()"
                  class="pl-4 pr-8 py-2 w-full border rounded-full text-xs font-bold cursor-pointer transition-all outline-none appearance-none <?= $sortBy !== 'none' ? 'bg-[#ffdbd0] text-[#a83300] border-[#a83300]' : 'border-gray-200 hover:bg-[#f5f3f3] bg-white text-gray-600' ?>">
            <option value="none"       <?= $sortBy === 'none'       ? 'selected' : '' ?>>⚙ Sort: Default</option>
            <option value="price_asc"  <?= $sortBy === 'price_asc'  ? 'selected' : '' ?>>₹ Price: Low to High</option>
            <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>₹ Price: High to Low</option>
            <option value="rating"     <?= $sortBy === 'rating'     ? 'selected' : '' ?>>⭐ Kitchen Rating</option>
            <option value="popularity" <?= $sortBy === 'popularity' ? 'selected' : '' ?>>🔥 Popularity</option>
          </select>
          <svg class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
      </form>
    </div>

  </div>

  <!-- Dishes Grid -->
  <?php if (empty($items)): ?>
  <div class="bg-white rounded-3xl p-16 text-center border border-gray-150 flex flex-col items-center justify-center gap-4">
    <span class="text-5xl">🍔</span>
    <div>
      <p class="font-extrabold text-lg text-[#1b1c1c]">No Dishes Found</p>
      <p class="text-xs text-gray-500 mt-1">We couldn't find any foods matching your selected cuisine, category or tag filters.</p>
    </div>
    <a href="<?= BASE_URL ?>/menu.php" class="text-xs font-bold text-white bg-[#a83300] px-6 py-3 rounded-xl hover:bg-[#d24200] transition-all shadow-sm">
      Show All Foods
    </a>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($items as $item): ?>
    <div class="bg-white rounded-2xl border border-gray-150 p-4 shadow-sm hover:shadow-md transition-all flex justify-between gap-4">
      
      <div class="flex-1 flex flex-col justify-between min-w-0">
        <div>
          <!-- veg/nonveg & rating info -->
          <div class="flex items-center gap-2">
            <span class="w-3.5 h-3.5 border flex items-center justify-center rounded-sm text-[8px] font-bold shrink-0 <?= $item['is_veg'] ? 'border-green-600 text-green-600' : 'border-red-600 text-red-600' ?>">
              <?= $item['is_veg'] ? '●' : '▲' ?>
            </span>
            <span class="text-[9px] text-[#f59e0b] font-bold">★ <?= number_format($item['restaurant_rating'], 1) ?></span>
            <?php if ($item['is_special']): ?>
            <span class="bg-[#ffdbd0] text-[#a83300] text-[8px] font-bold px-1.5 py-0.5 rounded select-none shrink-0 uppercase tracking-widest">TODAY'S SPECIAL</span>
            <?php endif; ?>
          </div>
          
          <h4 class="font-extrabold text-sm text-[#1b1c1c] mt-2 truncate"><?= e($item['name']) ?></h4>
          <p class="text-[10px] text-gray-400 mt-0.5">by <a href="<?= BASE_URL ?>/restaurant.php?id=<?= $item['restaurant_slug'] ?>" class="font-semibold text-gray-600 hover:underline"><?= e($item['restaurant_name']) ?></a></p>
          <p class="text-xs text-gray-500 line-clamp-2 mt-1 leading-relaxed"><?= e($item['description'] ?: 'Delightful delicious gourmet recipe cooked fresh daily.') ?></p>
        </div>

        <div class="flex justify-between items-center mt-3 pt-2.5 border-t border-gray-50">
          <span class="text-[#a83300] font-black text-sm"><?= formatPrice($item['price']) ?></span>
          <a href="<?= BASE_URL ?>/restaurant.php?id=<?= $item['restaurant_slug'] ?>" 
             class="px-4 py-1.5 bg-gray-50 hover:bg-[#ffdbd0] hover:text-[#a83300] text-gray-650 hover:border-[#a83300] border border-gray-200 text-[10px] font-bold rounded-lg transition-all shadow-sm">
            Order Dish
          </a>
        </div>
      </div>

      <!-- image thumbnail -->
      <div class="w-24 h-24 md:w-28 md:h-28 rounded-xl overflow-hidden shrink-0 border border-gray-100 shadow-sm relative">
        <img src="<?= $item['image'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBZLbHo94g2948yCQi_Q1dVUSPm7BgZNWKJWBJwlPkeAxvdQXlETDOg88T30AcJwkVKeiDN3TZ3h4Uzx-ktYgh2MxBjNSgQmOdj3cR8mlX0VcaeE9AA-ynZ-cXRNbEOjFU47cUGFE9pWTrzGgqg6liFOHMYjEWhj-CyDCSeVvyO5282aXh30ZUK6uEhmx48fz-0Os880RaqVw-iUMvfgiHqI0oGi_UikGPKsXXv80RBqP2yhQQchY8YwAnkKE6NJTZJYRarOE_5lng' ?>" 
             alt="<?= e($item['name']) ?>" class="w-full h-full object-cover">
      </div>

    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
