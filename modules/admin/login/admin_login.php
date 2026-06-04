<?php
/**
 * Zesto — Admin Login Page (Standalone, Admin-Only)
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

// If already logged in as admin, redirect straight to dashboard
if (isLoggedIn() && ($_SESSION['user_role'] ?? '') === ROLE_ADMIN) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

// If logged in as any other role, deny access
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $stmt = db()->prepare("SELECT * FROM users WHERE email = :email AND role = 'admin' LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['account_status'] !== 'active' || !$user['is_active']) {
                $error = 'This admin account is inactive or suspended.';
            } else {
                loginUser($user);
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid admin credentials. Access denied.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Zesto</title>
  <meta name="robots" content="noindex, nofollow">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Be Vietnam Pro', 'ui-sans-serif', 'system-ui'] },
          colors: {
            zesto: { dark: '#0A0A0A', orange: '#f59e0b', amber: '#d97706' }
          }
        }
      }
    }
  </script>
  <style>
    body { background: #0A0A0A; font-family: 'Be Vietnam Pro', sans-serif; }
    .glass {
      background: rgba(255,255,255,0.04);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.08);
    }
    .input-field {
      width: 100%;
      background: #10141a;
      border: 1px solid rgba(255,255,255,0.10);
      color: #fff;
      border-radius: 0.5rem;
      padding: 0.65rem 0.875rem;
      font-size: 0.8rem;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .input-field:focus {
      border-color: #f59e0b;
      box-shadow: 0 0 0 2px rgba(245,158,11,0.20);
    }
    .input-field::placeholder { color: rgba(255,255,255,0.2); }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fadeUp 0.45s ease both; }
  </style>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔐</text></svg>">
</head>
<body class="min-h-screen flex items-center justify-center p-4">

  <!-- Background grid pattern -->
  <div class="fixed inset-0 opacity-[0.03]" style="background-image: linear-gradient(rgba(255,255,255,.15) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.15) 1px, transparent 1px); background-size: 40px 40px;"></div>
  <!-- Glow -->
  <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] rounded-full opacity-10" style="background: radial-gradient(circle, #f59e0b 0%, transparent 70%); pointer-events:none;"></div>

  <div class="relative z-10 w-full max-w-sm fade-up">

    <!-- Logo -->
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-zesto-orange/10 border border-zesto-orange/20 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-zesto-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
      </div>
      <p class="text-[10px] font-bold text-zesto-orange uppercase tracking-widest mb-1">Admin Access Only</p>
      <h1 class="text-2xl font-extrabold text-white">Zesto Admin Panel</h1>
      <p class="text-xs text-white/40 mt-1">Restricted area — authorised personnel only</p>
    </div>

    <!-- Card -->
    <div class="glass rounded-2xl p-7 shadow-2xl shadow-black/60">

      <?php if ($error): ?>
      <div class="mb-5 flex items-center gap-2.5 px-4 py-3 bg-red-500/10 border border-red-500/20 rounded-xl text-xs text-red-400 font-semibold">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="space-y-5" autocomplete="off">
        <?= csrfField() ?>

        <div>
          <label class="block text-[10px] font-bold text-white/50 uppercase tracking-widest mb-1.5">Admin Email</label>
          <input
            type="email"
            name="email"
            class="input-field"
            placeholder="admin@zesto.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
            autofocus
          >
        </div>

        <div>
          <label class="block text-[10px] font-bold text-white/50 uppercase tracking-widest mb-1.5">Password</label>
          <input
            type="password"
            name="password"
            class="input-field"
            placeholder="••••••••"
            required
          >
        </div>

        <button
          type="submit"
          class="w-full py-3 bg-zesto-orange hover:bg-amber-400 text-black font-extrabold rounded-xl text-sm transition-all active:scale-95 shadow-lg shadow-zesto-orange/20 mt-2 flex items-center justify-center gap-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          Access Admin Panel
        </button>
      </form>
    </div>

    <!-- Back link -->
    <p class="text-center mt-6 text-[11px] text-white/30">
      Not an admin?
      <a href="<?= BASE_URL ?>/index.php" class="text-zesto-orange hover:text-amber-300 font-semibold no-underline transition-colors">← Back to Site</a>
    </p>

  </div>
</body>
</html>
