<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = db();
$stmt = $db->query("
    SELECT o.*, u.name as customer_name, u.email as customer_email, r.name as restaurant_name 
    FROM orders o
    JOIN users u ON u.id = o.user_id
    JOIN restaurants r ON r.id = o.restaurant_id
    ORDER BY o.id DESC LIMIT 1
");
$order = $stmt->fetch();

$items = [];
if ($order) {
    $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemStmt->execute([$order['id']]);
    $items = $itemStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Database Order Verification</title>
  <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;700&family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      background-color: #050505;
      color: #e2e8f0;
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 40px;
    }
    .container {
      max-width: 900px;
      margin: 0 auto;
      background: linear-gradient(145deg, #0b0b0b, #111111);
      border: 1px solid rgba(251, 191, 36, 0.15);
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
      padding: 30px;
    }
    .header {
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding-bottom: 20px;
      margin-bottom: 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .title {
      font-size: 20px;
      font-weight: 800;
      color: #fbbf24;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .badge {
      background-color: rgba(16, 185, 129, 0.15);
      border: 1px solid #10b981;
      color: #10b981;
      padding: 6px 14px;
      border-radius: 9999px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .badge-pending {
      background-color: rgba(245, 158, 11, 0.15);
      border: 1px solid #f59e0b;
      color: #f59e0b;
    }
    .grid {
      display: grid;
      grid-template-cols: 1fr 1fr;
      gap: 20px;
      margin-bottom: 30px;
    }
    .card {
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 10px;
      padding: 20px;
    }
    .card-title {
      font-size: 11px;
      font-weight: 800;
      color: rgba(255, 255, 255, 0.4);
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 15px;
      border-bottom: 1px dashed rgba(255, 255, 255, 0.05);
      padding-bottom: 5px;
    }
    .row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
      font-size: 13px;
    }
    .row .label {
      color: rgba(255, 255, 255, 0.6);
    }
    .row .value {
      font-weight: 600;
      font-family: 'Fira Code', monospace;
    }
    .table-container {
      margin-top: 25px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }
    th, td {
      padding: 12px 16px;
      text-align: left;
    }
    th {
      background: rgba(255, 255, 255, 0.03);
      color: rgba(255, 255, 255, 0.4);
      font-weight: 700;
      text-transform: uppercase;
      font-size: 10px;
      letter-spacing: 1px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    td {
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    }
    .grand-total {
      text-align: right;
      font-size: 16px;
      font-weight: 800;
      color: #fbbf24;
      margin-top: 20px;
      padding-top: 15px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
  </style>
</head>
<body>
  <div class="container">
    <?php if (!$order): ?>
      <div style="text-align:center; padding: 50px 0;">
        <h3 style="color:#ef4444;">No orders found in the database.</h3>
      </div>
    <?php else: ?>
      <div class="header">
        <div class="title">Database Record: Order <?= htmlspecialchars($order['order_number']) ?></div>
        <div class="badge <?= $order['payment_status'] === 'paid' ? '' : 'badge-pending' ?>">
          Payment: <?= htmlspecialchars($order['payment_status']) ?>
        </div>
      </div>

      <div class="grid">
        <div class="card">
          <div class="card-title">Order Details</div>
          <div class="row">
            <span class="label">Database ID:</span>
            <span class="value"><?= $order['id'] ?></span>
          </div>
          <div class="row">
            <span class="label">Order Reference:</span>
            <span class="value"><?= htmlspecialchars($order['order_number']) ?></span>
          </div>
          <div class="row">
            <span class="label">Customer Name:</span>
            <span class="value"><?= htmlspecialchars($order['customer_name']) ?></span>
          </div>
          <div class="row">
            <span class="label">Customer Email:</span>
            <span class="value"><?= htmlspecialchars($order['customer_email']) ?></span>
          </div>
          <div class="row">
            <span class="label">Restaurant:</span>
            <span class="value"><?= htmlspecialchars($order['restaurant_name']) ?></span>
          </div>
        </div>

        <div class="card">
          <div class="card-title">Payment & Status</div>
          <div class="row">
            <span class="label">Payment Method:</span>
            <span class="value"><?= strtoupper(htmlspecialchars($order['payment_method'])) ?></span>
          </div>
          <div class="row">
            <span class="label">Payment Status:</span>
            <span class="value" style="color: <?= $order['payment_status'] === 'paid' ? '#10b981' : '#f59e0b' ?>;">
              <?= strtoupper(htmlspecialchars($order['payment_status'])) ?>
            </span>
          </div>
          <div class="row">
            <span class="label">Order Status:</span>
            <span class="value" style="color: #fbbf24;"><?= strtoupper(htmlspecialchars($order['order_status'])) ?></span>
          </div>
          <div class="row">
            <span class="label">Stripe Session ID:</span>
            <span class="value" style="font-size:10px; max-width: 250px; overflow-wrap: break-word;"><?= htmlspecialchars($order['stripe_session_id'] ?? 'N/A') ?></span>
          </div>
          <div class="row">
            <span class="label">Stripe Intent ID:</span>
            <span class="value" style="font-size:10px;"><?= htmlspecialchars($order['stripe_payment_intent_id'] ?? 'N/A') ?></span>
          </div>
        </div>
      </div>

      <div class="card table-container">
        <div class="card-title">Order Items (Table: order_items)</div>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Item Name</th>
              <th>Price</th>
              <th>Quantity</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td class="value"><?= $item['id'] ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($item['item_name']) ?></td>
                <td class="value">₹<?= number_format($item['item_price'], 2) ?></td>
                <td class="value"><?= $item['quantity'] ?></td>
                <td class="value" style="color:#fbbf24;">₹<?= number_format($item['item_price'] * $item['quantity'], 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        
        <div class="grand-total">
          Grand Total: ₹<?= number_format($order['total'], 2) ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
