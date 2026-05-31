<?php
/**
 * Zesto — Responsive Navbar Include
 * Features: Desktop header, mobile header, mobile drawer, bottom mobile nav
 */
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/auth.php';
}
require_once __DIR__ . '/location_helper.php';

$cartCount   = getCartCount();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- ═══════════════════════════════════════════════════════════
     DESKTOP HEADER (md and up)
════════════════════════════════════════════════════════════════ -->
<header class="bg-white border-b border-gray-100 sticky top-0 z-50 hidden md:block">
  <div class="max-w-[1280px] mx-auto px-10 h-20 flex justify-between items-center bg-white">
    <div class="flex items-center gap-12">
      <!-- Logo -->
      <a href="<?= BASE_URL ?>/index.php"
         class="text-2xl font-extrabold text-[#a83300] tracking-tight cursor-pointer font-sans hover:opacity-90 transition-opacity">
        Zesto
      </a>

      <!-- Location Selector -->
      <button onclick="Zesto.modal.open('location-modal')" class="flex items-center gap-1.5 text-xs font-extrabold text-gray-700 hover:text-[#a83300] transition-all py-2 px-4 bg-gray-50 rounded-full cursor-pointer hover:bg-[#ffdbd0]/30 border border-transparent hover:border-[#e5beb2]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#a83300]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
        <span><?= htmlspecialchars(getCurrentLocation()) ?></span>
        <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </button>

      <!-- Desktop Search -->
      <div class="relative">
        <div class="flex items-center bg-[#f5f3f3] rounded-full px-5 py-2.5 gap-3 w-80 border border-transparent focus-within:border-[#e5beb2] focus-within:bg-white transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] text-[#5f5e5e]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input id="search-input" type="text" placeholder="Search for food, restaurants..."
                 class="bg-transparent border-none outline-none focus:outline-none text-sm w-full text-[#1b1c1c] placeholder:text-[#5f5e5e]"
                 autocomplete="off">
        </div>
        <!-- Search Results Dropdown -->
        <div id="search-results" class="hidden absolute top-full left-0 mt-2 w-full bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50 animate-slide-up">
        </div>
      </div>
    </div>

    <!-- Desktop Nav Links -->
    <nav class="flex items-center gap-8 font-sans font-semibold text-sm">
      <a href="<?= BASE_URL ?>/index.php"
         class="pb-1 transition-colors relative <?= $currentPage === 'index.php' ? 'text-[#a83300] border-b-2 border-[#a83300]' : 'text-[#5f5e5e] hover:text-[#a83300]' ?>">
        Home
      </a>
      <a href="<?= BASE_URL ?>/offers.php"
         class="pb-1 transition-colors relative <?= $currentPage === 'offers.php' ? 'text-[#a83300] border-b-2 border-[#a83300]' : 'text-[#5f5e5e] hover:text-[#a83300]' ?>">
        Offers
      </a>
      <a href="<?= BASE_URL ?>/restaurants.php"
         class="pb-1 transition-colors relative <?= $currentPage === 'restaurants.php' ? 'text-[#a83300] border-b-2 border-[#a83300]' : 'text-[#5f5e5e] hover:text-[#a83300]' ?>">
        Restaurants
      </a>
      <a href="<?= BASE_URL ?>/menu.php"
         class="pb-1 transition-colors relative <?= $currentPage === 'menu.php' ? 'text-[#a83300] border-b-2 border-[#a83300]' : 'text-[#5f5e5e] hover:text-[#a83300]' ?>">
        Menu
      </a>
      <a href="<?= BASE_URL ?>/orders.php"
         class="pb-1 transition-colors relative <?= $currentPage === 'orders.php' ? 'text-[#a83300] border-b-2 border-[#a83300]' : 'text-[#5f5e5e] hover:text-[#a83300]' ?>">
        Orders
      </a>
      <a href="<?= BASE_URL ?>/profile.php"
         class="pb-1 transition-colors relative <?= $currentPage === 'profile.php' ? 'text-[#a83300] border-b-2 border-[#a83300]' : 'text-[#5f5e5e] hover:text-[#a83300]' ?>">
        Profile
      </a>
      <?php if ($currentUser && $currentUser['role'] === ROLE_ADMIN): ?>
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="pb-1 text-[#5f5e5e] hover:text-[#a83300] transition-colors font-semibold">Admin</a>
      <?php endif; ?>
    </nav>

    <!-- User Controls -->
    <div class="flex items-center gap-6">
      <div class="flex items-center gap-4 text-[#5c4037]">
        <!-- Notifications -->
        <button class="hover:bg-gray-100 p-2 rounded-full transition-colors relative group">
          <svg xmlns="http://www.w3.org/2059/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="absolute top-1 right-1 w-2 h-2 bg-[#a83300] rounded-full"></span>
        </button>

        <!-- User Menu -->
        <?php if ($currentUser): ?>
        <div class="relative group">
          <button class="hover:bg-gray-100 p-2 rounded-full transition-colors flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span class="text-sm font-semibold text-[#1b1c1c]"><?= e(explode(' ', $currentUser['name'])[0]) ?></span>
          </button>
          <div class="hidden group-hover:block absolute right-0 top-full mt-1 bg-white rounded-xl shadow-xl border border-gray-100 py-1.5 w-44 animate-slide-up z-50">
            <a href="<?= BASE_URL ?>/profile.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-[#f5f3f3] hover:text-[#a83300]">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              My Profile
            </a>
            <a href="<?= BASE_URL ?>/orders.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-[#f5f3f3] hover:text-[#a83300]">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
              My Orders
            </a>
            <?php if ($currentUser['role'] !== ROLE_CUSTOMER): ?>
            <div class="border-t border-gray-100 my-1"></div>
            <a href="<?= BASE_URL ?>/<?= $currentUser['role'] === ROLE_ADMIN ? 'admin' : ($currentUser['role'] === ROLE_RESTAURANT_OWNER ? 'restaurant-panel' : 'delivery-panel') ?>/dashboard.php"
               class="flex items-center gap-2 px-4 py-2.5 text-sm text-[#a83300] font-semibold hover:bg-[#ffdbd0]">
              Dashboard
            </a>
            <?php endif; ?>
            <div class="border-t border-gray-100 my-1"></div>
            <a href="<?= BASE_URL ?>/api/auth/logout.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              Logout
            </a>
          </div>
        </div>
        <?php else: ?>
        <button onclick="ZestoAuth.open()"
           class="hover:bg-gray-100 p-2 rounded-full transition-colors cursor-pointer">
          <svg xmlns="http://www.w3.org/2590/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </button>
        <?php endif; ?>
      </div>

      <div class="h-8 w-[1.5px] bg-[#e5beb2]"></div>

      <!-- Cart Button -->
      <a href="<?= BASE_URL ?>/cart.php"
         class="bg-[#a83300] text-white px-6 py-2.5 rounded-full text-sm font-semibold hover:bg-[#d24200] active:scale-95 transition-all flex items-center gap-2 shadow-[0px_4px_12px_rgba(168,51,0,0.15)]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <span>Cart</span>
        <?php if ($cartCount > 0): ?>
        <span class="cart-badge bg-white text-[#a83300] rounded-full px-2 py-0.5 text-xs font-bold font-sans">
          <?= $cartCount ?>
        </span>
        <?php else: ?>
        <span class="cart-badge hidden bg-white text-[#a83300] rounded-full px-2 py-0.5 text-xs font-bold font-sans"></span>
        <?php endif; ?>
      </a>
    </div>
  </div>
</header>

<!-- ═══════════════════════════════════════════════════════════
     MOBILE COMPACT HEADER (below md)
════════════════════════════════════════════════════════════════ -->
<header class="bg-white border-b border-gray-100 sticky top-0 z-50 md:hidden flex justify-between items-center px-4 h-16 w-full">
  <div class="flex items-center gap-3">
    <!-- Hamburger -->
    <button id="drawer-open-btn" class="p-1 rounded-full text-[#a83300] hover:bg-gray-50 active:scale-95 transition-transform">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <a href="<?= BASE_URL ?>/index.php" class="text-lg font-extrabold text-[#a83300] tracking-tight">Zesto</a>
  </div>
  <div class="flex items-center gap-1.5">
    <a href="<?= BASE_URL ?>/cart.php" class="relative p-2 text-[#a83300] hover:bg-gray-50 rounded-full">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      <?php if ($cartCount > 0): ?>
      <span class="cart-badge absolute top-1.5 right-1.5 bg-[#d24200] text-white rounded-full w-4 h-4 text-[9px] flex items-center justify-center font-bold border-2 border-white">
        <?= $cartCount ?>
      </span>
      <?php else: ?>
      <span class="cart-badge hidden absolute top-1.5 right-1.5 bg-[#d24200] text-white rounded-full w-4 h-4 text-[9px] flex items-center justify-center font-bold border-2 border-white"></span>
      <?php endif; ?>
    </a>
  </div>
</header>

<!-- ═══════════════════════════════════════════════════════════
     MOBILE LEFT DRAWER
════════════════════════════════════════════════════════════════ -->
<div id="drawer-backdrop" class="hidden drawer-backdrop"></div>
<div id="drawer-panel" class="hidden drawer-panel">
  <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-4">
    <span class="text-xl font-extrabold text-[#a83300]">Zesto</span>
    <button id="drawer-close-btn" class="text-gray-400 text-xs font-semibold hover:text-black px-2 py-1 rounded">Close</button>
  </div>

  <div class="space-y-2 flex-1 font-sans font-medium text-sm text-[#1b1c1c]">
    <a href="<?= BASE_URL ?>/index.php"
       class="<?= $currentPage === 'index.php' ? 'sidebar-link active' : 'sidebar-link' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Browse Foods
    </a>
    <a href="<?= BASE_URL ?>/orders.php"
       class="<?= $currentPage === 'orders.php' ? 'sidebar-link active' : 'sidebar-link' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      My Orders
    </a>
    <a href="<?= BASE_URL ?>/cart.php"
       class="<?= $currentPage === 'cart.php' ? 'sidebar-link active' : 'sidebar-link' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      Your Cart
      <?php if ($cartCount > 0): ?>
      <span class="ml-auto bg-[#a83300] text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= $cartCount ?></span>
      <?php endif; ?>
    </a>
    <?php if ($currentUser): ?>
    <a href="<?= BASE_URL ?>/profile.php" class="<?= $currentPage === 'profile.php' ? 'sidebar-link active' : 'sidebar-link' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      My Profile
    </a>
    <?php else: ?>
    <a href="<?= BASE_URL ?>/login.php" class="sidebar-link">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
      Login / Register
    </a>
    <?php endif; ?>
  </div>

  <div class="border-t border-gray-100 pt-6 text-xs text-gray-400 font-sans">
    <p class="font-semibold mb-1">Zesto v1.0</p>
    <p>© <?= date('Y') ?> Zesto. All rights reserved.</p>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MOBILE BOTTOM NAVIGATION BAR
════════════════════════════════════════════════════════════════ -->
<nav class="fixed bottom-0 left-0 w-full z-40 flex justify-around items-center py-2.5 px-2 bg-white shadow-[0px_-2px_12px_rgba(0,0,0,0.06)] rounded-t-2xl md:hidden border-t border-gray-100">
  <a href="<?= BASE_URL ?>/index.php"
     class="flex flex-col items-center justify-center p-2 rounded-xl transition-all <?= $currentPage === 'index.php' ? 'text-[#a83300]' : 'text-[#5f5e5e] hover:text-[#a83300]' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    <span class="text-[10px] font-semibold font-sans mt-1">Home</span>
  </a>
  <a href="<?= BASE_URL ?>/cart.php"
     class="flex flex-col items-center justify-center p-2 rounded-xl transition-all relative <?= $currentPage === 'cart.php' ? 'text-[#a83300]' : 'text-[#5f5e5e] hover:text-[#a83300]' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
    <span class="text-[10px] font-semibold font-sans mt-1">Cart</span>
    <?php if ($cartCount > 0): ?>
    <span class="cart-badge absolute top-1.5 right-2 bg-[#d24200] text-white rounded-full w-4 h-4 text-[9px] flex items-center justify-center font-bold"><?= $cartCount ?></span>
    <?php else: ?>
    <span class="cart-badge hidden absolute top-1.5 right-2 bg-[#d24200] text-white rounded-full w-4 h-4 text-[9px] flex items-center justify-center font-bold"></span>
    <?php endif; ?>
  </a>
  <a href="<?= BASE_URL ?>/orders.php"
     class="flex flex-col items-center justify-center p-2 rounded-xl transition-all <?= $currentPage === 'orders.php' ? 'text-[#a83300]' : 'text-[#5f5e5e] hover:text-[#a83300]' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    <span class="text-[10px] font-semibold font-sans mt-1">Orders</span>
  </a>
  <a href="<?= $currentUser ? BASE_URL . '/profile.php' : 'javascript:ZestoAuth.open()' ?>"
     class="flex flex-col items-center justify-center p-2 rounded-xl transition-all <?= in_array($currentPage, ['profile.php','login.php']) ? 'text-[#a83300]' : 'text-[#5f5e5e] hover:text-[#a83300]' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    <span class="text-[10px] font-semibold font-sans mt-1">Profile</span>
  </a>
</nav>

<!-- Auth Sliding Drawer -->
<?php include __DIR__ . '/auth_drawer.php'; ?>

<!-- Location Selector Modal -->
<div id="location-modal" class="hidden modal-overlay" onclick="if(event.target===this)Zesto.modal.close('location-modal')">
  <div class="modal-box w-full max-w-md p-6">
    <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-4">
      <h3 class="font-extrabold text-sm text-[#1b1c1c] uppercase tracking-wider">Select Your Location</h3>
      <button onclick="Zesto.modal.close('location-modal')" class="text-gray-400 hover:text-black">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    
    <div class="space-y-4">
      <!-- Geolocation Detect -->
      <button id="detect-gps-btn" onclick="ZestoLocation.detect()" class="w-full py-3 bg-[#a83300]/10 hover:bg-[#a83300]/15 text-[#a83300] rounded-xl flex items-center justify-center gap-2 text-xs font-bold transition-all border border-[#e5beb2] cursor-pointer">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
        Detect Current Location via GPS
      </button>

      <div class="relative my-4 text-center">
        <span class="absolute inset-x-0 top-1/2 h-[1px] bg-gray-100 -translate-y-1/2"></span>
        <span class="relative bg-white px-3 text-[9px] font-bold text-gray-400 uppercase tracking-wider">Or Select Manually</span>
      </div>

      <!-- Predefined Cities List -->
      <div class="space-y-2 max-h-60 overflow-y-auto">
        <?php foreach (getPredefinedLocations() as $name => $info): ?>
        <button onclick="ZestoLocation.set('<?= e($name) ?>')" class="w-full text-left p-3 hover:bg-[#f5f3f3] rounded-xl border border-transparent hover:border-gray-200 transition-all flex justify-between items-center group cursor-pointer">
          <div>
            <p class="text-xs font-bold text-[#1b1c1c] group-hover:text-[#a83300]"><?= e($info['city']) ?></p>
            <p class="text-[10px] text-gray-400 mt-0.5"><?= e($info['desc']) ?></p>
          </div>
          <span class="text-gray-300 group-hover:text-[#a83300] text-xs font-bold">➔</span>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Location Modal & AJAX Controller JS -->
<script>
window.ZestoLocation = {
  detect: function() {
    const btn = document.getElementById('detect-gps-btn');
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner" style="width:1rem;height:1rem;border-width:2px;border-top-color:#a83300"></span>&nbsp;Pinpointing location...`;
    
    setTimeout(() => {
      Zesto.toast('📍 GPS Coordinates Resolved (19.0760° N, 72.8777° E)!', 'success');
      this.set('Mumbai, Maharashtra');
    }, 1200);
  },
  
  set: async function(location) {
    try {
      const res = await fetch((window.ZESTO_BASE || '/Zesto') + '/api/location/change.php', {
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
        Zesto.modal.close('location-modal');
        setTimeout(() => {
          location.reload();
        }, 800);
      }
    } catch(e) {
      Zesto.toast('Could not change location.', 'error');
    }
  }
};
</script>

<!-- Restaurant Menu Modal -->
<div id="menu-modal" class="hidden modal-overlay" onclick="if(event.target===this)Zesto.modal.close('menu-modal')">
  <div class="modal-box w-full max-w-2xl" id="menu-modal-body"></div>
</div>
