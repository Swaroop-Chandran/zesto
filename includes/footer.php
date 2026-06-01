<?php
/**
 * Zesto — Footer Include
 * Features: Brand column, company links, legal links, App download buttons, copyright
 */
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}
$appJsVersion = file_exists(__DIR__ . '/../assets/js/app.js') ? filemtime(__DIR__ . '/../assets/js/app.js') : APP_VERSION;
$cartJsVersion = file_exists(__DIR__ . '/../assets/js/cart.js') ? filemtime(__DIR__ . '/../assets/js/cart.js') : APP_VERSION;
?>

<!-- ── Scripts (loaded before </body>) ───────────────────────── -->
<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= e($appJsVersion) ?>"></script>
<script src="<?= BASE_URL ?>/assets/js/cart.js?v=<?= e($cartJsVersion) ?>"></script>
<?php if (!empty($extraJs)): foreach ($extraJs as $js): ?>
<?php
  $versionedJs = $js;
  $baseAssetPrefix = BASE_URL . '/assets/js/';
  if (strpos($js, $baseAssetPrefix) === 0) {
      $relativeJsPath = parse_url($js, PHP_URL_PATH);
      $baseUrlPath = parse_url(BASE_URL, PHP_URL_PATH) ?: '';
      if ($baseUrlPath !== '' && strpos($relativeJsPath, $baseUrlPath) === 0) {
          $relativeJsPath = substr($relativeJsPath, strlen($baseUrlPath));
      }
      $absoluteJsPath = $relativeJsPath ? realpath(__DIR__ . '/..' . $relativeJsPath) : false;
      if ($absoluteJsPath && file_exists($absoluteJsPath)) {
          $versionedJs .= (strpos($versionedJs, '?') === false ? '?' : '&') . 'v=' . filemtime($absoluteJsPath);
      }
  }
?>
<script src="<?= e($versionedJs) ?>"></script>
<?php endforeach; endif; ?>

<!-- ── Footer ─────────────────────────────────────────────────── -->
<?php if (!isset($noFooter) || !$noFooter): ?>
<footer class="bg-[#e4e2e2] border-t border-[#e5beb2]/40 mt-16 md:mt-24 pb-20 md:pb-8">
  <div class="max-w-[1280px] mx-auto px-6 md:px-10 py-12 md:py-16 grid grid-cols-1 md:grid-cols-4 gap-10 md:gap-8">

    <!-- Brand Column -->
    <div class="flex flex-col gap-4">
      <a href="<?= BASE_URL ?>/index.php" class="text-xl font-extrabold text-[#a83300] hover:opacity-80 transition-opacity">Zesto</a>
      <p class="text-sm text-[#5c4037] max-w-xs leading-relaxed font-sans">
        Delivering your favourite meals from top-rated restaurants right to your doorstep, fresh and fast.
      </p>
      <div class="flex gap-4 mt-2">
        <button class="h-9 w-9 items-center justify-center flex bg-white/70 hover:bg-[#ffdbd0] text-[#5f5e5e] hover:text-[#a83300] rounded-full transition-colors shadow-sm" title="Website">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        </button>
        <button class="h-9 w-9 items-center justify-center flex bg-white/70 hover:bg-[#ffdbd0] text-[#5f5e5e] hover:text-[#a83300] rounded-full transition-colors shadow-sm" title="Announcements">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
        </button>
        <button class="h-9 w-9 items-center justify-center flex bg-white/70 hover:bg-[#ffdbd0] text-[#5f5e5e] hover:text-[#a83300] rounded-full transition-colors shadow-sm" title="Share">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
        </button>
      </div>
    </div>

    <!-- Company Links -->
    <div class="flex flex-col gap-3 font-sans">
      <h5 class="font-bold text-[#1b1c1c] text-sm tracking-wide">Company</h5>
      <nav class="flex flex-col gap-2.5 text-sm text-[#5c4037]">
        <a href="#about"          class="hover:text-[#a83300] transition-colors">About Us</a>
        <a href="#partner-with-us"class="hover:text-[#a83300] transition-colors">Partner With Us</a>
        <a href="#careers"        class="hover:text-[#a83300] transition-colors">Careers</a>
        <a href="#press"          class="hover:text-[#a83300] transition-colors">Press</a>
      </nav>
    </div>

    <!-- Legal & Support -->
    <div class="flex flex-col gap-3 font-sans">
      <h5 class="font-bold text-[#1b1c1c] text-sm tracking-wide">Legal &amp; Support</h5>
      <nav class="flex flex-col gap-2.5 text-sm text-[#5c4037]">
        <a href="#privacy" class="hover:text-[#a83300] transition-colors">Privacy Policy</a>
        <a href="#terms"   class="hover:text-[#a83300] transition-colors">Terms of Service</a>
        <a href="#help"    class="hover:text-[#a83300] transition-colors">Help Center</a>
        <a href="#contact" class="hover:text-[#a83300] transition-colors">Contact</a>
      </nav>
    </div>

    <!-- Get App -->
    <div class="flex flex-col gap-4 font-sans">
      <h5 class="font-bold text-[#1b1c1c] text-sm tracking-wide">Get App</h5>
      <p class="text-xs text-[#5c4037]">Available on iOS and Android</p>
      <div class="flex flex-col gap-3 max-w-[180px]">
        <a href="#app-store"
           class="bg-[#303031] hover:bg-black text-white rounded-xl px-4 py-2 flex items-center gap-3 transition-colors shadow-md border border-gray-800">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 20.94c1.5 0 2.75 1.06 4 1.06 3 0 6-8 6-12.22A4.91 4.91 0 0 0 17 5c-2.22 0-4 1.44-5 2-1-.56-2.78-2-5-2a4.9 4.9 0 0 0-5 4.78C2 14 5 22 8 22c1.25 0 2.5-1.06 4-1.06z"/><path d="M10 2c1 .5 2 2 2 5"/></svg>
          <div>
            <p class="text-[9px] text-[#e5beb2] uppercase leading-none font-medium">Download on</p>
            <p class="font-bold text-xs leading-tight">App Store</p>
          </div>
        </a>
        <a href="#google-play"
           class="bg-[#303031] hover:bg-black text-white rounded-xl px-4 py-2 flex items-center gap-3 transition-colors shadow-md border border-gray-800">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
          <div>
            <p class="text-[9px] text-[#e5beb2] uppercase leading-none font-medium">Get it on</p>
            <p class="font-bold text-xs leading-tight">Google Play</p>
          </div>
        </a>
      </div>
    </div>
  </div>

  <!-- Bottom Bar -->
  <div class="border-t border-[#e5beb2]/20 py-6 px-10 max-w-[1280px] mx-auto flex justify-between items-center text-xs text-gray-500 font-sans">
    <p>&copy; <?= date('Y') ?> Zesto. All rights reserved.</p>
    <p class="hidden md:block">Designed with Modern Corporate Aesthetics</p>
  </div>
</footer>
<?php endif; ?>

</body>
</html>
