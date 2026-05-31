<?php
/**
 * Zesto — Sliding Right Auth Drawer Include
 * Embeds premium Swiggy-style slide-out panel for login, registration, and guest controls
 */
?>
<!-- Auth Drawer Backdrop -->
<div id="auth-drawer-backdrop" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[9990] hidden transition-opacity duration-300 opacity-0"></div>

<!-- Auth Drawer Panel -->
<div id="auth-drawer" class="fixed top-0 right-0 bottom-0 w-full max-w-[480px] bg-white shadow-[rgba(0,0,0,0.3)_0px_0px_60px] z-[9995] hidden flex-col transition-transform duration-300 translate-x-full overflow-hidden">
  
  <!-- Header -->
  <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
    <div class="flex flex-col">
      <span class="text-xs font-bold text-[#a83300] uppercase tracking-widest">Welcome to Zesto</span>
      <h2 id="auth-drawer-title" class="text-xl font-extrabold text-[#1b1c1c] mt-0.5">Authentication</h2>
    </div>
    <button id="auth-drawer-close" class="p-2 hover:bg-gray-150 rounded-full text-gray-400 hover:text-black transition-all">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="6" x2="6" y2="18"/>
        <line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>
  </div>

  <!-- Drawer Body -->
  <div class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">

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
    <div id="auth-view-login" class="space-y-5">
      <form id="ajax-login-form" class="space-y-4">
        <div>
          <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Email Address</label>
          <input type="email" name="email" required placeholder="name@example.com" class="zesto-input bg-gray-50/50">
        </div>
        
        <div>
          <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Password</label>
          <input type="password" name="password" required placeholder="••••••••" class="zesto-input bg-gray-50/50">
        </div>

        <div>
          <label class="block text-[10px] font-bold text-[#1b1c1c] mb-1.5 uppercase tracking-wider">Sign In As Role</label>
          <div class="grid grid-cols-3 gap-2">
            <label class="cursor-pointer">
              <input type="radio" name="login_role" value="customer" checked class="sr-only">
              <div class="text-center p-2.5 border rounded-lg text-xs font-bold transition-all border-[#a83300] bg-[#ffdbd0] text-[#a83300] role-login-pill">
                Customer
              </div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="login_role" value="restaurant_owner" class="sr-only">
              <div class="text-center p-2.5 border rounded-lg text-xs font-bold transition-all border-gray-200 text-gray-500 hover:border-gray-400 role-login-pill">
                Owner
              </div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="login_role" value="delivery_partner" class="sr-only">
              <div class="text-center p-2.5 border rounded-lg text-xs font-bold transition-all border-gray-200 text-gray-500 hover:border-gray-400 role-login-pill">
                Delivery
              </div>
            </label>
          </div>
        </div>

        <button type="submit" class="w-full btn-primary h-12 justify-center font-bold tracking-wide mt-2">
          Secure Sign In
        </button>
      </form>

      <div class="relative my-6 text-center">
        <span class="absolute inset-x-0 top-1/2 h-[1px] bg-gray-100 -translate-y-1/2"></span>
        <span class="relative bg-white px-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Or Continue With</span>
      </div>

      <!-- Quick Social Signin Options -->
      <div class="grid grid-cols-2 gap-3">
        <button onclick="ZestoAuth.quickMock('phone')" class="flex items-center justify-center gap-2 py-3 border border-gray-200 hover:border-gray-400 rounded-xl text-xs font-bold text-gray-700 transition-all">
          📱 Mobile OTP
        </button>
        <button onclick="ZestoAuth.quickMock('google')" class="flex items-center justify-center gap-2 py-3 border border-gray-200 hover:border-gray-400 rounded-xl text-xs font-bold text-gray-700 transition-all">
          ✉️ Google Mail
        </button>
      </div>

      <!-- Guest Checkout Panel (Conditional) -->
      <div id="guest-checkout-action" class="hidden mt-6 bg-[#ffdbd0]/20 border border-[#e5beb2]/50 rounded-2xl p-5 text-center">
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
              <div class="text-center p-2.5 border rounded-lg text-xs font-bold transition-all border-[#a83300] bg-[#ffdbd0] text-[#a83300] role-reg-pill">
                Customer
              </div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="role" value="restaurant_owner" class="sr-only">
              <div class="text-center p-2.5 border rounded-lg text-xs font-bold transition-all border-gray-200 text-gray-500 hover:border-gray-400 role-reg-pill">
                Owner
              </div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="role" value="delivery_partner" class="sr-only">
              <div class="text-center p-2.5 border rounded-lg text-xs font-bold transition-all border-gray-200 text-gray-500 hover:border-gray-400 role-reg-pill">
                Delivery
              </div>
            </label>
          </div>
        </div>

        <!-- Additional partner forms if delivery partner chosen -->
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
          Register & Continue
        </button>
      </form>
    </div>

  </div>

  <!-- Footer -->
  <div class="p-6 border-t border-gray-100 bg-gray-50 text-center text-xs text-gray-400">
    <p>By continuing, you agree to Zesto's terms of service and dynamic privacy policy statements.</p>
  </div>
</div>
