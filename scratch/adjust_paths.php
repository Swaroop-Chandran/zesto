<?php
/**
 * Zesto — Modular Path Adjustment Automator (Template Mode)
 * Re-maps relative requires/includes inside moved module subfolders.
 */

$directories = [
    'modules/customer/' => 3,
    'modules/restaurant/' => 3,
    'modules/delivery/' => 3,
    'modules/admin/' => 3
];

function processFile($filePath, $depth) {
    $content = file_get_contents($filePath);
    
    // Replacement mappings
    // For 3-level deep files (e.g. modules/customer/home/index.php)
    // 1. Root-relative config/includes to modular depth
    $content = str_replace("__DIR__ . '/config/", "__DIR__ . '/../../../config/", $content);
    $content = str_replace("__DIR__ . '/includes/", "__DIR__ . '/../../../includes/", $content);
    $content = str_replace("__DIR__ . '/../config/", "__DIR__ . '/../../../config/", $content);
    $content = str_replace("__DIR__ . '/../includes/", "__DIR__ . '/../../../includes/", $content);
    $content = str_replace("__DIR__ . '/../services/", "__DIR__ . '/../../../services/", $content);
    
    // 2. Adjust header inclusions for customer pages pointing to root files
    $content = str_replace("href=\"login.php\"", "href=\"<?= BASE_URL ?>/login.php\"", $content);
    $content = str_replace("href=\"register.php\"", "href=\"<?= BASE_URL ?>/register.php\"", $content);
    $content = str_replace("href=\"profile.php\"", "href=\"<?= BASE_URL ?>/profile.php\"", $content);
    $content = str_replace("href=\"orders.php\"", "href=\"<?= BASE_URL ?>/orders.php\"", $content);
    $content = str_replace("href=\"cart.php\"", "href=\"<?= BASE_URL ?>/cart.php\"", $content);
    $content = str_replace("href=\"checkout.php\"", "href=\"<?= BASE_URL ?>/checkout.php\"", $content);
    $content = str_replace("href=\"restaurants.php\"", "href=\"<?= BASE_URL ?>/restaurants.php\"", $content);
    
    file_put_contents($filePath, $content);
    echo "✔ Adjusted: {$filePath}\n";
}

function scanDirectory($dir, $depth) {
    if (!is_dir($dir)) return;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . $file;
        if (is_dir($path)) {
            scanDirectory($path . '/', $depth);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            processFile($path, $depth);
        }
    }
}

echo "Starting path adjustments...\n";
foreach ($directories as $dir => $depth) {
    scanDirectory($dir, $depth);
}
echo "Path adjustments completed successfully!\n";
