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
    
    if ($action === 'add_coupon') {
        $code = strtoupper(trim($_POST['code']));
        $discount = (float)$_POST['discount_percentage'];
        $max_discount = $_POST['max_discount'] !== '' ? (float)$_POST['max_discount'] : null;
        $min_order = (float)($_POST['min_order_value'] ?? 0.00);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($code)) {
            $errors[] = 'Coupon code cannot be empty.';
        } else {
            $stmt = db()->prepare("SELECT COUNT(*) FROM coupons WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Coupon code already exists.';
            } else {
                $stmt = db()->prepare("INSERT INTO coupons (code, discount_percentage, max_discount, min_order_value, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$code, $discount, $max_discount, $min_order, $is_active]);
                $successMessage = 'Coupon added successfully!';
            }
        }
    } elseif ($action === 'edit_coupon') {
        $id = (int)$_POST['id'];
        $code = strtoupper(trim($_POST['code']));
        $discount = (float)$_POST['discount_percentage'];
        $max_discount = $_POST['max_discount'] !== '' ? (float)$_POST['max_discount'] : null;
        $min_order = (float)($_POST['min_order_value'] ?? 0.00);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($code)) {
            $errors[] = 'Coupon code cannot be empty.';
        } else {
            $stmt = db()->prepare("SELECT COUNT(*) FROM coupons WHERE code = ? AND id != ?");
            $stmt->execute([$code, $id]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Coupon code already exists on another coupon.';
            } else {
                $stmt = db()->prepare("UPDATE coupons SET code=?, discount_percentage=?, max_discount=?, min_order_value=?, is_active=? WHERE id=?");
                $stmt->execute([$code, $discount, $max_discount, $min_order, $is_active, $id]);
                $successMessage = 'Coupon updated successfully!';
            }
        }
    } elseif ($action === 'delete_coupon') {
        $id = (int)$_POST['id'];
        $stmt = db()->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        $successMessage = 'Coupon deleted successfully!';
    } elseif ($action === 'toggle_coupon') {
        $id = (int)$_POST['id'];
        $active = (int)$_POST['is_active'];
        $stmt = db()->prepare("UPDATE coupons SET is_active = ? WHERE id = ?");
        $stmt->execute([$active, $id]);
        $successMessage = 'Coupon status updated!';
    } elseif ($action === 'add_offer') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $code = trim($_POST['code']) !== '' ? strtoupper(trim($_POST['code'])) : null;
        $image_url = trim($_POST['image_url']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($title)) {
            $errors[] = 'Offer title cannot be empty.';
        } else {
            require_once __DIR__ . '/../../../includes/upload_helper.php';
            $uploadedImage = null;
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadedImage = handleImageUpload($_FILES['image_file'], 'offers', $errors);
            }
            
            $finalImage = $uploadedImage ?: ($image_url !== '' ? $image_url : null);
            
            if (empty($errors)) {
                $stmt = db()->prepare("INSERT INTO offers (title, description, code, image, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $code, $finalImage, $is_active]);
                $successMessage = 'Offer added successfully!';
            }
        }
    } elseif ($action === 'edit_offer') {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $code = trim($_POST['code']) !== '' ? strtoupper(trim($_POST['code'])) : null;
        $image_url = trim($_POST['image_url']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($title)) {
            $errors[] = 'Offer title cannot be empty.';
        } else {
            require_once __DIR__ . '/../../../includes/upload_helper.php';
            $uploadedImage = null;
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadedImage = handleImageUpload($_FILES['image_file'], 'offers', $errors);
            }
            
            if (empty($errors)) {
                if ($uploadedImage) {
                    $finalImage = $uploadedImage;
                } else {
                    $finalImage = $image_url !== '' ? $image_url : null;
                }
                
                if ($finalImage !== null) {
                    $stmt = db()->prepare("UPDATE offers SET title=?, description=?, code=?, image=?, is_active=? WHERE id=?");
                    $stmt->execute([$title, $description, $code, $finalImage, $is_active, $id]);
                } else {
                    $stmt = db()->prepare("UPDATE offers SET title=?, description=?, code=?, is_active=? WHERE id=?");
                    $stmt->execute([$title, $description, $code, $is_active, $id]);
                }
                $successMessage = 'Offer updated successfully!';
            }
        }
    } elseif ($action === 'delete_offer') {
        $id = (int)$_POST['id'];
        $stmt = db()->prepare("DELETE FROM offers WHERE id = ?");
        $stmt->execute([$id]);
        $successMessage = 'Offer deleted successfully!';
    } elseif ($action === 'toggle_offer') {
        $id = (int)$_POST['id'];
        $active = (int)$_POST['is_active'];
        $stmt = db()->prepare("UPDATE offers SET is_active = ? WHERE id = ?");
        $stmt->execute([$active, $id]);
        $successMessage = 'Offer status updated!';
    }
}

$coupons = db()->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
$offers = db()->query("SELECT * FROM offers ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Coupons & Offers — Zesto Admin';
$sidebarType = 'admin';
$activePage = 'offers.php';
include __DIR__ . '/../../../includes/header.php';
?>
<div class="admin-layout">
  <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
  
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-7xl">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
      <div>
        <h1 class="text-2xl font-extrabold text-[#1b1c1c]">Coupons & Promotional Offers</h1>
        <p class="text-sm text-gray-500 mt-1">Manage platform discount coupons and marketing homepage banner offers.</p>
      </div>
      <div class="flex gap-2">
        <button onclick="openCouponModal('add')" class="btn-primary flex items-center gap-2 text-sm px-4 py-2 bg-[#a83300] hover:bg-[#d24200] text-white rounded-xl font-bold transition-all">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
          Add Coupon
        </button>
        <button onclick="openOfferModal('add')" class="btn-secondary flex items-center gap-2 text-sm px-4 py-2 border border-[#a83300] text-[#a83300] hover:bg-[#ffdbd0] rounded-xl font-bold transition-all bg-white">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
          Add Banner Offer
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
      <button onclick="switchTab('coupons')" id="tab-btn-coupons" class="tab-btn pb-4 font-bold text-sm border-b-2 border-[#a83300] text-[#a83300] transition-all">
        Platform Coupons (<?= count($coupons) ?>)
      </button>
      <button onclick="switchTab('offers')" id="tab-btn-offers" class="tab-btn pb-4 font-bold text-sm text-gray-500 hover:text-gray-800 transition-all">
        Homepage Banner Offers (<?= count($offers) ?>)
      </button>
    </div>

    <!-- Coupons Tab Content -->
    <div id="tab-content-coupons" class="tab-pane">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="bg-[#f5f3f3]">
              <tr>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Coupon Code</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Discount</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Max Discount Limit</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Min Order Value</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Created</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php if (empty($coupons)): ?>
              <tr>
                <td colspan="7" class="px-6 py-10 text-center text-gray-400 font-semibold">No coupons available. Click "Add Coupon" to create one.</td>
              </tr>
              <?php endif; ?>
              <?php foreach ($coupons as $cp): ?>
              <tr class="hover:bg-[#f5f3f3]/30 transition-colors">
                <td class="px-6 py-4">
                  <div class="inline-block px-3 py-1 font-mono font-bold text-sm bg-[#ffdbd0] text-[#a83300] rounded-lg tracking-wider border border-[#ffdbd0] hover:scale-105 transition-transform duration-200">
                    <?= e($cp['code']) ?>
                  </div>
                </td>
                <td class="px-6 py-4 font-extrabold text-gray-800">
                  <?= (float)$cp['discount_percentage'] ?>% OFF
                </td>
                <td class="px-6 py-4 font-semibold text-gray-700">
                  <?= $cp['max_discount'] !== null ? formatPrice($cp['max_discount']) : 'No Limit' ?>
                </td>
                <td class="px-6 py-4 text-gray-600">
                  <?= formatPrice($cp['min_order_value']) ?>
                </td>
                <td class="px-6 py-4">
                  <form method="POST" class="inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle_coupon">
                    <input type="hidden" name="id" value="<?= $cp['id'] ?>">
                    <input type="hidden" name="is_active" value="<?= $cp['is_active'] ? 0 : 1 ?>">
                    <button class="badge <?= $cp['is_active'] ? 'badge-delivered bg-green-100 text-green-700 hover:bg-green-200' : 'badge-cancelled bg-red-100 text-red-700 hover:bg-red-200' ?> transition-colors font-bold px-3 py-1 rounded-full text-xs">
                      <?= $cp['is_active'] ? 'Active' : 'Inactive' ?>
                    </button>
                  </form>
                </td>
                <td class="px-6 py-4 text-xs text-gray-400">
                  <?= date('M j, Y', strtotime($cp['created_at'])) ?>
                </td>
                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                  <button onclick="editCoupon(<?= htmlspecialchars(json_encode($cp), ENT_QUOTES, 'UTF-8') ?>)" class="text-[#a83300] hover:text-[#d24200] font-bold text-sm">Edit</button>
                  <form method="POST" class="inline" onsubmit="return confirm('Delete this coupon? This cannot be undone.');">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete_coupon">
                    <input type="hidden" name="id" value="<?= $cp['id'] ?>">
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

    <!-- Offers Tab Content -->
    <div id="tab-content-offers" class="tab-pane hidden">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php if (empty($offers)): ?>
        <div class="col-span-full bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-gray-400 font-semibold">
          No promotional banner offers configured. Click "Add Banner Offer" to create one.
        </div>
        <?php endif; ?>
        <?php foreach ($offers as $of): ?>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
          <div>
            <!-- Banner Image Preview -->
            <?php if ($of['image']): ?>
            <div class="w-full h-40 rounded-xl overflow-hidden mb-4 bg-gray-50 border border-gray-100">
              <img src="<?= e($of['image']) ?>" alt="<?= e($of['title']) ?>" class="w-full h-full object-cover">
            </div>
            <?php else: ?>
            <div class="w-full h-40 rounded-xl bg-gradient-to-r from-[#ffdbd0] to-[#ffeadb] flex items-center justify-center mb-4 border border-[#ffdbd0]">
              <span class="text-[#a83300] font-bold text-xl tracking-wide"><?= e($of['code'] ?? 'ZESTO PROMO') ?></span>
            </div>
            <?php endif; ?>

            <!-- Title & Description -->
            <div class="flex justify-between items-start gap-4 mb-2">
              <h3 class="font-extrabold text-lg text-[#1b1c1c]"><?= e($of['title']) ?></h3>
              <form method="POST" class="inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle_offer">
                <input type="hidden" name="id" value="<?= $of['id'] ?>">
                <input type="hidden" name="is_active" value="<?= $of['is_active'] ? 0 : 1 ?>">
                <button class="badge <?= $of['is_active'] ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' ?> transition-colors px-3 py-1 rounded-full text-xs font-bold">
                  <?= $of['is_active'] ? 'Active' : 'Inactive' ?>
                </button>
              </form>
            </div>
            <p class="text-sm text-gray-600 mb-4 font-medium"><?= e($of['description']) ?></p>
            
            <?php if ($of['code']): ?>
            <div class="mb-4">
              <span class="text-xs text-gray-400 font-bold uppercase tracking-wider block mb-1">Linked Coupon</span>
              <span class="inline-block font-mono font-bold text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-md border border-gray-200"><?= e($of['code']) ?></span>
            </div>
            <?php endif; ?>
          </div>

          <!-- Card Actions -->
          <div class="flex justify-end gap-3 border-t border-gray-100 pt-4 mt-2">
            <button onclick="editOffer(<?= htmlspecialchars(json_encode($of), ENT_QUOTES, 'UTF-8') ?>)" class="text-sm px-4 py-2 border border-gray-200 rounded-xl font-bold hover:bg-gray-50 transition-colors">
              Edit Offer
            </button>
            <form method="POST" class="inline" onsubmit="return confirm('Delete this banner offer?');">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="delete_offer">
              <input type="hidden" name="id" value="<?= $of['id'] ?>">
              <button type="submit" class="text-sm px-4 py-2 border border-red-100 text-red-500 rounded-xl font-bold hover:bg-red-50 transition-colors">
                Delete
              </button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- 1. ADD / EDIT COUPON MODAL -->
<!-- ========================================== -->
<div id="coupon-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
  <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl relative translate-y-4 transition-transform duration-300">
    <button onclick="closeCouponModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    
    <h2 id="coupon-modal-title" class="text-xl font-extrabold text-[#1b1c1c] mb-6">Add New Coupon</h2>
    
    <form method="POST" id="coupon-form">
      <?= csrfField() ?>
      <input type="hidden" name="action" id="coupon-form-action" value="add_coupon">
      <input type="hidden" name="id" id="coupon-id" value="">
      
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Coupon Code</label>
          <input type="text" name="code" id="coupon-code" placeholder="e.g. EXTRA50" required
                 class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] font-mono uppercase font-bold tracking-wider">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Discount %</label>
            <input type="number" step="0.01" name="discount_percentage" id="coupon-discount" placeholder="e.g. 20" required min="1" max="100"
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] font-semibold text-gray-700">
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Max Discount (₹)</label>
            <input type="number" step="0.01" name="max_discount" id="coupon-max-discount" placeholder="Leave empty for unlimited"
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] font-semibold text-gray-700">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Min Order Value (₹)</label>
          <input type="number" step="0.01" name="min_order_value" id="coupon-min-order" placeholder="e.g. 199" required min="0"
                 class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] font-semibold text-gray-700">
        </div>

        <div class="flex items-center gap-3 pt-2">
          <input type="checkbox" name="is_active" id="coupon-active" checked value="1"
                 class="w-5 h-5 accent-[#a83300] rounded focus:ring-0">
          <label for="coupon-active" class="text-sm font-bold text-gray-700">Set coupon as active immediately</label>
        </div>
      </div>

      <div class="mt-8 flex justify-end gap-3">
        <button type="button" onclick="closeCouponModal()" class="px-5 py-3 border border-gray-200 text-gray-500 rounded-xl font-bold hover:bg-gray-50 transition-colors">
          Cancel
        </button>
        <button type="submit" class="px-6 py-3 bg-[#a83300] text-white rounded-xl font-bold hover:bg-[#d24200] transition-colors">
          Save Coupon
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================== -->
<!-- 2. ADD / EDIT OFFER MODAL -->
<!-- ========================================== -->
<div id="offer-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
  <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl relative translate-y-4 transition-transform duration-300">
    <button onclick="closeOfferModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    
    <h2 id="offer-modal-title" class="text-xl font-extrabold text-[#1b1c1c] mb-6">Add Promotional Banner</h2>
    
    <form method="POST" id="offer-form" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" id="offer-form-action" value="add_offer">
      <input type="hidden" name="id" id="offer-id" value="">
      
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Offer Title</label>
          <input type="text" name="title" id="offer-title" placeholder="e.g. 50% Off First 3 Orders" required
                 class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] font-semibold text-gray-700">
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Short Description</label>
          <textarea name="description" id="offer-desc" placeholder="Details about this discount or kitchen campaign..." rows="3"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] font-medium text-gray-700 resize-none"></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Linked Coupon Code (Optional)</label>
            <select name="code" id="offer-code"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] font-semibold text-gray-700 bg-white">
              <option value="">-- No linked coupon --</option>
              <?php foreach ($coupons as $cp): ?>
              <option value="<?= e($cp['code']) ?>"><?= e($cp['code']) ?> (<?= (float)$cp['discount_percentage'] ?>% off)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Or Custom Code Link</label>
            <input type="text" name="custom_code" id="offer-custom-code" placeholder="e.g. FESTIVE20"
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] font-mono uppercase font-bold text-gray-700">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Upload Banner Image File</label>
          <input type="file" name="image_file" accept="image/*"
                 class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#ffdbd0] file:text-[#a83300] hover:file:bg-[#ffeadb] cursor-pointer">
          <span class="text-[10px] text-gray-400 font-semibold mt-1 block">Recommended size: 800x400 JPG, PNG or WEBP (Max 5MB)</span>
        </div>

        <div class="relative flex py-2 items-center">
          <div class="flex-grow border-t border-gray-200"></div>
          <span class="flex-shrink mx-4 text-xs font-bold text-gray-400 uppercase">OR</span>
          <div class="flex-grow border-t border-gray-200"></div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Banner Image URL</label>
          <input type="url" name="image_url" id="offer-image-url" placeholder="https://example.com/banner.jpg"
                 class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-[#a83300] font-medium text-gray-700">
        </div>

        <div class="flex items-center gap-3 pt-2">
          <input type="checkbox" name="is_active" id="offer-active" checked value="1"
                 class="w-5 h-5 accent-[#a83300] rounded focus:ring-0">
          <label for="offer-active" class="text-sm font-bold text-gray-700">Display this banner immediately on home page</label>
        </div>
      </div>

      <div class="mt-8 flex justify-end gap-3">
        <button type="button" onclick="closeOfferModal()" class="px-5 py-3 border border-gray-200 text-gray-500 rounded-xl font-bold hover:bg-gray-50 transition-colors">
          Cancel
        </button>
        <button type="submit" class="px-6 py-3 bg-[#a83300] text-white rounded-xl font-bold hover:bg-[#d24200] transition-colors">
          Save Banner
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function switchTab(tab) {
  // Update buttons active states
  const btnCoupons = document.getElementById('tab-btn-coupons');
  const btnOffers = document.getElementById('tab-btn-offers');
  const paneCoupons = document.getElementById('tab-content-coupons');
  const paneOffers = document.getElementById('tab-content-offers');

  if (tab === 'coupons') {
    btnCoupons.className = "tab-btn pb-4 font-bold text-sm border-b-2 border-[#a83300] text-[#a83300] transition-all";
    btnOffers.className = "tab-btn pb-4 font-bold text-sm text-gray-500 hover:text-gray-800 transition-all";
    paneCoupons.classList.remove('hidden');
    paneOffers.classList.add('hidden');
  } else {
    btnOffers.className = "tab-btn pb-4 font-bold text-sm border-b-2 border-[#a83300] text-[#a83300] transition-all";
    btnCoupons.className = "tab-btn pb-4 font-bold text-sm text-gray-500 hover:text-gray-800 transition-all";
    paneOffers.classList.remove('hidden');
    paneCoupons.classList.add('hidden');
  }
}

// Coupons Modal Control
function openCouponModal(mode) {
  const modal = document.getElementById('coupon-modal');
  const modalTitle = document.getElementById('coupon-modal-title');
  const formAction = document.getElementById('coupon-form-action');
  
  if (mode === 'add') {
    modalTitle.innerText = "Add New Coupon";
    formAction.value = "add_coupon";
    document.getElementById('coupon-id').value = "";
    document.getElementById('coupon-code').value = "";
    document.getElementById('coupon-code').removeAttribute('readonly');
    document.getElementById('coupon-discount').value = "";
    document.getElementById('coupon-max-discount').value = "";
    document.getElementById('coupon-min-order').value = "0.00";
    document.getElementById('coupon-active').checked = true;
  }
  
  modal.classList.remove('opacity-0', 'pointer-events-none');
  modal.firstElementChild.classList.remove('translate-y-4');
}

function closeCouponModal() {
  const modal = document.getElementById('coupon-modal');
  modal.classList.add('opacity-0', 'pointer-events-none');
  modal.firstElementChild.classList.add('translate-y-4');
}

function editCoupon(cp) {
  openCouponModal('edit');
  document.getElementById('coupon-modal-title').innerText = "Edit Coupon — " + cp.code;
  document.getElementById('coupon-form-action').value = "edit_coupon";
  document.getElementById('coupon-id').value = cp.id;
  document.getElementById('coupon-code').value = cp.code;
  document.getElementById('coupon-discount').value = cp.discount_percentage;
  document.getElementById('coupon-max-discount').value = cp.max_discount || '';
  document.getElementById('coupon-min-order').value = cp.min_order_value;
  document.getElementById('coupon-active').checked = cp.is_active == 1;
}

// Offers Modal Control
function openOfferModal(mode) {
  const modal = document.getElementById('offer-modal');
  const modalTitle = document.getElementById('offer-modal-title');
  const formAction = document.getElementById('offer-form-action');
  
  if (mode === 'add') {
    modalTitle.innerText = "Add Promotional Banner";
    formAction.value = "add_offer";
    document.getElementById('offer-id').value = "";
    document.getElementById('offer-title').value = "";
    document.getElementById('offer-desc').value = "";
    document.getElementById('offer-code').value = "";
    document.getElementById('offer-custom-code').value = "";
    document.getElementById('offer-image-url').value = "";
    document.getElementById('offer-active').checked = true;
  }
  
  modal.classList.remove('opacity-0', 'pointer-events-none');
  modal.firstElementChild.classList.remove('translate-y-4');
}

function closeOfferModal() {
  const modal = document.getElementById('offer-modal');
  modal.classList.add('opacity-0', 'pointer-events-none');
  modal.firstElementChild.classList.add('translate-y-4');
}

function editOffer(of) {
  openOfferModal('edit');
  document.getElementById('offer-modal-title').innerText = "Edit Offer: " + of.title;
  document.getElementById('offer-form-action').value = "edit_offer";
  document.getElementById('offer-id').value = of.id;
  document.getElementById('offer-title').value = of.title;
  document.getElementById('offer-desc').value = of.description || '';
  
  // Decide which code option to populate
  const codeSelect = document.getElementById('offer-code');
  let matched = false;
  for (let i = 0; i < codeSelect.options.length; i++) {
    if (codeSelect.options[i].value === of.code) {
      codeSelect.selectedIndex = i;
      matched = true;
      break;
    }
  }
  
  if (!matched && of.code) {
    codeSelect.value = "";
    document.getElementById('offer-custom-code').value = of.code;
  } else {
    document.getElementById('offer-custom-code').value = "";
  }
  
  document.getElementById('offer-image-url').value = of.image || '';
  document.getElementById('offer-active').checked = of.is_active == 1;
}

// Custom code sync with Select dropdown for better UX
document.getElementById('offer-code').addEventListener('change', function() {
  if (this.value !== "") {
    document.getElementById('offer-custom-code').value = "";
  }
});
document.getElementById('offer-custom-code').addEventListener('input', function() {
  if (this.value !== "") {
    document.getElementById('offer-code').value = "";
  }
});
</script>
<?php
$noFooter = true;
include __DIR__ . '/../../../includes/footer.php';
?>
