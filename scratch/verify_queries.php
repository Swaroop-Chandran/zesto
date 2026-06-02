<?php
/**
 * Zesto Nights — Verification Script for Kochi Kerala Database Queries
 */
$_SESSION['current_location'] = 'Kochi, Kerala';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/location_helper.php';

$city = getCurrentCity();
echo "Selected City: {$city}\n";

$categories = db()->query("SELECT * FROM categories WHERE is_active=1 ORDER BY display_order ASC")->fetchAll();
echo "Active Categories count: " . count($categories) . "\n";
foreach ($categories as $c) {
    echo " - ID {$c['id']}: {$c['name']} (image: {$c['image']})\n";
}

$restaurants = db()->prepare("SELECT * FROM restaurants WHERE is_active=1 AND city=:city");
$restaurants->execute([':city' => $city]);
$rList = $restaurants->fetchAll();
echo "Active Kochi Restaurants count: " . count($rList) . "\n";
foreach ($rList as $r) {
    echo " - ID {$r['id']}: {$r['name']} (slug: {$r['slug']})\n";
}

$specials = db()->prepare("
    SELECT mi.*, r.name AS restaurant_name
    FROM menu_items mi
    JOIN restaurants r ON r.id = mi.restaurant_id
    WHERE mi.is_available=1 AND mi.is_special=1 AND r.is_active=1 AND r.city=:city
");
$specials->execute([':city' => $city]);
$sList = $specials->fetchAll();
echo "Active Kochi Specials count: " . count($sList) . "\n";
foreach ($sList as $s) {
    echo " - ID {$s['id']}: {$s['name']} (price: ₹{$s['price']}) in {$s['restaurant_name']}\n";
}

$comboItem = db()->prepare("
    SELECT mi.*, r.name AS restaurant_name
    FROM menu_items mi
    JOIN restaurants r ON r.id = mi.restaurant_id
    WHERE mi.name = 'Crispy Flaky Porotta + Spicy Red Beef Fry' AND r.city = :city AND r.is_active = 1
    LIMIT 1
");
$comboItem->execute([':city' => $city]);
$combo = $comboItem->fetch();
if ($combo) {
    echo "Trending Combo found in database:\n";
    echo " - ID {$combo['id']}: {$combo['name']} (price: ₹{$combo['price']}) in {$combo['restaurant_name']}\n";
} else {
    echo "WARNING: Trending Combo NOT found in database!\n";
}
