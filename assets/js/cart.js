/**
 * Zesto — Cart JavaScript
 * Handles: add to cart (AJAX), quantity update, remove, badge update
 */

// ── Get CSRF Token from meta tag ──────────────────────────────
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

// ── Update cart badge in navbar ───────────────────────────────
function updateCartBadge(count) {
  const badges = document.querySelectorAll('.cart-badge');
  badges.forEach(b => {
    if (count > 0) {
      b.textContent = count;
      b.classList.remove('hidden');
    } else {
      b.classList.add('hidden');
    }
  });
}

// ── Add item to cart ──────────────────────────────────────────
async function addToCart(menuItemId, restaurantId, restaurantSlug, customization = '') {
  const btn = document.querySelector(`[data-add-cart="${menuItemId}"]`);
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner" style="width:1rem;height:1rem;border-width:2px;"></span>`;
  }

  try {
    const res = await fetch((window.ZESTO_BASE || '/Zesto') + '/api/cart/add.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfToken(),
      },
      body: JSON.stringify({ menu_item_id: menuItemId, restaurant_id: restaurantId, customization }),
    });

    const data = await res.json();

    if (data.success) {
      updateCartBadge(data.cart_count);
      Zesto.toast(`🛒 Added to your cart!`, 'cart');

      // Close menu modal if open
      const modal = document.getElementById('menu-modal');
      if (modal && !modal.classList.contains('hidden')) {
        // Keep modal open, user can add more
      }
    } else {
      Zesto.toast(data.message || 'Could not add item.', 'error');
    }
  } catch (e) {
    Zesto.toast('Network error. Please try again.', 'error');
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add to Cart`;
    }
  }
}

// ── Update item quantity in cart ──────────────────────────────
async function updateCartQuantity(cartKey, delta) {
  try {
    const res = await fetch((window.ZESTO_BASE || '/Zesto') + '/api/cart/update.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfToken(),
      },
      body: JSON.stringify({ cart_key: cartKey, delta }),
    });
    const data = await res.json();
    if (data.success) {
      // Reload cart page to reflect changes
      location.reload();
    } else {
      Zesto.toast(data.message || 'Could not update quantity.', 'error');
    }
  } catch (e) {
    Zesto.toast('Network error.', 'error');
  }
}

// ── Remove item from cart ─────────────────────────────────────
async function removeFromCart(cartKey) {
  try {
    const res = await fetch((window.ZESTO_BASE || '/Zesto') + '/api/cart/remove.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfToken(),
      },
      body: JSON.stringify({ cart_key: cartKey }),
    });
    const data = await res.json();
    if (data.success) {
      updateCartBadge(data.cart_count);
      location.reload();
    } else {
      Zesto.toast(data.message || 'Could not remove item.', 'error');
    }
  } catch (e) {
    Zesto.toast('Network error.', 'error');
  }
}

// ── Restaurant Menu Modal ─────────────────────────────────────
async function openRestaurantMenu(slug) {
  const modal     = document.getElementById('menu-modal');
  const modalBody = document.getElementById('menu-modal-body');
  if (!modal || !modalBody) return;

  // Show loading state
  modal.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
  modalBody.innerHTML = `
    <div class="flex items-center justify-center py-24">
      <div class="spinner" style="width:2.5rem;height:2.5rem;border-width:3px;"></div>
    </div>`;

  try {
    const res  = await fetch(`${window.ZESTO_BASE || '/Zesto'}/api/restaurants/menu.php?id=${slug}`);
    const data = await res.json();

    if (!data.success) {
      modalBody.innerHTML = `<p class="text-center py-12 text-gray-500">Could not load menu.</p>`;
      return;
    }

    const r = data.restaurant;
    modalBody.innerHTML = `
      <!-- Banner -->
      <div class="relative h-44 md:h-52 shrink-0">
        <img src="${r.image}" alt="${r.name}" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent flex flex-col justify-end p-6 text-white">
          <span class="bg-[#ffdbd0] text-[#a83300] px-2 py-0.5 rounded text-[10px] font-bold uppercase w-fit mb-2">Zesto Partner Kitchen</span>
          <h3 class="text-xl md:text-2xl font-extrabold tracking-tight">${r.name}</h3>
          <div class="flex items-center gap-4 text-xs mt-1.5 opacity-90">
            <span class="flex items-center gap-1 text-amber-400 font-bold">★ ${r.rating}</span>
            <span>•</span><span>${r.delivery_time}</span>
            <span>•</span><span>${r.distance} miles away</span>
          </div>
        </div>
        <button onclick="Zesto.modal.close('menu-modal')"
                class="absolute top-4 right-4 bg-black/50 hover:bg-black/80 text-white p-1.5 rounded-full shadow-md transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <!-- Menu List -->
      <div class="p-6 overflow-y-auto flex-1 flex flex-col gap-6">
        <div class="flex justify-between items-center border-b border-gray-100 pb-3">
          <h4 class="font-bold text-[#1b1c1c] text-sm uppercase tracking-wider">Presenting Menu</h4>
          <p class="text-xs text-gray-400">Select item to add to Cart</p>
        </div>
        <div class="space-y-4">
          ${data.menu.map(item => `
            <div class="p-4 bg-[#f5f3f3]/40 border border-gray-100 rounded-xl hover:border-[#e5beb2] transition-colors flex flex-col sm:flex-row gap-4 justify-between">
              <div class="flex-1">
                <h5 class="font-bold text-sm text-[#1b1c1c]">${item.name}</h5>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">${item.description}</p>
                ${item.customization_options && item.customization_options.length > 0 ? `
                  <div class="mt-3.5 flex flex-wrap gap-1.5 items-center">
                    <span class="text-[9px] text-[#5c4037] font-bold uppercase tracking-wide mr-1">Customize:</span>
                    ${item.customization_options.map(opt => `
                      <button onclick="this.classList.toggle('active-opt'); document.getElementById('custom-${item.id}').value = this.classList.contains('active-opt') ? '${opt}' : ''"
                              class="px-2 py-0.5 rounded text-[10px] font-semibold border transition-all bg-white text-gray-500 border-gray-200 hover:bg-[#ffdbd0] hover:text-[#a83300] hover:border-[#a83300]">
                        ${opt}
                      </button>
                    `).join('')}
                    <input type="hidden" id="custom-${item.id}" value="">
                  </div>` : ''}
              </div>
              <div class="flex sm:flex-col justify-between items-end gap-3 shrink-0">
                <span class="text-[#a83300] font-extrabold text-sm">$${parseFloat(item.price).toFixed(2)}</span>
                <button data-add-cart="${item.id}"
                        onclick="addToCart('${item.id}', '${r.id}', '${r.slug}', document.getElementById('custom-${item.id}') ? document.getElementById('custom-${item.id}').value : '')"
                        class="bg-[#a83300] hover:bg-[#d24200] text-white h-8 w-32 text-xs font-bold rounded-lg flex items-center justify-center gap-1.5 transition-all active:scale-95 shadow-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  Add to Cart
                </button>
              </div>
            </div>
          `).join('')}
        </div>
      </div>

      <!-- Footer -->
      <div class="bg-[#f5f3f3] py-4 px-6 shrink-0 flex justify-between items-center border-t border-gray-100">
        <span class="text-xs text-[#5c4037] font-semibold flex items-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Sizzling hot delivery guaranteed
        </span>
        <a href="${window.ZESTO_BASE || '/Zesto'}/cart.php" class="text-xs font-bold text-[#a83300] flex items-center gap-1.5">
          <span>View My Cart</span>
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      </div>
    `;
  } catch(e) {
    modalBody.innerHTML = `<p class="text-center py-12 text-gray-500">Failed to load menu. Please try again.</p>`;
  }
}

// ── Place Order (AJAX) ────────────────────────────────────────
async function placeOrder(paymentMethod, deliveryAddress) {
  const btn = document.getElementById('place-order-btn');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner" style="width:1rem;height:1rem;border-width:2px;"></span>&nbsp;Locking Payment Securely...`;
  }

  try {
    const res = await fetch((window.ZESTO_BASE || '/Zesto') + '/api/orders/place.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfToken(),
      },
      body: JSON.stringify({ payment_method: paymentMethod, delivery_address: deliveryAddress }),
    });
    const data = await res.json();

    if (data.success) {
      window.location.href = `${window.ZESTO_BASE || '/Zesto'}/checkout.php?order=${data.order_number}`;
    } else {
      Zesto.toast(data.message || 'Order failed. Please try again.', 'error');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `PLACE ORDER`;
      }
    }
  } catch(e) {
    Zesto.toast('Network error. Please check your connection.', 'error');
    if (btn) { btn.disabled = false; btn.innerHTML = 'PLACE ORDER'; }
  }
}

// ── Init cart page interactions ───────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  // Address edit toggle
  const editAddrBtn  = document.getElementById('edit-addr-btn');
  const addrDisplay  = document.getElementById('addr-display');
  const addrEditForm = document.getElementById('addr-edit-form');

  if (editAddrBtn) {
    editAddrBtn.addEventListener('click', () => {
      addrDisplay?.classList.toggle('hidden');
      addrEditForm?.classList.toggle('hidden');
    });
  }

  const cancelAddrBtn = document.getElementById('cancel-addr-btn');
  if (cancelAddrBtn) {
    cancelAddrBtn.addEventListener('click', () => {
      addrDisplay?.classList.remove('hidden');
      addrEditForm?.classList.add('hidden');
    });
  }

  // Payment method radio highlight
  const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
  paymentRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      paymentRadios.forEach(r => {
        r.closest('label')?.classList.remove('border-[#a83300]', 'bg-[#ffdbd0]/20');
        r.closest('label')?.classList.add('border-gray-200');
      });
      this.closest('label')?.classList.add('border-[#a83300]', 'bg-[#ffdbd0]/20');
      this.closest('label')?.classList.remove('border-gray-200');
    });
  });

  // Place order button
  const orderBtn = document.getElementById('place-order-btn');
  if (orderBtn) {
    orderBtn.addEventListener('click', function() {
      const payment = document.querySelector('input[name="payment_method"]:checked')?.value || 'stripe';
      const address = document.getElementById('delivery-address-value')?.value || '';
      if (!address.trim()) {
        Zesto.toast('Please enter your delivery address.', 'error');
        return;
      }
      placeOrder(payment, address);
    });
  }
});
