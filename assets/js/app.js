/**
 * Zesto — Main Application JavaScript
 * Handles: toasts, mobile drawer, modal helpers, search debounce, global UI
 */

// ── Toast Notification System ─────────────────────────────────
window.Zesto = window.Zesto || {};

Zesto.toast = function(message, type = 'info', duration = 3500) {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const icons = {
    info:    '🍽️',
    success: '✅',
    error:   '❌',
    cart:    '🛒',
  };

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<span>${icons[type] || '🍽️'}</span><p>${message}</p>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = 'fadeIn 0.25s ease-out reverse forwards';
    setTimeout(() => toast.remove(), 260);
  }, duration);
};

// ── Mobile Drawer (Left Sidebar) ──────────────────────────────
Zesto.drawer = {
  open: function() {
    const backdrop = document.getElementById('drawer-backdrop');
    const panel    = document.getElementById('drawer-panel');
    if (backdrop) backdrop.classList.remove('hidden');
    if (panel)    panel.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  },
  close: function() {
    const backdrop = document.getElementById('drawer-backdrop');
    const panel    = document.getElementById('drawer-panel');
    if (backdrop) backdrop.classList.add('hidden');
    if (panel)    panel.classList.add('hidden');
    document.body.style.overflow = '';
  }
};

// ── Modal Helpers ──────────────────────────────────────────────
Zesto.modal = {
  open: function(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
  },
  close: function(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('hidden'); document.body.style.overflow = ''; }
  }
};

// ── Search Debounce ───────────────────────────────────────────
Zesto.debounce = function(fn, delay = 350) {
  let timer;
  return function(...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
};

// ── Live Restaurant Search ────────────────────────────────────
function initSearch() {
  const searchInput = document.getElementById('search-input');
  const searchResults = document.getElementById('search-results');
  if (!searchInput) return;

  const doSearch = Zesto.debounce(async function() {
    const q = searchInput.value.trim();
    if (q.length < 2) {
      if (searchResults) searchResults.classList.add('hidden');
      return;
    }

    try {
      const res = await fetch(`${window.ZESTO_BASE || '/Zesto'}/api/restaurants/search.php?q=${encodeURIComponent(q)}`);
      const data = await res.json();
      if (!searchResults) return;

      if (data.results && data.results.length > 0) {
        searchResults.innerHTML = data.results.map(r => `
          <a href="${window.ZESTO_BASE || '/Zesto'}/restaurant.php?id=${r.slug}" 
             class="flex items-center gap-3 px-4 py-3 hover:bg-[#f5f3f3] transition-colors">
            <img src="${r.image || ''}" alt="${r.name}" 
                 class="w-10 h-10 rounded-lg object-cover border border-gray-100">
            <div>
              <p class="text-sm font-bold text-[#1b1c1c]">${r.name}</p>
              <p class="text-xs text-gray-500">${r.tags}</p>
            </div>
            <span class="ml-auto text-xs text-[#a83300] font-bold">★ ${r.rating}</span>
          </a>
        `).join('');
        searchResults.classList.remove('hidden');
      } else {
        searchResults.innerHTML = `<div class="px-4 py-3 text-sm text-gray-500">No restaurants found for "${q}"</div>`;
        searchResults.classList.remove('hidden');
      }
    } catch(e) {
      console.error('Search error:', e);
    }
  }, 350);

  searchInput.addEventListener('input', doSearch);

  // Close results on outside click
  document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && searchResults && !searchResults.contains(e.target)) {
      searchResults.classList.add('hidden');
    }
  });
}

// ── Restaurant Filter & Sort ───────────────────────────────────
function initRestaurantFilters() {
  const filterForm = document.getElementById('filter-form');
  if (!filterForm) return;

  const inputs = filterForm.querySelectorAll('input, select, button[data-category]');
  inputs.forEach(input => {
    input.addEventListener('change', () => filterForm.submit());
  });
}

// ── Category Pills ─────────────────────────────────────────────
function initCategoryScroll() {
  const track = document.getElementById('category-track');
  const btnLeft  = document.getElementById('cat-scroll-left');
  const btnRight = document.getElementById('cat-scroll-right');

  if (!track) return;
  if (btnLeft)  btnLeft.addEventListener('click',  () => track.scrollBy({ left: -300, behavior: 'smooth' }));
  if (btnRight) btnRight.addEventListener('click', () => track.scrollBy({ left:  300, behavior: 'smooth' }));
}

// ── Mobile Drawer Toggle ───────────────────────────────────────
function initDrawer() {
  const openBtn   = document.getElementById('drawer-open-btn');
  const closeBtn  = document.getElementById('drawer-close-btn');
  const backdrop  = document.getElementById('drawer-backdrop');

  if (openBtn)  openBtn.addEventListener('click',  () => Zesto.drawer.open());
  if (closeBtn) closeBtn.addEventListener('click', () => Zesto.drawer.close());
  if (backdrop) backdrop.addEventListener('click', () => Zesto.drawer.close());
}

// ── Flash Messages (auto-dismiss) ─────────────────────────────
function initFlash() {
  const flash = document.getElementById('flash-message');
  if (flash) {
    setTimeout(() => {
      flash.style.opacity = '0';
      flash.style.transform = 'translateY(-8px)';
      flash.style.transition = 'opacity 0.3s, transform 0.3s';
      setTimeout(() => flash.remove(), 350);
    }, 4000);
  }
}

// ── Confirm Delete / Action Dialogs ───────────────────────────
document.addEventListener('click', function(e) {
  const btn = e.target.closest('[data-confirm]');
  if (btn) {
    const msg = btn.dataset.confirm || 'Are you sure?';
    if (!confirm(msg)) e.preventDefault();
  }
});

// ── Init All ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  initSearch();
  initRestaurantFilters();
  initCategoryScroll();
  initDrawer();
  initFlash();
});
