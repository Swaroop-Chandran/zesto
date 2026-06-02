<?php
/**
 * Zesto — Restaurant Menu Panel Dashboard
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/upload_helper.php';
require_once __DIR__ . '/../../../includes/image_helper.php';

requireRole(ROLE_RESTAURANT_OWNER);
$ownerId = getCurrentUser()['id'];

// Fetch Owner's Restaurant
$res = db()->prepare("SELECT * FROM restaurants WHERE owner_id=:oid LIMIT 1");
$res->execute([':oid' => $ownerId]);
$restaurant = $res->fetch();

$menuItems = [];
$categories = db()->query("SELECT * FROM categories WHERE is_active=1 ORDER BY display_order ASC")->fetchAll();

if ($restaurant) {
    $menuItems = db()->prepare("
        SELECT mi.*, c.name AS category_name 
        FROM menu_items mi
        LEFT JOIN categories c ON c.id = mi.category_id
        WHERE mi.restaurant_id=:rid 
        ORDER BY mi.display_order ASC, mi.id ASC
    ");
    $menuItems->execute([':rid' => $restaurant['id']]);
    $menuItems = $menuItems->fetchAll();
}

// Handle add/edit menu item
$editItem = null;
if (isset($_GET['edit']) && $restaurant) {
    $editStmt = db()->prepare("SELECT * FROM menu_items WHERE id=:id AND restaurant_id=:rid LIMIT 1");
    $editStmt->execute([':id' => (int)$_GET['edit'], ':rid' => $restaurant['id']]);
    $editItem = $editStmt->fetch() ?: null;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $restaurant) {
    verifyCsrf();
    $name    = trim($_POST['name']        ?? '');
    $desc    = trim($_POST['description'] ?? '');
    $price   = (float)($_POST['price']    ?? 0);
    $catId   = (int)($_POST['category_id'] ?? 0);
    $isVeg   = isset($_POST['is_veg']) ? (int)$_POST['is_veg'] : 1;
    $avail   = isset($_POST['is_available']) ? 1 : 0;
    $opts    = json_encode(array_filter(array_map('trim', explode(',', $_POST['options'] ?? ''))));

    // Handle Image Upload
    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imageUrl = handleImageUpload($_FILES['image'], 'foods', $errors);
    }

    if (empty($name)) { $errors[] = 'Dish name is required.'; }
    if ($price <= 0) { $errors[] = 'Price must be greater than zero.'; }

    if (empty($errors)) {
        if (isset($_POST['item_id']) && $_POST['item_id']) {
            $itemId = (int)$_POST['item_id'];
            if ($imageUrl) {
                $upd = db()->prepare("
                    UPDATE menu_items 
                    SET name=:n, description=:d, price=:p, category_id=:cat, is_veg=:veg, is_available=:a, customization_options=:o, image=:img 
                    WHERE id=:id AND restaurant_id=:rid
                ");
                $upd->execute([':n'=>$name, ':d'=>$desc, ':p'=>$price, ':cat'=>$catId ?: null, ':veg'=>$isVeg, ':a'=>$avail, ':o'=>$opts, ':img'=>$imageUrl, ':id'=>$itemId, ':rid'=>$restaurant['id']]);
            } else {
                $upd = db()->prepare("
                    UPDATE menu_items 
                    SET name=:n, description=:d, price=:p, category_id=:cat, is_veg=:veg, is_available=:a, customization_options=:o 
                    WHERE id=:id AND restaurant_id=:rid
                ");
                $upd->execute([':n'=>$name, ':d'=>$desc, ':p'=>$price, ':cat'=>$catId ?: null, ':veg'=>$isVeg, ':a'=>$avail, ':o'=>$opts, ':id'=>$itemId, ':rid'=>$restaurant['id']]);
            }
            setFlash('success', 'Menu item updated successfully!');
        } else {
            $ins = db()->prepare("
                INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_veg, is_available, customization_options, image) 
                VALUES (:rid, :cat, :n, :d, :p, :veg, :a, :o, :img)
            ");
            $ins->execute([
                ':rid'=>$restaurant['id'], 
                ':cat'=>$catId ?: null, 
                ':n'=>$name, 
                ':d'=>$desc, 
                ':p'=>$price, 
                ':veg'=>$isVeg, 
                ':a'=>$avail, 
                ':o'=>$opts, 
                ':img'=>$imageUrl ?: ''
            ]);
            setFlash('success', 'New menu item created!');
        }
        header('Location: '.BASE_URL.'/restaurant-panel/menu.php'); 
        exit;
    }
}

$pageTitle = 'Menu Management — Restaurant Panel';
$sidebarType = 'restaurant'; $activePage = 'menu.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout font-sans bg-zesto-dark text-[#dfe2eb] min-h-screen flex">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl font-sans">
    
    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-5 flex-wrap gap-4">
      <div>
        <span class="text-xs font-bold text-zesto-orange uppercase tracking-widest">Restaurant Panel</span>
        <h1 class="text-2xl md:text-3xl font-black text-white mt-1">Menu Management</h1>
      </div>
      <?php if ($restaurant): ?>
      <a href="?add=1" class="btn-primary text-xs font-bold px-6 py-2.5 rounded-full">+ Add Item</a>
      <?php endif; ?>
    </div>

    <?php if (empty($restaurant)): ?>
    <div class="bg-zesto-orange/10 border border-zesto-orange/20 rounded-3xl p-8 text-center text-white/60 text-sm">
      🍴 You do not have a restaurant associated with your owner account yet. Please contact admin for kitchen setups.
    </div>
    <?php exit; endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-xs text-red-400 font-semibold space-y-1">
      <?php foreach ($errors as $e): ?><p>• <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ADD / EDIT DISH SECTION -->
    <?php if (isset($_GET['add']) || $editItem): ?>
    <div class="glass-panel rounded-3xl border border-white/10 p-6 shadow-md shadow-black/20 mb-8 animate-scale-up">
      <h3 class="font-extrabold text-sm mb-5 uppercase tracking-wider text-white/40 border-b border-white/10 pb-3"><?= $editItem ? 'Edit Food Item details' : 'Add New Food Item' ?></h3>
      
      <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-xs text-white/70 font-semibold">
        <?= csrfField() ?>
        <?php if ($editItem): ?>
        <input type="hidden" name="item_id" value="<?= $editItem['id'] ?>">
        <?php endif; ?>

        <div>
          <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Dish Name *</label>
          <input type="text" name="name" required value="<?= e($editItem['name'] ?? '') ?>" placeholder="Signature Pizza" class="zesto-input bg-white/5 border-white/10 text-xs">
        </div>
        <div>
          <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Price (₹) *</label>
          <input type="number" name="price" step="0.01" min="0.01" required value="<?= e($editItem['price'] ?? '') ?>" placeholder="299.00" class="zesto-input bg-white/5 border-white/10 text-xs">
        </div>
        <div>
          <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Category *</label>
          <select name="category_id" required class="zesto-input bg-white/5 border-white/10 text-xs">
            <option value="">Select Cuisine Category</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= (($editItem['category_id'] ?? 0) == $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Food Classification *</label>
          <div class="grid grid-cols-2 gap-3">
            <label class="cursor-pointer">
              <input type="radio" name="is_veg" value="1" <?= ($editItem['is_veg'] ?? 1) == 1 ? 'checked' : '' ?> class="sr-only">
              <div class="text-center p-2.5 border rounded-lg font-bold transition-all border-green-500/30 bg-green-500/10 text-green-400 role-veg-pill">
                🌱 Vegetarian
              </div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="is_veg" value="0" <?= ($editItem['is_veg'] ?? 1) == 0 ? 'checked' : '' ?> class="sr-only">
              <div class="text-center p-2.5 border rounded-lg font-bold transition-all border-white/10 text-white/60 hover:border-gray-400 role-veg-pill">
                🔺 Non-Veg
              </div>
            </label>
          </div>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Description</label>
          <textarea name="description" rows="3" placeholder="Describe the ingredients, presentation, and culinary taste..." class="zesto-input bg-white/5 border-white/10 text-xs resize-none"><?= e($editItem['description'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Customization Options (comma-separated)</label>
          <input type="text" name="options" value="<?= e(implode(', ', json_decode($editItem['customization_options'] ?? '[]', true) ?? [])) ?>" placeholder="Extra Cheese, Spicy, Wheat Base" class="zesto-input bg-white/5 border-white/10 text-xs">
        </div>
        
        <!-- Image Upload Field -->
        <div>
          <label class="block text-[10px] font-bold text-white mb-1.5 uppercase tracking-wider">Dish Image Upload</label>
          <input type="file" name="image" accept="image/*" class="zesto-input bg-white/5 border-white/10 text-xs py-2 px-3 border-dashed border-2">
          <?php if (!empty($editItem['image'])): ?>
          <p class="text-[10px] text-white/40 mt-1.5 flex items-center gap-1.5">✓ Current: <a href="<?= e($editItem['image']) ?>" target="_blank" class="font-bold text-zesto-orange hover:underline truncate"><?= basename($editItem['image']) ?></a></p>
          <?php endif; ?>
        </div>

        <div class="flex items-center gap-2 pt-6">
          <input type="checkbox" name="is_available" id="avail" <?= ($editItem['is_available'] ?? 1) ? 'checked' : '' ?> class="w-4 h-4 accent-[#f59e0b]">
          <label for="avail" class="text-sm font-bold text-white cursor-pointer">Available for Order</label>
        </div>

        <div class="sm:col-span-2 flex gap-3 mt-3">
          <button type="submit" class="btn-primary text-xs font-bold px-6 py-2.5 rounded-full">Save Menu Item</button>
          <a href="<?= BASE_URL ?>/restaurant-panel/menu.php" class="btn-secondary text-xs font-bold px-6 py-2.5 rounded-full flex items-center justify-center">Cancel</a>
        </div>
      </form>
    </div>
    <script>
      // Veg/Non-Veg Radios cards styling
      const pills = document.querySelectorAll('.role-veg-pill');
      const inputs = document.querySelectorAll('input[name="is_veg"]');
      inputs.forEach((input, index) => {
        input.addEventListener('change', function() {
          pills.forEach(p => p.className = "text-center p-2.5 border rounded-lg font-bold transition-all border-white/10 text-white/60 hover:border-gray-400 role-veg-pill");
          if (this.value === '1') {
            pills[index].className = "text-center p-2.5 border rounded-lg font-bold transition-all border-green-500/30 bg-green-500/10 text-green-400 role-veg-pill";
          } else {
            pills[index].className = "text-center p-2.5 border rounded-lg font-bold transition-all border-red-500/30 bg-red-500/10 text-red-400 role-veg-pill";
          }
        });
      });
    </script>
    <?php endif; ?>

    <!-- LIST OF FOOD ITEMS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($menuItems as $item): ?>
      <div class="glass-panel rounded-3xl border border-white/10 shadow-md shadow-black/20 p-5 flex flex-col gap-4 justify-between relative hover:border-zesto-orange/50 transition-colors group">
        
        <div class="space-y-3">
          <!-- Image thumbnail -->
          <div class="h-40 w-full rounded-2xl overflow-hidden shrink-0 border border-white/10 relative bg-white/5">
            <img src="<?= getFoodImage($item['image'], $item['name'], $item['category_name']) ?>" 
                 alt="dish" class="w-full h-full object-cover">
            
            <span class="absolute top-3 left-3 w-4 h-4 border flex items-center justify-center rounded-sm text-[8px] glass-panel font-bold <?= $item['is_veg'] ? 'border-green-600 text-green-600' : 'border-red-600 text-red-600' ?>">
              <?= $item['is_veg'] ? '●' : '▲' ?>
            </span>

            <span class="absolute top-3 right-3 text-[8px] font-black px-2 py-0.5 rounded shadow-md shadow-black/20 <?= $item['is_available'] ? 'bg-green-500/20 border border-green-500/30 text-green-400' : 'bg-red-500/20 border border-red-500/30 text-red-400' ?>">
              <?= $item['is_available'] ? 'Active' : 'Hidden' ?>
            </span>
          </div>

          <div>
            <div class="flex justify-between items-start gap-2">
              <h4 class="font-extrabold text-sm text-white truncate"><?= e($item['name']) ?></h4>
            </div>
            <p class="text-[10px] text-white/40 font-bold uppercase tracking-wider mt-0.5"><?= e($item['category_name'] ?: 'No Category') ?></p>
            <p class="text-xs text-white/40 mt-1 line-clamp-2 leading-relaxed"><?= e($item['description'] ?: 'Delightful food menu dish.') ?></p>
          </div>
        </div>

        <div class="flex justify-between items-center pt-3 border-t border-white/10 mt-3 shrink-0">
          <span class="text-zesto-orange font-black text-sm"><?= formatPrice($item['price']) ?></span>
          <div class="flex gap-1.5">
            <a href="?edit=<?= $item['id'] ?>" class="text-[10px] px-3.5 py-1.5 rounded-full border border-white/10 glass-panel hover:bg-white/5 text-white/80 font-bold cursor-pointer transition-colors shadow-md shadow-black/20">Edit</a>
            <form method="POST" action="<?= BASE_URL ?>/api/restaurants/delete-item.php" class="inline">
              <?= csrfField() ?>
              <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
              <button type="submit" data-confirm="Remove this menu item permanently?"
                      class="text-[10px] px-3.5 py-1.5 rounded-full border border-red-500/20 text-red-400 hover:bg-red-500/10 font-bold cursor-pointer transition-colors shadow-md shadow-black/20">Delete</button>
            </form>
          </div>
        </div>

      </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
