<?php
/**
 * Zesto — Swiggy-Style Restaurant Detail & Menu Page v2.0
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/location_helper.php';
require_once __DIR__ . '/../../../includes/image_helper.php';

$slug = trim($_GET['id'] ?? '');
if (empty($slug)) { header('Location: ' . BASE_URL . '/index.php'); exit; }

// Fetch Restaurant
$stmt = db()->prepare("SELECT * FROM restaurants WHERE slug=:slug AND is_active=1 LIMIT 1");
$stmt->execute([':slug' => $slug]);
$restaurant = $stmt->fetch();
if (!$restaurant) { http_response_code(404); echo "<h1>Restaurant Not Found</h1>"; exit; }

// Fetch categories used by this restaurant's active items
$catStmt = db()->prepare("
    SELECT DISTINCT c.*
    FROM categories c
    JOIN menu_items mi ON mi.category_id = c.id
    WHERE mi.restaurant_id=:rid AND mi.is_available=1
    ORDER BY c.display_order ASC
");
$catStmt->execute([':rid' => $restaurant['id']]);
$categories = $catStmt->fetchAll();

// Fetch all menu items
$menuStmt = db()->prepare("
    SELECT mi.*, c.name AS category_name
    FROM menu_items mi
    LEFT JOIN categories c ON c.id = mi.category_id
    WHERE mi.restaurant_id=:rid AND mi.is_available=1
    ORDER BY mi.is_popular DESC, c.display_order ASC, mi.display_order ASC
");
$menuStmt->execute([':rid' => $restaurant['id']]);
$menuItems = $menuStmt->fetchAll();

// Fetch restaurant reviews from order_reviews
$reviewsStmt = db()->prepare("
    SELECT r.review_text, r.restaurant_rating, r.created_at, u.name AS customer_name
    FROM order_reviews r
    JOIN users u ON u.id = r.customer_id
    WHERE r.restaurant_id = :rid AND r.review_text IS NOT NULL AND r.review_text != ''
    ORDER BY r.created_at DESC LIMIT 5
");
$reviewsStmt->execute([':rid' => $restaurant['id']]);
$restaurantReviews = $reviewsStmt->fetchAll();

// Group items by category
$groupedMenu = [];
foreach ($menuItems as $item) {
    $catName = $item['category_name'] ?: 'Menu';
    $groupedMenu[$catName][] = $item;
}

// Recommended items (popular)
$recommended = array_filter($menuItems, fn($i) => $i['is_popular']);

$pageTitle   = $restaurant['name'] . ' Menu — Order on Zesto';
$description = 'Order from ' . $restaurant['name'] . '. ' . ($restaurant['description'] ?? 'Great food delivered fast.');
include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/navbar.php';
?>

<main class="flex-1 bg-[#f5f5f0] pb-mobile-nav" id="restaurant-page">

  <!-- ── Back Button ─────────────────────────────────────────── -->
  <div class="max-w-[1280px] mx-auto px-4 md:px-8 lg:px-10 pt-4">
    <a href="<?= BASE_URL ?>/restaurants.php"
       class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-[#a83300] transition-colors group">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      Back to Restaurants
    </a>
  </div>

  <!-- ── Restaurant Banner ───────────────────────────────────── -->
  <div class="relative h-48 md:h-72 lg:h-80 bg-[#1b1c1c] overflow-hidden mt-3">
    <img src="<?= e(getRestaurantBanner($restaurant)) ?>"
         alt="<?= e($restaurant['name']) ?> — Restaurant Banner"
         class="w-full h-full object-cover opacity-80">
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
  </div>

  <!-- ── Restaurant Info Card ────────────────────────────────── -->
  <div class="max-w-[1280px] mx-auto px-4 md:px-8 lg:px-10">
    <div class="bg-white rounded-2xl md:rounded-3xl shadow-lg border border-[#ece9e6] -mt-12 md:-mt-16 relative z-10 p-5 md:p-8">
      <div class="flex gap-4 md:gap-6 items-start">
        <!-- Logo -->
        <div class="w-16 h-16 md:w-24 md:h-24 rounded-xl md:rounded-2xl overflow-hidden border-2 border-[#ece9e6] bg-white flex-shrink-0 shadow-md">
          <img src="<?= e(getRestaurantLogo($restaurant)) ?>"
               alt="<?= e($restaurant['name']) ?> logo"
               class="w-full h-full object-cover">
        </div>
        
        <!-- Details -->
        <div class="flex-1 min-w-0">
          <h1 class="text-xl md:text-3xl font-black text-[#1b1c1c] leading-tight"><?= e($restaurant['name']) ?></h1>
          <p class="text-sm md:text-base text-gray-500 mt-1 line-clamp-2"><?= e($restaurant['tags']) ?></p>
          
          <div class="flex flex-wrap items-center gap-3 mt-3">
            <!-- Rating -->
            <div class="flex items-center gap-1.5 bg-green-600 text-white text-sm font-bold px-2.5 py-1 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 fill-current" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              <?= number_format((float)($restaurant['rating'] ?? 0), 1) ?>
              <span class="text-green-200 font-normal text-xs">(<?= number_format((int)($restaurant['rating_count'] ?? 0)) ?>+)</span>
            </div>
            
            <!-- Delivery Time -->
            <div class="flex items-center gap-1.5 text-sm text-gray-600 font-semibold bg-gray-100 px-2.5 py-1 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <?= e($restaurant['delivery_time'] ?: '30–45 min') ?>
            </div>
            
            <!-- Delivery Fee -->
            <div class="text-sm text-gray-600 font-semibold bg-gray-100 px-2.5 py-1 rounded-lg">
              <?= $restaurant['is_free_delivery'] ? '🎉 Free Delivery' : '₹' . number_format($restaurant['delivery_fee'], 0) . ' delivery' ?>
            </div>
            
            <!-- Distance -->
            <?php if ($restaurant['distance']): ?>
            <div class="flex items-center gap-1 text-sm text-gray-500 font-medium">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <?= number_format($restaurant['distance'], 1) ?> km
            </div>
            <?php endif; ?>
          </div>
          
          <?php if ($restaurant['description']): ?>
          <p class="text-sm text-gray-500 mt-3 leading-relaxed line-clamp-3 md:line-clamp-none"><?= e($restaurant['description']) ?></p>
          <?php endif; ?>
          
          <?php if ($restaurant['discount']): ?>
          <div class="restaurant-offer-badge mt-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M7 7h.01M7 3h5l7 7a3 3 0 0 1 0 4.24l-5 5a3 3 0 0 1-4.24 0L3 12V7a4 4 0 0 1 4-4z"/></svg>
            <?= e($restaurant['discount']) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Menu Section ────────────────────────────────────────── -->
  <div class="max-w-[1280px] mx-auto px-4 md:px-8 lg:px-10 mt-6 md:mt-8">
    
    <!-- Search + Veg Filter Bar -->
    <div class="flex items-center gap-3 mb-6 bg-white rounded-xl border border-[#ece9e6] p-3">
      <div class="flex-1 flex items-center gap-2.5 bg-gray-50 rounded-lg px-3.5 py-2.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="menu-search" placeholder="Search in menu..."
               class="flex-1 bg-transparent border-none outline-none text-sm font-medium text-[#1b1c1c] placeholder:text-gray-400">
      </div>
      <label class="flex items-center gap-2 cursor-pointer select-none shrink-0">
        <div class="relative">
          <input type="checkbox" id="veg-toggle" class="sr-only">
          <div class="w-10 h-5 bg-gray-200 rounded-full transition-colors" id="veg-track"></div>
          <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform" id="veg-thumb"></div>
        </div>
        <div class="veg-dot veg"></div>
        <span class="text-sm font-bold text-[#1b1c1c]">Veg Only</span>
      </label>
    </div>

    <!-- Layout: Category Nav (desktop left) + Menu (main) -->
    <div class="flex gap-6 lg:gap-8 items-start">
      
      <!-- Category Nav — desktop sticky sidebar -->
      <?php if (count($categories) > 1): ?>
      <nav class="hidden lg:block w-52 shrink-0 sticky top-4 bg-white rounded-2xl border border-[#ece9e6] p-3 max-h-[calc(100vh-6rem)] overflow-y-auto scrollbar-none">
        <p class="text-xs font-black text-gray-400 uppercase tracking-wider mb-3 px-2">Menu</p>
        <?php if (!empty($recommended)): ?>
        <a href="#section-recommended" onclick="scrollToSection('recommended')" class="category-nav-item active" data-section="recommended">
          ⭐ Recommended (<?= count($recommended) ?>)
        </a>
        <?php endif; ?>
        <?php foreach ($categories as $cat): ?>
        <?php $safeName = preg_replace('/[^a-z0-9]+/', '-', strtolower($cat['name'])); ?>
        <a href="#section-<?= $safeName ?>" onclick="scrollToSection('<?= $safeName ?>')" class="category-nav-item" data-section="<?= $safeName ?>">
          <?= getCategoryEmoji($cat['name']) ?> <?= e($cat['name']) ?> (<?= count($groupedMenu[$cat['name']] ?? []) ?>)
        </a>
        <?php endforeach; ?>
      </nav>
      <?php endif; ?>

      <!-- Menu Items -->
      <div class="flex-1 min-w-0 space-y-8" id="menu-container">

        <!-- Recommended Section -->
        <?php if (!empty($recommended)): ?>
        <div class="menu-section" data-section="recommended" id="section-recommended">
          <div class="menu-section-divider">
            <h3>⭐ Recommended <span class="text-gray-400 font-medium text-sm"><?= count($recommended) ?> items</span></h3>
          </div>
          <div class="space-y-3 mt-3">
            <?php foreach ($recommended as $item): ?>
            <?php $itemImg = getFoodImage($item['image'], $item['name'], $item['category_name'] ?? ''); ?>
            <div class="food-card menu-item" 
                 data-name="<?= strtolower(e($item['name'])) ?>"
                 data-veg="<?= $item['is_veg'] ? '1' : '0' ?>"
                 data-id="<?= (int)$item['id'] ?>">
              <!-- Text side -->
              <div class="food-card-content">
                <div class="flex items-center gap-1.5 mb-1.5">
                  <div class="veg-dot <?= $item['is_veg'] ? 'veg' : 'nonveg' ?>"></div>
                  <?php if ($item['is_special']): ?><span class="text-[10px] bg-[#ffdbd0] text-[#a83300] font-black px-2 py-0.5 rounded uppercase tracking-wide">Chef's Special</span><?php endif; ?>
                  <?php if ($item['is_popular']): ?><span class="text-[10px] bg-amber-100 text-amber-700 font-black px-2 py-0.5 rounded uppercase tracking-wide">Bestseller</span><?php endif; ?>
                </div>
                <h3 class="food-card-name"><?= e($item['name']) ?></h3>
                <?php if ($item['description']): ?>
                <p class="food-card-desc"><?= e($item['description']) ?></p>
                <?php endif; ?>
                <div class="flex items-end justify-between mt-3">
                  <span class="food-card-price"><?= formatPrice($item['price']) ?></span>
                  <div class="add-btn-wrap" id="wrap-<?= $item['id'] ?>">
                    <button onclick="cartAdd(<?= $item['id'] ?>, <?= $restaurant['id'] ?>, '<?= e($restaurant['slug']) ?>')"
                            class="add-to-cart-btn">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                      ADD
                    </button>
                  </div>
                </div>
              </div>
              <!-- Image side -->
              <div class="food-card-img-wrap">
                <img src="<?= e($itemImg) ?>" alt="<?= e($item['name']) ?>" class="food-card-img" loading="lazy">
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Category Sections -->
        <?php foreach ($groupedMenu as $catName => $items): ?>
        <?php $sectionId = preg_replace('/[^a-z0-9]+/', '-', strtolower($catName)); ?>
        <div class="menu-section" data-section="<?= $sectionId ?>" id="section-<?= $sectionId ?>">
          <div class="menu-section-divider">
            <h3><?= getCategoryEmoji($catName) ?> <?= e($catName) ?> <span class="text-gray-400 font-medium text-sm"><?= count($items) ?> items</span></h3>
          </div>
          <div class="space-y-3 mt-3">
            <?php foreach ($items as $item): ?>
            <?php $itemImg = getFoodImage($item['image'], $item['name'], $catName); ?>
            <div class="food-card menu-item"
                 data-name="<?= strtolower(htmlspecialchars($item['name'], ENT_QUOTES)) ?>"
                 data-veg="<?= $item['is_veg'] ? '1' : '0' ?>"
                 data-id="<?= (int)$item['id'] ?>">
              <div class="food-card-content">
                <div class="flex items-center gap-1.5 mb-1.5">
                  <div class="veg-dot <?= $item['is_veg'] ? 'veg' : 'nonveg' ?>"></div>
                  <?php if ($item['is_special']): ?><span class="text-[10px] bg-[#ffdbd0] text-[#a83300] font-black px-2 py-0.5 rounded uppercase tracking-wide">Chef's Special</span><?php endif; ?>
                  <?php if ($item['is_popular']): ?><span class="text-[10px] bg-amber-100 text-amber-700 font-black px-2 py-0.5 rounded uppercase tracking-wide">Bestseller</span><?php endif; ?>
                </div>
                <h3 class="food-card-name"><?= e($item['name']) ?></h3>
                <?php if ($item['description']): ?>
                <p class="food-card-desc"><?= e($item['description']) ?></p>
                <?php endif; ?>
                <div class="flex items-end justify-between mt-3">
                  <span class="food-card-price"><?= formatPrice($item['price']) ?></span>
                  <div class="add-btn-wrap" id="wrap-<?= $item['id'] ?>">
                    <button onclick="cartAdd(<?= $item['id'] ?>, <?= $restaurant['id'] ?>, '<?= e($restaurant['slug']) ?>')"
                            class="add-to-cart-btn">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                      ADD
                    </button>
                  </div>
                </div>
              </div>
              <div class="food-card-img-wrap">
                <img src="<?= e($itemImg) ?>" alt="<?= e($item['name']) ?>" class="food-card-img" loading="lazy">
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Empty State -->
        <div id="menu-empty" class="hidden py-16 text-center">
          <div class="text-4xl mb-3">🔍</div>
          <h3 class="text-lg font-bold text-[#1b1c1c]">No items found</h3>
          <p class="text-gray-500 text-sm mt-1">Try a different search term or remove the veg filter.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Customer Reviews & Feedback Section ────────────────── -->
  <div class="max-w-[1280px] mx-auto px-4 md:px-8 lg:px-10 mt-10 mb-12">
    <div class="bg-white rounded-2xl md:rounded-3xl border border-[#ece9e6] p-6 md:p-8 shadow-sm flex flex-col gap-6 font-sans">
      <div class="flex justify-between items-center border-b border-gray-100 pb-4">
        <div>
          <h3 class="text-lg md:text-xl font-black text-[#1b1c1c]">Customer Reviews &amp; Feedback</h3>
          <p class="text-xs text-gray-500 mt-1 font-semibold">Verified orders feedback and food ratings from real customers</p>
        </div>
        <div class="text-right">
          <p class="text-2xl font-black text-green-600 flex items-center gap-1">★ <?= number_format((float)($restaurant['rating'] ?? 0), 1) ?></p>
          <span class="text-[10px] text-gray-400 font-bold block mt-0.5"><?= number_format((int)($restaurant['rating_count'] ?? 0)) ?> Verified Reviews</span>
        </div>
      </div>

      <?php if (empty($restaurantReviews)): ?>
      <div class="text-center py-10 text-gray-400 font-bold text-sm">
        🍳 No customer reviews left for this kitchen yet. Be the first to order and review!
      </div>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($restaurantReviews as $rev): ?>
        <div class="bg-gray-50 rounded-2xl border border-gray-100 p-4 flex flex-col gap-1.5 text-xs font-semibold text-gray-700">
          <div class="flex justify-between items-center font-extrabold">
            <span class="text-gray-900"><?= e($rev['customer_name']) ?></span>
            <span class="text-amber-500 font-bold">★ <?= number_format((float)($rev['restaurant_rating'] ?? 0), 1) ?> rating</span>
          </div>
          <p class="text-gray-600 leading-normal">"<?= e($rev['review_text']) ?>"</p>
          <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block mt-1"><?= date('M j, Y — g:i A', strtotime($rev['created_at'])) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- Mobile Category Scroll Bar -->
<?php if (count($categories) > 1): ?>
<div class="lg:hidden fixed bottom-14 left-0 right-0 bg-white border-t border-[#ece9e6] z-30 px-4 py-2 flex gap-2 overflow-x-auto scrollbar-none" id="mobile-cat-bar">
  <?php if (!empty($recommended)): ?>
  <button onclick="scrollToSection('recommended')" class="category-nav-item active whitespace-nowrap text-xs py-1.5 px-3" data-section="recommended">
    ⭐ Recommended
  </button>
  <?php endif; ?>
  <?php foreach ($categories as $cat): ?>
  <?php $safeName = preg_replace('/[^a-z0-9]+/', '-', strtolower($cat['name'])); ?>
  <button onclick="scrollToSection('<?= $safeName ?>')" class="category-nav-item whitespace-nowrap text-xs py-1.5 px-3" data-section="<?= $safeName ?>">
    <?= getCategoryEmoji($cat['name']) ?> <?= e($cat['name']) ?>
  </button>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script src="<?= BASE_URL ?>/assets/js/cart.js" defer></script>
<script>
// ── Cart quantities state ──────────────────────────────────────
const cartQty = {};

async function cartAdd(itemId, restaurantId, restaurantSlug) {
  const wrap = document.getElementById('wrap-' + itemId);
  if (!wrap) return;

  // Show loading state
  const origHtml = wrap.innerHTML;
  wrap.innerHTML = `<div class="flex items-center justify-center w-24 h-[38px]"><div class="spinner"></div></div>`;

  try {
    const res = await fetch(`${window.ZESTO_BASE}/api/cart/add.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
      body: JSON.stringify({ menu_item_id: itemId, restaurant_id: restaurantId })
    });
    const data = await res.json();

    if (data.success) {
      cartQty[itemId] = (cartQty[itemId] || 0) + 1;
      renderStepper(itemId, restaurantId, restaurantSlug);
      updateCartBadge(data.cart_count);
      Zesto.toast('🛒 Added to cart!', 'cart');
    } else {
      wrap.innerHTML = origHtml;
      Zesto.toast(data.message || 'Could not add item.', 'error');
    }
  } catch(e) {
    wrap.innerHTML = origHtml;
    Zesto.toast('Network error.', 'error');
  }
}

function renderStepper(itemId, restaurantId, restaurantSlug) {
  const wrap = document.getElementById('wrap-' + itemId);
  if (!wrap) return;
  const qty = cartQty[itemId] || 1;
  wrap.innerHTML = `
    <div class="qty-stepper">
      <button class="qty-stepper-btn" onclick="cartDecrement(${itemId},${restaurantId},'${restaurantSlug}')">−</button>
      <span class="qty-stepper-count">${qty}</span>
      <button class="qty-stepper-btn" onclick="cartIncrement(${itemId},${restaurantId},'${restaurantSlug}')">+</button>
    </div>`;
}

async function cartIncrement(itemId, restaurantId, restaurantSlug) {
  try {
    const res = await fetch(`${window.ZESTO_BASE}/api/cart/add.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
      body: JSON.stringify({ menu_item_id: itemId, restaurant_id: restaurantId, delta: 1 })
    });
    const data = await res.json();
    if (data.success) {
      cartQty[itemId] = (cartQty[itemId] || 1) + 1;
      renderStepper(itemId, restaurantId, restaurantSlug);
      updateCartBadge(data.cart_count);
    }
  } catch(e) {}
}

async function cartDecrement(itemId, restaurantId, restaurantSlug) {
  cartQty[itemId] = Math.max(0, (cartQty[itemId] || 1) - 1);
  if (cartQty[itemId] === 0) {
    // Remove from cart
    try {
      const res = await fetch(`${window.ZESTO_BASE}/api/cart/remove.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
        body: JSON.stringify({ menu_item_id: itemId })
      });
      const data = await res.json();
      if (data.success) {
        updateCartBadge(data.cart_count);
      }
    } catch(e) {}
    // Restore ADD button
    const wrap = document.getElementById('wrap-' + itemId);
    if (wrap) {
      wrap.innerHTML = `<button onclick="cartAdd(${itemId}, ${restaurantId}, '${restaurantSlug}')" class="add-to-cart-btn">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        ADD
      </button>`;
    }
  } else {
    renderStepper(itemId, restaurantId, restaurantSlug);
    // Update quantity via API
    try {
      await fetch(`${window.ZESTO_BASE}/api/cart/update.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
        body: JSON.stringify({ menu_item_id: itemId, delta: -1 })
      });
    } catch(e) {}
  }
}

// ── Menu search & veg filter ───────────────────────────────────
let vegOnly = false;
let searchQ  = '';

document.getElementById('menu-search')?.addEventListener('input', function() {
  searchQ = this.value.toLowerCase().trim();
  applyFilters();
});

const vegToggle = document.getElementById('veg-toggle');
const vegTrack  = document.getElementById('veg-track');
const vegThumb  = document.getElementById('veg-thumb');
vegToggle?.addEventListener('change', function() {
  vegOnly = this.checked;
  vegTrack.classList.toggle('bg-green-500', vegOnly);
  vegTrack.classList.toggle('bg-gray-200', !vegOnly);
  vegThumb.style.transform = vegOnly ? 'translateX(20px)' : '';
  applyFilters();
});

function applyFilters() {
  const items = document.querySelectorAll('.menu-item');
  let visibleCount = 0;
  items.forEach(el => {
    const name    = el.dataset.name || '';
    const isVeg   = el.dataset.veg === '1';
    const matchSr = !searchQ || name.includes(searchQ);
    const matchVg = !vegOnly || isVeg;
    const visible = matchSr && matchVg;
    el.style.display = visible ? '' : 'none';
    if (visible) visibleCount++;
  });
  // Show/hide sections
  document.querySelectorAll('.menu-section').forEach(sec => {
    const visItems = sec.querySelectorAll('.menu-item:not([style*="none"])');
    sec.style.display = visItems.length > 0 ? '' : 'none';
  });
  document.getElementById('menu-empty').classList.toggle('hidden', visibleCount > 0);
}

// ── Category nav active state (scroll spy) ─────────────────────
function scrollToSection(sectionId) {
  const el = document.getElementById('section-' + sectionId);
  if (el) {
    const offset = 100;
    const y = el.getBoundingClientRect().top + window.scrollY - offset;
    window.scrollTo({ top: y, behavior: 'smooth' });
  }
  setActiveNav(sectionId);
}

function setActiveNav(sectionId) {
  document.querySelectorAll('.category-nav-item').forEach(el => {
    el.classList.toggle('active', el.dataset.section === sectionId);
  });
}

// Intersection observer for scroll-spy
const sections = document.querySelectorAll('.menu-section');
const observer = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      setActiveNav(entry.target.dataset.section);
    }
  });
}, { rootMargin: '-30% 0px -50% 0px' });
sections.forEach(s => observer.observe(s));
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
