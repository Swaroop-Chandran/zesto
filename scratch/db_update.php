<?php
/**
 * Zesto — Database Schema Update & Seeding Script
 */

require_once 'c:/xampp/htdocs/zesto/config/config.php';
require_once 'c:/xampp/htdocs/zesto/config/database.php';

try {
    $pdo = db();
    echo "Successfully connected to the database.\n";

    // 1. Prepare Order Status Transition (mapping 'placed' to 'pending')
    echo "Altering orders.order_status ENUM to include new delivery states...\n";
    $pdo->exec("ALTER TABLE orders MODIFY COLUMN order_status ENUM('placed', 'pending', 'accepted', 'preparing', 'ready_for_pickup', 'assigned_to_delivery', 'picked_up', 'out_for_delivery', 'delivered', 'cancelled') NOT NULL DEFAULT 'placed'");
    
    echo "Updating existing 'placed' orders to 'pending'...\n";
    $pdo->exec("UPDATE orders SET order_status = 'pending' WHERE order_status = 'placed'");

    echo "Finalizing order_status ENUM default and removing legacy 'placed'...\n";
    $pdo->exec("ALTER TABLE orders MODIFY COLUMN order_status ENUM('pending', 'accepted', 'preparing', 'ready_for_pickup', 'assigned_to_delivery', 'picked_up', 'out_for_delivery', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending'");

    // 2. Create Table: delivery_settings
    echo "Creating delivery_settings table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivery_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            base_fare DECIMAL(10,2) NOT NULL DEFAULT 40.00,
            per_km_charge DECIMAL(10,2) NOT NULL DEFAULT 5.00,
            min_delivery_charge DECIMAL(10,2) NOT NULL DEFAULT 40.00,
            peak_hour_bonus DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            rain_bonus DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            festival_bonus DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Seed default settings row if empty
    $chkSettings = $pdo->query("SELECT COUNT(*) FROM delivery_settings")->fetchColumn();
    if ($chkSettings == 0) {
        echo "Seeding default delivery settings...\n";
        $pdo->exec("INSERT INTO delivery_settings (id, base_fare, per_km_charge, min_delivery_charge) VALUES (1, 40.00, 5.00, 40.00)");
    }

    // 3. Create Table: delivery_locations
    echo "Creating delivery_locations table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivery_locations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED DEFAULT NULL,
            restaurant_id INT UNSIGNED DEFAULT NULL,
            latitude DECIMAL(10,8) NOT NULL,
            longitude DECIMAL(11,8) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
            UNIQUE KEY uq_user_loc (user_id),
            UNIQUE KEY uq_rest_loc (restaurant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. Create Table: delivery_assignments
    echo "Creating delivery_assignments table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivery_assignments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL,
            delivery_partner_id INT UNSIGNED NOT NULL,
            status ENUM('assigned', 'accepted', 'rejected', 'completed', 'cancelled') NOT NULL DEFAULT 'assigned',
            restaurant_lat DECIMAL(10,8) DEFAULT NULL,
            restaurant_lng DECIMAL(11,8) DEFAULT NULL,
            customer_lat DECIMAL(10,8) DEFAULT NULL,
            customer_lng DECIMAL(11,8) DEFAULT NULL,
            delivery_partner_lat DECIMAL(10,8) DEFAULT NULL,
            delivery_partner_lng DECIMAL(11,8) DEFAULT NULL,
            distance_to_restaurant DECIMAL(10,2) DEFAULT NULL,
            distance_to_customer DECIMAL(10,2) DEFAULT NULL,
            total_distance DECIMAL(10,2) DEFAULT NULL,
            earnings DECIMAL(10,2) DEFAULT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            accepted_at TIMESTAMP NULL DEFAULT NULL,
            picked_up_at TIMESTAMP NULL DEFAULT NULL,
            delivered_at TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (delivery_partner_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 5. Create Table: delivery_notifications
    echo "Creating delivery_notifications table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivery_notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            delivery_partner_id INT UNSIGNED NOT NULL,
            order_id INT UNSIGNED NOT NULL,
            message TEXT DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (delivery_partner_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 6. Create Table: delivery_earnings
    echo "Creating delivery_earnings table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivery_earnings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            delivery_partner_id INT UNSIGNED NOT NULL,
            order_id INT UNSIGNED NOT NULL,
            base_fare DECIMAL(10,2) NOT NULL DEFAULT 40.00,
            distance_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            peak_hour_bonus DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            rain_bonus DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            festival_bonus DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_earnings DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            distance_travelled DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (delivery_partner_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 7. Seed Coordinates for Demo Restaurants & Delivery Partner (Marcus) and Customer (Alex)
    echo "Seeding geographical coordinates...\n";

    // Demo customer: alex@example.com
    $alexId = $pdo->query("SELECT id FROM users WHERE email = 'alex@example.com' LIMIT 1")->fetchColumn();
    if ($alexId) {
        $pdo->exec("INSERT INTO delivery_locations (user_id, latitude, longitude) VALUES ($alexId, 19.08200000, 72.88200000) 
                    ON DUPLICATE KEY UPDATE latitude = 19.08200000, longitude = 72.88200000");
    }

    // Demo delivery partner: marcus@zesto.com
    $marcusId = $pdo->query("SELECT id FROM users WHERE email = 'marcus@zesto.com' LIMIT 1")->fetchColumn();
    if ($marcusId) {
        $pdo->exec("INSERT INTO delivery_locations (user_id, latitude, longitude) VALUES ($marcusId, 19.07800000, 72.87500000) 
                    ON DUPLICATE KEY UPDATE latitude = 19.07800000, longitude = 72.87500000");
        // Also auto-approve Marcus and mark him available to be safe
        $pdo->exec("UPDATE delivery_partners SET is_approved = 1, is_available = 1 WHERE user_id = $marcusId");
        $pdo->exec("UPDATE users SET is_active = 1 WHERE id = $marcusId");
    }

    // Restaurants
    $rests = [
        1 => [19.07600000, 72.87770000], // Andheri West
        2 => [19.11760000, 72.90600000], // Powai
        3 => [19.02690000, 72.85000000], // Matunga
        4 => [19.05960000, 72.82950000], // Bandra West
        5 => [19.07000000, 72.87000000],
        6 => [19.08000000, 72.89000000],
        7 => [19.06000000, 72.83000000],
        8 => [19.12000000, 72.91000000],
    ];

    foreach ($rests as $rid => $coords) {
        // Verify restaurant exists before seeding
        $exists = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE id = $rid")->fetchColumn();
        if ($exists) {
            $pdo->exec("INSERT INTO delivery_locations (restaurant_id, latitude, longitude) VALUES ($rid, {$coords[0]}, {$coords[1]})
                        ON DUPLICATE KEY UPDATE latitude = {$coords[0]}, longitude = {$coords[1]}");
        }
    }

    echo "Database migrations and seeding completed successfully!\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
