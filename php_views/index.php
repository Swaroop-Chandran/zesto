<?php
/* 
  Zesto Nights - Premium Late-Night Food Delivery of Kerala
  Homepage Hub View (index.php) 
*/
include_once 'header.php';

// Mock DB Result structures for showcase purposes
$categories = [
  ['name' => 'Porotta', 'image' => 'https://images.unsplash.com/photo-1626132647523-66f5bf380027?auto=format&fit=crop&q=80&w=150'],
  ['name' => 'Beef Roast', 'image' => 'https://images.unsplash.com/photo-1603360946369-dc9bb6258143?auto=format&fit=crop&q=80&w=150'],
  ['name' => 'Kappa', 'image' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?auto=format&fit=crop&q=80&w=150'],
  ['name' => 'Black Tea', 'image' => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&q=80&w=150'],
];

// In production, fetch these from your MySQL db using PDO:
// $stmt = $pdo->prepare("SELECT * FROM food_items WHERE is_bestseller = 1 LIMIT 3");
// $stmt->execute();
// $specials = $stmt->fetchAll(PDO::FETCH_ASSOC);

$specials = [
  [
    'id' => 1,
    'name' => 'Beef Roast & Porotta',
    'description' => 'Spicy slow-roasted shredded beef tossed with organic Kerala coconut slices, served with 2 crispy handmade Porottas.',
    'price' => 180,
    'category' => 'Beef Roast',
    'image' => 'https://images.unsplash.com/photo-1603360946369-dc9bb6258143?auto=format&fit=crop&q=80&w=400',
    'spice_level' => 3
  ],
  [
    'id' => 2,
    'name' => 'Thattukada Set',
    'description' => 'Authentic roadside combination: 3 soft fluffy Porottas and a flavorful spicy chicken or beef gravy.',
    'price' => 180,
    'category' => 'Porotta',
    'image' => 'https://images.unsplash.com/photo-1626132647523-66f5bf380027?auto=format&fit=crop&q=80&w=400',
    'spice_level' => 2
  ]
];

// Fetch Thattukada restaurants
$restaurants = [
  [
    'id' => 1,
    'name' => "Mani's Thattukada",
    'rating' => 4.8,
    'specialty' => 'Chicken Fry, Porotta & Beef Roast',
    'delivery_time_mins' => 25,
    'open_until' => '4 AM',
    'distance_km' => 1.2,
    'image' => 'https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&q=80&w=400'
  ],
  [
    'id' => 2,
    'name' => "Night Rider Eats",
    'rating' => 4.5,
    'specialty' => 'Kappa Biriyani & Sulaimani Black Tea',
    'delivery_time_mins' => 25,
    'open_until' => '4 AM',
    'distance_km' => 2.5,
    'image' => 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?auto=format&fit=crop&q=80&w=400'
  ]
];
?>

<div class="w-full max-w-7xl mx-auto px-6 sm:px-10 py-8 space-y-12">
  
  <!-- Hero banner -->
  <section class="relative rounded-2xl overflow-hidden bg-zinc-950 py-16 px-10 flex flex-col justify-center min-h-[440px] border border-white/5">
    <div className="absolute inset-0 z-0 bg-gradient-to-r from-black via-black/80 to-transparent"></div>
    <div class="absolute top-10 right-10 w-48 h-48 bg-orange-600/10 rounded-full blur-3xl"></div>
    
    <div class="relative z-10 max-w-xl space-y-6 text-left">
      <span class="inline-block bg-orange-600/15 border border-orange-600/30 text-orange-400 text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full">
        🔥 KOCHI AFTER DARK CRAVING CURE
      </span>
      <h1 class="text-4xl sm:text-5xl font-display font-extrabold text-white tracking-tight leading-none">
        The Taste of Kerala <br>
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-amber-300">
          After Dark
        </span>
      </h1>
      <p class="text-sm text-white/70 leading-relaxed font-sans">
        Craving something hot and spicy at 2 AM? We deliver the authentic Thattukada experience straight to your door with smoking hot Porotta and sizzling Beef Roast.
      </p>
      
      <div class="flex items-center gap-4 pt-2">
        <a href="restaurants.php" class="px-8 py-3 bg-orange-600 hover:bg-orange-500 text-white text-xs font-bold rounded-full transition">
          Order Now
        </a>
        <a href="restaurants.php?near=1" class="px-6 py-3 bg-white/5 hover:bg-white/10 text-white border border-white/10 rounded-full text-xs font-semibold">
          📍 Near Me
        </a>
      </div>
    </div>
  </section>

  <!-- Categories strip -->
  <section class="space-y-4">
    <h3 class="text-base font-display font-semibold text-white tracking-wide">Craving Categories</h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <?php foreach ($categories as $cat): ?>
        <a href="restaurants.php?specialty=<?php echo urlencode($cat['name']); ?>" class="p-4 bg-white/3 border border-white/5 rounded-xl flex items-center gap-4 hover:border-orange-500/40 transition">
          <img src="<?php echo $cat['image']; ?>" class="w-12 h-12 rounded-full object-cover shadow border border-white/10" alt="">
          <div class="text-left">
            <h4 class="text-xs font-bold text-white"><?php echo $cat['name']; ?></h4>
            <p class="text-[9px] text-white/40 mt-0.5">Explore authentic recipes</p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Night Specials -->
  <section class="space-y-6">
    <div class="flex items-center justify-between">
      <div class="text-left">
        <h2 class="text-xl font-display font-extrabold text-white">Night Specials 🔥</h2>
        <p class="text-xs text-white/40 font-semibold mt-0.5">Steaming hot, right off the tawa</p>
      </div>
      <a href="specials.php" class="text-xs font-bold text-orange-500 hover:underline">See All</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($specials as $special): ?>
        <div class="bg-[#1c2026] border border-white/5 hover:border-orange-500/30 rounded-2xl overflow-hidden flex flex-col justify-between p-4 space-y-4">
          <img src="<?php echo $special['image']; ?>" class="w-full h-40 object-cover rounded-xl" alt="">
          
          <div class="space-y-2 text-left">
            <div class="flex justify-between items-start">
              <h3 class="text-sm font-bold text-white"><?php echo $special['name']; ?></h3>
              <span class="text-sm font-black text-orange-500">₹<?php echo $special['price']; ?></span>
            </div>
            <p class="text-xs text-white/60 line-clamp-2"><?php echo $special['description']; ?></p>
          </div>

          <div class="flex items-center justify-between border-t border-white/5 pt-3">
            <span class="text-[10px] text-white/40">🌶️ Spicy level: <?php echo $special['spice_level']; ?>/3</span>
            <a href="cart_actions.php?action=add&id=<?php echo $special['id']; ?>" class="px-4 py-1 bg-white/5 border border-white/10 text-[10px] font-bold rounded-full hover:bg-orange-600 hover:text-white transition">
              + Add
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Thattukadas Near You -->
  <section class="space-y-6">
    <div class="flex items-center justify-between">
      <div class="text-left">
        <h3 class="text-xl font-display font-extrabold text-white">Thattukadas Near You 📍</h3>
        <p class="text-xs text-white/40 font-semibold mt-0.5">Highly rated street counters delivering late</p>
      </div>
      <a href="restaurants.php" class="text-xs font-bold text-orange-500 hover:underline">See All</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <?php foreach ($restaurants as $rest): ?>
        <a href="restaurant_detail.php?id=<?php echo $rest['id']; ?>" class="bg-[#1c2026]/40 border border-white/5 hover:border-amber-400/30 rounded-2xl p-4 flex flex-col justify-between transition text-left group">
          <img src="<?php echo $rest['image']; ?>" class="w-full h-32 object-cover rounded-xl mb-3" alt="">
          
          <h4 class="text-xs font-bold text-white group-hover:text-orange-500 transition"><?php echo $rest['name']; ?></h4>
          <p class="text-[10px] text-white/50 mt-1 line-clamp-1"><?php echo $rest['specialty']; ?></p>
          
          <div class="flex items-center justify-between border-t border-white/5 pt-3 mt-3 text-[10px] text-white/40">
            <span>⭐ <?php echo $rest['rating']; ?></span>
            <span>⚡ <?php echo $rest['delivery_time_mins']; ?> mins</span>
            <span><?php echo $rest['distance_km']; ?> km</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

</div>

<?php 
include_once 'footer.php';
?>
