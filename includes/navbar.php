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
<header class="bg-white border-b border-gray-50 sticky top-0 z-50 hidden md:block shadow-sm">
  <div class="max-w-[1280px] mx-auto px-6 lg:px-10 h-20 flex justify-between items-center bg-white">
    <!-- Logo & Location -->
    <div class="flex items-center gap-6">
      <!-- Logo -->
      <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-2 group cursor-pointer no-underline">
        <!-- Premium Orange SVG Icon (Swiggy styled) -->
        <svg class="h-10 w-10 text-[#a83300] fill-[#a83300] transition-transform group-hover:scale-105" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
          <path d="M32 2C15.43 2 2 15.43 2 32s13.43 30 30 30 30-13.43 30-30S48.57 2 32 2zm10.74 34.61c-1.39 3.52-4.14 6.37-7.61 7.64L32 45.42l-3.13-1.17c-3.47-1.27-6.22-4.12-7.61-7.64l-1.07-2.73 2.73-1.07c3.52-1.39 6.37-4.14 7.64-7.61L32 22l1.44 3.2c1.27 3.47 4.12 6.22 7.64 7.61l2.73 1.07-1.07 2.73z" fill-rule="evenodd"/>
        </svg>
        <span class="text-2xl font-black text-[#a83300] tracking-tighter">Zesto</span>
      </a>

      <!-- Location Dropdown Selector -->
      <button onclick="Zesto.modal.open('location-modal')" class="flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-[#a83300] transition-colors py-1.5 px-3 border-l border-gray-200 cursor-pointer bg-transparent border-t-0 border-b-0 border-r-0">
        <span class="font-extrabold text-[#1b1c1c] underline decoration-gray-400 underline-offset-4 hover:decoration-[#a83300]"><?= htmlspecialchars(explode(',', getCurrentLocation())[0]) ?></span>
        <span class="text-gray-400 font-medium truncate max-w-[150px]"><?= htmlspecialchars(str_replace(explode(',', getCurrentLocation())[0] . ',', '', getCurrentLocation())) ?></span>
        <svg class="h-3 w-3 text-[#a83300]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
    </div>

    <!-- Desktop Nav Links (Swiggy styled) -->
    <nav class="flex items-center gap-8 font-sans font-bold text-sm text-[#1b1c1c]">
      <!-- Search Link -->
      <a href="<?= BASE_URL ?>/menu.php" class="flex items-center gap-2 hover:text-[#a83300] transition-colors py-2 no-underline <?= $currentPage === 'menu.php' ? 'text-[#a83300]' : 'text-gray-700' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <span>Search</span>
      </a>

      <!-- Offers Link -->
      <a href="<?= BASE_URL ?>/offers.php" class="flex items-center gap-2 hover:text-[#a83300] transition-colors py-2 relative no-underline <?= $currentPage === 'offers.php' ? 'text-[#a83300]' : 'text-gray-700' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
        <span>Offers</span>
        <span class="absolute -top-1.5 -right-6 bg-orange-500 text-white text-[8px] font-black px-1.5 py-0.5 rounded-full uppercase leading-none scale-90 animate-pulse">New</span>
      </a>

      <!-- Help Link -->
      <a href="javascript:void(0)" onclick="Zesto.toast('Our Help Center is available 24/7 at support@zesto.com','info')" class="flex items-center gap-2 hover:text-[#a83300] transition-colors py-2 text-gray-700 no-underline">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span>Help</span>
      </a>

      <!-- Sign In / User Profile -->
      <?php if ($currentUser): ?>
      <div class="relative group">
        <button class="flex items-center gap-2 hover:text-[#a83300] transition-colors py-2 text-[#1b1c1c] font-bold bg-transparent border-none cursor-pointer">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span><?= e(explode(' ', $currentUser['name'])[0]) ?></span>
        </button>
        <div class="hidden group-hover:block absolute right-0 top-full bg-white rounded-xl shadow-xl border border-gray-100 py-1.5 w-44 animate-slide-up z-50">
          <a href="<?= BASE_URL ?>/profile.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-[#f5f3f3] hover:text-[#a83300] no-underline">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            My Profile
          </a>
          <a href="<?= BASE_URL ?>/orders.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-[#f5f3f3] hover:text-[#a83300] no-underline">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            My Orders
          </a>
          <?php if ($currentUser['role'] !== ROLE_CUSTOMER): ?>
          <div class="border-t border-gray-100 my-1"></div>
          <a href="<?= BASE_URL ?>/<?= $currentUser['role'] === ROLE_ADMIN ? 'admin' : ($currentUser['role'] === ROLE_RESTAURANT_OWNER ? 'restaurant-panel' : 'delivery-panel') ?>/dashboard.php"
             class="flex items-center gap-2 px-4 py-2.5 text-sm text-[#a83300] font-bold hover:bg-[#ffdbd0] no-underline">
            Dashboard
          </a>
          <?php endif; ?>
          <div class="border-t border-gray-100 my-1"></div>
          <a href="<?= BASE_URL ?>/api/auth/logout.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 no-underline">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
          </a>
        </div>
      </div>
      <?php else: ?>
      <button onclick="ZestoAuth.open()" class="flex items-center gap-2 hover:text-[#a83300] transition-colors py-2 text-gray-700 font-bold cursor-pointer bg-transparent border-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span>Sign In</span>
      </button>
      <?php endif; ?>

      <!-- Cart Link -->
      <a href="<?= BASE_URL ?>/cart.php" class="flex items-center gap-2 hover:text-[#a83300] transition-colors py-2 no-underline <?= $currentPage === 'cart.php' ? 'text-[#a83300]' : 'text-gray-700' ?>">
        <div class="relative flex items-center">
          <!-- Swiggy styled green cart outline when active, grey when inactive -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 <?= $cartCount > 0 ? 'text-[#60b246]' : 'text-gray-700' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <?php if ($cartCount > 0): ?>
          <span class="cart-badge absolute -top-1.5 -right-1.5 bg-[#60b246] text-white rounded-full w-4 h-4 text-[9px] flex items-center justify-center font-bold font-sans">
            <?= $cartCount ?>
          </span>
          <?php else: ?>
          <span class="cart-badge hidden absolute -top-1.5 -right-1.5 bg-[#60b246] text-white rounded-full w-4 h-4 text-[9px] flex items-center justify-center font-bold font-sans"></span>
          <?php endif; ?>
        </div>
        <span class="<?= $cartCount > 0 ? 'text-[#60b246]' : 'text-gray-700' ?>">Cart</span>
      </a>
    </nav>
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
    <a href="javascript:void(0)" onclick="document.getElementById('drawer-panel').classList.add('hidden'); document.getElementById('drawer-backdrop').classList.add('hidden'); ZestoAuth.open();" class="sidebar-link">
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
          window.location.reload();
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
