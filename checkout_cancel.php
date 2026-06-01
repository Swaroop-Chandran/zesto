<?php
/**
 * Zesto — Checkout Cancelled
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

$orderNumber = $_GET['order'] ?? '';

$pageTitle = 'Payment Cancelled — Zesto';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 pb-16 md:pb-8 bg-[#fbf9f8] flex items-center justify-center min-h-[70vh]">
    <div class="max-w-md w-full mx-auto px-6 py-10 bg-white rounded-3xl border border-gray-150 shadow-sm text-center">
        <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl shadow-inner">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
        </div>
        
        <h1 class="text-2xl font-black text-[#1b1c1c] tracking-tight mb-2">Payment Cancelled</h1>
        <p class="text-gray-500 text-sm mb-6">
            Your payment process was interrupted or cancelled. Don't worry, you have not been charged.
            <?php if ($orderNumber): ?>
            Your order <strong><?= e($orderNumber) ?></strong> was not completed.
            <?php endif; ?>
        </p>

        <a href="<?= BASE_URL ?>/cart.php" class="inline-block bg-[#a83300] hover:bg-[#d24200] text-white text-xs font-bold px-8 py-3.5 rounded-xl shadow-md active:scale-95 transition-all">
            Back to Cart
        </a>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
