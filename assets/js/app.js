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
      const res = await fetch(`${window.ZESTO_BASE || ''}/api/restaurants/search.php?q=${encodeURIComponent(q)}`);
      const data = await res.json();
      if (!searchResults) return;

      if (data.results && data.results.length > 0) {
        searchResults.innerHTML = data.results.map(r => {
          const icon = r.result_type === 'menu'
            ? `<span class="text-[10px] font-bold text-amber-400 bg-amber-400/10 px-1.5 py-0.5 rounded uppercase tracking-wide shrink-0">🍽 Dish</span>`
            : `<span class="text-[10px] font-bold text-zinc-400 bg-white/5 px-1.5 py-0.5 rounded uppercase tracking-wide shrink-0">🏠 Place</span>`;
          return `
          <a href="${window.ZESTO_BASE || ''}/restaurant.php?id=${r.slug}"
             class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 transition-colors border-b border-white/5 last:border-0 no-underline group">
            <img src="${r.image || ''}" alt="${r.name}"
                 class="w-10 h-10 rounded-lg object-cover border border-white/10 shrink-0"
                 onerror="this.src='https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=80&q=60'">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-white truncate group-hover:text-amber-400 transition-colors">${r.name}</p>
              <p class="text-xs text-zinc-500 truncate mt-0.5">${r.subtitle || r.tags}</p>
            </div>
            <div class="flex flex-col items-end gap-1 shrink-0">
              ${icon}
              <span class="text-[10px] text-amber-400 font-bold">★ ${r.rating}</span>
            </div>
          </a>`;
        }).join('');

        // Add "See all results" footer
        searchResults.innerHTML += `
          <a href="${window.ZESTO_BASE || ''}/index.php?search=${encodeURIComponent(q)}"
             class="flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-400/5 hover:bg-amber-400/10 transition-colors text-amber-400 text-xs font-bold no-underline">
            <i data-lucide="search" class="w-3.5 h-3.5"></i>
            See all results for "${q}"
          </a>`;

        searchResults.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
      } else {
        searchResults.innerHTML = `<div class="px-4 py-4 text-sm text-zinc-500 text-center">No restaurants or dishes found for "<strong class="text-white">${q}</strong>"</div>`;
        searchResults.classList.remove('hidden');
      }
    } catch(e) {
      console.error('Search error:', e);
    }
  }, 350);

  searchInput.addEventListener('input', doSearch);

  // Submit form on Enter (regular behaviour) — results are just a preview
  searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      searchResults && searchResults.classList.add('hidden');
      searchInput.blur();
    }
  });

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
