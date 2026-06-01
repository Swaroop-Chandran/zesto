<?php
/**
 * Test AnalyticsService methods
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AnalyticsService.php';

try {
    $rid = 1;
    echo "Testing AnalyticsService...\n";

    $rev = AnalyticsService::getRevenueMetrics($rid);
    echo "Revenue Metrics:\n";
    print_r($rev);

    $ord = AnalyticsService::getOrderMetrics($rid);
    echo "Order Metrics:\n";
    print_r($ord);

    $food = AnalyticsService::getFoodAnalytics($rid);
    echo "Food Analytics:\n";
    print_r($food);

    $cust = AnalyticsService::getCustomerAnalytics($rid);
    echo "Customer Metrics:\n";
    print_r($cust);

    $deliv = AnalyticsService::getDeliveryAnalytics($rid);
    echo "Delivery Metrics:\n";
    print_r($deliv);

    $charts = AnalyticsService::getChartsData($rid);
    echo "Charts Data:\n";
    print_r($charts);

    echo "\n🎉 AnalyticsService testing passed successfully!\n";
} catch (Exception $e) {
    echo "🔴 Testing failed: " . $e->getMessage() . "\n";
}
