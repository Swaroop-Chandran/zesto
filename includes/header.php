<?php
/**
 * Zesto — HTML Head / Header Include
 * @param string $pageTitle   The <title> tag content
 * @param string $description Meta description for the page
 * @param array  $extraCss    Additional CSS files to load
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/auth.php';
}

$pageTitle   = $pageTitle   ?? APP_NAME . ' — Delivering your favourite meals';
$description = $description ?? 'Zesto — Order food from top-rated restaurants. Fast delivery, fresh meals.';
$cartCount   = getCartCount();
$csrfToken   = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($description) ?>">
  <meta name="csrf-token" content="<?= e($csrfToken) ?>">
  <meta name="theme-color" content="#a83300">

  <title><?= e($pageTitle) ?></title>

  <!-- Preconnect -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- Google Fonts: Plus Jakarta Sans -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Tailwind CSS CDN with custom config -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Plus Jakarta Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
          },
          colors: {
            brand:       '#a83300',
            'brand-light':'#ffdbd0',
            'brand-hover':'#d24200',
            dark:        '#303031',
            surface:     '#fbf9f8',
            'text-main': '#1b1c1c',
            'text-muted':'#5f5e5e',
          }
        }
      }
    }
  </script>

  <!-- Custom Global Base URL for AJAX routing -->
  <script>
    window.ZESTO_BASE = '<?= BASE_URL ?>';
  </script>

  <!-- Custom App Styles -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">

  <!-- Core JavaScript modules -->
  <script src="<?= BASE_URL ?>/assets/js/auth.js" defer></script>

  <?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
  <link rel="stylesheet" href="<?= e($css) ?>">
  <?php endforeach; endif; ?>

  <!-- Favicon (emoji fallback) -->
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🍽️</text></svg>">
</head>
<body class="bg-[#fbf9f8] text-[#1b1c1c] font-sans selection:bg-[#ffdbd0] min-h-screen flex flex-col">

<!-- Toast Container -->
<div id="toast-container"></div>

<?php
// Show flash message if any
$flash = getFlash();
if ($flash):
?>
<div id="flash-message" class="fixed top-4 left-1/2 -translate-x-1/2 z-[9998] animate-slide-up
     flex items-center gap-3 px-5 py-3 rounded-xl shadow-xl text-sm font-semibold
     <?= $flash['type'] === 'success' ? 'bg-[#00c853] text-white' : ($flash['type'] === 'error' ? 'bg-red-500 text-white' : 'bg-[#303031] text-white') ?>">
  <?= $flash['type'] === 'success' ? '✅' : ($flash['type'] === 'error' ? '❌' : 'ℹ️') ?>
  <?= e($flash['message']) ?>
</div>
<?php endif; ?>
