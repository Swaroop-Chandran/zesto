<?php
/**
 * Zesto — Register Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) { redirectToDashboard(); }

$errors = [];
$values = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    require_once __DIR__ . '/config/database.php';

    $values['name']  = trim(filter_input(INPUT_POST, 'name',  FILTER_SANITIZE_SPECIAL_CHARS));
    $values['email'] = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $values['phone'] = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
    $values['role']  = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_SPECIAL_CHARS);
    $password        = $_POST['password']         ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($values['name']))  $errors[] = 'Full name is required.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($password) < 8)   $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirmPassword)  $errors[] = 'Passwords do not match.';

    $allowedRoles = [ROLE_CUSTOMER, ROLE_RESTAURANT_OWNER, ROLE_DELIVERY_PARTNER];
    if (!in_array($values['role'], $allowedRoles, true)) $values['role'] = ROLE_CUSTOMER;

    if (empty($errors)) {
        // Check duplicate email
        $check = db()->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $check->execute([':email' => $values['email']]);
        if ($check->fetch()) {
            $errors[] = 'This email address is already registered. Please login instead.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $ins  = db()->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (:name, :email, :pass, :phone, :role)");
            $ins->execute([
                ':name'  => $values['name'],
                ':email' => $values['email'],
                ':pass'  => $hash,
                ':phone' => $values['phone'],
                ':role'  => $values['role'],
            ]);
            $newId = db()->lastInsertId();

            // If delivery partner, create profile row
            if ($values['role'] === ROLE_DELIVERY_PARTNER) {
                db()->prepare("INSERT IGNORE INTO delivery_partners (user_id) VALUES (:uid)")->execute([':uid' => $newId]);
            }

            // Auto-login
            $user = db()->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $user->execute([':id' => $newId]);
            loginUser($user->fetch());

            setFlash('success', 'Welcome to Zesto, ' . $values['name'] . '! 🎉');
            redirectToDashboard();
        }
    }
}

$pageTitle   = 'Create Account — Zesto';
$description = 'Join Zesto to order your favourite meals, track deliveries, and enjoy exclusive offers.';
include __DIR__ . '/includes/header.php';
?>
<div class="min-h-screen bg-[#fbf9f8] flex items-center justify-center px-4 py-12">
  <div class="w-full max-w-lg">

    <div class="text-center mb-8">
      <a href="<?= BASE_URL ?>/index.php" class="text-3xl font-extrabold text-[#a83300] tracking-tight">Zesto</a>
      <p class="text-sm text-gray-500 mt-2">Create your free account</p>
    </div>

    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
      <?php if (!empty($errors)): ?>
      <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600 font-semibold space-y-1">
        <?php foreach ($errors as $err): ?>
        <p class="flex items-start gap-1.5"><span>•</span><?= e($err) ?></p>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="flex flex-col gap-5">
        <?= csrfField() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-xs font-bold text-[#1b1c1c] mb-2 uppercase tracking-wider">Full Name *</label>
            <input type="text" name="name" required value="<?= e($values['name'] ?? '') ?>"
                   placeholder="Alex Johnson" class="zesto-input">
          </div>
          <div>
            <label class="block text-xs font-bold text-[#1b1c1c] mb-2 uppercase tracking-wider">Phone</label>
            <input type="tel" name="phone" value="<?= e($values['phone'] ?? '') ?>"
                   placeholder="+1 555-0100" class="zesto-input">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#1b1c1c] mb-2 uppercase tracking-wider">Email Address *</label>
          <input type="email" name="email" required value="<?= e($values['email'] ?? '') ?>"
                 placeholder="you@example.com" class="zesto-input">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-xs font-bold text-[#1b1c1c] mb-2 uppercase tracking-wider">Password *</label>
            <input type="password" name="password" required minlength="8"
                   placeholder="Min. 8 characters" class="zesto-input">
          </div>
          <div>
            <label class="block text-xs font-bold text-[#1b1c1c] mb-2 uppercase tracking-wider">Confirm Password *</label>
            <input type="password" name="confirm_password" required
                   placeholder="Repeat password" class="zesto-input">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#1b1c1c] mb-2 uppercase tracking-wider">Join As</label>
          <div class="grid grid-cols-3 gap-3">
            <?php
            $roleOptions = [
              ['value' => ROLE_CUSTOMER,          'label' => 'Customer',          'icon' => '🧑'],
              ['value' => ROLE_RESTAURANT_OWNER,  'label' => 'Restaurant Owner',  'icon' => '🍴'],
              ['value' => ROLE_DELIVERY_PARTNER,  'label' => 'Delivery Partner',  'icon' => '🏍'],
            ];
            foreach ($roleOptions as $ro):
            $selected = ($values['role'] ?? ROLE_CUSTOMER) === $ro['value'];
            ?>
            <label class="cursor-pointer">
              <input type="radio" name="role" value="<?= e($ro['value']) ?>" class="sr-only" <?= $selected ? 'checked' : '' ?>>
              <div class="text-center p-3 border-2 rounded-xl text-sm font-semibold transition-all
                          <?= $selected ? 'border-[#a83300] bg-[#ffdbd0] text-[#a83300]' : 'border-gray-200 hover:border-[#a83300]' ?>
                          role-option-card">
                <div class="text-xl mb-1"><?= $ro['icon'] ?></div>
                <p class="text-xs"><?= e($ro['label']) ?></p>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <button type="submit"
                class="btn-primary w-full justify-center py-3.5 rounded-xl text-sm mt-2">
          Create My Account 🎉
        </button>
      </form>
    </div>

    <p class="text-center text-sm text-gray-500 mt-6">
      Already have an account?
      <a href="<?= BASE_URL ?>/login.php" class="text-[#a83300] font-bold hover:underline">Sign in →</a>
    </p>
  </div>
</div>
<script>
// Radio card styling
document.querySelectorAll('input[name="role"]').forEach(radio => {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.role-option-card').forEach(card => {
      card.classList.remove('border-[#a83300]','bg-[#ffdbd0]','text-[#a83300]');
      card.classList.add('border-gray-200');
    });
    this.nextElementSibling.classList.add('border-[#a83300]','bg-[#ffdbd0]','text-[#a83300]');
    this.nextElementSibling.classList.remove('border-gray-200');
  });
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
