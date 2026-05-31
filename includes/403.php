<?php
/**
 * Zesto — 403 Forbidden Include
 */
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/auth.php';
}
http_response_code(403);
$pageTitle = '403 Forbidden — Zesto';
include __DIR__ . '/header.php';
include __DIR__ . '/navbar.php';
?>
<main class="flex-1 flex items-center justify-center py-24">
  <div class="text-center">
    <div class="text-6xl mb-4">🚫</div>
    <h1 class="text-3xl font-extrabold text-[#1b1c1c] mb-2">403 — Access Denied</h1>
    <p class="text-gray-500 mb-8">You don't have permission to access this page.</p>
    <a href="<?= BASE_URL ?>/index.php" class="btn-primary">Go Back Home</a>
  </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
