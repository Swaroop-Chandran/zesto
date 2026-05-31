<?php
/**
 * Zesto — Login Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';

// Redirect already-logged-in users
if (isLoggedIn()) { redirectToDashboard(); }

$error    = '';
$redirect = filter_input(INPUT_GET, 'redirect', FILTER_SANITIZE_URL) ?: BASE_URL . '/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        require_once __DIR__ . '/config/database.php';
        $stmt = db()->prepare("SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            setFlash('success', 'Welcome back, ' . $user['name'] . '! 👋');
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

$pageTitle   = 'Login — Zesto';
$description = 'Sign in to your Zesto account to order food, track deliveries, and manage your profile.';
include __DIR__ . '/includes/header.php';
?>
<div class="min-h-screen bg-[#fbf9f8] flex items-center justify-center px-4 py-12">
  <div class="w-full max-w-md">

    <!-- Logo -->
    <div class="text-center mb-8">
      <a href="<?= BASE_URL ?>/index.php" class="text-3xl font-extrabold text-[#a83300] tracking-tight">Zesto</a>
      <p class="text-sm text-gray-500 mt-2">Sign in to your account</p>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
      <?php if ($error): ?>
      <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600 font-semibold flex items-center gap-2">
        <span>❌</span> <?= e($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="flex flex-col gap-5">
        <?= csrfField() ?>
        <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

        <div>
          <label class="block text-xs font-bold text-[#1b1c1c] mb-2 uppercase tracking-wider">Email Address</label>
          <input type="email" name="email" required autocomplete="email"
                 value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>"
                 placeholder="you@example.com"
                 class="zesto-input">
        </div>

        <div>
          <label class="block text-xs font-bold text-[#1b1c1c] mb-2 uppercase tracking-wider">Password</label>
          <input type="password" name="password" required autocomplete="current-password"
                 placeholder="Your password"
                 class="zesto-input">
        </div>

        <button type="submit"
                class="btn-primary w-full justify-center py-3.5 rounded-xl text-sm mt-2">
          Sign In to Zesto
        </button>
      </form>

      <!-- Demo credentials -->
      <div class="mt-6 p-4 bg-[#f5f3f3] rounded-xl">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Demo Credentials</p>
        <div class="space-y-1.5 text-xs text-gray-600 font-mono">
          <p>👤 <strong>Admin:</strong> admin@zesto.com / password</p>
          <p>🧑 <strong>Customer:</strong> alex@example.com / password</p>
          <p>🍴 <strong>Restaurant:</strong> mario@zesto.com / password</p>
          <p>🏍 <strong>Delivery:</strong> marcus@zesto.com / password</p>
        </div>
      </div>
    </div>

    <p class="text-center text-sm text-gray-500 mt-6">
      New to Zesto?
      <a href="<?= BASE_URL ?>/register.php" class="text-[#a83300] font-bold hover:underline">Create an account →</a>
    </p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
