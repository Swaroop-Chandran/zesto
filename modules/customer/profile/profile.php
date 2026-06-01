<?php
/**
 * Zesto — User Profile Page
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/location_helper.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
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
                header('Location: ' . BASE_URL . '/profile.php'); exit;
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
            header('Location: ' . BASE_URL . '/profile.php'); exit;
        }
    } elseif ($action === 'delete_address') {
        $addrId = (int)($_POST['address_id'] ?? 0);
        db()->prepare("DELETE FROM addresses WHERE id = :aid AND user_id = :uid")->execute([':aid' => $addrId, ':uid' => $userId]);
        setFlash('success', 'Address removed successfully!');
        header('Location: ' . BASE_URL . '/profile.php'); exit;
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

// Count total orders for user
$orderCountStmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :uid");
$orderCountStmt->execute([':uid' => $userId]);
$totalOrders = $orderCountStmt->fetchColumn();

$pageTitle = 'My Profile — Zesto Nights';
include __DIR__ . '/../../../includes/header.php';
include __DIR__ . '/../../../includes/navbar.php';
?>

<main class="flex-1 bg-zesto-dark font-sans text-[#dfe2eb]">
<div class="w-full max-w-4xl mx-auto px-4 sm:px-10 py-6 space-y-8 animate-fade-in text-left pb-20">

  <?php if (!empty($errors)): ?>
    <div class="p-4 bg-red-500/20 border border-red-500/50 rounded-xl text-xs text-red-200 font-semibold space-y-1">
      <?php foreach ($errors as $e): ?><p>• <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Profile Header Block resembling a glossy card -->
  <section class="relative rounded-2xl overflow-hidden glass-panel p-6 sm:p-8 border border-white/10 flex flex-col sm:flex-row items-center gap-6">
    <div class="absolute top-0 right-0 w-48 h-48 bg-zesto-orange/10 rounded-full blur-2xl"></div>
    
    <!-- User Large Avatar Icon -->
    <div class="w-20 h-20 rounded-full bg-zesto-orange/20 border border-zesto-orange/30 flex items-center justify-center text-zesto-orange relative z-10">
      <i data-lucide="user" class="w-12 h-12"></i>
    </div>

    <div class="space-y-2 flex-1 relative z-10 text-center sm:text-left">
      <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-zesto-amber/15 border border-zesto-amber/30 text-[10px] font-bold text-zesto-amber uppercase">
        👑 Premium Gourmet Night Owl
      </div>
      <h2 class="text-2xl font-display font-extrabold text-white"><?= e($userData['name']) ?></h2>
      <div class="flex flex-wrap items-center justify-center sm:justify-start gap-4 text-xs text-white/60">
        <span class="flex items-center gap-1">
          <i data-lucide="mail" class="w-3.5 h-3.5 text-white/40"></i>
          <?= e($userData['email']) ?>
        </span>
        <?php if (!empty($userData['phone'])): ?>
          <span class="flex items-center gap-1">
            <i data-lucide="phone" class="w-3.5 h-3.5 text-white/40"></i>
            <?= e($userData['phone']) ?>
          </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Orders Streak Metric -->
    <div class="glass-panel p-4 rounded-xl border border-white/5 text-center flex flex-col justify-center min-w-[120px] relative z-10">
      <span class="text-[10px] uppercase font-bold text-white/40 block">Tawa Feedings</span>
      <span class="text-3xl font-display font-black text-zesto-orange mt-1"><?= $totalOrders ?></span>
      <span class="text-[9px] text-white/50 font-medium">Late night feasts</span>
    </div>
  </section>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <!-- Saved Addresses Lists -->
    <div class="glass-panel rounded-2xl p-6 border border-white/10 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-base font-display font-extrabold text-white flex items-center gap-2">
          <i data-lucide="map-pin" class="w-4 h-4 text-zesto-orange"></i>
          <span>Saved Deliveries</span>
        </h3>
        <button onclick="document.getElementById('new-address-section').classList.toggle('hidden')" class="text-[10px] font-bold text-zesto-orange hover:text-white transition cursor-pointer bg-transparent border-none">
          + Add Address
        </button>
      </div>

      <!-- Add Address Form (Toggled) -->
      <div id="new-address-section" class="hidden bg-white/5 border border-white/10 p-4 rounded-xl space-y-4">
        <h4 class="font-extrabold text-xs text-zesto-orange uppercase tracking-wider border-b border-white/10 pb-2">New Address</h4>
        
        <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="add_address">
          
          <div class="sm:col-span-2">
            <input type="text" name="full_name" required placeholder="Full Name *" class="w-full bg-[#10141a] border border-white/10 text-white text-[11px] rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
          </div>
          <div>
            <input type="tel" name="mobile_number" required placeholder="Mobile Number *" class="w-full bg-[#10141a] border border-white/10 text-white text-[11px] rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
          </div>
          <div>
            <input type="text" name="pincode" required placeholder="Pincode *" class="w-full bg-[#10141a] border border-white/10 text-white text-[11px] rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
          </div>
          <div class="sm:col-span-2">
            <input type="text" name="flat_number" placeholder="Flat / House No. (Optional)" class="w-full bg-[#10141a] border border-white/10 text-white text-[11px] rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
          </div>
          <div class="sm:col-span-2">
            <input type="text" name="street" required placeholder="Street Address *" class="w-full bg-[#10141a] border border-white/10 text-white text-[11px] rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
          </div>
          <div>
            <input type="text" name="area" required placeholder="Area / Locality *" class="w-full bg-[#10141a] border border-white/10 text-white text-[11px] rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
          </div>
          <div>
            <input type="text" name="city" required value="<?= e(getCurrentCity()) ?>" class="w-full bg-[#10141a] border border-white/10 text-white text-[11px] rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
          </div>
          <div class="sm:col-span-2">
            <select name="address_type" class="w-full bg-[#10141a] border border-white/10 text-white text-[11px] font-semibold rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
              <option value="home">Home</option>
              <option value="work">Work</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div class="sm:col-span-2 flex gap-2 pt-1">
            <button type="submit" class="bg-zesto-orange text-white text-[10px] font-bold px-4 py-2 rounded-lg cursor-pointer hover:bg-zesto-orange/90 transition border-none flex-1">Save</button>
            <button type="button" onclick="document.getElementById('new-address-section').classList.add('hidden')" class="bg-white/10 text-white text-[10px] font-bold px-4 py-2 rounded-lg cursor-pointer hover:bg-white/20 transition border-none flex-1">Cancel</button>
          </div>
        </form>
      </div>

      <div class="space-y-3">
        <?php if (empty($addresses)): ?>
          <p class="text-xs text-white/40 py-4 text-center">No saved addresses.</p>
        <?php else: ?>
          <?php foreach ($addresses as $addr): ?>
            <div class="p-4 rounded-xl bg-white/5 border border-white/5 flex gap-3.5 items-start group hover:border-white/20 transition relative">
              <div class="p-2 rounded-lg bg-zesto-orange/15 text-zesto-orange">
                <?php if (strtolower($addr['address_type']) === 'home'): ?>
                  <i data-lucide="map-pin" class="w-4 h-4"></i>
                <?php else: ?>
                  <i data-lucide="building" class="w-4 h-4"></i>
                <?php endif; ?>
              </div>
              <div class="flex-1 pr-6">
                <h4 class="text-xs font-bold text-white uppercase"><?= e($addr['full_name']) ?> <span class="text-[9px] text-white/40 ml-1">(<?= e($addr['address_type']) ?>)</span></h4>
                <p class="text-xs text-white/80 mt-1">
                  <?= e($addr['flat_number']) ? e($addr['flat_number']) . ', ' : '' ?>
                  <?= e($addr['building_name']) ? e($addr['building_name']) . ', ' : '' ?>
                  <?= e($addr['street']) ?>, <?= e($addr['area']) ?>
                </p>
                <p class="text-[10px] text-white/40 mt-0.5">
                  <?= e($addr['city']) ?> - <?= e($addr['pincode']) ?> | <?= e($addr['mobile_number']) ?>
                </p>
              </div>
              <form method="POST" class="absolute top-4 right-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_address">
                <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                <button type="submit" onclick="return confirm('Remove this address?')" class="text-white/20 hover:text-red-400 bg-transparent border-none cursor-pointer p-1">
                  <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Edit Profile / Update Details -->
    <div class="glass-panel rounded-2xl p-6 border border-white/10 space-y-4 h-fit">
      <h3 class="text-base font-display font-extrabold text-white flex items-center gap-2">
        <i data-lucide="user" class="w-4 h-4 text-zesto-cyan"></i>
        <span>Account Details</span>
      </h3>
      
      <form method="POST" class="space-y-4">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_profile">
        
        <div>
          <label class="block text-[10px] font-bold text-white/50 mb-1.5 uppercase tracking-wider">Full Name</label>
          <input type="text" name="name" required value="<?= e($userData['name']) ?>" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
        </div>
        
        <div>
          <label class="block text-[10px] font-bold text-white/50 mb-1.5 uppercase tracking-wider">Email Address</label>
          <input type="email" name="email" required value="<?= e($userData['email']) ?>" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
        </div>

        <div>
          <label class="block text-[10px] font-bold text-white/50 mb-1.5 uppercase tracking-wider">Mobile Number</label>
          <input type="tel" name="phone" value="<?= e($userData['phone'] ?? '') ?>" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
        </div>

        <div>
          <label class="block text-[10px] font-bold text-white/50 mb-1.5 uppercase tracking-wider">New Password <span class="lowercase text-[9px] font-normal">(leave blank to keep current)</span></label>
          <input type="password" name="new_password" placeholder="Min. 8 characters" class="w-full bg-[#10141a] border border-white/10 text-white text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-zesto-orange">
        </div>

        <button type="submit" class="w-full bg-white/10 hover:bg-white/20 text-white text-xs font-bold py-2.5 rounded-lg transition border-none cursor-pointer">
          Update Profile
        </button>
      </form>
    </div>

  </div>

  <!-- Bottom Profile Options -->
  <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-white/5">
    <a href="<?= BASE_URL ?>/orders.php" class="px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-semibold text-white/90 transition cursor-pointer no-underline flex items-center gap-2">
      <i data-lucide="clock" class="w-4 h-4 text-zesto-amber"></i> View Order History
    </a>

    <a href="<?= BASE_URL ?>/api/auth/logout.php" class="px-5 py-2.5 rounded-xl bg-red-400/10 hover:bg-red-500 text-red-200 hover:text-white border border-red-500/20 text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer no-underline">
      <i data-lucide="log-out" class="w-4 h-4"></i>
      <span>Sign Out from Nights</span>
    </a>
  </div>

</div>
</main>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
