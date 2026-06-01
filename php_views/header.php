<!-- 
  Zesto Nights - Premium Late-Night Food Delivery of Kerala
  Header Layout Component (header.php) 
-->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Zesto Nights - The Taste of Kerala After Dark</title>
  
  <!-- Premium Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  
  <!-- Tailwind CDN (Configure exact theme styles dynamically) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Be Vietnam Pro', 'sans-serif'],
            display: ['Syne', 'sans-serif'],
            mono: ['JetBrains Mono', 'monospace'],
          },
          colors: {
            zesto: {
              dark: '#10141a',
              charcoal: '#1c2026',
              surface: '#181c22',
              orange: '#ff5625',
              'orange-glow': '#ffb5a0',
              amber: '#ffe2ab',
              cyan: '#00daf3',
            }
          }
        }
      }
    }
  </script>
  <style>
    body {
      background-color: #10141a;
      color: #dfe2eb;
    }
    .glass-panel {
      background: rgba(255, 255, 255, 0.04);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.08);
    }
  </style>
</head>
<body class="min-h-screen flex flex-col justify-between selection:bg-[#ff5625] selection:text-white">

<header class="sticky top-0 z-40 w-full glass-panel border-b border-white/10 px-6 sm:px-10 py-3 flex flex-wrap items-center justify-between gap-4">
  <!-- Brand Logo -->
  <div class="flex items-center gap-6">
    <a href="index.php" class="flex items-center gap-2 text-2xl font-display font-extrabold text-white tracking-tight hover:opacity-90">
      <span class="text-white">Zesto <span class="text-zesto-orange">Nights</span></span>
    </a>
    
    <!-- Location Dropdown simulation -->
    <div class="relative">
      <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs font-semibold text-white/90">
        <span class="text-zesto-orange">📍</span>
        <span>Kochi, Kerala</span>
      </button>
    </div>
  </div>

  <!-- Global Search -->
  <div class="flex-1 max-w-lg">
    <form action="restaurants.php" method="GET" class="relative">
      <input 
        type="text" 
        name="search" 
        placeholder="Search for Porotta, Beef Roast, Kappa..." 
        class="w-full bg-white/5 border border-white/10 text-white rounded-lg pl-5 pr-10 py-2 text-xs focus:outline-none focus:border-zesto-orange"
      >
      <button type="submit" class="absolute inset-y-0 right-0 pr-3 flex items-center text-white/40 hover:text-white">
        🔍
      </button>
    </form>
  </div>

  <!-- Navigation anchors -->
  <nav class="flex items-center gap-6 text-xs font-bold text-white/80">
    <a href="offers.php" class="hover:text-zesto-orange transition">Offers</a>
    <a href="help.php" class="hover:text-zesto-orange transition">Help</a>
    
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="profile.php" class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-zesto-orange/15 text-zesto-orange border border-zesto-orange/20 font-black">
        👤 <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Amal Dev'); ?>
      </a>
    <?php else: ?>
      <a href="login.php" class="hover:text-zesto-orange transition">Login</a>
    <?php endif; ?>

    <!-- Cart link -->
    <a href="cart.php" class="relative bg-zesto-orange px-4 py-2 rounded-full text-white font-extrabold flex items-center gap-1.5">
      <span>🛒</span>
      <span>Cart</span>
    </a>
  </nav>
</header>
