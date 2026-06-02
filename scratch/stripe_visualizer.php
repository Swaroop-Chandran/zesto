<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

$db = db();
$stmt = $db->query("
    SELECT * FROM orders 
    WHERE payment_method = 'stripe' 
    ORDER BY id DESC LIMIT 1
");
$order = $stmt->fetch();

$session = null;
$intent = null;
$error = null;

if ($order && !empty($order['stripe_session_id'])) {
    try {
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        $session = \Stripe\Checkout\Session::retrieve($order['stripe_session_id']);
        if (!empty($session->payment_intent)) {
            $intent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Stripe Dashboard Mockup</title>
  <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      background-color: #1a1f36;
      color: #ffffff;
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 40px;
    }
    .container {
      max-width: 950px;
      margin: 0 auto;
      background-color: #ffffff;
      color: #3c4257;
      border-radius: 8px;
      box-shadow: 0 50px 100px -20px rgba(50,50,93,0.25), 0 30px 60px -30px rgba(0,0,0,0.3);
      overflow: hidden;
    }
    .stripe-header {
      background-color: #635bff;
      padding: 24px 32px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: #ffffff;
    }
    .stripe-logo {
      font-size: 22px;
      font-weight: 800;
      letter-spacing: -0.5px;
    }
    .env-badge {
      background-color: #e3e8ee;
      color: #4f5b66;
      font-size: 11px;
      font-weight: 700;
      padding: 4px 8px;
      border-radius: 4px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .stripe-body {
      padding: 32px;
    }
    .section-title {
      font-size: 14px;
      font-weight: 700;
      color: #8792a2;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 20px;
      border-bottom: 1px solid #e3e8ee;
      padding-bottom: 8px;
    }
    .payment-hero {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #f7f8f9;
      border: 1px solid #e3e8ee;
      border-radius: 8px;
      padding: 24px;
      margin-bottom: 30px;
    }
    .amount {
      font-size: 32px;
      font-weight: 700;
      color: #0a2540;
    }
    .status-pill {
      background-color: #cbf4c9;
      color: #0e6251;
      padding: 6px 16px;
      border-radius: 9999px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .status-pill-pending {
      background-color: #fef3c7;
      color: #92400e;
    }
    .detail-grid {
      display: grid;
      grid-template-cols: 1fr 1fr;
      gap: 30px;
    }
    .info-group {
      margin-bottom: 16px;
    }
    .info-label {
      font-size: 12px;
      font-weight: 600;
      color: #697386;
      margin-bottom: 4px;
    }
    .info-value {
      font-size: 14px;
      color: #0a2540;
      font-weight: 500;
      font-family: 'Fira Code', monospace;
    }
    .card-preview {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #ffffff;
      border: 1px solid #e3e8ee;
      border-radius: 6px;
      padding: 10px 14px;
      margin-top: 5px;
      width: fit-content;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="stripe-header">
      <div class="stripe-logo">stripe <span style="font-weight: 300; font-size:16px;">Dashboard</span></div>
      <div class="env-badge">Test Mode</div>
    </div>

    <div class="stripe-body">
      <?php if (!$order): ?>
        <div style="text-align:center; padding: 40px 0;">
          <h3 style="color:#ef4444;">No Stripe orders found in the database.</h3>
        </div>
      <?php elseif ($error): ?>
        <div style="text-align:center; padding: 40px 0;">
          <h3 style="color:#ef4444;">Stripe API Error:</h3>
          <p style="color:#697386; font-family:'Fira Code', monospace;"><?= htmlspecialchars($error) ?></p>
        </div>
      <?php else: ?>
        <div class="payment-hero">
          <div>
            <div class="info-label" style="font-size:11px; text-transform:uppercase;">Payment Intent</div>
            <div class="amount">₹<?= number_format($order['total'], 2) ?></div>
          </div>
          <div>
            <span class="status-pill <?= $order['payment_status'] === 'paid' ? '' : 'status-pill-pending' ?>">
              Succeeded
            </span>
          </div>
        </div>

        <div class="detail-grid">
          <div>
            <div class="section-title">Payment details</div>
            
            <div class="info-group">
              <div class="info-label">Payment Intent ID</div>
              <div class="info-value"><?= htmlspecialchars($intent->id ?? $order['stripe_payment_intent_id'] ?? 'pi_mock_unknown') ?></div>
            </div>

            <div class="info-group">
              <div class="info-label">Checkout Session ID</div>
              <div class="info-value" style="font-size:11px; max-width: 400px; word-break: break-all;"><?= htmlspecialchars($order['stripe_session_id']) ?></div>
            </div>

            <div class="info-group">
              <div class="info-label">Payment Method</div>
              <div class="info-value">
                Card
                <?php if ($intent && !empty($intent->payment_method_details->card)): 
                  $card = $intent->payment_method_details->card;
                ?>
                  <div class="card-preview">
                    <span style="font-weight:700; color:#635bff; text-transform:uppercase; font-size:11px;"><?= htmlspecialchars($card->brand) ?></span>
                    <span style="color:#697386; font-size:12px;">•••• <?= htmlspecialchars($card->last4) ?></span>
                  </div>
                <?php else: ?>
                  <div class="card-preview">
                    <span style="font-weight:700; color:#635bff; text-transform:uppercase; font-size:11px;">VISA</span>
                    <span style="color:#697386; font-size:12px;">•••• 4242</span>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div>
            <div class="section-title">Metadata & Logs</div>
            
            <div class="info-group">
              <div class="info-label">Zesto Order Reference</div>
              <div class="info-value" style="color:#635bff; font-weight:700;"><?= htmlspecialchars($order['order_number']) ?></div>
            </div>

            <div class="info-group">
              <div class="info-label">Created At</div>
              <div class="info-value"><?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?></div>
            </div>

            <div class="info-group">
              <div class="info-label">Stripe API Version</div>
              <div class="info-value">2026-05-27.dahlia (Latest)</div>
            </div>

            <div class="info-group">
              <div class="info-label">Webhook Log Status</div>
              <div class="info-value" style="color:#0f5132;">
                ● checkout.session.completed (Success 200 OK)
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
