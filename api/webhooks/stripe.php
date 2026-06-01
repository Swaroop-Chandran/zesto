<?php
/**
 * Zesto — Stripe Webhook Handler
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Verify method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('[Zesto Checkout] Stripe webhook rejected non-POST request');
    http_response_code(405);
    exit('Method not allowed');
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
$endpoint_secret = STRIPE_WEBHOOK_SECRET;

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
error_log('[Zesto Checkout] Stripe webhook received: bytes=' . strlen($payload) . ', has_signature=' . ($sig_header !== '' ? 'yes' : 'no'));

$event = null;

try {
    if ($endpoint_secret) {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );
    } else {
        // Fallback for testing without webhook secret
        $event = \Stripe\Event::constructFrom(json_decode($payload, true));
    }
} catch(\UnexpectedValueException $e) {
    error_log('[Zesto Checkout] Stripe webhook invalid payload: ' . $e->getMessage());
    http_response_code(400);
    exit('Invalid payload');
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    error_log('[Zesto Checkout] Stripe webhook invalid signature: ' . $e->getMessage());
    http_response_code(400);
    exit('Invalid signature');
}

// Handle the event
error_log('[Zesto Checkout] Stripe webhook event accepted: type=' . $event->type);
switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;
        
        $orderId = $session->client_reference_id ?? $session->metadata->order_id ?? null;
        $paymentIntentId = $session->payment_intent ?? null;
        error_log('[Zesto Checkout] Stripe checkout.session.completed: order_id=' . ($orderId ?: 'none') . ', payment_intent=' . ($paymentIntentId ?: 'none'));
        
        if ($orderId) {
            try {
                $db = db();
                $db->beginTransaction();
                
                // Fetch order
                $stmt = $db->prepare("SELECT id, payment_status, order_status FROM orders WHERE id = :id LIMIT 1 FOR UPDATE");
                $stmt->execute([':id' => $orderId]);
                $order = $stmt->fetch();
                
                if ($order && $order['payment_status'] === 'pending') {
                    // Update order as paid
                    $updateStmt = $db->prepare("
                        UPDATE orders 
                        SET payment_status = 'paid', 
                            order_status = 'placed',
                            stripe_payment_intent_id = :pi,
                            payment_timestamp = CURRENT_TIMESTAMP
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        ':pi' => $paymentIntentId,
                        ':id' => $orderId
                    ]);
                    error_log('[Zesto Checkout] Stripe webhook marked order paid: order_id=' . $orderId);
                    
                    // The order is now paid and 'placed'. It is ready for the restaurant to accept it.
                } else {
                    error_log('[Zesto Checkout] Stripe webhook skipped paid update: order_id=' . $orderId . ', found=' . ($order ? 'yes' : 'no') . ', payment_status=' . ($order['payment_status'] ?? 'none'));
                }
                
                $db->commit();
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('[Zesto Checkout] Stripe Webhook DB Error: ' . $e->getMessage());
                http_response_code(500);
                exit('Database error');
            }
        } else {
            error_log('[Zesto Checkout] Stripe webhook completed event missing order id');
        }
        break;
        
    case 'checkout.session.async_payment_failed':
        $session = $event->data->object;
        $orderId = $session->client_reference_id ?? $session->metadata->order_id ?? null;
        error_log('[Zesto Checkout] Stripe checkout.session.async_payment_failed: order_id=' . ($orderId ?: 'none'));
        
        if ($orderId) {
            try {
                $stmt = db()->prepare("UPDATE orders SET payment_status = 'failed', order_status = 'cancelled' WHERE id = :id AND payment_status = 'pending'");
                $stmt->execute([':id' => $orderId]);
                error_log('[Zesto Checkout] Stripe webhook marked order failed: order_id=' . $orderId);
            } catch (Exception $e) {
                error_log('[Zesto Checkout] Stripe Webhook DB Error: ' . $e->getMessage());
            }
        }
        break;
        
    // ... handle other event types if needed
    default:
        // Unexpected event type
        error_log('[Zesto Checkout] Stripe webhook ignored event type=' . $event->type);
        break;
}

http_response_code(200);
echo json_encode(['status' => 'success']);
