/**
 * Zesto — AJAX Right Authentication sliding drawer JS
 */

window.ZestoAuth = {
  // Flag to know if guest checkout mode is active
  isCheckoutFlow: false,

  open: function(options = {}) {
    const backdrop = document.getElementById('auth-drawer-backdrop');
    const drawer   = document.getElementById('auth-drawer');
    const guestBox = document.getElementById('guest-checkout-action');

    if (!backdrop || !drawer) return;

    this.isCheckoutFlow = !!options.checkout;
    if (this.isCheckoutFlow && guestBox) {
      guestBox.classList.remove('hidden');
    } else if (guestBox) {
      guestBox.classList.add('hidden');
    }

    // Toggle visibility
    backdrop.classList.remove('hidden');
    drawer.classList.remove('hidden');
    
    // Animate
    setTimeout(() => {
      backdrop.classList.add('opacity-100');
      backdrop.classList.remove('opacity-0');
      drawer.classList.remove('translate-x-full');
    }, 10);

    document.body.style.overflow = 'hidden';
  },

  close: function() {
    const backdrop = document.getElementById('auth-drawer-backdrop');
    const drawer   = document.getElementById('auth-drawer');

    if (!backdrop || !drawer) return;

    backdrop.classList.remove('opacity-100');
    backdrop.classList.add('opacity-0');
    drawer.classList.add('translate-x-full');

    setTimeout(() => {
      backdrop.classList.add('hidden');
      drawer.classList.add('hidden');
    }, 300);

    document.body.style.overflow = '';
  },

  switchTab: function(tabName) {
    const loginTab   = document.getElementById('auth-tab-login');
    const registerTab = document.getElementById('auth-tab-register');
    const loginView  = document.getElementById('auth-view-login');
    const registerView = document.getElementById('auth-view-register');

    if (tabName === 'login') {
      loginTab.className = "flex-1 py-2.5 rounded-lg text-xs font-bold transition-all bg-white text-[#a83300] shadow-sm";
      registerTab.className = "flex-1 py-2.5 rounded-lg text-xs font-bold text-gray-500 hover:text-gray-800 transition-all";
      loginView.classList.remove('hidden');
      registerView.classList.add('hidden');
    } else {
      registerTab.className = "flex-1 py-2.5 rounded-lg text-xs font-bold transition-all bg-white text-[#a83300] shadow-sm";
      loginTab.className = "flex-1 py-2.5 rounded-lg text-xs font-bold text-gray-500 hover:text-gray-800 transition-all";
      registerView.classList.remove('hidden');
      loginView.classList.add('hidden');
    }
  },

  // Fill demo account credentials into the login form
  fillDemo: function(email, role) {
    const emailInput = document.getElementById('login-email');
    const passInput  = document.getElementById('login-password');
    if (emailInput) emailInput.value = email;
    if (passInput)  passInput.value  = 'Zesto@123';

    // Select the correct role radio
    const roleMap = {
      'admin':            'admin',
      'customer':         'customer',
      'restaurant_owner': 'restaurant_owner',
      'delivery_partner': 'delivery_partner'
    };
    const roleInputs = document.querySelectorAll('input[name="login_role"]');
    const roleLabels = document.querySelectorAll('.role-login-pill');
    roleInputs.forEach((input, i) => {
      if (input.value === role) {
        input.checked = true;
        roleLabels.forEach(l => {
          l.className = 'text-center p-2 border rounded-lg text-[10px] font-bold transition-all border-gray-200 text-gray-500 hover:border-gray-400 role-login-pill';
        });
        if (roleLabels[i]) {
          roleLabels[i].className = 'text-center p-2 border rounded-lg text-[10px] font-bold transition-all border-[#a83300] bg-[#ffdbd0] text-[#a83300] role-login-pill';
        }
      }
    });

    Zesto.toast(`🧑 Demo: ${email} filled — click Sign In`, 'info');
  },

  // Mock social signins
  quickMock: async function(type) {
    Zesto.toast(`💬 Attempting demo ${type} login...`, 'info');
    this.fillDemo('alex@example.com', 'customer');
  }
};

// Event Listeners Initialization
document.addEventListener('DOMContentLoaded', function() {
  const backdrop = document.getElementById('auth-drawer-backdrop');
  const closeBtn = document.getElementById('auth-drawer-close');
  const tabLogin = document.getElementById('auth-tab-login');
  const tabRegister = document.getElementById('auth-tab-register');
  const guestBtn = document.getElementById('auth-btn-guest');

  if (backdrop) backdrop.addEventListener('click', () => ZestoAuth.close());
  if (closeBtn) closeBtn.addEventListener('click', () => ZestoAuth.close());
  
  if (tabLogin) tabLogin.addEventListener('click', () => ZestoAuth.switchTab('login'));
  if (tabRegister) tabRegister.addEventListener('click', () => ZestoAuth.switchTab('register'));

  // Role selections in Login Form
  const roleLoginLabels = document.querySelectorAll('.role-login-pill');
  const roleLoginInputs = document.querySelectorAll('input[name="login_role"]');
  roleLoginInputs.forEach((input, index) => {
    input.addEventListener('change', function() {
      roleLoginLabels.forEach(lbl => {
        lbl.className = 'text-center p-2 border rounded-lg text-[10px] font-bold transition-all border-gray-200 text-gray-500 hover:border-gray-400 role-login-pill';
      });
      if (roleLoginLabels[index]) {
        roleLoginLabels[index].className = 'text-center p-2 border rounded-lg text-[10px] font-bold transition-all border-[#a83300] bg-[#ffdbd0] text-[#a83300] role-login-pill';
      }
    });
  });

  // Role selections in Register Form
  const roleRegLabels = document.querySelectorAll('.role-reg-pill');
  const roleRegInputs = document.querySelectorAll('input[name="role"]');
  const partnerInputs = document.getElementById('delivery-partner-inputs');

  roleRegInputs.forEach((input, index) => {
    input.addEventListener('change', function() {
      roleRegLabels.forEach(lbl => {
        lbl.className = "text-center p-2.5 border rounded-lg text-xs font-bold transition-all border-gray-200 text-gray-500 hover:border-gray-400 role-reg-pill";
      });
      roleRegLabels[index].className = "text-center p-2.5 border rounded-lg text-xs font-bold transition-all border-[#a83300] bg-[#ffdbd0] text-[#a83300] role-reg-pill";

      // Show delivery partner fields if selected
      if (this.value === 'delivery_partner') {
        partnerInputs?.classList.remove('hidden');
      } else {
        partnerInputs?.classList.add('hidden');
      }
    });
  });

  // Handle forms submissions
  const loginForm = document.getElementById('ajax-login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      const email = this.querySelector('input[name="email"]').value.trim();
      const password = this.querySelector('input[name="password"]').value;
      const role = this.querySelector('input[name="login_role"]:checked').value;

      try {
        const csrfToken = this.querySelector('input[name="csrf_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.content || '';
        const res = await fetch((window.ZESTO_BASE || '/Zesto') + '/api/auth/login.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          body: JSON.stringify({ email, password, role })
        });
        const data = await res.json();
        
        if (data.success) {
          Zesto.toast(data.message, 'success');
          ZestoAuth.close();
          setTimeout(() => {
            if (ZestoAuth.isCheckoutFlow) {
              location.reload();
            } else {
              window.location.href = data.redirect;
            }
          }, 800);
        } else {
          Zesto.toast(data.message || 'Login failed.', 'error');
        }
      } catch(e) {
        Zesto.toast('Connection error.', 'error');
      }
    });
  }

  const registerForm = document.getElementById('ajax-register-form');
  if (registerForm) {
    registerForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      const name = this.querySelector('input[name="name"]').value.trim();
      const email = this.querySelector('input[name="email"]').value.trim();
      const phone = this.querySelector('input[name="phone"]').value.trim();
      const password = this.querySelector('input[name="password"]').value;
      const confirm_password = this.querySelector('input[name="confirm_password"]').value;
      const role = this.querySelector('input[name="role"]:checked').value;

      if (password !== confirm_password) {
        Zesto.toast('Passwords do not match.', 'error');
        return;
      }

      // Collect delivery partner specific details
      let dpFields = {};
      if (role === 'delivery_partner') {
        dpFields.vehicle_type = this.querySelector('select[name="vehicle_type"]').value;
        dpFields.vehicle_number = this.querySelector('input[name="vehicle_number"]').value.trim();
        dpFields.driving_license_number = this.querySelector('input[name="driving_license_number"]').value.trim();
      }

      try {
        const csrfToken = this.querySelector('input[name="csrf_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.content || '';
        const res = await fetch((window.ZESTO_BASE || '/Zesto') + '/api/auth/register.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          body: JSON.stringify({ name, email, phone, password, role, ...dpFields })
        });
        const data = await res.json();
        
        if (data.success) {
          Zesto.toast(data.message, 'success');
          ZestoAuth.close();
          setTimeout(() => {
            if (role === 'delivery_partner') {
              window.location.href = (window.ZESTO_BASE || '/Zesto') + '/index.php';
            } else if (ZestoAuth.isCheckoutFlow) {
              location.reload();
            } else {
              window.location.href = data.redirect;
            }
          }, 1500);
        } else {
          Zesto.toast(data.message || data.errors?.[0] || 'Registration failed.', 'error');
        }
      } catch(e) {
        Zesto.toast('Connection error.', 'error');
      }
    });
  }

  // Continue as Guest Handler
  if (guestBtn) {
    guestBtn.addEventListener('click', function() {
      // Set a mock session guest key via ajax, then refresh the page to show checkout
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
      fetch((window.ZESTO_BASE || '/Zesto') + '/api/auth/guest-checkout.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        }
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          Zesto.toast('🛒 Checkout continuing as Guest!', 'success');
          ZestoAuth.close();
          setTimeout(() => {
            location.reload();
          }, 500);
        }
      });
    });
  }

  // Auto-open drawer if requested in URL parameter (?auth=open)
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('auth') === 'open') {
    ZestoAuth.open();
  }
});
