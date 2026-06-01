<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

requireRole(ROLE_ADMIN);

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_dish_flag') {
        $id = (int)$_POST['id'];
        $flag = $_POST['flag'] ?? '';
        $val = (int)$_POST['value'];
        
        if (in_array($flag, ['special', 'popular', 'trending'], true)) {
            $col = 'is_' . $flag;
            $stmt = db()->prepare("UPDATE menu_items SET $col = ? WHERE id = ?");
            $stmt->execute([$val, $id]);
            $successMessage = 'Dish configuration updated successfully!';
        }
    } elseif ($action === 'add_category') {
        $name = trim($_POST['name']);
        $image_url = trim($_POST['image_url']);
        $display_order = (int)$_POST['display_order'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $errors[] = 'Category name is required.';
        } else {
            require_once __DIR__ . '/../../../includes/upload_helper.php';
            $uploadedImage = null;
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadedImage = handleImageUpload($_FILES['image_file'], 'categories', $errors);
            }
            $finalImage = $uploadedImage ?: ($image_url !== '' ? $image_url : null);
            
            if (empty($errors)) {
                $stmt = db()->prepare("INSERT INTO categories (name, image, display_order, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $finalImage, $display_order, $is_active]);
                $successMessage = 'Category added successfully!';
            }
        }
    } elseif ($action === 'edit_category') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $image_url = trim($_POST['image_url']);
        $display_order = (int)$_POST['display_order'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $errors[] = 'Category name is required.';
        } else {
            require_once __DIR__ . '/../../../includes/upload_helper.php';
            $uploadedImage = null;
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadedImage = handleImageUpload($_FILES['image_file'], 'categories', $errors);
            }
            
            if (empty($errors)) {
                if ($uploadedImage) {
                    $finalImage = $uploadedImage;
                } else {
                    $finalImage = $image_url !== '' ? $image_url : null;
                }
                
                if ($finalImage !== null) {
                    $stmt = db()->prepare("UPDATE categories SET name=?, image=?, display_order=?, is_active=? WHERE id=?");
                    $stmt->execute([$name, $finalImage, $display_order, $is_active, $id]);
                } else {
                    $stmt = db()->prepare("UPDATE categories SET name=?, display_order=?, is_active=? WHERE id=?");
                    $stmt->execute([$name, $display_order, $is_active, $id]);
                }
                $successMessage = 'Category updated successfully!';
            }
        }
    } elseif ($action === 'delete_category') {
        $id = (int)$_POST['id'];
        $stmt = db()->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $successMessage = 'Category deleted successfully!';
    }
}

// Fetch lists
$categories = db()->query("SELECT * FROM categories ORDER BY display_order ASC, name ASC")->fetchAll();
$restaurants = db()->query("SELECT id, name FROM restaurants WHERE is_active=1 ORDER BY name ASC")->fetchAll();

// Handle filter inputs
$search = trim($_GET['search'] ?? '');
$rest_id = $_GET['restaurant_id'] ?? '';
$cat_id = $_GET['category_id'] ?? '';

$params = [];
$where = ["1=1"];

if ($search !== '') {
    $where[] = "m.name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}
if ($rest_id !== '') {
    $where[] = "m.restaurant_id = :rest_id";
    $params[':rest_id'] = (int)$rest_id;
}
if ($cat_id !== '') {
    $where[] = "m.category_id = :cat_id";
    $params[':cat_id'] = (int)$cat_id;
}

$whereClause = implode(" AND ", $where);

$dishes = db()->prepare("
    SELECT m.*, r.name AS restaurant_name, c.name AS category_name
    FROM menu_items m
    JOIN restaurants r ON r.id = m.restaurant_id
    LEFT JOIN categories c ON c.id = m.category_id
    WHERE $whereClause
    ORDER BY r.name ASC, m.name ASC
    LIMIT 200
");
$dishes->execute($params);
$dishesList = $dishes->fetchAll();

$pageTitle = 'Specials & Categories — Zesto Admin';
$sidebarType = 'admin';
$activePage = 'specials.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
      <div>
        <h1 class="text-2xl font-extrabold text-[#1b1c1c]">Featured Specials & Categories</h1>
        <p class="text-sm text-gray-500 mt-1">Configure homepage Today's Specials, Viral, Trending items, and manage platform food categories.</p>
      </div>
      <div>
        <button onclick="openCategoryModal('add')" class="btn-primary flex items-center gap-2 text-sm px-4 py-2 bg-[#a83300] hover:bg-[#d24200] text-white rounded-xl font-bold transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
          Add Food Category
        </button>
      </div>
    </div>

    <!-- Notifications -->
    <?php if ($successMessage): ?>
    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-xl text-sm font-semibold flex items-center gap-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
      <?= e($successMessage) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-xl text-sm font-semibold flex flex-col gap-1">
      <?php foreach ($errors as $err): ?>
      <div class="flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <span><?= e($err) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tabs Header -->
    <div class="flex border-b border-gray-200 mb-8 gap-6">
      <button onclick="switchTab('specials')" id="tab-btn-specials" class="tab-btn pb-4 font-bold text-sm border-b-2 border-[#a83300] text-[#a83300] transition-all">
        Featured Dishes / Flag Manager (<?= count($dishesList) ?>)
      </button>
      <button onclick="switchTab('categories')" id="tab-btn-categories" class="tab-btn pb-4 font-bold text-sm text-gray-500 hover:text-gray-800 transition-all">
        Menu Categories (<?= count($categories) ?>)
      </button>
    </div>

    <!-- Specials / Flag Manager Content -->
    <div id="tab-content-specials" class="tab-pane">
      <!-- Filter Bar -->
      <form method="GET" class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm mb-6 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Search Dishes</label>
          <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search dish name..."
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] text-sm text-gray-700 font-semibold">
        </div>
        <div class="min-w-[180px]">
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Restaurant</label>
          <select name="restaurant_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] text-sm text-gray-700 bg-white font-semibold">
            <option value="">All Restaurants</option>
            <?php foreach ($restaurants as $rest): ?>
            <option value="<?= $rest['id'] ?>" <?= $rest_id == $rest['id'] ? 'selected' : '' ?>><?= e($rest['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="min-w-[180px]">
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Category</label>
          <select name="category_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] text-sm text-gray-700 bg-white font-semibold">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $cat_id == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex gap-2">
          <button type="submit" class="px-5 py-2.5 bg-[#a83300] hover:bg-[#d24200] text-white rounded-xl font-bold text-sm transition-colors">Filter</button>
          <a href="specials.php" class="px-4 py-2.5 border border-gray-200 rounded-xl font-bold text-sm hover:bg-gray-50 bg-white text-gray-600 transition-colors flex items-center">Reset</a>
        </div>
      </form>

      <!-- Dishes Table -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="bg-[#f5f3f3]">
              <tr>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider w-16">Image</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Dish Name</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Restaurant</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Category</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Price</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">Today's Special</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">Viral/Popular</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">Trending</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php if (empty($dishesList)): ?>
              <tr>
                <td colspan="8" class="px-6 py-10 text-center text-gray-400 font-semibold">No dishes match the selected filters.</td>
              </tr>
              <?php endif; ?>
              <?php foreach ($dishesList as $dish): ?>
              <tr class="hover:bg-[#f5f3f3]/30 transition-colors">
                <td class="px-6 py-4">
                  <?php if ($dish['image']): ?>
                  <img src="<?= e($dish['image']) ?>" alt="<?= e($dish['name']) ?>" class="w-12 h-12 rounded-xl object-cover border border-gray-100 bg-gray-50 shrink-0">
                  <?php else: ?>
                  <div class="w-12 h-12 rounded-xl bg-[#ffdbd0] flex items-center justify-center text-[#a83300] font-bold text-xs shrink-0">🍔</div>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                  <div class="font-bold text-gray-800 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0 <?= $dish['is_veg'] ? 'bg-green-600' : 'bg-red-600' ?>" title="<?= $dish['is_veg'] ? 'Veg' : 'Non-Veg' ?>"></span>
                    <?= e($dish['name']) ?>
                  </div>
                  <?php if ($dish['description']): ?>
                  <p class="text-xs text-gray-400 line-clamp-1 max-w-sm mt-0.5"><?= e($dish['description']) ?></p>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 font-semibold text-gray-600">
                  <?= e($dish['restaurant_name']) ?>
                </td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-md font-semibold text-xs border border-gray-200">
                    <?= e($dish['category_name'] ?? 'Uncategorized') ?>
                  </span>
                </td>
                <td class="px-6 py-4 font-extrabold text-[#a83300]">
                  <?= formatPrice($dish['price']) ?>
                </td>
                
                <!-- Flag: Today's Special -->
                <td class="px-6 py-4 text-center">
                  <form method="POST" class="inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle_dish_flag">
                    <input type="hidden" name="id" value="<?= $dish['id'] ?>">
                    <input type="hidden" name="flag" value="special">
                    <input type="hidden" name="value" value="<?= $dish['is_special'] ? 0 : 1 ?>">
                    <button type="submit" class="focus:outline-none hover:scale-110 transition-transform">
                      <?php if ($dish['is_special']): ?>
                      <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-700 rounded-full font-bold text-xs border border-amber-200">
                        ⭐ Special
                      </span>
                      <?php else: ?>
                      <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-50 text-gray-400 hover:text-gray-600 rounded-full font-bold text-xs border border-gray-100">
                        ☆ Enable
                      </span>
                      <?php endif; ?>
                    </button>
                  </form>
                </td>

                <!-- Flag: Popular -->
                <td class="px-6 py-4 text-center">
                  <form method="POST" class="inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle_dish_flag">
                    <input type="hidden" name="id" value="<?= $dish['id'] ?>">
                    <input type="hidden" name="flag" value="popular">
                    <input type="hidden" name="value" value="<?= $dish['is_popular'] ? 0 : 1 ?>">
                    <button type="submit" class="focus:outline-none hover:scale-110 transition-transform">
                      <?php if ($dish['is_popular']): ?>
                      <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-50 text-rose-700 rounded-full font-bold text-xs border border-rose-200">
                        🔥 Viral
                      </span>
                      <?php else: ?>
                      <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-50 text-gray-400 hover:text-gray-600 rounded-full font-bold text-xs border border-gray-100">
                        ♢ Enable
                      </span>
                      <?php endif; ?>
                    </button>
                  </form>
                </td>

                <!-- Flag: Trending -->
                <td class="px-6 py-4 text-center">
                  <form method="POST" class="inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle_dish_flag">
                    <input type="hidden" name="id" value="<?= $dish['id'] ?>">
                    <input type="hidden" name="flag" value="trending">
                    <input type="hidden" name="value" value="<?= $dish['is_trending'] ? 0 : 1 ?>">
                    <button type="submit" class="focus:outline-none hover:scale-110 transition-transform">
                      <?php if ($dish['is_trending']): ?>
                      <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full font-bold text-xs border border-emerald-200">
                        📈 Trending
                      </span>
                      <?php else: ?>
                      <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-50 text-gray-400 hover:text-gray-600 rounded-full font-bold text-xs border border-gray-100">
                        ⬈ Enable
                      </span>
                      <?php endif; ?>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Menu Categories Tab Content -->
    <div id="tab-content-categories" class="tab-pane hidden">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="bg-[#f5f3f3]">
              <tr>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider w-20">Preview</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Category Name</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Display Order</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php if (empty($categories)): ?>
              <tr>
                <td colspan="5" class="px-6 py-10 text-center text-gray-400 font-semibold">No food categories registered. Click "Add Food Category" to create one.</td>
              </tr>
              <?php endif; ?>
              <?php foreach ($categories as $cat): ?>
              <tr class="hover:bg-[#f5f3f3]/30 transition-colors">
                <td class="px-6 py-4">
                  <?php if ($cat['image']): ?>
                  <img src="<?= e($cat['image']) ?>" alt="<?= e($cat['name']) ?>" class="w-14 h-14 rounded-2xl object-cover border border-gray-100 bg-gray-50 shrink-0 shadow-sm">
                  <?php else: ?>
                  <div class="w-14 h-14 rounded-2xl bg-[#ffdbd0] flex items-center justify-center text-[#a83300] font-extrabold text-sm shrink-0 shadow-sm">🍽️</div>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 font-bold text-gray-800 text-base">
                  <?= e($cat['name']) ?>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-block px-3 py-1 bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-extrabold">
                    Order #<?= $cat['display_order'] ?>
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span class="badge <?= $cat['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> font-extrabold px-3 py-1 rounded-full text-xs">
                    <?= $cat['is_active'] ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
                  <button onclick="editCategory(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES, 'UTF-8') ?>)" class="text-[#a83300] hover:text-[#d24200] font-bold text-sm">Edit</button>
                  <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this category? Dishes associated with this category will remain, but will not have this category assigned.');">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                    <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-sm">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- ADD / EDIT CATEGORY MODAL -->
<!-- ========================================== -->
<div id="category-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
  <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl relative translate-y-4 transition-transform duration-300">
    <button onclick="closeCategoryModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    
    <h2 id="category-modal-title" class="text-xl font-extrabold text-[#1b1c1c] mb-6">Add Food Category</h2>
    
    <form method="POST" id="category-form" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" id="category-form-action" value="add_category">
      <input type="hidden" name="id" id="category-id" value="">
      
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Category Name</label>
          <input type="text" name="name" id="category-name" placeholder="e.g. Desserts" required
                 class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] font-semibold text-gray-700">
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Display Order Sequence</label>
          <input type="number" name="display_order" id="category-order" placeholder="e.g. 5" required min="0" value="0"
                 class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] font-semibold text-gray-700">
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Upload Category Image File</label>
          <input type="file" name="image_file" accept="image/*"
                 class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#ffdbd0] file:text-[#a83300] hover:file:bg-[#ffeadb] cursor-pointer">
          <span class="text-[10px] text-gray-400 font-semibold mt-1 block">Recommended size: Square 200x200 JPG, PNG or WEBP (Max 5MB)</span>
        </div>

        <div class="relative flex py-2 items-center">
          <div class="flex-grow border-t border-gray-200"></div>
          <span class="flex-shrink mx-4 text-xs font-bold text-gray-400 uppercase">OR</span>
          <div class="flex-grow border-t border-gray-200"></div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Category Image URL</label>
          <input type="url" name="image_url" id="category-image-url" placeholder="https://example.com/category-image.jpg"
                 class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] font-medium text-gray-700">
        </div>

        <div class="flex items-center gap-3 pt-2">
          <input type="checkbox" name="is_active" id="category-active" checked value="1"
                 class="w-5 h-5 accent-[#a83300] rounded focus:ring-0">
          <label for="category-active" class="text-sm font-bold text-gray-700">Enable this category immediately</label>
        </div>
      </div>

      <div class="mt-8 flex justify-end gap-3">
        <button type="button" onclick="closeCategoryModal()" class="px-5 py-3 border border-gray-200 text-gray-500 rounded-xl font-bold hover:bg-gray-50 transition-colors">
          Cancel
        </button>
        <button type="submit" class="px-6 py-3 bg-[#a83300] text-white rounded-xl font-bold hover:bg-[#d24200] transition-colors">
          Save Category
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function switchTab(tab) {
  const btnSpecials = document.getElementById('tab-btn-specials');
  const btnCategories = document.getElementById('tab-btn-categories');
  const paneSpecials = document.getElementById('tab-content-specials');
  const paneCategories = document.getElementById('tab-content-categories');

  if (tab === 'specials') {
    btnSpecials.className = "tab-btn pb-4 font-bold text-sm border-b-2 border-[#a83300] text-[#a83300] transition-all";
    btnCategories.className = "tab-btn pb-4 font-bold text-sm text-gray-500 hover:text-gray-800 transition-all";
    paneSpecials.classList.remove('hidden');
    paneCategories.classList.add('hidden');
  } else {
    btnCategories.className = "tab-btn pb-4 font-bold text-sm border-b-2 border-[#a83300] text-[#a83300] transition-all";
    btnSpecials.className = "tab-btn pb-4 font-bold text-sm text-gray-500 hover:text-gray-800 transition-all";
    paneCategories.classList.remove('hidden');
    paneSpecials.classList.add('hidden');
  }
}

// Persist tab view state if page refreshed via toggle post
document.addEventListener("DOMContentLoaded", function() {
  <?php if (isset($_POST['action']) && ($_POST['action'] === 'add_category' || $_POST['action'] === 'edit_category' || $_POST['action'] === 'delete_category')): ?>
    switchTab('categories');
  <?php endif; ?>
});

// Category Modal Controls
function openCategoryModal(mode) {
  const modal = document.getElementById('category-modal');
  const modalTitle = document.getElementById('category-modal-title');
  const formAction = document.getElementById('category-form-action');
  
  if (mode === 'add') {
    modalTitle.innerText = "Add Food Category";
    formAction.value = "add_category";
    document.getElementById('category-id').value = "";
    document.getElementById('category-name').value = "";
    document.getElementById('category-order').value = "0";
    document.getElementById('category-image-url').value = "";
    document.getElementById('category-active').checked = true;
  }
  
  modal.classList.remove('opacity-0', 'pointer-events-none');
  modal.firstElementChild.classList.remove('translate-y-4');
}

function closeCategoryModal() {
  const modal = document.getElementById('category-modal');
  modal.classList.add('opacity-0', 'pointer-events-none');
  modal.firstElementChild.classList.add('translate-y-4');
}

function editCategory(cat) {
  openCategoryModal('edit');
  document.getElementById('category-modal-title').innerText = "Edit Category: " + cat.name;
  document.getElementById('category-form-action').value = "edit_category";
  document.getElementById('category-id').value = cat.id;
  document.getElementById('category-name').value = cat.name;
  document.getElementById('category-order').value = cat.display_order;
  document.getElementById('category-image-url').value = cat.image || '';
  document.getElementById('category-active').checked = cat.is_active == 1;
}
</script>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
