<?php
/**
 * Zesto — Admin/Panel Sidebar Include
 * Usage: Set $sidebarType = 'admin' | 'restaurant' | 'delivery' before including
 *        Set $activePage to the current page filename for active state
 */
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/auth.php';
}

$sidebarType = $sidebarType ?? 'admin';
$activePage  = $activePage  ?? basename($_SERVER['PHP_SELF']);
$currentUser = getCurrentUser();

$menus = [
  'admin' => [
    'title' => 'Admin Panel',
    'color' => '#a83300',
    'links' => [
      ['href' => BASE_URL . '/admin/dashboard.php',   'label' => 'Dashboard',    'file' => 'dashboard.php',   'icon' => 'grid'],
      ['href' => BASE_URL . '/admin/orders.php',      'label' => 'Orders',       'file' => 'orders.php',      'icon' => 'clipboard'],
      ['href' => BASE_URL . '/admin/restaurants.php', 'label' => 'Restaurants',  'file' => 'restaurants.php', 'icon' => 'store'],
      ['href' => BASE_URL . '/admin/customers.php',   'label' => 'Customers',    'file' => 'customers.php',   'icon' => 'users'],
      ['href' => BASE_URL . '/admin/delivery.php',    'label' => 'Delivery Partners', 'file' => 'delivery.php', 'icon' => 'truck'],
      ['href' => BASE_URL . '/admin/offers.php',      'label' => 'Coupons & Offers',  'file' => 'offers.php',    'icon' => 'percent'],
      ['href' => BASE_URL . '/admin/specials.php',    'label' => 'Specials & Featured','file' => 'specials.php',  'icon' => 'star'],
      ['href' => BASE_URL . '/admin/reports.php',     'label' => 'Reports',      'file' => 'reports.php',     'icon' => 'bar-chart'],
      ['href' => BASE_URL . '/admin/delivery_settings.php', 'label' => 'Delivery Settings', 'file' => 'delivery_settings.php', 'icon' => 'settings'],
    ],
  ],
  'restaurant' => [
    'title' => 'Restaurant Panel',
    'color' => '#a83300',
    'links' => [
      ['href' => BASE_URL . '/restaurant-panel/dashboard.php', 'label' => 'Dashboard', 'file' => 'dashboard.php', 'icon' => 'grid'],
      ['href' => BASE_URL . '/restaurant-panel/orders.php',    'label' => 'Orders',    'file' => 'orders.php',    'icon' => 'clipboard'],
      ['href' => BASE_URL . '/restaurant-panel/menu.php',      'label' => 'Menu',      'file' => 'menu.php',      'icon' => 'book'],
      ['href' => BASE_URL . '/restaurant-panel/analytics.php', 'label' => 'Analytics', 'file' => 'analytics.php', 'icon' => 'bar-chart'],
      ['href' => BASE_URL . '/restaurant-panel/settings.php',  'label' => 'Settings',  'file' => 'settings.php',  'icon' => 'settings'],
    ],
  ],
  'delivery' => [
    'title' => 'Delivery Panel',
    'color' => '#00c853',
    'links' => [
      ['href' => BASE_URL . '/delivery-panel/dashboard.php',   'label' => 'Dashboard',   'file' => 'dashboard.php',   'icon' => 'grid'],
      ['href' => BASE_URL . '/delivery-panel/deliveries.php',  'label' => 'Deliveries',  'file' => 'deliveries.php',  'icon' => 'truck'],
      ['href' => BASE_URL . '/delivery-panel/earnings.php',    'label' => 'Earnings',    'file' => 'earnings.php',    'icon' => 'dollar-sign'],
      ['href' => BASE_URL . '/delivery-panel/analytics.php',   'label' => 'Analytics',   'file' => 'analytics.php',   'icon' => 'bar-chart'],
    ],
  ],
];

$menu = $menus[$sidebarType] ?? $menus['admin'];

$icons = [
  'grid'       => '<path d="M3 3h7v7H3z"/><path d="M14 3h7v7h-7z"/><path d="M3 14h7v7H3z"/><path d="M14 14h7v7h-7z"/>',
  'clipboard'  => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>',
  'store'      => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
  'users'      => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
  'bar-chart'  => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
  'book'       => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
  'truck'      => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
  'dollar-sign'=> '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
  'settings'   => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
  'percent'    => '<circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/><line x1="19" y1="5" x2="5" y2="19"/>',
  'star'       => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
];
?>

<aside class="admin-sidebar">
  <!-- Logo -->
  <div class="mb-8 px-2">
    <a href="<?= BASE_URL ?>/index.php" class="text-xl font-extrabold text-[#a83300]">Zesto</a>
    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-1"><?= e($menu['title']) ?></p>
  </div>

  <!-- Nav Links -->
  <nav class="flex flex-col gap-1">
    <?php foreach ($menu['links'] as $link): ?>
    <a href="<?= e($link['href']) ?>"
       class="sidebar-link <?= $activePage === $link['file'] ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <?= $icons[$link['icon']] ?? '' ?>
      </svg>
      <?= e($link['label']) ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- Divider -->
  <div class="border-t border-gray-100 my-6"></div>

  <!-- Back to site -->
  <a href="<?= BASE_URL ?>/index.php" class="sidebar-link text-[#5c4037]">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    Back to Site
  </a>
  <a href="<?= BASE_URL ?>/api/auth/logout.php" class="sidebar-link text-red-500 hover:bg-red-50">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    Logout
  </a>

  <!-- User info -->
  <?php if ($currentUser): ?>
  <div class="mt-auto pt-6 border-t border-gray-100">
    <div class="flex items-center gap-3 px-2">
      <div class="w-8 h-8 rounded-full bg-[#ffdbd0] flex items-center justify-center text-[#a83300] font-bold text-sm shrink-0">
        <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-bold text-[#1b1c1c] truncate"><?= e($currentUser['name']) ?></p>
        <p class="text-[10px] text-gray-400 font-medium capitalize"><?= e(str_replace('_', ' ', $currentUser['role'])) ?></p>
      </div>
    </div>
  </div>
  <?php endif; ?>
</aside>
