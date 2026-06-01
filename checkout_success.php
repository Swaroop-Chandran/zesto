<?php
/**
 * Zesto — Checkout Success
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

$sessionId = $_GET['session_id'] ?? null;
if (!$sessionId) {
    error_log('[Zesto Checkout] checkout_success.php missing session_id; redirecting home');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
error_log('[Zesto Checkout] checkout_success.php entered: session_id=' . $sessionId);

$pageTitle = 'Payment Successful — Zesto';

// You could verify the session with Stripe here if you want to display details before the webhook fires
// For now, we trust the webhook to do the actual db update, we just show a nice message to the user.
try {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    $session = \Stripe\Checkout\Session::retrieve($sessionId);
    $orderNumber = $session->metadata->order_number ?? '';
    error_log('[Zesto Checkout] Stripe success session retrieved: session_id=' . $sessionId . ', order_number=' . ($orderNumber ?: 'none'));
} catch (Exception $e) {
    error_log('[Zesto Checkout] Stripe success session retrieval failed: ' . $e->getMessage());
    $orderNumber = '';
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 pb-16 md:pb-8 bg-[#fbf9f8] flex items-center justify-center min-h-[70vh]">
    <div class="max-w-md w-full mx-auto px-6 py-10 bg-white rounded-3xl border border-gray-150 shadow-sm text-center">
        <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl shadow-inner">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        
        <h1 class="text-2xl font-black text-[#1b1c1c] tracking-tight mb-2">Payment Successful!</h1>
        <p class="text-gray-500 text-sm mb-6">
            Your payment was processed successfully. 
            <?php if ($orderNumber): ?>
            Your order <strong><?= e($orderNumber) ?></strong> is now being prepared.
            <?php else: ?>
            Your order is now being prepared.
            <?php endif; ?>
        </p>

        <a href="<?= BASE_URL ?>/index.php" class="inline-block bg-[#a83300] hover:bg-[#d24200] text-white text-xs font-bold px-8 py-3.5 rounded-xl shadow-md active:scale-95 transition-all">
            Return to Home
        </a>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
