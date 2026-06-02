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

window.cartQty = window.cartQty || {};

// ── Cart Conflict Modal ───────────────────────────────────────
let _conflictModalOpen = false;
let _pendingConflict   = null;

function getOrCreateConflictModal() {
  let modal = document.getElementById('cart-conflict-modal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'cart-conflict-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'ccm-title');
    modal.className = 'hidden fixed inset-0 z-[9999] flex items-center justify-center p-4';
    modal.innerHTML = `
      <div id="cart-conflict-backdrop" class="absolute inset-0 bg-black/75 backdrop-blur-sm"></div>
      <div class="relative bg-zinc-900 border border-white/10 rounded-2xl shadow-2xl p-6 max-w-sm w-full z-10 animate-scale-up">
        <div class="flex items-start gap-4 mb-5">
          <div class="w-11 h-11 rounded-full bg-amber-400/10 flex items-center justify-center shrink-0 text-2xl" aria-hidden="true">🛒</div>
          <div>
            <h3 id="ccm-title" class="text-white font-extrabold text-base leading-tight">Different Restaurant</h3>
            <p class="text-zinc-400 text-sm mt-2 leading-relaxed">
              Your cart contains items from another restaurant.<br>
              Would you like to clear the cart and start a new order?
            </p>
          </div>
        </div>
        <div class="flex gap-3">
          <button id="cart-conflict-cancel"
                  class="flex-1 py-2.5 px-4 rounded-xl border border-white/10 text-white/70 font-bold text-sm hover:bg-white/5 transition-colors cursor-pointer bg-transparent">
            Cancel
          </button>
          <button id="cart-conflict-clear"
                  class="flex-1 py-2.5 px-4 rounded-xl bg-amber-400 text-zinc-950 font-extrabold text-sm hover:bg-amber-500 active:scale-95 transition-all cursor-pointer border-none">
            Clear Cart &amp; Continue
          </button>
        </div>
      </div>`;
    document.body.appendChild(modal);
    document.getElementById('cart-conflict-backdrop').addEventListener('click', closeConflictModal);
    document.getElementById('cart-conflict-cancel').addEventListener('click', closeConflictModal);
    document.getElementById('cart-conflict-clear').addEventListener('click', handleClearAndContinue);
  }
  return modal;
}

function openConflictModal(itemId, restaurantId, restaurantSlug, customization) {
  if (_conflictModalOpen) return;      // only one modal at a time
  _conflictModalOpen = true;
  _pendingConflict   = { itemId, restaurantId, restaurantSlug, customization };
  const modal = getOrCreateConflictModal();
  modal.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeConflictModal() {
  const modal = document.getElementById('cart-conflict-modal');
  if (modal) modal.classList.add('hidden');
  document.body.style.overflow = '';
  _conflictModalOpen = false;
  _pendingConflict   = null;
}

async function handleClearAndContinue() {
  if (!_pendingConflict) { closeConflictModal(); return; }

  const clearBtn = document.getElementById('cart-conflict-clear');
  if (clearBtn) {
    clearBtn.disabled = true;
    clearBtn.innerHTML = `<span class="spinner" style="width:1rem;height:1rem;border-width:2px;display:inline-block;vertical-align:middle;border-radius:50%;border:2px solid #09090b;border-right-color:transparent;animation:spin 0.7s linear infinite;"></span>`;
  }

  try {
    const res  = await fetch((window.ZESTO_BASE || '') + '/api/cart/clear.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
    });
    const data = await res.json();

    if (data.success) {
      window.cartQty = {};
      updateCartBadge(0);
      const { itemId, restaurantId, restaurantSlug, customization } = _pendingConflict;
      closeConflictModal();
      await cartAdd(itemId, restaurantId, restaurantSlug, customization);
    } else {
      Zesto.toast('Could not clear cart. Please try again.', 'error');
      closeConflictModal();
    }
  } catch (e) {
    Zesto.toast('Network error. Please try again.', 'error');
    closeConflictModal();
  }
}

async function cartAdd(itemId, restaurantId, restaurantSlug, customization = '') {
  const wrap = document.getElementById('wrap-' + itemId);
  let btn = null;
  if (wrap) {
    btn = wrap.querySelector('button');
  } else {
    btn = document.querySelector(`[data-add-cart="${itemId}"]`);
  }
  
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner" style="width:1rem;height:1rem;border-width:2px;display:inline-block;vertical-align:middle;border-radius:50%;border:2px solid currentColor;border-right-color:transparent;animation:spin 1s linear infinite;"></span>`;
  }

  try {
    const res = await fetch((window.ZESTO_BASE || '') + '/api/cart/add.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
      body: JSON.stringify({ menu_item_id: itemId, restaurant_id: restaurantId, customization })
    });
    const data = await res.json();

    if (data.success) {
      window.cartQty[itemId] = (window.cartQty[itemId] || 0) + 1;
      
      if (wrap) {
        renderStepper(itemId, restaurantId, restaurantSlug);
      } else if (btn) {
        btn.disabled = false;
        btn.innerHTML = `Added ✓`;
      }
      
      updateCartBadge(data.cart_count);
      Zesto.toast('🛒 Added to your cart!', 'cart');
    } else if (data.conflict) {
      // Cross-restaurant conflict — show modal once, no toast
      restoreAddBtn(itemId, restaurantId, restaurantSlug, wrap, btn);
      if (window.lucide) lucide.createIcons();
      openConflictModal(itemId, restaurantId, restaurantSlug, customization);
    } else {
      restoreAddBtn(itemId, restaurantId, restaurantSlug, wrap, btn);
      if (window.lucide) lucide.createIcons();
      Zesto.toast(data.message || 'Could not add item.', 'error');
    }
  } catch(e) {
    restoreAddBtn(itemId, restaurantId, restaurantSlug, wrap, btn);
    if (window.lucide) lucide.createIcons();
    Zesto.toast('Network error.', 'error');
  }
}

function restoreAddBtn(itemId, restaurantId, restaurantSlug, wrap, btn) {
  if (wrap) {
    const theme = wrap.dataset.theme || 'light';
    const img = wrap.querySelector('img');
    if (theme === 'modal') {
      const priceVal = parseFloat(wrap.dataset.price || 0);
      const priceText = priceVal > 0 ? `<span class="text-zesto-orange font-extrabold text-sm">&#8377;${priceVal.toFixed(0)}</span>` : '';
      wrap.innerHTML = `
        ${img ? img.outerHTML : ''}
        ${priceText}
        <button data-add-cart="${itemId}"
                onclick="cartAdd('${itemId}', '${restaurantId}', '${restaurantSlug}', document.getElementById('custom-${itemId}') ? document.getElementById('custom-${itemId}').value : '')"
                class="zesto-add-btn w-full">
          + Add
        </button>`;
    } else {
      wrap.innerHTML = `
        ${img ? img.outerHTML : ''}
        <button
          onclick="event.preventDefault(); event.stopPropagation(); cartAdd(${itemId}, ${restaurantId}, '${restaurantSlug}')"
          class="zesto-add-btn w-full mt-auto"
        >
          <span>+ Add</span>
        </button>`;
    }
  } else if (btn) {
    btn.disabled = false;
    btn.className = "zesto-add-btn";
    btn.innerHTML = `+ Add`;
  }
}

function renderStepper(itemId, restaurantId, restaurantSlug) {
  const wrap = document.getElementById('wrap-' + itemId);
  if (!wrap) return;
  const qty = window.cartQty[itemId] || 1;
  
  const img = wrap.querySelector('img');
  const theme = wrap.dataset.theme || 'light';
  const priceSpan = theme === 'modal' ? `<span class="text-zesto-orange font-extrabold text-sm">$${parseFloat(wrap.dataset.price || 0).toFixed(2)}</span>` : '';
  const containerClass = theme === 'modal' ? 'w-32 mt-auto' : 'w-full mt-auto';
  
  wrap.innerHTML = `
    ${img ? img.outerHTML : ''}
    ${priceSpan}
    <div class="qty-stepper ${containerClass}">
      <button class="qty-stepper-btn" onclick="event.preventDefault(); event.stopPropagation(); cartDecrement(${itemId},${restaurantId},'${restaurantSlug}')">−</button>
      <span class="qty-stepper-count">${qty}</span>
      <button class="qty-stepper-btn" onclick="event.preventDefault(); event.stopPropagation(); cartIncrement(${itemId},${restaurantId},'${restaurantSlug}')">+</button>
    </div>`;
}

async function cartIncrement(itemId, restaurantId, restaurantSlug) {
  try {
    const res = await fetch((window.ZESTO_BASE || '') + '/api/cart/add.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
      body: JSON.stringify({ menu_item_id: itemId, restaurant_id: restaurantId, delta: 1 })
    });
    const data = await res.json();
    if (data.success) {
      window.cartQty[itemId] = (window.cartQty[itemId] || 1) + 1;
      renderStepper(itemId, restaurantId, restaurantSlug);
      updateCartBadge(data.cart_count);
    }
  } catch(e) {}
}

async function cartDecrement(itemId, restaurantId, restaurantSlug) {
  window.cartQty[itemId] = Math.max(0, (window.cartQty[itemId] || 1) - 1);
  if (window.cartQty[itemId] === 0) {
    try {
      const res = await fetch((window.ZESTO_BASE || '') + '/api/cart/remove.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
        body: JSON.stringify({ menu_item_id: itemId })
      });
      const data = await res.json();
      if (data.success) {
        updateCartBadge(data.cart_count);
      }
    } catch(e) {}
    
    const wrap = document.getElementById('wrap-' + itemId);
    restoreAddBtn(itemId, restaurantId, restaurantSlug, wrap, null);
    if (window.lucide) lucide.createIcons();
  } else {
    renderStepper(itemId, restaurantId, restaurantSlug);
    try {
      await fetch((window.ZESTO_BASE || '') + '/api/cart/update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
        body: JSON.stringify({ menu_item_id: itemId, delta: -1 })
      });
    } catch(e) {}
  }
}

// ── Alias for backwards compatibility ───────────────────────────
window.addToCart = cartAdd;

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
      <div class="relative bg-black/40 h-40 md:h-56 shrink-0 flex flex-col justify-end p-6">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('${(window.ZESTO_BASE||'')+'/assets/img/restaurants/'+r.image}')"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-zesto-dark via-zesto-dark/70 to-transparent"></div>
        <div class="relative z-10 text-white">
          <span class="bg-zesto-orange/20 text-zesto-orange border border-zesto-orange/30 px-2 py-0.5 rounded text-[10px] font-bold uppercase w-fit mb-2">Zesto Partner Kitchen</span>
          <h3 class="text-xl md:text-2xl font-extrabold tracking-tight">${r.name}</h3>
          <div class="flex items-center gap-4 text-xs mt-1.5 opacity-90">
            <span class="flex items-center gap-1 text-amber-400 font-bold">★ ${r.rating}</span>
            <span>•</span><span>${r.delivery_time}</span>
            <span>•</span><span>${r.distance} miles away</span>
          </div>
        </div>
        <button onclick="Zesto.modal.close('menu-modal')"
                class="absolute top-4 right-4 bg-black/50 hover:bg-black/80 text-white p-1.5 rounded-full shadow-md transition-colors cursor-pointer border-none">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <!-- Menu List -->
      <div class="p-6 overflow-y-auto flex-1 flex flex-col gap-6 bg-zesto-dark">
        <div class="flex justify-between items-center border-b border-white/10 pb-3">
          <h4 class="font-bold text-white text-sm uppercase tracking-wider">Presenting Menu</h4>
          <p class="text-xs text-white/50">Select item to add to Cart</p>
        </div>
        <div class="space-y-4">
          ${data.menu.map(item => `
            <div class="p-4 glass-panel border border-white/10 rounded-xl hover:border-zesto-orange/30 transition-colors flex flex-col sm:flex-row gap-4 justify-between">
              <div class="flex-1">
                <h5 class="font-bold text-sm text-white">${item.name}</h5>
                <p class="text-xs text-white/60 mt-1 leading-relaxed">${item.description}</p>
                ${item.customization_options && item.customization_options.length > 0 ? `
                  <div class="mt-3.5 flex flex-wrap gap-1.5 items-center">
                    <span class="text-[9px] text-white/50 font-bold uppercase tracking-wide mr-1">Customize:</span>
                    ${item.customization_options.map(opt => `
                      <button onclick="this.classList.toggle('active-opt'); document.getElementById('custom-${item.id}').value = this.classList.contains('active-opt') ? '${opt}' : ''"
                              class="px-2 py-0.5 rounded text-[10px] font-semibold border transition-all bg-white/5 text-white/70 border-white/10 hover:bg-zesto-orange/20 hover:text-zesto-orange hover:border-zesto-orange cursor-pointer">
                        ${opt}
                      </button>
                    `).join('')}
                    <input type="hidden" id="custom-${item.id}" value="">
                  </div>` : ''}
              </div>
              <div id="wrap-${item.id}" data-theme="modal" data-price="${item.price}" class="flex sm:flex-col justify-between items-end gap-3 shrink-0 w-32">
                <span class="text-zesto-orange font-extrabold text-sm">&#8377;${parseFloat(item.price).toFixed(0)}</span>
                <button data-add-cart="${item.id}"
                        onclick="cartAdd('${item.id}', '${r.id}', '${r.slug}', document.getElementById('custom-${item.id}') ? document.getElementById('custom-${item.id}').value : '')"
                        class="zesto-add-btn w-full">
                  + Add
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
async function placeOrder(paymentMethod, deliveryAddress, couponCode = null) {
  const btn = document.getElementById('cart-order-btn') || document.getElementById('place-order-btn');
  const endpoint = paymentMethod === 'stripe'
      ? '/api/checkout/create_checkout_session.php'
      : '/api/orders/place.php';

  console.log('[Zesto Checkout] placeOrder called', {
    paymentMethod,
    endpoint,
    method: 'POST',
    hasDeliveryAddress: Boolean(deliveryAddress && deliveryAddress.trim()),
    couponCode: couponCode || null
  });

  if (btn) {
    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.dataset.originalHtml = origHtml;
    btn.innerHTML = `<span class="spinner" style="width:1rem;height:1rem;border-width:2px;display:inline-block;vertical-align:middle;border-radius:50%;border:2px solid currentColor;border-right-color:transparent;animation:spin 1s linear infinite;"></span>&nbsp;${paymentMethod === 'stripe' ? 'Opening Stripe Checkout...' : 'Placing Order Securely...'}`;
  }

  try {
    console.log('[Zesto Checkout] fetch checkout request', {
      url: (window.ZESTO_BASE || '/Zesto') + endpoint,
      method: 'POST'
    });
    const res = await fetch((window.ZESTO_BASE || '/Zesto') + endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfToken(),
      },
      body: JSON.stringify({ 
        payment_method: paymentMethod, 
        delivery_address: deliveryAddress,
        coupon_code: couponCode
      }),
    });
    const data = await res.json();
    console.log('[Zesto Checkout] Response', { status: res.status, data });

    if (data.success) {
      if (paymentMethod === 'stripe' && data.url) {
          console.log('[Zesto Checkout] Redirecting to Stripe Checkout', data.url);
          window.location.href = data.url;
      } else {
          console.log('[Zesto Checkout] Redirecting to order confirmation', data.order_number);
          window.location.href = `${window.ZESTO_BASE || '/Zesto'}/checkout.php?order=${data.order_number}`;
      }
    } else {
      Zesto.toast(data.message || 'Order failed. Please try again.', 'error');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalHtml || `<span>PAY NOW</span>`;
      }
    }
  } catch(e) {
    console.error('[Zesto Checkout] Request failed', e);
    Zesto.toast('Network error. Please check your connection.', 'error');
    if (btn) { btn.disabled = false; btn.innerHTML = btn.dataset.originalHtml || 'PAY NOW'; }
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
      console.log('[Zesto Checkout] Payment method selected', this.value);
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
      console.log('[Zesto Checkout] Pay Now clicked', { payment, hasAddress: Boolean(address.trim()) });
      if (!address.trim()) {
        Zesto.toast('Please enter your delivery address.', 'error');
        return;
      }
      placeOrder(payment, address);
    });
  }
});
