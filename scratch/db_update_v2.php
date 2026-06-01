<?php
/**
 * Zesto — Database Schema Update V2 (Delivery Confirmation, Ratings, Disputes, and Audits)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = db();
    echo "Connected to the database for Migration V2.\n";

    // 1. Alter order_status ENUM
    echo "Updating orders.order_status ENUM columns...\n";
    $pdo->exec("
        ALTER TABLE orders MODIFY COLUMN order_status ENUM(
            'pending', 'accepted', 'preparing', 'ready_for_pickup', 
            'assigned_to_delivery', 'picked_up', 'out_for_delivery', 
            'awaiting_customer_confirmation', 'completed', 'delivery_issue', 'cancelled'
        ) NOT NULL DEFAULT 'pending'
    ");
    echo "✔ Modified order_status ENUM successfully.\n";

    // 2. Add confirmed_at to delivery_assignments
    echo "Adding confirmed_at column to delivery_assignments...\n";
    try {
        $pdo->exec("ALTER TABLE delivery_assignments ADD COLUMN confirmed_at TIMESTAMP NULL DEFAULT NULL AFTER delivered_at");
        echo "✔ Added confirmed_at column.\n";
    } catch (PDOException $e) {
        echo "ℹ confirmed_at column might already exist. Skipping...\n";
    }

    // 3. Add auto_confirmed to orders
    echo "Adding auto_confirmed column to orders...\n";
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN auto_confirmed TINYINT(1) NOT NULL DEFAULT 0 AFTER total");
        echo "✔ Added auto_confirmed column.\n";
    } catch (PDOException $e) {
        echo "ℹ auto_confirmed column might already exist. Skipping...\n";
    }

    // 4. Create order_reviews table
    echo "Creating order_reviews table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_reviews (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL UNIQUE,
            customer_id INT UNSIGNED NOT NULL,
            restaurant_id INT UNSIGNED NOT NULL,
            delivery_partner_id INT UNSIGNED DEFAULT NULL,
            restaurant_rating INT NOT NULL,
            delivery_rating INT NOT NULL,
            review_text TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
            FOREIGN KEY (delivery_partner_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✔ Created order_reviews table successfully.\n";

    // 5. Create delivery_audit_logs table
    echo "Creating delivery_audit_logs table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivery_audit_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL,
            action_name VARCHAR(100) NOT NULL,
            details TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✔ Created delivery_audit_logs table successfully.\n";

    echo "\n🎉 Database Migration V2 completed successfully!\n";

} catch (PDOException $e) {
    echo "🔴 Migration V2 Error: " . $e->getMessage() . "\n";
    exit(1);
}
