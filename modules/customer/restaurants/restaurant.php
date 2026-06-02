<?php
/**
 * Zesto — Restaurant Detail Page (Menu)
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/location_helper.php';
require_once __DIR__ . '/../../../includes/image_helper.php';

$idSlug = $_GET['id'] ?? '';
if (!$idSlug) {
    header("Location: " . BASE_URL . "/restaurants.php");
    exit;
}

// Fetch Restaurant
$stmt = db()->prepare("SELECT * FROM restaurants WHERE slug = ? OR id = ?");
$stmt->execute([$idSlug, $idSlug]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    header("Location: " . BASE_URL . "/restaurants.php");
    exit;
}

$pageTitle = e($restaurant['name']) . ' - Zesto Nights Menu';

// Fetch Categories for this restaurant
$catStmt = db()->prepare("
    SELECT DISTINCT c.name as category 
    FROM menu_items mi
    JOIN categories c ON c.id = mi.category_id
    WHERE mi.restaurant_id = ? AND mi.is_available = 1 
    ORDER BY c.name ASC
");
$catStmt->execute([$restaurant['id']]);
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
array_unshift($categories, 'All');

// Menu Filters
$selectedCat = $_GET['cat'] ?? 'All';
$searchQuery = $_GET['q'] ?? '';

// Fetch Menu Items
$sql = "SELECT mi.* FROM menu_items mi WHERE mi.restaurant_id = :rid AND mi.is_available = 1";
$params = [':rid' => $restaurant['id']];

if ($selectedCat !== 'All') {
    $sql .= " AND mi.category_id IN (SELECT id FROM categories WHERE name = :cat)";
    $params[':cat'] = $selectedCat;
}
if (!empty($searchQuery)) {
    $sql .= " AND (mi.name LIKE :q OR mi.description LIKE :q2)";
    $params[':q'] = "%$searchQuery%";
    $params[':q2'] = "%$searchQuery%";
}
$sql .= " ORDER BY mi.is_bestseller DESC, mi.name ASC";

$itemStmt = db()->prepare($sql);
$itemStmt->execute($params);
$items = $itemStmt->fetchAll();

// Get items in cart (if we want to show 'Added')
$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/navbar.php';
?>

<main class="flex-1 bg-zesto-dark font-sans text-[#dfe2eb]">
<div class="w-full max-w-7xl mx-auto px-4 sm:px-10 py-6 space-y-8 animate-fade-in">

  <!-- Back Button and Header -->
  <div class="flex items-center gap-2">
    <a href="<?= BASE_URL ?>/restaurants.php" class="p-1.5 rounded-full hover:bg-white/5 border border-white/5 text-white/70 hover:text-white transition flex items-center gap-1.5 text-xs font-semibold cursor-pointer no-underline text-inherit">
      <i data-lucide="chevron-left" class="w-4 h-4"></i>
      <span>Back to Thattukadas</span>
    </a>
  </div>

  <!-- Hero Banner Details -->
  <section class="relative rounded-2xl overflow-hidden glass-panel p-6 sm:p-10 flex flex-col md:flex-row items-center gap-8 border border-white/10">
    <div 
      class="absolute inset-0 z-0 opacity-15 bg-cover bg-center filter blur-xl scale-110"
      style="background-image: url('<?= e(getRestaurantBanner($restaurant)) ?>')"
    ></div>
    <div class="absolute inset-0 z-0 bg-gradient-to-r from-[#10141a] via-[#10141a]/95 to-transparent"></div>

    <!-- Cover Thumbnail Image -->
    <div class="relative z-10 w-full md:w-64 h-44 rounded-xl overflow-hidden shadow-2xl border border-white/10 flex-shrink-0">
      <img 
        src="<?= e(getRestaurantBanner($restaurant)) ?>" 
        alt="<?= e($restaurant['name']) ?>"
        class="w-full h-full object-cover"
      />
    </div>

    <!-- Metadata stats -->
    <div class="relative z-10 space-y-4 text-left flex-1">
      <div class="flex flex-wrap items-center gap-2">
        <span class="bg-zesto-orange/15 border border-zesto-orange/30 text-zesto-orange px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-widest uppercase">
          DELIVERING UNTIL <?= e($restaurant['open_until'] ?? '3 AM') ?>
        </span>
        <span class="bg-zesto-cyan/15 border border-[#00daf3]/30 text-zesto-cyan px-2.5 py-0.5 rounded-full text-[10px] font-semibold flex items-center gap-1">
          <i data-lucide="clock" class="w-3 h-3 animate-spin"></i> ACTIVE IN <?= strtoupper(e($restaurant['city'])) ?>
        </span>
      </div>

      <h1 class="text-3xl sm:text-4xl font-display font-extrabold text-white"><?= e($restaurant['name']) ?></h1>
      <p class="text-sm text-zesto-amber font-semibold"><?= e($restaurant['tags']) ?></p>

      <div class="flex flex-wrap items-center gap-6 text-xs text-white/70 pt-2 pb-1">
        <div class="flex items-center gap-1">
          <i data-lucide="star" class="w-4 h-4 text-zesto-amber fill-zesto-amber"></i>
          <span class="font-bold text-white text-sm"><?= number_format($restaurant['rating'], 1) ?></span>
          <span class="text-white/40">(<?= (int)($restaurant['rating'] * 20 + mt_rand(10,50)) ?> night evaluations)</span>
        </div>
        
        <span class="text-white/30 hidden sm:inline">|</span>
        
        <div class="flex items-center gap-1.5 font-medium">
          <i data-lucide="clock" class="w-4 h-4 text-zesto-orange"></i>
          <span><?= e($restaurant['delivery_time']) ?> delivery time</span>
        </div>

        <span class="text-white/30 hidden sm:inline">|</span>

        <div class="flex items-center gap-1.5 font-medium">
          <i data-lucide="map-pin" class="w-4 h-4 text-white/50"></i>
          <span><?= number_format($restaurant['distance'], 1) ?> km away from you</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Menu Area Filters & Items -->
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    
    <!-- Left Side: Filter Categories List -->
    <div class="lg:col-span-1 space-y-4">
      <div class="glass-panel rounded-xl p-4 border border-white/5 sticky top-24">
        <h3 class="text-xs font-bold text-white/40 uppercase tracking-widest mb-3">Menu Sections</h3>
        <div class="flex flex-row lg:flex-col overflow-x-auto lg:overflow-visible gap-1.5 pb-2 lg:pb-0 scrollbar-none">
          <?php foreach ($categories as $cat): 
            $isActive = ($selectedCat === $cat);
            $btnClass = $isActive
                ? 'bg-zesto-orange/15 text-zesto-orange border-zesto-orange/30'
                : 'text-white/80 hover:bg-white/5 border-transparent';
            $link = '?id=' . urlencode($idSlug) . '&cat=' . urlencode($cat);
            if (!empty($searchQuery)) $link .= '&q=' . urlencode($searchQuery);
          ?>
            <a href="<?= $link ?>" class="w-full text-left px-3.5 py-2.5 rounded-lg text-xs font-semibold transition active:scale-95 whitespace-nowrap cursor-pointer border no-underline <?= $btnClass ?>">
              <?= e($cat) ?>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- Menu Specific Filter input -->
        <div class="relative mt-6">
          <form method="GET" action="">
            <input type="hidden" name="id" value="<?= e($idSlug) ?>">
            <input type="hidden" name="cat" value="<?= e($selectedCat) ?>">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
              <i data-lucide="search" class="h-4.5 w-4.5 text-white/35"></i>
            </span>
            <input
              type="text"
              name="q"
              placeholder="Search dish..."
              value="<?= e($searchQuery) ?>"
              class="w-full bg-[#10141a] border border-white/10 text-white rounded-lg pl-9 pr-4 py-2 text-xs focus:outline-none focus:border-zesto-orange transition-all placeholder:text-white/35"
            />
          </form>
        </div>
      </div>
    </div>

    <!-- Right Side: Dishes list -->
    <div class="lg:col-span-3 space-y-6">
      <div class="flex items-center justify-between pl-1">
        <h2 class="text-xl font-display font-extrabold text-white flex items-center gap-1.5">
          <i data-lucide="sparkles" class="w-4 h-4 text-zesto-amber"></i>
          <span><?= $selectedCat === 'All' ? 'Thattukada Specialties' : e($selectedCat) ?></span>
        </h2>
        <span class="text-xs text-white/40 font-bold"><?= count($items) ?> delicacies found</span>
      </div>

      <div class="space-y-4">
        <?php if (empty($items)): ?>
          <div class="glass-panel rounded-xl p-10 text-center border border-white/10">
             <p class="text-white/50 text-sm">No dishes found matching your criteria.</p>
          </div>
        <?php else: ?>
          <?php foreach ($items as $item): 
            // Check if item is in cart
            $qtyInCart = 0;
            foreach ($cartItems as $cItem) {
                if ($cItem['menu_item_id'] == $item['id']) {
                    $qtyInCart = $cItem['quantity'];
                    break;
                }
            }
          ?>
            <div class="p-5 rounded-2xl bg-zesto-charcoal/40 hover:bg-zesto-charcoal/80 border border-white/5 hover:border-white/10 transition duration-300 flex flex-col sm:flex-row gap-5 justify-between relative group">
              
              <?php if ($qtyInCart > 0): ?>
                <span class="absolute top-3 right-3 bg-zesto-amber text-zesto-dark text-[10px] font-extrabold px-2.5 py-1 rounded-full flex items-center gap-1 shadow-md">
                  <i data-lucide="check" class="w-3.5 h-3.5 stroke-[3]"></i> Added x<?= $qtyInCart ?>
                </span>
              <?php endif; ?>

              <!-- Left Side: Dish info details -->
              <div class="space-y-3.5 flex-1 max-w-xl">
                <div class="space-y-1.5">
                  <div class="flex items-center gap-2">
                    <h4 class="text-lg font-display font-extrabold text-white tracking-wide"><?= e($item['name']) ?></h4>
                    <?php if ($item['is_bestseller']): ?>
                      <span class="bg-zesto-orange/10 text-zesto-orange border border-zesto-orange/30 px-2 py-0.5 rounded text-[8px] font-black uppercase">
                        Bestseller
                      </span>
                    <?php endif; ?>
                  </div>
                  <p class="text-xs text-white/60 leading-relaxed font-sans"><?= e($item['description']) ?></p>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-xs font-semibold pt-1">
                  <span class="text-lg font-extrabold text-zesto-orange"><?= formatPrice($item['price']) ?></span>
                  
                  <span class="text-white/20">|</span>
                  
                  <div class="flex items-center gap-1 text-[11px] text-white/55 font-medium">
                    <i data-lucide="flame" class="w-3.5 h-3.5 text-zesto-orange-glow fill-zesto-orange-glow"></i>
                    <span>Spice: 🌶️🌶️</span>
                  </div>
                </div>
              </div>

              <div class="w-full sm:w-28 flex flex-row sm:flex-col items-center sm:justify-start gap-4 flex-shrink-0" id="wrap-<?= $item['id'] ?>" data-theme="dark">
                <img 
                  src="<?= getFoodImage($item['image'], $item['name']) ?>" 
                  alt="<?= e($item['name']) ?>"
                  class="w-24 h-24 sm:w-28 sm:h-24 rounded-xl object-cover border border-white/10 group-hover:scale-102 transition"
                />
                
                <?php if ($qtyInCart > 0): ?>
                  <!-- Render Stepper directly if already in cart -->
                  <div class="qty-stepper w-full mt-auto">
                    <button class="qty-stepper-btn" onclick="event.preventDefault(); event.stopPropagation(); cartDecrement(<?= $item['id'] ?>, <?= $restaurant['id'] ?>, '<?= e($restaurant['slug']) ?>')">−</button>
                    <span class="qty-stepper-count"><?= $qtyInCart ?></span>
                    <button class="qty-stepper-btn" onclick="event.preventDefault(); event.stopPropagation(); cartIncrement(<?= $item['id'] ?>, <?= $restaurant['id'] ?>, '<?= e($restaurant['slug']) ?>')">+</button>
                  </div>
                  <script>
                    window.cartQty = window.cartQty || {};
                    window.cartQty[<?= $item['id'] ?>] = <?= $qtyInCart ?>;
                  </script>
                <?php else: ?>
                  <button
                    onclick="event.preventDefault(); event.stopPropagation(); cartAdd(<?= $item['id'] ?>, <?= $restaurant['id'] ?>, '<?= e($restaurant['slug']) ?>')"
                    class="zesto-add-btn w-full mt-auto"
                  >
                    <span>+ Add</span>
                  </button>
                <?php endif; ?>
              </div>

            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>
</main>

<script>
// Global initialization for cartQty if not already done.
window.cartQty = window.cartQty || {};
</script>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
