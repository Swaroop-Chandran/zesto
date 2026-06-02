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

$pageTitle   = $pageTitle   ?? APP_NAME . ' — Premium Late-Night Food Delivery';
$description = $description ?? 'Zesto Nights — The Taste of Kerala After Dark. Order food from top-rated restaurants. Fast delivery, fresh meals.';
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
  <meta name="theme-color" content="#0A0A0A">

  <title><?= e($pageTitle) ?></title>

  <!-- Preconnect -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- Google Fonts: Be Vietnam Pro, Syne, JetBrains Mono -->
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

  <!-- Lucide Icons via CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- Tailwind CSS CDN with custom config -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Be Vietnam Pro', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            display: ['Georgia', 'Times New Roman', 'serif'],
            mono: ['JetBrains Mono', 'monospace'],
            serif: ['Georgia', 'Times New Roman', 'serif'],
          },
          colors: {
            zesto: {
              midnight: '#050505',
              dark: '#0A0A0A',
              charcoal: '#121212',
              surface: '#0e0e0e',
              'surface-high': '#1c1c1c',
              'surface-bright': '#282828',
              orange: '#f59e0b',
              'orange-glow': '#fef3c7',
              amber: '#d97706',
              cyan: '#fbbf24',
            }
          }
        }
      }
    }
  </script>

  <style type="text/tailwindcss">
    @layer utilities {
      .glass-panel {
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.08);
      }
      
      .glass-panel-heavy {
        background: rgba(16, 20, 26, 0.85);
        backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.12);
      }

      .glass-card {
        background: rgba(28, 32, 38, 0.6);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.06);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      }

      .glass-card:hover {
        background: rgba(28, 32, 38, 0.85);
        border-color: rgba(255, 86, 37, 0.4);
        box-shadow: 0 0 25px rgba(255, 86, 37, 0.15);
        transform: translateY(-2px);
      }

      .fire-glow {
        box-shadow: 0 0 30px rgba(255, 86, 37, 0.2);
      }

      .amber-glow {
        box-shadow: 0 0 25px rgba(255, 226, 171, 0.15);
      }

      .cyan-glow {
        box-shadow: 0 0 25px rgba(0, 218, 243, 0.2);
      }

      /* Custom scrollbar */
      ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
      }
      ::-webkit-scrollbar-track {
        background: #0A0A0A;
      }
      ::-webkit-scrollbar-thumb {
        background: #1c1c1c;
        border-radius: 999px;
      }
      ::-webkit-scrollbar-thumb:hover {
        background: #f59e0b;
      }
    }
  </style>

  <style>
    body {
      background-color: #0A0A0A;
      color: #dfe2eb;
      font-family: 'Be Vietnam Pro', sans-serif;
      overflow-x: hidden;
    }
  </style>

  <!-- Custom Global Base URL for AJAX routing -->
  <script>
    window.ZESTO_BASE = '<?= BASE_URL ?>';
  </script>

  <!-- Custom App Styles -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">

  <!-- Core JavaScript modules -->
  <script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
  <script src="<?= BASE_URL ?>/assets/js/auth.js" defer></script>
  <script src="<?= BASE_URL ?>/assets/js/cart.js" defer></script>

  <?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
  <link rel="stylesheet" href="<?= e($css) ?>">
  <?php endforeach; endif; ?>

  <!-- Favicon -->
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌙</text></svg>">
</head>
<body class="bg-zesto-dark text-[#dfe2eb] font-sans selection:bg-zesto-orange selection:text-white min-h-screen flex flex-col">

<!-- Toast Container -->
<div id="toast-container" class="fixed bottom-4 right-4 z-[9999] flex flex-col gap-2"></div>

<?php
// Show flash message if any
$flash = getFlash();
if ($flash):
?>
<div id="flash-message" class="fixed top-20 left-1/2 -translate-x-1/2 z-[9998] animate-slide-up
     flex items-center gap-3 px-5 py-3 rounded-xl shadow-2xl text-sm font-semibold glass-panel-heavy
     <?= $flash['type'] === 'success' ? 'border-zesto-cyan text-white' : ($flash['type'] === 'error' ? 'border-red-500 text-white' : 'border-zesto-amber text-white') ?>">
  <?= $flash['type'] === 'success' ? '✨' : ($flash['type'] === 'error' ? '⚠️' : 'ℹ️') ?>
  <?= e($flash['message']) ?>
</div>
<?php endif; ?>
