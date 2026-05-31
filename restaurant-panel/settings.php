<?php
/**
 * Zesto — Restaurant Settings & Image Uploads Panel
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/upload_helper.php';

requireRole(ROLE_RESTAURANT_OWNER);
$ownerId = getCurrentUser()['id'];

// Fetch Owner's Restaurant
$res = db()->prepare("SELECT * FROM restaurants WHERE owner_id=:oid LIMIT 1");
$res->execute([':oid' => $ownerId]);
$restaurant = $res->fetch();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $restaurant) {
    verifyCsrf();
    $name   = trim($_POST['name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $tags   = trim($_POST['tags'] ?? '');
    $city   = trim($_POST['city'] ?? 'Mumbai');
    $discount = trim($_POST['discount'] ?? '');

    // Handle Uploads
    $logoUrl = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $logoUrl = handleImageUpload($_FILES['logo'], 'logos', $errors);
    }

    $bannerUrl = null;
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] !== UPLOAD_ERR_NO_FILE) {
        $bannerUrl = handleImageUpload($_FILES['banner'], 'banners', $errors);
    }

    if (empty($name)) { $errors[] = 'Restaurant name cannot be empty.'; }

    if (empty($errors)) {
        // Build SQL
        $sql = "UPDATE restaurants SET name=:name, description=:desc, tags=:tags, city=:city, discount=:discount";
        $params = [
            ':name' => $name,
            ':desc' => $desc,
            ':tags' => $tags,
            ':city' => $city,
            ':discount' => $discount,
            ':rid' => $restaurant['id']
        ];

        if ($logoUrl) {
            $sql .= ", logo_image=:logo, image=:logo2"; // Sync both logo and default image
            $params[':logo'] = $logoUrl;
            $params[':logo2'] = $logoUrl;
        }

        if ($bannerUrl) {
            $sql .= ", banner_image=:banner";
            $params[':banner'] = $bannerUrl;
        }

        $sql .= " WHERE id=:rid";
        
        $upd = db()->prepare($sql);
        $upd->execute($params);

        setFlash('success', 'Restaurant settings saved successfully!');
        header('Location: ' . BASE_URL . '/restaurant-panel/settings.php');
        exit;
    }
}

// Fetch fresh restaurant details
if ($restaurant) {
    $res = db()->prepare("SELECT * FROM restaurants WHERE id=:rid LIMIT 1");
    $res->execute([':rid' => $restaurant['id']]);
    $restaurant = $res->fetch();
}

$pageTitle = 'Branding & Settings — Restaurant Panel';
$sidebarType = 'restaurant'; $activePage = 'settings.php';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout font-sans">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  
  <div class="flex-1 overflow-auto p-6 md:p-10 max-w-5xl">
    
    <div class="mb-8 border-b border-gray-100 pb-5">
      <span class="text-xs font-bold text-[#a83300] uppercase tracking-widest">Restaurant Panel</span>
      <h1 class="text-2xl md:text-3xl font-black text-[#1b1c1c] mt-1">Branding & Settings</h1>
    </div>

    <?php if (empty($restaurant)): ?>
    <div class="bg-[#ffdbd0]/10 border border-[#e5beb2] rounded-3xl p-8 text-center text-gray-500 text-sm">
      🍴 You do not have a restaurant associated with your owner account.
    </div>
    <?php exit; endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-xs text-red-600 font-semibold space-y-1">
      <?php foreach ($errors as $e): ?><p>• <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
      
      <!-- LEFT FORM -->
      <form method="POST" enctype="multipart/form-data" class="md:col-span-8 bg-white rounded-3xl border border-gray-150 p-6 shadow-sm flex flex-col gap-5 text-xs text-gray-600 font-semibold">
        <?= csrfField() ?>
        
        <div>
          <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Restaurant Kitchen Name *</label>
          <input type="text" name="name" required value="<?= e($restaurant['name']) ?>" class="zesto-input bg-gray-50/50 text-xs">
        </div>

        <div>
          <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Active Promotional Discount (e.g. 50% OFF)</label>
          <input type="text" name="discount" value="<?= e($restaurant['discount'] ?? '') ?>" placeholder="50% OFF | Flat ₹100 Off" class="zesto-input bg-gray-50/50 text-xs">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Cuisines / Tags (comma-separated)</label>
            <input type="text" name="tags" value="<?= e($restaurant['tags'] ?? '') ?>" placeholder="Pizza, Burgers, Italian" class="zesto-input bg-gray-50/50 text-xs">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">City Location *</label>
            <select name="city" required class="zesto-input bg-gray-50/50 text-xs font-semibold text-gray-700">
              <option value="Mumbai" <?= $restaurant['city'] === 'Mumbai' ? 'selected' : '' ?>>Mumbai</option>
              <option value="Delhi" <?= $restaurant['city'] === 'Delhi' ? 'selected' : '' ?>>Delhi</option>
              <option value="Bangalore" <?= $restaurant['city'] === 'Bangalore' ? 'selected' : '' ?>>Bangalore</option>
              <option value="Pune" <?= $restaurant['city'] === 'Pune' ? 'selected' : '' ?>>Pune</option>
              <option value="Hyderabad" <?= $restaurant['city'] === 'Hyderabad' ? 'selected' : '' ?>>Hyderabad</option>
              <option value="Chennai" <?= $restaurant['city'] === 'Chennai' ? 'selected' : '' ?>>Chennai</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Description</label>
          <textarea name="description" rows="3" placeholder="Tell customers about your kitchen's rich history, standards, and chef specials..." class="zesto-input bg-gray-50/50 text-xs resize-none"><?= e($restaurant['description'] ?? '') ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 border-t border-gray-100 pt-5">
          <div>
            <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Upload Restaurant Logo (Square)</label>
            <input type="file" name="logo" accept="image/*" class="zesto-input bg-gray-50/50 text-xs py-2 px-3 border-dashed border-2">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Upload Restaurant Banner (Landscape)</label>
            <input type="file" name="banner" accept="image/*" class="zesto-input bg-gray-50/50 text-xs py-2 px-3 border-dashed border-2">
          </div>
        </div>

        <button type="submit" class="btn-primary w-fit h-11 px-6 font-bold rounded-xl mt-3 text-xs">
          Save Settings & Images
        </button>
      </form>

      <!-- RIGHT BRANDING STATUS CARD -->
      <div class="md:col-span-4 space-y-6">
        
        <!-- Logo Preview -->
        <div class="bg-white rounded-3xl border border-gray-150 p-5 shadow-sm text-center">
          <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-50 pb-2">Logo Thumbnail</h4>
          <div class="w-24 h-24 rounded-2xl overflow-hidden border border-gray-100 shadow-sm mx-auto flex items-center justify-center p-1 bg-white">
            <img src="<?= $restaurant['logo_image'] ?: ($restaurant['image'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuACPe1OwcnqiSYz6mGkYPwpTwUZkoQT8Jeq336MHTLd5-szfhdGafbxKuJ3QVMBjxqcxm4UwTDipbBKsEECFSl_VHIJI58oJjjfYhQRcILi8-eedqeW9Mmlq_MJCKbX6yX6excKavJXTN1YruIGDT445j8SmCA9w4wNuJUqWrKgCGPpn5cc-E6Ph19OOcwM0Lu_vntB6rnd88Rr2jXfoBPCYqOX-gehGl-S_UIFfvPKeRPs0iP4Kc_0ZbV9KJ8H6mFYWZPD6gO7v2U') ?>" 
                 alt="Logo Preview" class="w-full h-full object-cover rounded-xl">
          </div>
        </div>

        <!-- Banner Preview -->
        <div class="bg-white rounded-3xl border border-gray-150 p-5 shadow-sm">
          <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-50 pb-2 text-center">Landscape Banner</h4>
          <div class="h-32 rounded-xl overflow-hidden border border-gray-100 shadow-sm relative bg-gray-50">
            <img src="<?= $restaurant['banner_image'] ?: ($restaurant['image'] ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuACPe1OwcnqiSYz6mGkYPwpTwUZkoQT8Jeq336MHTLd5-szfhdGafbxKuJ3QVMBjxqcxm4UwTDipbBKsEECFSl_VHIJI58oJjjfYhQRcILi8-eedqeW9Mmlq_MJCKbX6yX6excKavJXTN1YruIGDT445j8SmCA9w4wNuJUqWrKgCGPpn5cc-E6Ph19OOcwM0Lu_vntB6rnd88Rr2jXfoBPCYqOX-gehGl-S_UIFfvPKeRPs0iP4Kc_0ZbV9KJ8H6mFYWZPD6gO7v2U') ?>" 
                 alt="Banner Preview" class="w-full h-full object-cover">
          </div>
        </div>

      </div>

    </div>

  </div>
</div>
<?php
$noFooter = true;
include __DIR__ . '/../includes/footer.php';
?>
