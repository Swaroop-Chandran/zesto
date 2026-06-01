<?php
/**
 * Zesto — Restaurant List / Discovery Page
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/location_helper.php';
require_once __DIR__ . '/../../../includes/image_helper.php';

$pageTitle = 'Restaurants Near You | Zesto Nights';
$city      = getCurrentCity();

// Search & filter processing
$search   = trim($_GET['search'] ?? '');
$catTag   = trim($_GET['category'] ?? '');
$sortBy   = $_GET['sort'] ?? 'none';

$sql = "SELECT * FROM restaurants WHERE is_active=1 AND city=:city";
$params = [':city' => $city];

if ($search !== '') {
    $sql .= " AND (name LIKE :s OR tags LIKE :s2)";
    $params[':s'] = "%$search%"; $params[':s2'] = "%$search%";
}
if ($catTag !== '') {
    $sql .= " AND tags LIKE :cat";
    $params[':cat'] = "%$catTag%";
}

// Sorting logic
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

// Specialties for filter chips
$specialties = ['All', 'Porotta', 'Beef Roast', 'Kappa', 'Chicken Fry', 'Black Tea'];

// Quick helper to fetch top items for mini preview
function getMiniPreviewItems($restId) {
    $stmt = db()->prepare("SELECT name, price FROM menu_items WHERE restaurant_id = ? AND is_available = 1 LIMIT 3");
    $stmt->execute([$restId]);
    return $stmt->fetchAll();
}

include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/navbar.php';
?>

<main class="flex-1 bg-zesto-dark font-sans text-[#dfe2eb]">
<div class="w-full max-w-7xl mx-auto px-4 sm:px-10 py-6 space-y-8 animate-fade-in">

  <!-- Header Info -->
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
      <h1 class="text-3xl font-display font-extrabold text-white">Thattukadas Open Now</h1>
      <p class="text-sm text-white/50 mt-1">Kerala street food joints active and delivering late night cravings in <?= e($city) ?></p>
    </div>

    <!-- Sort Selectors -->
    <div class="flex flex-wrap items-center gap-3">
      <span class="text-xs text-white/50 font-semibold flex items-center gap-1">
        <i data-lucide="filter" class="w-3.5 h-3.5"></i> Sort By:
      </span>
      <?php
      $sortOptions = [
        'none'     => 'Recommended',
        'rating'   => 'Top Rated ⭐',
        'time'     => 'Fastest ⚡',
        'distance' => 'Closest 📍',
      ];
      foreach ($sortOptions as $key => $label):
        $isActive = ($sortBy === $key);
        $btnClass = $isActive 
            ? 'bg-zesto-orange text-white border-zesto-orange font-bold' 
            : 'bg-white/5 text-white/70 border-white/10 hover:bg-white/10';
      ?>
        <a href="?sort=<?= $key ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($catTag) ?>" class="text-xs px-3.5 py-1.5 rounded-full border transition active:scale-95 cursor-pointer no-underline <?= $btnClass ?>">
          <?= $label ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Specialty Filter Chips -->
  <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
    <?php foreach ($specialties as $spec): 
      // If catTag is empty and spec is All, it's active. If catTag matches spec, it's active.
      $isActive = ($spec === 'All' && $catTag === '') || (strtolower($catTag) === strtolower($spec));
      $chipClass = $isActive
        ? 'bg-zesto-amber text-[#402d00] border-zesto-amber shadow-lg shadow-zesto-amber/10'
        : 'bg-white/5 text-white/80 border-white/5 hover:bg-white/10';
      $chipLink = ($spec === 'All') ? '?' : '?category=' . urlencode($spec);
      if ($sortBy !== 'none') $chipLink .= '&sort=' . urlencode($sortBy);
    ?>
      <a href="<?= $chipLink ?>" class="text-xs px-4 py-2 font-semibold rounded-full border transition whitespace-nowrap cursor-pointer no-underline <?= $chipClass ?>">
        <?= $spec ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Grid Results -->
  <?php if (empty($restaurants)): ?>
    <div class="text-center py-20 bg-white/5 rounded-2xl border border-white/5 max-w-xl mx-auto">
      <span class="text-3xl">🌙</span>
      <h3 class="text-base font-bold text-white mt-4">No Active Joints Found</h3>
      <p class="text-xs text-white/50 mt-1 max-w-md mx-auto px-4">
        No Thattukadas matched "<?= e($search ?: $catTag) ?>". Try broadening your cravings or adjusting filters to find cozy meals!
      </p>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php foreach ($restaurants as $rest): ?>
        <a href="<?= BASE_URL ?>/restaurant.php?id=<?= e($rest['slug']) ?>" class="glass-card group rounded-2xl overflow-hidden cursor-pointer flex flex-col justify-between no-underline text-inherit">
          <!-- Image banner with overlay -->
          <div class="relative h-44 w-full">
            <img 
              src="<?= e(getRestaurantBanner($rest)) ?>" 
              alt="<?= e($rest['name']) ?>"
              class="w-full h-full object-cover group-hover:scale-105 transition-all duration-500"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-[#10141a]/95 via-transparent to-transparent"></div>
            
            <!-- Floating Open Tag & Distance Indicator -->
            <div class="absolute top-3 right-3 flex items-center gap-1.5">
              <span class="bg-[#10141a]/85 backdrop-blur-md px-2.5 py-1 rounded-full border border-white/10 text-[9px] font-extrabold text-zesto-orange uppercase tracking-wider">
                Till <?= e($rest['open_until'] ?? '3 AM') ?>
              </span>
              <span class="bg-[#10141a]/85 backdrop-blur-md px-2.5 py-1 rounded-full border border-white/10 text-[9px] font-bold text-white">
                <?= number_format($rest['distance'], 1) ?> km away
              </span>
            </div>
          </div>

          <!-- Text content details -->
          <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
            <div>
              <h3 class="text-lg font-display font-extrabold text-white group-hover:text-zesto-orange transition-colors">
                <?= e($rest['name']) ?>
              </h3>
              <p class="text-xs text-white/50 mt-1 line-clamp-1"><?= e($rest['tags']) ?></p>
            </div>

            <!-- Reviews, Rating & Time -->
            <div class="flex items-center justify-between text-xs text-white/70 pt-3 border-t border-white/5">
              <div class="flex items-center gap-1">
                <i data-lucide="star" class="w-3.5 h-3.5 text-zesto-amber fill-zesto-amber"></i>
                <span class="font-bold text-white"><?= number_format($rest['rating'], 1) ?></span>
                <span class="text-white/40">(<?= (int)($rest['rating'] * 20 + mt_rand(10,50)) ?> night owls)</span>
              </div>
              <div class="flex items-center gap-1 font-semibold text-zesto-cyan text-[11px]">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-zesto-cyan"></i>
                <span><?= e($rest['delivery_time']) ?> DELIVERY</span>
              </div>
            </div>

            <!-- Mini Preview items available -->
            <div class="bg-white/5 rounded-xl p-2.5 border border-white/5">
              <span class="text-[10px] font-bold text-white/40 block mb-1.5 uppercase tracking-widest">Late Night Menu Highlights:</span>
              <div class="flex flex-wrap gap-1.5">
                <?php 
                $miniItems = getMiniPreviewItems($rest['id']);
                foreach ($miniItems as $mi): 
                ?>
                  <span class="bg-[#10141a] px-2 py-1 rounded text-[10px] font-semibold text-white/80 border border-white/5">
                    <?= e($mi['name']) ?> • <?= formatPrice($mi['price']) ?>
                  </span>
                <?php endforeach; ?>
                <?php if (empty($miniItems)): ?>
                  <span class="text-white/30 text-[10px] italic">Menu updating...</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="flex items-center justify-between text-xs font-bold text-zesto-orange pt-1">
              <span>Explore Full Menu</span>
              <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1.5 transition-transform"></i>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
