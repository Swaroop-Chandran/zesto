<?php
/**
 * Zesto — Sliding Right Auth Drawer Include
 * Features: Swiggy-style slide-out panel, Demo Accounts quick-fill
 */
?>
<!-- Auth Drawer Backdrop -->
<div id="auth-drawer-backdrop" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[9990] hidden transition-opacity duration-300 opacity-0"></div>

<!-- Auth Drawer Panel -->
<div id="auth-drawer" class="fixed top-0 right-0 bottom-0 w-full max-w-[480px] bg-white shadow-[rgba(0,0,0,0.3)_0px_0px_60px] z-[9995] hidden flex-col transition-transform duration-300 translate-x-full overflow-hidden">
  
  <!-- Header -->
  <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white shrink-0">
    <div class="flex flex-col">
      <span class="text-xs font-bold text-[#a83300] uppercase tracking-widest">Welcome to Zesto</span>
      <h2 id="auth-drawer-title" class="text-xl font-extrabold text-[#1b1c1c] mt-0.5">Sign In or Create Account</h2>
    </div>
    <button id="auth-drawer-close" class="p-2 hover:bg-gray-100 rounded-full text-gray-400 hover:text-black transition-all">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>
  </div>

  <!-- Drawer Body -->
  <div class="flex-1 overflow-y-auto p-6 md:p-8 space-y-5">

    <!-- Mode Selector Tabs -->
    <div class="flex bg-gray-100 p-1.5 rounded-xl gap-1">
      <button id="auth-tab-login" class="flex-1 py-2.5 rounded-lg text-xs font-bold transition-all bg-white text-[#a83300] shadow-sm">
        Sign In
      </button>
      <button id="auth-tab-register" class="flex-1 py-2.5 rounded-lg text-xs font-bold text-gray-500 hover:text-gray-800 transition-all">
        Create Account
      </button>
    </div>

    <!-- ── SIGN IN FORM ────────────────────────────────────────── -->
    <div id="auth-view-login" class="space-y-4">

      <!-- Demo Accounts Quick Fill -->
      <div class="bg-[#ffdbd0]/30 border border-[#e5beb2] rounded-2xl p-4">
        <div class="flex items-center justify-between mb-2.5">
          <p class="text-[10px] font-black text-[#a83300] uppercase tracking-widest">⚡ Demo Accounts</p>
          <span class="text-[9px] text-gray-400 font-semibold">Password: Zesto@123</span>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <button type="button" onclick="ZestoAuth.fillDemo('admin@zesto.com','admin')"
                  class="text-left p-2.5 bg-white hover:bg-[#ffdbd0] border border-gray-200 hover:border-[#a83300] rounded-xl transition-all group cursor-pointer">
            <p class="text-[10px] font-black text-[#1b1c1c] group-hover:text-[#a83300]">🔐 Admin</p>
            <p class="text-[9px] text-gray-400 mt-0.5 truncate">admin@zesto.com</p>
          </button>
          <button type="button" onclick="ZestoAuth.fillDemo('alex@example.com','customer')"
                  class="text-left p-2.5 bg-white hover:bg-[#ffdbd0] border border-gray-200 hover:border-[#a83300] rounded-xl transition-all group cursor-pointer">
            <p class="text-[10px] font-black text-[#1b1c1c] group-hover:text-[#a83300]">🛍 Customer</p>
            <p class="text-[9px] text-gray-400 mt-0.5 truncate">alex@example.com</p>
          </button>
          <button type="button" onclick="ZestoAuth.fillDemo('mario@zesto.com','restaurant_owner')"
                  class="text-left p-2.5 bg-white hover:bg-[#ffdbd0] border border-gray-200 hover:border-[#a83300] rounded-xl transition-all group cursor-pointer">
            <p class="text-[10px] font-black text-[#1b1c1c] group-hover:text-[#a83300]">🍽 Owner</p>
            <p class="text-[9px] text-gray-400 mt-0.5 truncate">mario@zesto.com</p>
          </button>
          <button type="button" onclick="ZestoAuth.fillDemo('marcus@zesto.com','delivery_partner')"
                  class="text-left p-2.5 bg-white hover:bg-[#ffdbd0] border border-gray-200 hover:border-[#a83300] rounded-xl transition-all group cursor-pointer">
            <p class="text-[10px] font-black text-[#1b1c1c] group-hover:text-[#a83300]">🚴 Delivery</p>
            <p class="text-[9px] text-gray-400 mt-0.5 truncate">marcus@zesto.com</p>
          </button>
        </div>
      </div>

      <form id="ajax-login-form" class="space-y-4">
        <div>
          <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Email Address</label>
          <input id="login-email" type="email" name="email" required placeholder="name@example.com" class="zesto-input bg-gray-50/50">
        </div>
        
        <div>
          <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Password</label>
          <input id="login-password" type="password" name="password" required placeholder="••••••••" class="zesto-input bg-gray-50/50">
        </div>

        <div>
          <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Sign In As Role</label>
          <div class="grid grid-cols-4 gap-2">
            <label class="cursor-pointer">
              <input type="radio" name="login_role" value="customer" checked class="sr-only">
              <div class="text-center p-2 border rounded-lg text-[10px] font-bold transition-all border-[#a83300] bg-[#ffdbd0] text-[#a83300] role-login-pill">Customer</div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="login_role" value="restaurant_owner" class="sr-only">
              <div class="text-center p-2 border rounded-lg text-[10px] font-bold transition-all border-gray-200 text-gray-500 hover:border-gray-400 role-login-pill">Owner</div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="login_role" value="delivery_partner" class="sr-only">
              <div class="text-center p-2 border rounded-lg text-[10px] font-bold transition-all border-gray-200 text-gray-500 hover:border-gray-400 role-login-pill">Delivery</div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="login_role" value="admin" class="sr-only">
              <div class="text-center p-2 border rounded-lg text-[10px] font-bold transition-all border-gray-200 text-gray-500 hover:border-gray-400 role-login-pill">Admin</div>
            </label>
          </div>
        </div>

        <button type="submit" class="w-full btn-primary h-12 justify-center font-bold tracking-wide mt-2">
          Secure Sign In
        </button>
      </form>

      <!-- Guest Checkout Panel (shown only in checkout flow) -->
      <div id="guest-checkout-action" class="hidden mt-2 bg-[#ffdbd0]/20 border border-[#e5beb2]/50 rounded-2xl p-5 text-center">
        <h4 class="font-extrabold text-sm text-[#a83300]">Ordering in a hurry?</h4>
        <p class="text-[11px] text-gray-500 mt-1">Skip account registration and place order immediately.</p>
        <button id="auth-btn-guest" class="mt-4 w-full py-2.5 bg-[#a83300] hover:bg-[#d24200] text-white rounded-xl text-xs font-bold shadow-sm transition-all active:scale-[0.98]">
          Continue as Guest →
        </button>
      </div>
    </div>

    <!-- ── REGISTRATION FORM ────────────────────────────────────── -->
    <div id="auth-view-register" class="space-y-5 hidden">
      <form id="ajax-register-form" class="space-y-4">
        <div>
          <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Full Name *</label>
          <input type="text" name="name" required placeholder="Alex Johnson" class="zesto-input bg-gray-50/50">
        </div>

        <div>
          <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Email Address *</label>
          <input type="email" name="email" required placeholder="alex@example.com" class="zesto-input bg-gray-50/50">
        </div>

        <div>
          <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Mobile Number *</label>
          <input type="tel" name="phone" required placeholder="+91 98765 43210" class="zesto-input bg-gray-50/50">
        </div>

        <div>
          <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Join As *</label>
          <div class="grid grid-cols-3 gap-2">
            <label class="cursor-pointer">
              <input type="radio" name="role" value="customer" checked class="sr-only">
              <div class="text-center p-2.5 border rounded-lg text-xs font-bold transition-all border-[#a83300] bg-[#ffdbd0] text-[#a83300] role-reg-pill">Customer</div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="role" value="restaurant_owner" class="sr-only">
              <div class="text-center p-2.5 border rounded-lg text-xs font-bold transition-all border-gray-200 text-gray-500 hover:border-gray-400 role-reg-pill">Owner</div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="role" value="delivery_partner" class="sr-only">
              <div class="text-center p-2.5 border rounded-lg text-xs font-bold transition-all border-gray-200 text-gray-500 hover:border-gray-400 role-reg-pill">Delivery</div>
            </label>
          </div>
        </div>

        <!-- Delivery partner extra fields -->
        <div id="delivery-partner-inputs" class="hidden p-4 border border-dashed border-gray-200 rounded-2xl bg-gray-50 space-y-4">
          <p class="text-[10px] font-extrabold text-[#a83300] uppercase tracking-wider mb-2">Onboarding Documents</p>
          <div>
            <label class="block text-[10px] font-bold text-gray-600 mb-1">Vehicle Type</label>
            <select name="vehicle_type" class="zesto-input bg-white">
              <option value="bike">Motorcycle / Scooter</option>
              <option value="bicycle">Bicycle</option>
              <option value="car">Car</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-600 mb-1">Vehicle Registration Number</label>
            <input type="text" name="vehicle_number" placeholder="MH 02 AA 1234" class="zesto-input bg-white">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-600 mb-1">Driving License Number</label>
            <input type="text" name="driving_license_number" placeholder="DL-1420110012345" class="zesto-input bg-white">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Password *</label>
            <input type="password" name="password" required minlength="8" placeholder="••••••••" class="zesto-input bg-gray-50/50">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Confirm *</label>
            <input type="password" name="confirm_password" required placeholder="••••••••" class="zesto-input bg-gray-50/50">
          </div>
        </div>

        <button type="submit" class="w-full btn-primary h-12 justify-center font-bold tracking-wide mt-2">
          Register &amp; Continue
        </button>
      </form>
    </div>

  </div>

  <!-- Footer -->
  <div class="p-5 border-t border-gray-100 bg-gray-50 text-center text-xs text-gray-400 shrink-0">
    <p>By continuing, you agree to Zesto's terms of service and privacy policy.</p>
  </div>
</div>
