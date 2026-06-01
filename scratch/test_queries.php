<?php
/**
 * Test Analytics Queries to catch strict SQL errors
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = db();
    $rid = 1; // Test restaurant ID
    echo "Connected to DB. Testing queries...\n\n";

    // Query 1: Total Orders
    echo "Query 1: Total Orders... ";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = :rid");
    $stmt->execute([':rid' => $rid]);
    echo "OK (" . $stmt->fetchColumn() . ")\n";

    // Query 2: Revenue
    echo "Query 2: Total Revenue... ";
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM orders WHERE restaurant_id = :rid AND payment_status = 'paid'");
    $stmt->execute([':rid' => $rid]);
    echo "OK (" . $stmt->fetchColumn() . ")\n";

    // Query 3: Top Selling Foods
    echo "Query 3: Top Selling Foods... ";
    $stmt = $pdo->prepare("
        SELECT oi.item_name, SUM(oi.quantity) AS total_qty, SUM(oi.quantity * oi.item_price) AS total_rev
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE o.restaurant_id = :rid AND o.payment_status = 'paid'
        GROUP BY oi.menu_item_id, oi.item_name
        ORDER BY total_qty DESC LIMIT 5
    ");
    $stmt->execute([':rid' => $rid]);
    echo "OK (" . count($stmt->fetchAll()) . " rows)\n";

    // Query 4: Daily Trends
    echo "Query 4: Daily Trends... ";
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) AS day, COUNT(*) AS orders, SUM(total) AS revenue
        FROM orders
        WHERE restaurant_id = :rid AND payment_status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ");
    $stmt->execute([':rid' => $rid]);
    echo "OK (" . count($stmt->fetchAll()) . " rows)\n";

    // Query 5: Weekly Trends
    echo "Query 5: Weekly Trends... ";
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(created_at, '%X-W%V') AS week_label, COUNT(*) AS orders, SUM(total) AS revenue
        FROM orders
        WHERE restaurant_id = :rid AND payment_status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
        GROUP BY week_label
        ORDER BY week_label ASC
    ");
    $stmt->execute([':rid' => $rid]);
    echo "OK (" . count($stmt->fetchAll()) . " rows)\n";

    // Query 6: Monthly Trends
    echo "Query 6: Monthly Trends... ";
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(created_at, '%b %Y') AS month_label, DATE_FORMAT(created_at, '%Y-%m') as sort_month, COUNT(*) AS orders, SUM(total) AS revenue
        FROM orders
        WHERE restaurant_id = :rid AND payment_status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month_label, sort_month
        ORDER BY sort_month ASC
    ");
    $stmt->execute([':rid' => $rid]);
    echo "OK (" . count($stmt->fetchAll()) . " rows)\n";

    // Query 7: Popular Categories
    echo "Query 7: Popular Categories... ";
    $stmt = $pdo->prepare("
        SELECT c.name AS category_name, SUM(oi.quantity) AS total_qty
        FROM order_items oi
        JOIN menu_items mi ON mi.id = oi.menu_item_id
        JOIN categories c ON c.id = mi.category_id
        JOIN orders o ON o.id = oi.order_id
        WHERE o.restaurant_id = :rid AND o.payment_status = 'paid'
        GROUP BY c.id, c.name
        ORDER BY total_qty DESC
    ");
    $stmt->execute([':rid' => $rid]);
    echo "OK (" . count($stmt->fetchAll()) . " rows)\n";

    // Query 8: Praised Foods
    echo "Query 8: Praised Foods... ";
    $stmt = $pdo->prepare("
        SELECT oi.item_name, COUNT(oi.id) AS praise_count, SUM(oi.quantity) AS total_qty
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        JOIN order_reviews r ON r.order_id = o.id
        WHERE o.restaurant_id = :rid AND r.restaurant_rating >= 4
        GROUP BY oi.menu_item_id, oi.item_name
        ORDER BY praise_count DESC, total_qty DESC LIMIT 5
    ");
    $stmt->execute([':rid' => $rid]);
    echo "OK (" . count($stmt->fetchAll()) . " rows)\n";

    // Query 9: Recent Reviews
    echo "Query 9: Recent Reviews... ";
    $stmt = $pdo->prepare("
        SELECT r.review_text, r.restaurant_rating, r.created_at, u.name AS customer_name
        FROM order_reviews r
        JOIN users u ON u.id = r.customer_id
        WHERE r.restaurant_id = :rid AND r.review_text IS NOT NULL AND r.review_text != ''
        ORDER BY r.created_at DESC LIMIT 5
    ");
    $stmt->execute([':rid' => $rid]);
    echo "OK (" . count($stmt->fetchAll()) . " rows)\n";

    echo "\n🎉 All queries executed successfully!\n";
} catch (Exception $e) {
    echo "\n🔴 Query Execution Failure: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
