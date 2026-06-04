<?php
/**
 * Zesto — Sliding Right Auth Modal/Drawer Include (Zesto UI)
 */
?>
<!-- Auth Drawer Backdrop -->
<div id="auth-drawer-backdrop" class="fixed inset-0 bg-black/70 backdrop-blur-md z-[9990] hidden transition-opacity duration-300 opacity-0"></div>

<!-- Auth Drawer Panel (Flies in as a centered modal now) -->
<div id="auth-drawer" class="fixed inset-0 flex items-center justify-center z-[9995] hidden transition-transform duration-300 translate-x-full p-4">
  
  <div class="relative glass-panel-heavy rounded-2xl max-w-sm w-full p-6 sm:p-8 border border-white/15 shadow-2xl z-10 text-left space-y-6 overflow-y-auto max-h-[90vh]">
    
    <!-- Header -->
    <button id="auth-drawer-close" class="absolute top-4 right-4 p-1 rounded-full text-white/50 hover:text-white hover:bg-white/5 transition">
      <i data-lucide="x" class="w-5 h-5"></i>
    </button>

    <div class="space-y-1.5 text-center sm:text-left">
      <div class="inline-flex items-center gap-1.5 text-xs text-zesto-orange font-bold uppercase tracking-wider pl-0.5">
        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-zesto-amber"></i>
        <span>Join 2 AM Kochi Feasts</span>
      </div>
      <h2 id="auth-drawer-title" class="text-2xl font-display font-extrabold text-white tracking-tight">Welcome to the Dark</h2>
    </div>

    <!-- Mode Selector Tabs -->
    <div class="flex bg-white/5 p-1 rounded-xl gap-1">
      <button id="auth-tab-login" class="flex-1 py-2 rounded-lg text-xs font-bold transition-all bg-zesto-orange text-white shadow-sm border-none cursor-pointer">
        Sign In
      </button>
      <button id="auth-tab-register" class="flex-1 py-2 rounded-lg text-xs font-bold text-white/50 hover:text-white transition-all bg-transparent border-none cursor-pointer">
        Create Account
      </button>
    </div>

    <!-- ── SIGN IN FORM ────────────────────────────────────────── -->
    <div id="auth-view-login" class="space-y-4">

      <!-- Demo accounts quick fill panel disabled/removed -->

      <form id="ajax-login-form" class="space-y-4">
        <?= csrfField() ?>
        <div>
          <label class="block text-[10px] text-white/50 font-bold uppercase tracking-wider pl-1 font-mono mb-1">Email Address</label>
          <input id="login-email" type="email" name="email" required placeholder="owl@zestonights.com" class="w-full bg-[#10141a] border border-white/10 text-white rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-zesto-orange focus:ring-1 focus:ring-zesto-orange placeholder:text-white/20">
        </div>
        
        <div>
          <label class="block text-[10px] text-white/50 font-bold uppercase tracking-wider pl-1 font-mono mb-1">Secret Password</label>
          <input id="login-password" type="password" name="password" required placeholder="••••••••" class="w-full bg-[#10141a] border border-white/10 text-white rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-zesto-orange focus:ring-1 focus:ring-zesto-orange placeholder:text-white/20">
        </div>

        <div>
          <label class="block text-[10px] text-white/50 font-bold uppercase tracking-wider pl-1 font-mono mb-1">Sign In As Role</label>
          <div class="grid grid-cols-3 gap-2">
            <label class="cursor-pointer">
              <input type="radio" name="login_role" value="customer" checked class="sr-only">
              <div class="text-center p-1.5 border rounded-md text-[9px] font-bold transition-all border-zesto-orange bg-zesto-orange/20 text-zesto-orange role-login-pill">Customer</div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="login_role" value="restaurant_owner" class="sr-only">
              <div class="text-center p-1.5 border rounded-md text-[9px] font-bold transition-all border-white/10 text-white/50 hover:border-white/30 role-login-pill">Owner</div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="login_role" value="delivery_partner" class="sr-only">
              <div class="text-center p-1.5 border rounded-md text-[9px] font-bold transition-all border-white/10 text-white/50 hover:border-white/30 role-login-pill">Delivery</div>
            </label>
          </div>
        </div>

        <button type="submit" class="w-full py-3 bg-zesto-orange text-white hover:bg-zesto-orange/95 rounded-full text-xs font-extrabold flex items-center justify-center gap-2 transition active:scale-98 fire-glow cursor-pointer mt-4">
          <i data-lucide="log-in" class="w-4 h-4"></i>
          <span>Enter Midnight Mode</span>
        </button>
      </form>

      <!-- Guest Checkout Panel (shown only in checkout flow) -->
      <div id="guest-checkout-action" class="hidden mt-2 bg-zesto-orange/10 border border-zesto-orange/30 rounded-xl p-4 text-center">
        <h4 class="font-extrabold text-sm text-zesto-orange">Ordering in a hurry?</h4>
        <p class="text-[10px] text-white/50 mt-1">Skip account registration and place order immediately.</p>
        <button id="auth-btn-guest" class="mt-3 w-full py-2 bg-zesto-amber/20 hover:bg-zesto-amber text-zesto-orange hover:text-[#10141a] rounded-lg text-xs font-bold transition-all border border-zesto-amber cursor-pointer">
          Continue as Guest →
        </button>
      </div>
    </div>

    <!-- ── REGISTRATION FORM ────────────────────────────────────── -->
    <div id="auth-view-register" class="space-y-4 hidden">
      <form id="ajax-register-form" class="space-y-3">
        <?= csrfField() ?>
        <div>
          <label class="block text-[10px] text-white/50 font-bold uppercase tracking-wider pl-1 font-mono mb-1">Full Name *</label>
          <input type="text" name="name" required placeholder="Alex Johnson" class="w-full bg-[#10141a] border border-white/10 text-white rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-zesto-orange focus:ring-1 focus:ring-zesto-orange placeholder:text-white/20">
        </div>

        <div>
          <label class="block text-[10px] text-white/50 font-bold uppercase tracking-wider pl-1 font-mono mb-1">Email Address *</label>
          <input type="email" name="email" required placeholder="alex@example.com" class="w-full bg-[#10141a] border border-white/10 text-white rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-zesto-orange focus:ring-1 focus:ring-zesto-orange placeholder:text-white/20">
        </div>

        <div>
          <label class="block text-[10px] text-white/50 font-bold uppercase tracking-wider pl-1 font-mono mb-1">Mobile Number *</label>
          <input type="tel" name="phone" required placeholder="+91 98765 43210" class="w-full bg-[#10141a] border border-white/10 text-white rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-zesto-orange focus:ring-1 focus:ring-zesto-orange placeholder:text-white/20">
        </div>

        <div>
          <label class="block text-[10px] text-white/50 font-bold uppercase tracking-wider pl-1 font-mono mb-1">Join As *</label>
          <div class="grid grid-cols-3 gap-2">
            <label class="cursor-pointer">
              <input type="radio" name="role" value="customer" checked class="sr-only">
              <div class="text-center p-2 border rounded-lg text-[10px] font-bold transition-all border-zesto-orange bg-zesto-orange/20 text-zesto-orange role-reg-pill">Customer</div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="role" value="restaurant_owner" class="sr-only">
              <div class="text-center p-2 border rounded-lg text-[10px] font-bold transition-all border-white/10 text-white/50 hover:border-white/30 role-reg-pill">Owner</div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="role" value="delivery_partner" class="sr-only">
              <div class="text-center p-2 border rounded-lg text-[10px] font-bold transition-all border-white/10 text-white/50 hover:border-white/30 role-reg-pill">Delivery</div>
            </label>
          </div>
        </div>

        <!-- Delivery partner extra fields -->
        <div id="delivery-partner-inputs" class="hidden p-3 border border-dashed border-white/20 rounded-xl bg-white/5 space-y-3">
          <p class="text-[10px] font-extrabold text-zesto-orange uppercase tracking-wider">Onboarding Documents</p>
          <div>
            <label class="block text-[10px] text-white/50 font-bold mb-1">Vehicle Type</label>
            <select name="vehicle_type" class="w-full bg-[#10141a] border border-white/10 text-white rounded-lg px-2 py-1.5 text-xs focus:outline-none">
              <option value="bike">Motorcycle / Scooter</option>
              <option value="bicycle">Bicycle</option>
              <option value="car">Car</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] text-white/50 font-bold mb-1">Vehicle Registration Number</label>
            <input type="text" name="vehicle_number" placeholder="KL 07 AA 1234" class="w-full bg-[#10141a] border border-white/10 text-white rounded-lg px-2 py-1.5 text-xs focus:outline-none">
          </div>
          <div>
            <label class="block text-[10px] text-white/50 font-bold mb-1">Driving License Number</label>
            <input type="text" name="driving_license_number" placeholder="DL-1420110012345" class="w-full bg-[#10141a] border border-white/10 text-white rounded-lg px-2 py-1.5 text-xs focus:outline-none">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-[10px] text-white/50 font-bold uppercase tracking-wider pl-1 font-mono mb-1">Password *</label>
            <input type="password" name="password" required minlength="8" placeholder="••••••••" class="w-full bg-[#10141a] border border-white/10 text-white rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-zesto-orange focus:ring-1 focus:ring-zesto-orange placeholder:text-white/20">
          </div>
          <div>
            <label class="block text-[10px] text-white/50 font-bold uppercase tracking-wider pl-1 font-mono mb-1">Confirm *</label>
            <input type="password" name="confirm_password" required placeholder="••••••••" class="w-full bg-[#10141a] border border-white/10 text-white rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-zesto-orange focus:ring-1 focus:ring-zesto-orange placeholder:text-white/20">
          </div>
        </div>

        <button type="submit" class="w-full py-3 bg-zesto-orange text-white hover:bg-zesto-orange/95 rounded-full text-xs font-extrabold flex items-center justify-center gap-2 transition active:scale-98 fire-glow cursor-pointer mt-4">
          <i data-lucide="user-plus" class="w-4 h-4"></i>
          <span>Sign Up Account</span>
        </button>
      </form>
    </div>

  </div>
</div>

<script>
  // Ensure the tabs styling logic in auth.js is slightly tweaked to match new classes
  // In a real project we might override ZestoAuth.switchTab here or adjust auth.js.
  // We'll override the tab switcher here to use dark mode classes safely.
  
  document.addEventListener('DOMContentLoaded', function() {
    const origSwitchTab = window.ZestoAuth.switchTab;
    window.ZestoAuth.switchTab = function(tabName) {
      const loginTab   = document.getElementById('auth-tab-login');
      const registerTab = document.getElementById('auth-tab-register');
      const loginView  = document.getElementById('auth-view-login');
      const registerView = document.getElementById('auth-view-register');
      const title = document.getElementById('auth-drawer-title');

      if (tabName === 'login') {
        loginTab.className = "flex-1 py-2 rounded-lg text-xs font-bold transition-all bg-zesto-orange text-white shadow-sm border-none cursor-pointer";
        registerTab.className = "flex-1 py-2 rounded-lg text-xs font-bold text-white/50 hover:text-white transition-all bg-transparent border-none cursor-pointer";
        loginView.classList.remove('hidden');
        registerView.classList.add('hidden');
        if (title) title.innerText = "Welcome to the Dark";
      } else {
        registerTab.className = "flex-1 py-2 rounded-lg text-xs font-bold transition-all bg-zesto-orange text-white shadow-sm border-none cursor-pointer";
        loginTab.className = "flex-1 py-2 rounded-lg text-xs font-bold text-white/50 hover:text-white transition-all bg-transparent border-none cursor-pointer";
        registerView.classList.remove('hidden');
        loginView.classList.add('hidden');
        if (title) title.innerText = "Create Night Account";
      }
    };

    // Override role pill switch styles for Login
    const roleLoginLabels = document.querySelectorAll('.role-login-pill');
    const roleLoginInputs = document.querySelectorAll('input[name="login_role"]');
    roleLoginInputs.forEach((input, index) => {
      input.addEventListener('change', function() {
        roleLoginLabels.forEach(lbl => {
          lbl.className = 'text-center p-1.5 border rounded-md text-[9px] font-bold transition-all border-white/10 text-white/50 hover:border-white/30 role-login-pill';
        });
        if (roleLoginLabels[index]) {
          roleLoginLabels[index].className = 'text-center p-1.5 border rounded-md text-[9px] font-bold transition-all border-zesto-orange bg-zesto-orange/20 text-zesto-orange role-login-pill';
        }
      });
    });

    // Override role pill switch styles for Register
    const roleRegLabels = document.querySelectorAll('.role-reg-pill');
    const roleRegInputs = document.querySelectorAll('input[name="role"]');
    roleRegInputs.forEach((input, index) => {
      input.addEventListener('change', function() {
        roleRegLabels.forEach(lbl => {
          lbl.className = "text-center p-2 border rounded-lg text-[10px] font-bold transition-all border-white/10 text-white/50 hover:border-white/30 role-reg-pill";
        });
        roleRegLabels[index].className = "text-center p-2 border rounded-lg text-[10px] font-bold transition-all border-zesto-orange bg-zesto-orange/20 text-zesto-orange role-reg-pill";
      });
    });
    
    // fillDemo override removed
  });
</script>
