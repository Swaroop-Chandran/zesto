<?php
/**
 * Zesto — Dynamic Swiggy-Style Profile Dashboard (profile.php)
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/location_helper.php';

// Soft auth gate — show drawer instead of hard redirect
if (!isLoggedIn()) {
    $pageTitle = 'My Profile — Zesto';
    include __DIR__ . '/../../../includes/header.php';
    include __DIR__ . '/../../../includes/navbar.php';
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof ZestoAuth !== 'undefined') ZestoAuth.open();
    });
    </script>
    <main class="flex-1 pb-16 md:pb-8">
    <div class="max-w-2xl mx-auto px-6 py-24 text-center flex flex-col items-center gap-6">
      <div class="w-20 h-20 bg-[#ffdbd0]/70 rounded-full flex items-center justify-center text-3xl">👤</div>
      <h1 class="text-2xl font-black text-[#1b1c1c]">Sign in to view your profile</h1>
      <p class="text-sm text-gray-500 max-w-sm">Access your saved addresses, order history and account settings.</p>
      <button onclick="ZestoAuth.open()" class="btn-primary px-8">Sign In to Continue</button>
      <a href="<?= BASE_URL ?>/index.php" class="text-xs text-gray-400 hover:text-[#a83300] font-semibold">← Back to Home</a>
    </div>
    </main>
    <?php include __DIR__ . '/../../../includes/footer.php'; ?>
    <?php exit; ?>
<?php
}

$user = getCurrentUser();
$userId = $user['id'];
$errors = [];

// Handle dynamic profile Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $name  = trim(filter_input(INPUT_POST, 'name',  FILTER_SANITIZE_SPECIAL_CHARS));
        $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $newPass = $_POST['new_password'] ?? '';

        if (empty($name))  $errors[] = 'Name cannot be empty.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';

        if (empty($errors)) {
            if (!empty($newPass)) {
                if (strlen($newPass) < 8) { $errors[] = 'New password must be 8+ characters.'; }
                else {
                    $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                    db()->prepare("UPDATE users SET name=:n,email=:e,phone=:p,password=:h WHERE id=:id")
                       ->execute([':n'=>$name,':e'=>$email,':p'=>$phone,':h'=>$hash,':id'=>$userId]);
                }
            } else {
                db()->prepare("UPDATE users SET name=:n,email=:e,phone=:p WHERE id=:id")
                   ->execute([':n'=>$name,':e'=>$email,':p'=>$phone,':id'=>$userId]);
            }
            if (empty($errors)) {
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;
                setFlash('success', 'Profile updated successfully!');
                header('Location: ' . BASE_URL . '/profile.php?tab=info'); exit;
            }
        }
    } elseif ($action === 'add_address') {
        $fullName     = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS));
        $mobileNumber = trim(filter_input(INPUT_POST, 'mobile_number', FILTER_SANITIZE_SPECIAL_CHARS));
        $flatNumber   = trim(filter_input(INPUT_POST, 'flat_number', FILTER_SANITIZE_SPECIAL_CHARS));
        $buildingName = trim(filter_input(INPUT_POST, 'building_name', FILTER_SANITIZE_SPECIAL_CHARS));
        $street       = trim(filter_input(INPUT_POST, 'street', FILTER_SANITIZE_SPECIAL_CHARS));
        $area         = trim(filter_input(INPUT_POST, 'area', FILTER_SANITIZE_SPECIAL_CHARS));
        $landmark     = trim(filter_input(INPUT_POST, 'landmark', FILTER_SANITIZE_SPECIAL_CHARS));
        $cityVal      = trim(filter_input(INPUT_POST, 'city', FILTER_SANITIZE_SPECIAL_CHARS));
        $stateVal     = trim(filter_input(INPUT_POST, 'state', FILTER_SANITIZE_SPECIAL_CHARS));
        $pincode      = trim(filter_input(INPUT_POST, 'pincode', FILTER_SANITIZE_SPECIAL_CHARS));
        $addrType     = trim(filter_input(INPUT_POST, 'address_type', FILTER_SANITIZE_SPECIAL_CHARS));

        if (empty($fullName) || empty($mobileNumber) || empty($street) || empty($area) || empty($cityVal) || empty($stateVal) || empty($pincode)) {
            $errors[] = 'Please fill out all required address fields.';
        }

        if (empty($errors)) {
            $ins = db()->prepare("
                INSERT INTO addresses (user_id, full_name, mobile_number, flat_number, building_name, street, area, landmark, city, state, pincode, address_type)
                VALUES (:uid, :fname, :mobile, :flat, :build, :street, :area, :landmark, :city, :state, :pin, :type)
            ");
            $ins->execute([
                ':uid' => $userId,
                ':fname' => $fullName,
                ':mobile' => $mobileNumber,
                ':flat' => $flatNumber,
                ':build' => $buildingName,
                ':street' => $street,
                ':area' => $area,
                ':landmark' => $landmark,
                ':city' => $cityVal,
                ':state' => $stateVal,
                ':pin' => $pincode,
                ':type' => $addrType
            ]);
            setFlash('success', 'New address saved successfully!');
            header('Location: ' . BASE_URL . '/profile.php?tab=addresses'); exit;
        }
    } elseif ($action === 'delete_address') {
        $addrId = (int)($_POST['address_id'] ?? 0);
        db()->prepare("DELETE FROM addresses WHERE id = :aid AND user_id = :uid")->execute([':aid' => $addrId, ':uid' => $userId]);
        setFlash('success', 'Address removed successfully!');
        header('Location: ' . BASE_URL . '/profile.php?tab=addresses'); exit;
    }
}

// Fetch fresh user data
$userData = db()->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$userData->execute([':id' => $userId]);
$userData = $userData->fetch();

// Fetch saved addresses
$addresses = db()->prepare("SELECT * FROM addresses WHERE user_id = :uid ORDER BY id DESC");
$addresses->execute([':uid' => $userId]);
$addresses = $addresses->fetchAll();

// Fetch Order History
$ordersStmt = db()->prepare("
    SELECT o.*, r.name AS restaurant_name, r.slug AS restaurant_slug
    FROM orders o
    JOIN restaurants r ON r.id = o.restaurant_id
    WHERE o.user_id = :uid
    ORDER BY o.created_at DESC
");
$ordersStmt->execute([':uid' => $userId]);
$orders = $ordersStmt->fetchAll();

// Active Tab
$activeTab = $_GET['tab'] ?? 'info';
$validTabs = ['info', 'addresses', 'orders', 'favourites', 'notifications', 'location'];
if (!in_array($activeTab, $validTabs, true)) $activeTab = 'info';

$pageTitle = 'My Profile Dashboard — Zesto';
include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/navbar.php';
?>

<main class="flex-1 pb-16 md:pb-8 bg-[#fbf9f8]">
<div class="max-w-[1280px] mx-auto px-6 md:px-10 py-10 font-sans">
  
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 md:gap-10 items-start">
    
    <!-- LEFT SIDEBAR: PROFILE SUMMARY & TABS NAVIGATION -->
    <div class="lg:col-span-4 flex flex-col gap-6">
      
      <!-- Avatar Card -->
      <div class="bg-white rounded-3xl border border-gray-150 p-6 shadow-sm flex items-center gap-5">
        <div class="w-16 h-16 rounded-2xl bg-[#ffdbd0] flex items-center justify-center text-[#a83300] font-black text-xl shrink-0">
          <?= strtoupper(substr($userData['name'], 0, 1)) ?>
        </div>
        <div class="min-w-0">
          <h2 class="text-base font-extrabold text-[#1b1c1c] truncate"><?= e($userData['name']) ?></h2>
          <p class="text-xs text-gray-500 truncate"><?= e($userData['email']) ?></p>
          <span class="inline-block mt-1.5 px-3 py-0.5 bg-[#ffdbd0] text-[#a83300] text-[9px] font-bold rounded-full uppercase tracking-wider"><?= e(str_replace('_', ' ', $userData['role'])) ?></span>
        </div>
      </div>

      <!-- Tab Buttons Links -->
      <div class="bg-white rounded-3xl border border-gray-150 p-4 space-y-1 shadow-sm font-semibold text-xs text-gray-600">
        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-3 mb-3">User Options</h4>
        
        <button onclick="switchTab('info')" id="tab-btn-info" class="w-full text-left px-4 py-3 rounded-xl flex items-center gap-3 hover:bg-[#ffdbd0] hover:text-[#a83300] transition-all cursor-pointer">
          👤 Personal Information
        </button>
        <button onclick="switchTab('addresses')" id="tab-btn-addresses" class="w-full text-left px-4 py-3 rounded-xl flex items-center gap-3 hover:bg-[#ffdbd0] hover:text-[#a83300] transition-all cursor-pointer">
          📍 Saved Addresses
        </button>
        <button onclick="switchTab('orders')" id="tab-btn-orders" class="w-full text-left px-4 py-3 rounded-xl flex items-center gap-3 hover:bg-[#ffdbd0] hover:text-[#a83300] transition-all cursor-pointer">
          📦 My Order History
        </button>
        <button onclick="switchTab('favourites')" id="tab-btn-favourites" class="w-full text-left px-4 py-3 rounded-xl flex items-center gap-3 hover:bg-[#ffdbd0] hover:text-[#a83300] transition-all cursor-pointer">
          ❤️ Favorite Restaurants
        </button>
        <button onclick="switchTab('notifications')" id="tab-btn-notifications" class="w-full text-left px-4 py-3 rounded-xl flex items-center gap-3 hover:bg-[#ffdbd0] hover:text-[#a83300] transition-all cursor-pointer">
          🔔 Notifications
        </button>
        <button onclick="switchTab('location')" id="tab-btn-location" class="w-full text-left px-4 py-3 rounded-xl flex items-center gap-3 hover:bg-[#ffdbd0] hover:text-[#a83300] transition-all cursor-pointer">
          🌍 Location Settings
        </button>
      </div>

    </div>

    <!-- RIGHT SECTION: DYNAMIC CONTENT PANEL -->
    <div class="lg:col-span-8 bg-white rounded-3xl border border-gray-150 p-6 md:p-8 shadow-sm">
      
      <?php if (!empty($errors)): ?>
      <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-xs text-red-600 font-semibold space-y-1">
        <?php foreach ($errors as $e): ?><p>• <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- ── TAB: PERSONAL INFORMATION ─────────────────────────── -->
      <div id="tab-panel-info" class="tab-panel hidden space-y-6">
        <div>
          <h2 class="text-lg font-black text-[#1b1c1c] tracking-tight uppercase border-b border-gray-100 pb-2">Personal Information</h2>
          <p class="text-xs text-gray-400 mt-1">Keep your profile details and security passwords updated</p>
        </div>

        <form method="POST" class="flex flex-col gap-5">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="update_profile">

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Full Name</label>
              <input type="text" name="name" required value="<?= e($userData['name']) ?>" class="zesto-input">
            </div>
            <div>
              <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Mobile Number</label>
              <input type="tel" name="phone" value="<?= e($userData['phone'] ?? '') ?>" class="zesto-input">
            </div>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Email Address</label>
            <input type="email" name="email" required value="<?= e($userData['email']) ?>" class="zesto-input">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">New Password <span class="text-gray-400 normal-case font-normal">(leave blank to keep current)</span></label>
            <input type="password" name="new_password" minlength="8" placeholder="Enter new password (min. 8 chars)" class="zesto-input">
          </div>
          <button type="submit" class="btn-primary w-fit h-11 px-6 font-bold rounded-xl text-xs">
            Save Profile Info
          </button>
        </form>
      </div>

      <!-- ── TAB: SAVED ADDRESSES ──────────────────────────────── -->
      <div id="tab-panel-addresses" class="tab-panel hidden space-y-6">
        <div class="flex justify-between items-center border-b border-gray-100 pb-3 flex-wrap gap-4">
          <div>
            <h2 class="text-lg font-black text-[#1b1c1c] tracking-tight uppercase">Saved Addresses</h2>
            <p class="text-xs text-gray-400 mt-1">Manage multiple addresses for faster food delivery</p>
          </div>
          <button onclick="document.getElementById('new-address-section').classList.toggle('hidden')" class="btn-primary text-[10px] font-bold h-9 px-4 rounded-lg cursor-pointer">
            + Add Address
          </button>
        </div>

        <!-- Add Address Form (Toggled) -->
        <div id="new-address-section" class="hidden bg-gray-50 border border-[#e5beb2]/50 p-5 rounded-2xl space-y-4">
          <h3 class="font-extrabold text-xs text-[#a83300] uppercase tracking-wider border-b border-gray-100 pb-2">Add New Location Address</h3>
          
          <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_address">

            <div>
              <label class="block text-[9px] font-bold text-gray-600 mb-1 uppercase">Full Name *</label>
              <input type="text" name="full_name" required placeholder="Alex Johnson" class="zesto-input bg-white">
            </div>
            <div>
              <label class="block text-[9px] font-bold text-gray-600 mb-1 uppercase">Mobile Number *</label>
              <input type="tel" name="mobile_number" required placeholder="+91 98765 43210" class="zesto-input bg-white">
            </div>
            <div>
              <label class="block text-[9px] font-bold text-gray-600 mb-1 uppercase">Flat/House Number</label>
              <input type="text" name="flat_number" placeholder="Flat No. 402" class="zesto-input bg-white">
            </div>
            <div>
              <label class="block text-[9px] font-bold text-gray-600 mb-1 uppercase">Building Name</label>
              <input type="text" name="building_name" placeholder="Skyline Towers" class="zesto-input bg-white">
            </div>
            <div class="sm:col-span-2">
              <label class="block text-[9px] font-bold text-gray-600 mb-1 uppercase">Street Address *</label>
              <input type="text" name="street" required placeholder="Main Street Road" class="zesto-input bg-white">
            </div>
            <div>
              <label class="block text-[9px] font-bold text-gray-600 mb-1 uppercase">Area / Locality *</label>
              <input type="text" name="area" required placeholder="Andheri West" class="zesto-input bg-white">
            </div>
            <div>
              <label class="block text-[9px] font-bold text-gray-600 mb-1 uppercase">Landmark</label>
              <input type="text" name="landmark" placeholder="Near Railway Station" class="zesto-input bg-white">
            </div>
            <div>
              <label class="block text-[9px] font-bold text-gray-600 mb-1 uppercase">City *</label>
              <input type="text" name="city" required value="<?= e(getCurrentCity()) ?>" class="zesto-input bg-white">
            </div>
            <div>
              <label class="block text-[9px] font-bold text-gray-600 mb-1 uppercase">State *</label>
              <input type="text" name="state" required placeholder="Maharashtra" class="zesto-input bg-white">
            </div>
            <div>
              <label class="block text-[9px] font-bold text-gray-600 mb-1 uppercase">Pincode *</label>
              <input type="text" name="pincode" required placeholder="400053" class="zesto-input bg-white">
            </div>
            <div>
              <label class="block text-[9px] font-bold text-gray-600 mb-1 uppercase">Address Type *</label>
              <select name="address_type" class="zesto-input bg-white text-xs font-semibold">
                <option value="home">Home (All Day)</option>
                <option value="work">Work (Office Hours)</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div class="sm:col-span-2 flex gap-3 pt-2">
              <button type="submit" class="btn-primary text-xs font-bold px-5 py-2.5 rounded-xl">Save Address</button>
              <button type="button" onclick="document.getElementById('new-address-section').classList.add('hidden')" class="btn-secondary text-xs font-bold px-5 py-2.5 rounded-xl">Cancel</button>
            </div>
          </form>
        </div>

        <!-- Saved Address Cards list -->
        <?php if (empty($addresses)): ?>
        <div class="bg-gray-50 border border-dashed rounded-3xl p-8 text-center text-gray-400 text-xs">
          📍 You have no saved delivery addresses. Create one to enable quick checkout.
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <?php foreach ($addresses as $addr): ?>
          <div class="bg-white border border-gray-200 p-5 rounded-2xl flex flex-col justify-between shadow-sm relative group hover:border-[#a83300] transition-all">
            <span class="absolute top-4 right-4 text-[9px] uppercase font-black px-2 py-0.5 rounded bg-[#ffdbd0] text-[#a83300]">
              <?= e($addr['address_type']) ?>
            </span>
            
            <div class="space-y-2.5">
              <div class="flex items-center gap-1.5 font-bold text-xs text-gray-800">
                <span>🏠</span>
                <h4><?= e($addr['full_name']) ?></h4>
              </div>
              <p class="text-xs text-gray-500 leading-relaxed">
                <?= e($addr['flat_number']) ? e($addr['flat_number']) . ', ' : '' ?>
                <?= e($addr['building_name']) ? e($addr['building_name']) . ', ' : '' ?>
                <?= e($addr['street']) ?>, <?= e($addr['area']) ?><br>
                <?= e($addr['landmark']) ? 'Landmark: ' . e($addr['landmark']) . ', ' : '' ?>
                <?= e($addr['city']) ?>, <?= e($addr['state']) ?> - <span class="font-semibold text-gray-700"><?= e($addr['pincode']) ?></span>
              </p>
              <p class="text-[10px] text-gray-400 font-semibold">📞 Mobile: <?= e($addr['mobile_number']) ?></p>
            </div>

            <form method="POST" class="mt-4 border-t border-gray-100 pt-3 flex justify-end">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="delete_address">
              <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
              <button type="submit" data-confirm="Remove this saved address?" class="text-red-500 hover:text-red-600 font-extrabold text-[10px] uppercase tracking-wider bg-transparent border-none cursor-pointer">
                Delete Address
              </button>
            </form>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- ── TAB: MY ORDER HISTORY ─────────────────────────────── -->
      <div id="tab-panel-orders" class="tab-panel hidden space-y-6">
        <div>
          <h2 class="text-lg font-black text-[#1b1c1c] tracking-tight uppercase border-b border-gray-100 pb-2">My Orders History</h2>
          <p class="text-xs text-gray-400 mt-1">Review receipts and track active dinner orders</p>
        </div>

        <?php if (empty($orders)): ?>
        <div class="bg-gray-50 border border-dashed rounded-3xl p-10 text-center text-gray-400 text-xs">
          📦 No past orders found. Let's get lunch going!
        </div>
        <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($orders as $ord): ?>
          <div class="bg-white border border-gray-150 p-5 rounded-2xl hover:border-gray-300 transition-colors shadow-sm flex flex-col sm:flex-row justify-between gap-4">
            <div class="space-y-1">
              <div class="flex items-center gap-3">
                <span class="font-black text-[#a83300] text-sm"><?= e($ord['order_number']) ?></span>
                <span class="badge badge-<?= e($ord['order_status']) ?>"><?= e(str_replace('_', ' ', $ord['order_status'])) ?></span>
              </div>
              <p class="font-extrabold text-xs text-gray-700 mt-1">from <?= e($ord['restaurant_name']) ?></p>
              <p class="text-[10px] text-gray-400"><?= date('M j, Y — g:i A', strtotime($ord['created_at'])) ?></p>
            </div>
            
            <div class="flex flex-col items-end gap-2 shrink-0 justify-between">
              <span class="font-black text-[#a83300] text-base"><?= formatPrice($ord['total']) ?></span>
              <a href="<?= BASE_URL ?>/checkout.php?order=<?= e($ord['order_number']) ?>" class="text-[10px] font-bold text-gray-500 hover:text-[#a83300] flex items-center gap-1">
                View Receipt ➔
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- ── TAB: FAVORITE RESTAURANTS ────────────────────────── -->
      <div id="tab-panel-favourites" class="tab-panel hidden space-y-6">
        <div>
          <h2 class="text-lg font-black text-[#1b1c1c] tracking-tight uppercase border-b border-gray-100 pb-2">Favorite Restaurants</h2>
          <p class="text-xs text-gray-400 mt-1">Your saved partner kitchens for quick reference</p>
        </div>
        
        <div class="bg-gray-50 border border-dashed rounded-3xl p-10 text-center text-gray-400 text-xs">
          ❤️ You have no saved favorites. Click the heart buttons on restaurant pages to save them here!
        </div>
      </div>

      <!-- ── TAB: NOTIFICATIONS ────────────────────────────────── -->
      <div id="tab-panel-notifications" class="tab-panel hidden space-y-6">
        <div>
          <h2 class="text-lg font-black text-[#1b1c1c] tracking-tight uppercase border-b border-gray-100 pb-2">Notifications</h2>
          <p class="text-xs text-gray-400 mt-1">Live tracking and order update alerts</p>
        </div>

        <div class="space-y-4">
          <div class="p-4 bg-[#ffdbd0]/10 border border-[#e5beb2]/30 rounded-2xl flex gap-3.5">
            <span class="text-xl">🎉</span>
            <div>
              <h4 class="font-bold text-xs text-gray-800">Welcome to dynamic Zesto v1.0!</h4>
              <p class="text-[10px] text-gray-550 mt-0.5">Your Swiggy-style delivery workflow is active. Explore cuisines, detect location and checkout securely.</p>
              <span class="block text-[8px] text-gray-400 mt-1">June 1, 2026</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── TAB: LOCATION SETTINGS ────────────────────────────── -->
      <div id="tab-panel-location" class="tab-panel hidden space-y-6">
        <div>
          <h2 class="text-lg font-black text-[#1b1c1c] tracking-tight uppercase border-b border-gray-100 pb-2">Location Settings</h2>
          <p class="text-xs text-gray-400 mt-1">Configure active location parameters</p>
        </div>

        <div class="bg-gray-50 border p-5 rounded-2xl flex flex-col sm:flex-row justify-between items-center gap-4">
          <div class="flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-full bg-[#ffdbd0] flex items-center justify-center shrink-0">📍</div>
            <div>
              <p class="text-[10px] font-bold text-gray-400 uppercase">Active Delivery Zone</p>
              <h4 class="font-extrabold text-sm text-[#1b1c1c] mt-0.5"><?= e($locName) ?></h4>
            </div>
          </div>
          <button onclick="Zesto.modal.open('location-modal')" class="btn-primary text-[10px] font-bold h-9 px-4 rounded-lg cursor-pointer">
            Change Zone
          </button>
        </div>
      </div>

    </div>

  </div>

</div>
</main>

<script>
// Dynamic client side Switch Tab
function switchTab(tabName) {
  // Hide all panels
  document.querySelectorAll('.tab-panel').forEach(panel => {
    panel.classList.add('hidden');
  });

  // Remove active styling on all buttons
  document.querySelectorAll('[id^="tab-btn-"]').forEach(btn => {
    btn.classList.remove('bg-[#ffdbd0]', 'text-[#a83300]');
  });

  // Show selected panel
  const activePanel = document.getElementById('tab-panel-' + tabName);
  if (activePanel) {
    activePanel.classList.remove('hidden');
  }

  // Add active style to selected button
  const activeBtn = document.getElementById('tab-btn-' + tabName);
  if (activeBtn) {
    activeBtn.classList.add('bg-[#ffdbd0]', 'text-[#a83300]');
  }

  // Update URL search query cleanly
  const url = new URL(window.location.href);
  url.searchParams.set('tab', tabName);
  window.history.replaceState({}, '', url);
}

// Initialize active tab from query param
document.addEventListener('DOMContentLoaded', function() {
  const active = "<?= $activeTab ?>";
  switchTab(active);
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
