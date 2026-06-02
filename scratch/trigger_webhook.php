<?php
/**
 * Zesto — Manual Webhook Trigger Helper
 * Resolves payment details from Stripe API and fires checkout.session.completed event locally.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

$sessionId = $argv[1] ?? '';
$orderId = $argv[2] ?? '';
$orderNumber = $argv[3] ?? '';

if (empty($sessionId) || empty($orderId) || empty($orderNumber)) {
    die("Usage: php trigger_webhook.php <session_id> <order_id> <order_number>\n");
}

try {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    echo "Retrieving session from Stripe API: $sessionId\n";
    $session = \Stripe\Checkout\Session::retrieve($sessionId);
    $paymentIntentId = $session->payment_intent;
    echo "Resolved Payment Intent ID: $paymentIntentId\n";

    $payload = [
        'id' => 'evt_test_' . bin2hex(random_bytes(8)),
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => $sessionId,
                'client_reference_id' => $orderId,
                'payment_intent' => $paymentIntentId,
                'metadata' => [
                    'order_id' => $orderId,
                    'order_number' => $orderNumber
                ]
            ]
        ]
    ];

    $payloadJson = json_encode($payload);
    
    echo "POSTing event to webhook endpoint...\n";
    $ch = curl_init('http://127.0.0.1/zesto/api/webhooks/stripe.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payloadJson)
    ]);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Webhook response (HTTP $httpCode): $response\n";
    if ($httpCode === 200) {
        echo "SUCCESS: Webhook triggered successfully.\n";
    } else {
        echo "ERROR: Failed to trigger webhook.\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
