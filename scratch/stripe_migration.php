<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = db();
    
    // 1. Add stripe columns
    $db->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS stripe_session_id VARCHAR(255) DEFAULT NULL;");
    $db->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS stripe_payment_intent_id VARCHAR(255) DEFAULT NULL;");
    $db->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_timestamp TIMESTAMP NULL DEFAULT NULL;");
    
    // 2. Modify order_status ENUM
    // The previous ENUM was ('placed','preparing','out_for_delivery','delivered','cancelled')
    // We update it to include 'pending_payment', 'accepted', 'ready_for_pickup', 'assigned_to_delivery', 'picked_up', 'awaiting_customer_confirmation'
    $db->exec("ALTER TABLE orders MODIFY COLUMN order_status ENUM(
        'pending_payment',
        'placed',
        'accepted',
        'preparing',
        'ready_for_pickup',
        'assigned_to_delivery',
        'picked_up',
        'out_for_delivery',
        'awaiting_customer_confirmation',
        'delivered',
        'cancelled'
    ) NOT NULL DEFAULT 'pending_payment';");
    
    echo "Database migrated successfully.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
