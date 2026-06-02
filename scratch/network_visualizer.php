<?php
$logFile = __DIR__ . '/network_log.json';
$requests = [];
if (file_exists($logFile)) {
    $requests = json_decode(file_get_contents($logFile), true) ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Chrome DevTools Network Tab</title>
  <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      background-color: #202124;
      color: #dfe1e5;
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 0;
      font-size: 12px;
      overflow: hidden;
      height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .devtools-toolbar {
      background-color: #2c2c2c;
      border-bottom: 1px solid #3c3c3c;
      padding: 6px 12px;
      display: flex;
      align-items: center;
      gap: 15px;
      color: #9aa0a6;
      font-size: 11px;
    }
    .toolbar-button {
      display: flex;
      align-items: center;
      gap: 4px;
      color: #dfe1e5;
      font-weight: 500;
    }
    .record-icon {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background-color: #f28b82;
      animation: pulse 1s infinite alternate;
    }
    @keyframes pulse {
      from { opacity: 0.5; }
      to { opacity: 1; }
    }
    .network-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }
    th {
      background-color: #2c2c2c;
      color: #9aa0a6;
      font-weight: 500;
      padding: 6px 8px;
      border-bottom: 1px solid #3c3c3c;
      border-right: 1px solid #3c3c3c;
      font-size: 11px;
    }
    td {
      padding: 6px 8px;
      border-bottom: 1px solid #303030;
      border-right: 1px solid #3c3c3c;
      font-family: 'Fira Code', monospace;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 250px;
    }
    tr:nth-child(even) {
      background-color: #242424;
    }
    tr:hover {
      background-color: #35363a;
    }
    .status-ok {
      color: #81c784;
    }
    .status-redirect {
      color: #ffb74d;
    }
    .status-error {
      color: #e57373;
    }
    .method {
      font-weight: bold;
      color: #64b5f6;
    }
    .time-bar {
      height: 6px;
      border-radius: 3px;
      background-color: #4db6ac;
    }
  </style>
</head>
<body>
  <div class="devtools-toolbar">
    <div class="toolbar-button">
      <div class="record-icon"></div>
      <span>Network log recording...</span>
    </div>
    <span>|</span>
    <span>Preserve log</span>
    <span>Disable cache</span>
    <span>|</span>
    <span style="color:#64b5f6;">Fetch/XHR</span>
    <span>JS</span>
    <span>CSS</span>
    <span>Img</span>
    <span>Doc</span>
  </div>

  <div class="network-container">
    <table>
      <thead>
        <tr>
          <th style="width: 25%;">Name</th>
          <th style="width: 10%;">Method</th>
          <th style="width: 10%;">Status</th>
          <th style="width: 15%;">Type</th>
          <th style="width: 15%;">Initiator</th>
          <th style="width: 10%;">Size</th>
          <th style="width: 15%;">Time</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($requests)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding: 40px; color:#9aa0a6;">No XHR/Fetch API requests logged yet.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($requests as $req): 
            $statusClass = 'status-ok';
            if ($req['status'] >= 300 && $req['status'] < 400) $statusClass = 'status-redirect';
            if ($req['status'] >= 400) $statusClass = 'status-error';
          ?>
            <tr>
              <td style="color: #e8eaed; font-weight: 500;"><?= htmlspecialchars(basename($req['url'])) ?></td>
              <td class="method"><?= htmlspecialchars($req['method']) ?></td>
              <td class="<?= $statusClass ?>"><?= htmlspecialchars($req['status']) ?></td>
              <td style="color:#9aa0a6;"><?= htmlspecialchars($req['type']) ?></td>
              <td style="color:#64b5f6; text-decoration: underline; cursor: pointer;"><?= htmlspecialchars($req['initiator'] ?? 'fetch') ?></td>
              <td style="color:#9aa0a6;"><?= htmlspecialchars($req['size'] ?? '320 B') ?></td>
              <td>
                <div style="display:flex; align-items:center; gap: 8px;">
                  <span><?= htmlspecialchars($req['time']) ?>ms</span>
                  <div class="time-bar" style="width: <?= min(80, max(10, $req['time'] / 4)) ?>px;"></div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
