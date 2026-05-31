<?php
/**
 * Zesto — Delivery Partner Registration Portal
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/upload_helper.php';

if (isLoggedIn()) { redirectToDashboard(); }

$errors = [];
$values = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $values['name']    = trim(filter_input(INPUT_POST, 'name',    FILTER_SANITIZE_SPECIAL_CHARS));
    $values['email']   = trim(filter_input(INPUT_POST, 'email',   FILTER_SANITIZE_EMAIL));
    $values['phone']   = trim(filter_input(INPUT_POST, 'phone',   FILTER_SANITIZE_SPECIAL_CHARS));
    $values['address'] = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS));
    $password          = $_POST['password'] ?? '';
    $confirmPassword   = $_POST['confirm_password'] ?? '';
    
    $values['vehicle_type']   = trim(filter_input(INPUT_POST, 'vehicle_type', FILTER_SANITIZE_SPECIAL_CHARS));
    $values['vehicle_number'] = trim(filter_input(INPUT_POST, 'vehicle_number', FILTER_SANITIZE_SPECIAL_CHARS));
    $values['license_number'] = trim(filter_input(INPUT_POST, 'license_number', FILTER_SANITIZE_SPECIAL_CHARS));
    
    $values['bank_name']    = trim(filter_input(INPUT_POST, 'bank_name',    FILTER_SANITIZE_SPECIAL_CHARS));
    $values['bank_acc_num'] = trim(filter_input(INPUT_POST, 'bank_acc_num', FILTER_SANITIZE_SPECIAL_CHARS));
    $values['bank_ifsc']    = trim(filter_input(INPUT_POST, 'bank_ifsc',    FILTER_SANITIZE_SPECIAL_CHARS));

    // Upload Files
    $licenseUrl = null;
    if (isset($_FILES['license_file']) && $_FILES['license_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $licenseUrl = handleImageUpload($_FILES['license_file'], 'docs', $errors);
    } else {
        $errors[] = 'Driving License upload is required.';
    }

    $selfieUrl = null;
    if (isset($_FILES['selfie_file']) && $_FILES['selfie_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $selfieUrl = handleImageUpload($_FILES['selfie_file'], 'selfies', $errors);
    } else {
        $errors[] = 'Selfie photo upload is required.';
    }

    // Standard Validation
    if (empty($values['name']))  $errors[] = 'Name is required.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email address is required.';
    if (strlen($password) < 8)   $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';
    
    if (empty($errors)) {
        // Check duplicate email + role
        $check = db()->prepare("SELECT id FROM users WHERE email = :email AND role = 'delivery_partner' LIMIT 1");
        $check->execute([':email' => $values['email']]);
        if ($check->fetch()) {
            $errors[] = 'A delivery partner account with this email address already exists.';
        } else {
            // Insert user as inactive (pending approval)
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $insUser = db()->prepare("
                INSERT INTO users (name, email, password, phone, role, is_active)
                VALUES (:name, :email, :pass, :phone, 'delivery_partner', 0)
            ");
            $insUser->execute([
                ':name'  => $values['name'],
                ':email' => $values['email'],
                ':pass'  => $hash,
                ':phone' => $values['phone']
            ]);
            $newId = db()->lastInsertId();

            // Pack Bank details as JSON
            $bankDetails = json_encode([
                'bank_name' => $values['bank_name'],
                'account_number' => $values['bank_acc_num'],
                'ifsc_code' => $values['bank_ifsc']
            ]);

            // Insert partner details
            $insPartner = db()->prepare("
                INSERT INTO delivery_partners (user_id, vehicle_type, vehicle_number, driving_license_number, driving_license_image, selfie_image, bank_details, address, is_approved, is_available)
                VALUES (:uid, :vtype, :vnum, :lnum, :limg, :simg, :bank, :addr, 0, 0)
            ");
            $insPartner->execute([
                ':uid'   => $newId,
                ':vtype' => $values['vehicle_type'],
                ':vnum'  => $values['vehicle_number'],
                ':lnum'  => $values['license_number'],
                ':limg'  => $licenseUrl ?: '',
                ':simg'  => $selfieUrl ?: '',
                ':bank'  => $bankDetails,
                ':addr'  => $values['address']
            ]);

            setFlash('success', 'Application submitted successfully! Our administrators will review and activate your account shortly.');
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
}

$pageTitle = 'Join Zesto Fleet — Delivery Partner Registration';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<main class="flex-1 pb-16 md:pb-8 bg-[#fbf9f8] font-sans">
<div class="max-w-2xl mx-auto px-6 py-10 md:py-16">
  
  <div class="text-center mb-8">
    <a href="<?= BASE_URL ?>/index.php" class="text-3xl font-black text-[#a83300]">Zesto Fleet</a>
    <h1 class="text-xl font-extrabold text-gray-800 mt-2">Become a Delivery Partner</h1>
    <p class="text-xs text-gray-550 mt-1">Deliver food, earn competitive rates, and control your own schedule.</p>
  </div>

  <div class="bg-white rounded-3xl border border-gray-150 p-6 md:p-8 shadow-md">
    
    <?php if (!empty($errors)): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-xs text-red-650 font-semibold space-y-1">
      <?php foreach ($errors as $e): ?><p>• <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-6 text-xs text-gray-600 font-semibold">
      <?= csrfField() ?>

      <!-- Section: Personal Info -->
      <div class="space-y-4">
        <h3 class="font-extrabold text-sm text-[#a83300] uppercase tracking-wider border-b border-gray-100 pb-2">1. Personal Profile</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">Full Name *</label>
            <input type="text" name="name" required value="<?= e($values['name'] ?? '') ?>" placeholder="Alex Johnson" class="zesto-input bg-gray-50/50">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">Mobile Number *</label>
            <input type="tel" name="phone" required value="<?= e($values['phone'] ?? '') ?>" placeholder="+91 98765 43210" class="zesto-input bg-gray-50/50">
          </div>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">Email Address *</label>
          <input type="email" name="email" required value="<?= e($values['email'] ?? '') ?>" placeholder="alex@example.com" class="zesto-input bg-gray-50/50">
        </div>
        <div>
          <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">Full Permanent Address *</label>
          <textarea name="address" required rows="2" placeholder="Street name, house details, pincode..." class="zesto-input bg-gray-50/50 resize-none"><?= e($values['address'] ?? '') ?></textarea>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">Password *</label>
            <input type="password" name="password" required minlength="8" placeholder="Create strong password" class="zesto-input bg-gray-50/50">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">Confirm Password *</label>
            <input type="password" name="confirm_password" required placeholder="Re-enter password" class="zesto-input bg-gray-50/50">
          </div>
        </div>
      </div>

      <!-- Section: Vehicle & License Details -->
      <div class="space-y-4">
        <h3 class="font-extrabold text-sm text-[#a83300] uppercase tracking-wider border-b border-gray-100 pb-2">2. Vehicle & Driving Credentials</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">Vehicle Type *</label>
            <select name="vehicle_type" class="zesto-input bg-gray-50/50 font-semibold text-gray-700">
              <option value="bike">Motorcycle / Scooter</option>
              <option value="bicycle">Bicycle</option>
              <option value="car">Car</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">Vehicle Plate Number *</label>
            <input type="text" name="vehicle_number" required value="<?= e($values['vehicle_number'] ?? '') ?>" placeholder="MH 02 AA 1234" class="zesto-input bg-gray-50/50">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">Driving License Number *</label>
            <input type="text" name="license_number" required value="<?= e($values['license_number'] ?? '') ?>" placeholder="DL-1420110012345" class="zesto-input bg-gray-50/50">
          </div>
        </div>
      </div>

      <!-- Section: Onboarding Document Uploads -->
      <div class="space-y-4">
        <h3 class="font-extrabold text-sm text-[#a83300] uppercase tracking-wider border-b border-gray-100 pb-2">3. Document Uploads</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">Driving License Image File *</label>
            <input type="file" name="license_file" required accept="image/*" class="zesto-input bg-gray-50/50 py-2 border-dashed border-2">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">Partner Selfie Photo (PNG/JPG) *</label>
            <input type="file" name="selfie_file" required accept="image/*" class="zesto-input bg-gray-50/50 py-2 border-dashed border-2">
          </div>
        </div>
      </div>

      <!-- Section: Bank Account Credentials -->
      <div class="space-y-4">
        <h3 class="font-extrabold text-sm text-[#a83300] uppercase tracking-wider border-b border-gray-100 pb-2">4. Bank Account Details (for Earnings Transfers)</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">Bank Name *</label>
            <input type="text" name="bank_name" required value="<?= e($values['bank_name'] ?? '') ?>" placeholder="State Bank of India" class="zesto-input bg-gray-50/50">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">Account Number *</label>
            <input type="text" name="bank_acc_num" required value="<?= e($values['bank_acc_num'] ?? '') ?>" placeholder="100012345678" class="zesto-input bg-gray-50/50">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-650 mb-1.5 uppercase">IFSC Code *</label>
            <input type="text" name="bank_ifsc" required value="<?= e($values['bank_ifsc'] ?? '') ?>" placeholder="SBIN0001234" class="zesto-input bg-gray-50/50">
          </div>
        </div>
      </div>

      <button type="submit" class="w-full btn-primary h-12 justify-center font-bold tracking-wide mt-4 rounded-xl text-xs">
        Submit Registration Application 🎉
      </button>
    </form>
  </div>
</div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
