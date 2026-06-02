<?php
/**
 * Zesto — Analytics Business Logic Service
 * Centralizes SQL calculations and aggregates for restaurant analytics, avoiding duplicate code.
 */

class AnalyticsService {
    
    /**
     * Get revenue metrics for a specific restaurant.
     */
    public static function getRevenueMetrics(int $restaurantId): array {
        $db = db();
        $metrics = [
            'today' => 0.0,
            'weekly' => 0.0,
            'monthly' => 0.0,
            'total' => 0.0
        ];

        try {
            // Today's Revenue
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(total), 0) 
                FROM orders 
                WHERE restaurant_id = :rid 
                  AND payment_status = 'paid' 
                  AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $metrics['today'] = (float)$stmt->fetchColumn();

            // Weekly Revenue (Last 7 Days)
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(total), 0) 
                FROM orders 
                WHERE restaurant_id = :rid 
                  AND payment_status = 'paid' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $metrics['weekly'] = (float)$stmt->fetchColumn();

            // Monthly Revenue (Last 30 Days)
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(total), 0) 
                FROM orders 
                WHERE restaurant_id = :rid 
                  AND payment_status = 'paid' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $metrics['monthly'] = (float)$stmt->fetchColumn();

            // Total Revenue
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(total), 0) 
                FROM orders 
                WHERE restaurant_id = :rid 
                  AND payment_status = 'paid'
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $metrics['total'] = (float)$stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("AnalyticsService::getRevenueMetrics Error: " . $e->getMessage());
        }

        return $metrics;
    }

    /**
     * Get order volume metrics grouped by status.
     */
    public static function getOrderMetrics(int $restaurantId): array {
        $db = db();
        $metrics = [
            'total' => 0,
            'pending' => 0,
            'preparing' => 0,
            'delivered' => 0,
            'cancelled' => 0
        ];

        try {
            $stmt = $db->prepare("
                SELECT order_status, COUNT(*) AS count 
                FROM orders 
                WHERE restaurant_id = :rid 
                GROUP BY order_status
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $status = $row['order_status'];
                $count = (int)$row['count'];
                $metrics['total'] += $count;

                if ($status === 'pending') {
                    $metrics['pending'] = $count;
                } elseif ($status === 'preparing') {
                    $metrics['preparing'] = $count;
                } elseif ($status === 'completed') {
                    $metrics['delivered'] = $count;
                } elseif ($status === 'cancelled') {
                    $metrics['cancelled'] = $count;
                }
            }
        } catch (PDOException $e) {
            error_log("AnalyticsService::getOrderMetrics Error: " . $e->getMessage());
        }

        return $metrics;
    }

    /**
     * Get food sales performance metrics (top selling, low performing, categories).
     */
    public static function getFoodAnalytics(int $restaurantId): array {
        $db = db();
        $analytics = [
            'top_selling' => [],
            'low_performing' => [],
            'popular_categories' => []
        ];

        try {
            // Top Selling Foods (Limit 5)
            $stmt = $db->prepare("
                SELECT oi.item_name, SUM(oi.quantity) AS total_qty, SUM(oi.quantity * oi.item_price) AS total_rev
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.restaurant_id = :rid AND o.payment_status = 'paid'
                GROUP BY oi.menu_item_id, oi.item_name
                ORDER BY total_qty DESC LIMIT 5
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $analytics['top_selling'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Low Performing Foods (Sold less than 5 units or lowest sales, Limit 5)
            $stmt = $db->prepare("
                SELECT oi.item_name, SUM(oi.quantity) AS total_qty, SUM(oi.quantity * oi.item_price) AS total_rev
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.restaurant_id = :rid AND o.payment_status = 'paid'
                GROUP BY oi.menu_item_id, oi.item_name
                ORDER BY total_qty ASC LIMIT 5
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $analytics['low_performing'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Popular Categories
            $stmt = $db->prepare("
                SELECT c.name AS category_name, SUM(oi.quantity) AS total_qty
                FROM order_items oi
                JOIN menu_items mi ON mi.id = oi.menu_item_id
                JOIN categories c ON c.id = mi.category_id
                JOIN orders o ON o.id = oi.order_id
                WHERE o.restaurant_id = :rid AND o.payment_status = 'paid'
                GROUP BY c.id, c.name
                ORDER BY total_qty DESC
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $analytics['popular_categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("AnalyticsService::getFoodAnalytics Error: " . $e->getMessage());
        }

        return $analytics;
    }

    /**
     * Get customer segments (total, new, returning).
     */
    public static function getCustomerAnalytics(int $restaurantId): array {
        $db = db();
        $metrics = [
            'total' => 0,
            'new' => 0,
            'returning' => 0
        ];

        try {
            // Count orders per customer for this restaurant
            $stmt = $db->prepare("
                SELECT user_id, COUNT(*) AS order_count 
                FROM orders 
                WHERE restaurant_id = :rid AND payment_status = 'paid'
                GROUP BY user_id
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $metrics['total'] = count($customers);
            foreach ($customers as $c) {
                if ((int)$c['order_count'] > 1) {
                    $metrics['returning']++;
                } else {
                    $metrics['new']++;
                }
            }
        } catch (PDOException $e) {
            error_log("AnalyticsService::getCustomerAnalytics Error: " . $e->getMessage());
        }

        return $metrics;
    }

    /**
     * Get delivery efficiency analytics (average time, completed, delayed).
     */
    public static function getDeliveryAnalytics(int $restaurantId): array {
        $db = db();
        $metrics = [
            'avg_time' => 0.0, // in minutes
            'completed' => 0,
            'delayed' => 0
        ];

        try {
            // Completed deliveries
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM delivery_assignments da
                JOIN orders o ON o.id = da.order_id
                WHERE o.restaurant_id = :rid AND da.status = 'completed'
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $metrics['completed'] = (int)$stmt->fetchColumn();

            // Average delivery time (from picked up to delivered by courier)
            $stmt = $db->prepare("
                SELECT AVG(TIMESTAMPDIFF(SECOND, da.picked_up_at, da.delivered_at)) 
                FROM delivery_assignments da
                JOIN orders o ON o.id = da.order_id
                WHERE o.restaurant_id = :rid 
                  AND da.status = 'completed' 
                  AND da.picked_up_at IS NOT NULL 
                  AND da.delivered_at IS NOT NULL
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $avgSeconds = $stmt->fetchColumn();
            if ($avgSeconds !== null && $avgSeconds !== false) {
                $metrics['avg_time'] = round((float)$avgSeconds / 60, 1);
            }

            // Delayed deliveries (took more than 40 minutes / 2400 seconds)
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM delivery_assignments da
                JOIN orders o ON o.id = da.order_id
                WHERE o.restaurant_id = :rid 
                  AND da.status = 'completed' 
                  AND da.picked_up_at IS NOT NULL 
                  AND da.delivered_at IS NOT NULL
                  AND TIMESTAMPDIFF(SECOND, da.picked_up_at, da.delivered_at) > 2400
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $metrics['delayed'] = (int)$stmt->fetchColumn();

        } catch (PDOException $e) {
            error_log("AnalyticsService::getDeliveryAnalytics Error: " . $e->getMessage());
        }

        return $metrics;
    }

    /**
     * Get chart dataset metrics (daily trend, category performance, top sellers).
     */
    public static function getChartsData(int $restaurantId): array {
        $db = db();
        $data = [
            'trend_days' => [],
            'trend_revenue' => [],
            'trend_orders' => [],
            'categories' => [],
            'category_quantities' => [],
            'top_dishes' => [],
            'top_dish_revenue' => []
        ];

        try {
            // Daily Trend (Last 14 days)
            $stmt = $db->prepare("
                SELECT DATE(created_at) AS day, COUNT(*) AS orders, SUM(total) AS revenue
                FROM orders
                WHERE restaurant_id = :rid AND payment_status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                GROUP BY DATE(created_at)
                ORDER BY day ASC
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($daily as $d) {
                $data['trend_days'][] = date('M j', strtotime($d['day']));
                $data['trend_revenue'][] = (float)$d['revenue'];
                $data['trend_orders'][] = (int)$d['orders'];
            }

            // Category Performance
            $stmt = $db->prepare("
                SELECT c.name AS category_name, SUM(oi.quantity) AS total_qty
                FROM order_items oi
                JOIN menu_items mi ON mi.id = oi.menu_item_id
                JOIN categories c ON c.id = mi.category_id
                JOIN orders o ON o.id = oi.order_id
                WHERE o.restaurant_id = :rid AND o.payment_status = 'paid'
                GROUP BY c.id, c.name
                ORDER BY total_qty DESC
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($cats as $cat) {
                $data['categories'][] = $cat['category_name'];
                $data['category_quantities'][] = (int)$cat['total_qty'];
            }

            // Top Dishes Revenue Chart
            $stmt = $db->prepare("
                SELECT oi.item_name, SUM(oi.quantity * oi.item_price) AS total_rev
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.restaurant_id = :rid AND o.payment_status = 'paid'
                GROUP BY oi.menu_item_id, oi.item_name
                ORDER BY total_rev DESC LIMIT 5
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($dishes as $dish) {
                $data['top_dishes'][] = $dish['item_name'];
                $data['top_dish_revenue'][] = (float)$dish['total_rev'];
            }

        } catch (PDOException $e) {
            error_log("AnalyticsService::getChartsData Error: " . $e->getMessage());
        }

        return $data;
    }

    /**
     * Get extended KPIs for restaurant owner analytics.
     */
    public static function getExtendedMetrics(int $restaurantId): array {
        $db = db();
        $metrics = [
            'aov' => 0.0,
            'daily_orders' => 0,
            'weekly_orders' => 0,
            'monthly_orders' => 0,
            'peak_hour' => 'None',
            'repeat_pct' => 0.0,
            'completion_rate' => 100.0,
            'cancellation_rate' => 0.0
        ];

        try {
            // AOV
            $stmt = $db->prepare("
                SELECT COALESCE(AVG(total), 0) 
                FROM orders 
                WHERE restaurant_id = :rid AND payment_status = 'paid'
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $metrics['aov'] = (float)$stmt->fetchColumn();

            // Daily, Weekly, Monthly Orders
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM orders 
                WHERE restaurant_id = :rid AND created_at >= CURDATE()
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $metrics['daily_orders'] = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("
                SELECT COUNT(*) FROM orders 
                WHERE restaurant_id = :rid AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $metrics['weekly_orders'] = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("
                SELECT COUNT(*) FROM orders 
                WHERE restaurant_id = :rid AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $metrics['monthly_orders'] = (int)$stmt->fetchColumn();

            // Peak Order Hour
            $stmt = $db->prepare("
                SELECT HOUR(created_at) AS hr, COUNT(*) AS count 
                FROM orders 
                WHERE restaurant_id = :rid 
                GROUP BY HOUR(created_at) 
                ORDER BY count DESC LIMIT 1
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $peak = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($peak) {
                $hr = (int)$peak['hr'];
                $ampm = $hr >= 12 ? 'PM' : 'AM';
                $displayHr = $hr % 12;
                if ($displayHr === 0) $displayHr = 12;
                $nextHr = ($hr + 1) % 12;
                if ($nextHr === 0) $nextHr = 12;
                $nextAmpm = ($hr + 1) >= 12 && ($hr + 1) < 24 ? 'PM' : (($hr + 1) >= 24 || ($hr + 1) < 12 ? 'AM' : 'PM');
                $metrics['peak_hour'] = "{$displayHr}:00 {$ampm} - {$nextHr}:00 {$nextAmpm}";
            }

            // Repeat Customer %
            $custStats = self::getCustomerAnalytics($restaurantId);
            if ($custStats['total'] > 0) {
                $metrics['repeat_pct'] = round(($custStats['returning'] / $custStats['total']) * 100, 1);
            }

            // Completion & Cancellation Rates
            $stmt = $db->prepare("
                SELECT 
                  SUM(CASE WHEN order_status = 'completed' THEN 1 ELSE 0 END) AS completed,
                  SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                  COUNT(*) AS total
                FROM orders
                WHERE restaurant_id = :rid
            ");
            $stmt->execute([':rid' => $restaurantId]);
            $rates = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($rates && (int)$rates['total'] > 0) {
                $total = (int)$rates['total'];
                $metrics['completion_rate'] = round(((int)$rates['completed'] / $total) * 100, 1);
                $metrics['cancellation_rate'] = round(((int)$rates['cancelled'] / $total) * 100, 1);
            }

        } catch (PDOException $e) {
            error_log("AnalyticsService::getExtendedMetrics Error: " . $e->getMessage());
        }

        return $metrics;
    }
}
