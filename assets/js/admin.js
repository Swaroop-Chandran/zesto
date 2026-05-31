/**
 * Zesto — Admin Panel JavaScript
 * Handles: status updates (AJAX), Chart.js initialization, table actions
 */

// ── CSRF helper ───────────────────────────────────────────────
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// ── Update Order Status (Admin/Restaurant panel) ──────────────
async function updateOrderStatus(orderId, newStatus, btn) {
  const original = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = `<span class="spinner" style="width:0.875rem;height:0.875rem;border-width:2px;display:inline-block;"></span>`;

  try {
    const res = await fetch((window.ZESTO_BASE || '/Zesto') + '/api/orders/status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
      body: JSON.stringify({ order_id: orderId, status: newStatus }),
    });
    const data = await res.json();

    if (data.success) {
      Zesto.toast(`Order status updated to "${newStatus}"`, 'success');
      // Update badge in row
      const badge = document.getElementById(`order-status-${orderId}`);
      if (badge) {
        badge.className = `badge badge-${newStatus}`;
        badge.textContent = newStatus.replace(/_/g, ' ');
      }
      // Update dropdown
      const select = document.getElementById(`status-select-${orderId}`);
      if (select) select.value = newStatus;
    } else {
      Zesto.toast(data.message || 'Update failed.', 'error');
    }
  } catch(e) {
    Zesto.toast('Network error.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = original;
  }
}

// ── Revenue Chart (Admin Dashboard) ──────────────────────────
function initRevenueChart(labels, data) {
  const ctx = document.getElementById('revenue-chart');
  if (!ctx || typeof Chart === 'undefined') return;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Revenue ($)',
        data,
        borderColor: '#a83300',
        backgroundColor: 'rgba(168,51,0,0.08)',
        borderWidth: 2.5,
        pointBackgroundColor: '#a83300',
        pointRadius: 4,
        fill: true,
        tension: 0.4,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => `$${ctx.parsed.y.toFixed(2)}`
          }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } } },
        y: { grid: { color: '#f0eded' }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, callback: v => '$' + v } }
      }
    }
  });
}

// ── Orders Donut Chart ────────────────────────────────────────
function initOrdersChart(labels, data) {
  const ctx = document.getElementById('orders-chart');
  if (!ctx || typeof Chart === 'undefined') return;

  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: ['#a83300','#d24200','#ffdbd0','#00c853','#f44336'],
        borderWidth: 0,
      }]
    },
    options: {
      responsive: true,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: { font: { family: 'Plus Jakarta Sans', size: 11 }, padding: 16 }
        }
      }
    }
  });
}

// ── Status Select Change (inline table) ──────────────────────
document.addEventListener('change', function(e) {
  if (e.target.matches('[data-status-select]')) {
    const orderId = e.target.dataset.orderId;
    const newStatus = e.target.value;
    const fakeBtn = { innerHTML: '', disabled: false };
    updateOrderStatus(orderId, newStatus, e.target);
  }
});

// ── Date Range Picker (Reports page) ─────────────────────────
function initDateRange() {
  const form = document.getElementById('report-filter-form');
  if (!form) return;
  // Allow form submit normally; placeholder for date picker enhancement
}

// ── Confirm Delete ────────────────────────────────────────────
document.addEventListener('click', function(e) {
  const btn = e.target.closest('[data-delete-confirm]');
  if (btn) {
    if (!confirm('Are you sure you want to delete this item? This cannot be undone.')) {
      e.preventDefault();
    }
  }
});

// ── CSV Export ────────────────────────────────────────────────
function exportTableCSV(tableId, filename) {
  const table = document.getElementById(tableId);
  if (!table) return;

  const rows = Array.from(table.querySelectorAll('tr'));
  const csv = rows.map(row =>
    Array.from(row.querySelectorAll('th,td'))
      .map(cell => `"${cell.textContent.replace(/"/g, '""').trim()}"`)
      .join(',')
  ).join('\n');

  const blob = new Blob([csv], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = filename; a.click();
  URL.revokeObjectURL(url);
}

document.addEventListener('DOMContentLoaded', initDateRange);
