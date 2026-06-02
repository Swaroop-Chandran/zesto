<?php
/**
 * Zesto — Responsive Navbar Include (Zesto UI)
 */
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/auth.php';
}
require_once __DIR__ . '/location_helper.php';

$cartCount   = getCartCount();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
$searchQuery = $_GET['search'] ?? '';

$currentLoc = htmlspecialchars(getCurrentLocation());
?>

<header class="sticky top-0 z-40 w-full bg-black/45 backdrop-blur-xl border-b border-white/5 px-4 sm:px-10 py-3.5 flex flex-wrap items-center justify-between gap-4 shadow-lg shadow-black/30">
  <!-- Brand & Location -->
  <div class="flex items-center gap-6">
    <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-3 cursor-pointer hover:opacity-90 no-underline">
      <div class="relative">
        <i data-lucide="moon" class="w-5 h-5 text-zesto-orange fill-zesto-orange"></i>
        <div class="absolute -top-1 -right-1 w-1.5 h-1.5 rounded-full bg-zesto-orange fire-glow"></div>
      </div>
      <div class="flex flex-col text-left">
        <span class="text-xl sm:text-2xl font-bold tracking-tighter text-zesto-orange uppercase" style="font-family: 'Georgia', serif; color: #fbbf24;">ZESTO NIGHTS</span>
        <span class="text-[9px] tracking-[0.25em] -mt-0.5 uppercase opacity-60 font-sans font-extrabold text-white">KERALA NIGHT FOOD CULTURE</span>
      </div>
    </a>

    <!-- Location Dropdown -->
    <div class="relative hidden sm:block">
      <button onclick="toggleLocDropdown()" class="flex items-center gap-2 px-4 py-2 rounded-full bg-zinc-900/80 border border-white/5 text-xs font-bold text-white hover:bg-zinc-800/80 transition cursor-pointer border-none shadow-md shadow-black/30">
        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-zesto-orange"></i>
        <span><?= $currentLoc ?></span>
        <span class="text-[8px] text-white/55">▼</span>
      </button>
      
      <div id="loc-dropdown" class="hidden absolute left-0 mt-2 w-48 rounded-lg glass-panel-heavy p-1 shadow-2xl z-50 animate-fade-in">
        <?php foreach (getPredefinedLocations() as $name => $info): ?>
        <button onclick="ZestoLocation.set('<?= e($name) ?>')" class="w-full text-left px-3 py-2 rounded-md text-xs font-semibold transition text-white/80 hover:bg-white/5 hover:text-zesto-orange cursor-pointer border-none bg-transparent">
          <?= e($info['city']) ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Global Search Bar -->
  <div class="flex-1 max-w-xl min-w-[240px]">
    <form action="<?= BASE_URL ?>/menu.php" method="GET" class="relative w-full m-0 p-0">
      <span class="absolute inset-y-0 left-0 flex items-center pl-3">
        <i data-lucide="search" class="h-4 w-4 text-white/40"></i>
      </span>
      <input
        type="text"
        name="search"
        value="<?= e($searchQuery) ?>"
        placeholder="Search for Porotta, Beef Roast, Thattukada..."
        class="w-full bg-white/5 border border-white/10 text-white rounded-lg pl-9 pr-10 py-1.5 text-sm focus:outline-none focus:border-zesto-orange focus:ring-1 focus:ring-zesto-orange transition-all placeholder:text-white/30"
      />
      <button type="submit" class="absolute inset-y-0 right-0 pr-3 flex items-center text-white/40 hover:text-white/80 cursor-pointer bg-transparent border-none">
        <i data-lucide="filter" class="w-4 h-4"></i>
      </button>
    </form>
  </div>

  <!-- Secondary Nav Controls -->
  <nav class="flex items-center gap-6">
    <a href="<?= BASE_URL ?>/offers.php" class="hidden md:flex items-center gap-1.5 text-sm font-medium text-white/80 hover:text-zesto-orange transition-all cursor-pointer no-underline">
      <i data-lucide="gift" class="w-4 h-4 text-zesto-amber"></i>
      <span>Offers</span>
    </a>
    <button onclick="Zesto.toast('Welcome to Zesto Nights Help Desk! Live late-night chat is fully online to serve your cravings.', 'info')" class="hidden md:flex items-center gap-1.5 text-sm font-medium text-white/80 hover:text-zesto-orange transition-all cursor-pointer bg-transparent border-none">
      <i data-lucide="help-circle" class="w-4 h-4 text-white/60"></i>
      <span>Help</span>
    </button>

    <?php if ($currentUser): ?>
      <div class="relative group">
        <a href="<?= BASE_URL ?>/profile.php" class="flex items-center gap-1.5 text-sm font-semibold text-white/95 hover:text-zesto-orange transition-all px-3 py-1.5 rounded-full <?= $currentPage === 'profile.php' ? 'bg-zesto-orange/20 text-zesto-orange border border-zesto-orange/30' : '' ?> no-underline">
          <i data-lucide="user" class="w-4 h-4 text-zesto-amber"></i>
          <span><?= e(explode(' ', $currentUser['name'])[0]) ?></span>
        </a>
        <div class="hidden group-hover:block absolute right-0 top-full glass-panel-heavy rounded-xl shadow-xl py-1.5 w-44 animate-slide-up z-50">
          <a href="<?= BASE_URL ?>/profile.php" class="block px-4 py-2.5 text-sm text-white/80 hover:bg-white/5 hover:text-zesto-orange no-underline">My Profile</a>
          <a href="<?= BASE_URL ?>/orders.php" class="block px-4 py-2.5 text-sm text-white/80 hover:bg-white/5 hover:text-zesto-orange no-underline">My Orders</a>
          <?php if ($currentUser['role'] !== ROLE_CUSTOMER): ?>
          <a href="<?= BASE_URL ?>/<?= $currentUser['role'] === ROLE_ADMIN ? 'admin' : ($currentUser['role'] === ROLE_RESTAURANT_OWNER ? 'restaurant-panel' : 'delivery-panel') ?>/dashboard.php" class="block px-4 py-2.5 text-sm text-zesto-orange font-bold hover:bg-white/5 no-underline">Dashboard</a>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>/api/auth/logout.php" class="block px-4 py-2.5 text-sm text-red-500 hover:bg-red-500/10 no-underline">Logout</a>
        </div>
      </div>
    <?php else: ?>
      <button onclick="ZestoAuth.open()" class="flex items-center gap-1.5 text-sm font-semibold text-white/90 hover:text-zesto-orange transition-all px-3 py-1.5 rounded-full bg-transparent border-none cursor-pointer">
        <i data-lucide="user" class="w-4 h-4 text-white/60"></i>
        <span>Login</span>
      </button>
    <?php endif; ?>

    <!-- Floating Cart Button -->
    <a href="<?= BASE_URL ?>/cart.php" class="relative flex items-center gap-2 bg-gradient-to-r from-[#fbbf24] to-[#f59e0b] text-zinc-950 hover:opacity-90 active:scale-95 px-5 py-2.5 rounded-full font-black text-xs uppercase tracking-wider transition-all shadow-lg shadow-amber-500/10 no-underline cursor-pointer">
      <i data-lucide="shopping-cart" class="w-3.5 h-3.5 text-zinc-950 stroke-[3]"></i>
      <span>Cart</span>
      <?php if ($cartCount > 0): ?>
        <div class="cart-badge absolute -top-1.5 -right-1.5 bg-white text-zinc-950 min-w-[20px] h-5 rounded-full flex items-center justify-center text-[10px] font-black px-1 border border-zinc-950 shadow-md">
          <?= $cartCount ?>
        </div>
      <?php else: ?>
        <div class="cart-badge hidden absolute -top-1.5 -right-1.5 bg-white text-zinc-950 min-w-[20px] h-5 rounded-full flex items-center justify-center text-[10px] font-black px-1 border border-zinc-950 shadow-md">
        </div>
      <?php endif; ?>
    </a>
  </nav>
</header>

<!-- Mobile Bottom Navigation Bar -->
<nav class="fixed bottom-0 left-0 w-full z-40 flex justify-around items-center py-2.5 px-2 glass-panel-heavy rounded-t-2xl md:hidden border-t border-white/10">
  <a href="<?= BASE_URL ?>/index.php" class="flex flex-col items-center justify-center p-2 rounded-xl transition-all <?= $currentPage === 'index.php' ? 'text-zesto-orange' : 'text-white/60 hover:text-zesto-orange' ?> no-underline">
    <i data-lucide="home" class="h-5 w-5"></i>
  </a>
  <a href="<?= BASE_URL ?>/cart.php" class="flex flex-col items-center justify-center p-2 rounded-xl transition-all relative <?= $currentPage === 'cart.php' ? 'text-zesto-orange' : 'text-white/60 hover:text-zesto-orange' ?> no-underline">
    <i data-lucide="shopping-cart" class="h-5 w-5"></i>
    <?php if ($cartCount > 0): ?>
    <span class="cart-badge absolute top-1 right-2 bg-zesto-orange text-white rounded-full w-4 h-4 text-[9px] flex items-center justify-center font-bold"><?= $cartCount ?></span>
    <?php else: ?>
    <span class="cart-badge hidden absolute top-1 right-2 bg-zesto-orange text-white rounded-full w-4 h-4 text-[9px] flex items-center justify-center font-bold"></span>
    <?php endif; ?>
  </a>
  <a href="<?= BASE_URL ?>/orders.php" class="flex flex-col items-center justify-center p-2 rounded-xl transition-all <?= $currentPage === 'orders.php' ? 'text-zesto-orange' : 'text-white/60 hover:text-zesto-orange' ?> no-underline">
    <i data-lucide="list" class="h-5 w-5"></i>
  </a>
  <a href="<?= $currentUser ? BASE_URL . '/profile.php' : 'javascript:ZestoAuth.open()' ?>" class="flex flex-col items-center justify-center p-2 rounded-xl transition-all <?= in_array($currentPage, ['profile.php','login.php']) ? 'text-zesto-orange' : 'text-white/60 hover:text-zesto-orange' ?> no-underline">
    <i data-lucide="user" class="h-5 w-5"></i>
  </a>
</nav>

<!-- Auth Drawer Included -->
<?php include __DIR__ . '/auth_drawer.php'; ?>

<!-- Inline JS for Dropdown & Lucide Icons Initialization -->
<script>
  function toggleLocDropdown() {
    const el = document.getElementById('loc-dropdown');
    el.classList.toggle('hidden');
  }
  // Close dropdown on outside click
  document.addEventListener('click', function(e) {
    const btn = document.querySelector('button[onclick="toggleLocDropdown()"]');
    const drop = document.getElementById('loc-dropdown');
    if(btn && !btn.contains(e.target) && drop && !drop.contains(e.target)) {
      drop.classList.add('hidden');
    }
  });

  // Initialize Lucide Icons
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // Location Change logic
  window.ZestoLocation = {
    set: async function(location) {
      try {
        const res = await fetch((window.ZESTO_BASE || '') + '/api/location/change.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
          },
          body: JSON.stringify({ location })
        });
        const data = await res.json();
        if (data.success) {
          Zesto.toast(`📍 Location set to ${data.city}!`, 'success');
          setTimeout(() => window.location.reload(), 800);
        }
      } catch(e) {
        Zesto.toast('Could not change location.', 'error');
      }
    }
  };
</script>
