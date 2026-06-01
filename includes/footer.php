<?php
/**
 * Zesto — Footer Include (Zesto UI)
 */
?>
<footer class="w-full bg-[#0a0e14]/90 border-t border-white/5 py-10 mt-16 px-4 sm:px-10">
  <div class="max-w-7xl mx-auto">
    
    <!-- Core Value Badges -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 pb-10 border-b border-white/5">
      <div class="flex items-start gap-3">
        <div class="p-2.5 rounded-lg bg-zesto-orange/10 border border-zesto-orange/20 text-zesto-orange">
          <i data-lucide="shield-check" class="w-5 h-5"></i>
        </div>
        <div>
          <h4 class="text-sm font-semibold text-white">Secure payment</h4>
          <p class="text-xs text-white/50 mt-0.5">Stripe and UPI protected checkout experience</p>
        </div>
      </div>

      <div class="flex items-start gap-3">
        <div class="p-2.5 rounded-lg bg-zesto-amber/10 border border-zesto-amber/20 text-zesto-amber">
          <i data-lucide="heart-handshake" class="w-5 h-5"></i>
        </div>
        <div>
          <h4 class="text-sm font-semibold text-white">24/7 support</h4>
          <p class="text-xs text-white/50 mt-0.5">Lively customer support built for the night owls</p>
        </div>
      </div>

      <div class="flex items-start gap-3">
        <div class="p-2.5 rounded-lg bg-zesto-cyan/10 border border-zesto-cyan/20 text-zesto-cyan">
          <i data-lucide="map-pin" class="w-5 h-5"></i>
        </div>
        <div>
          <h4 class="text-sm font-semibold text-white">Thattukada Culture</h4>
          <p class="text-xs text-white/50 mt-0.5">Delivering authenticity across Kochi after dark</p>
        </div>
      </div>

      <div class="flex items-start gap-3 col-span-1">
        <div class="flex flex-col gap-1 text-xs text-white/50 max-w-xs">
          <div class="flex items-center gap-1.5">
            <i data-lucide="phone" class="w-3.5 h-3.5 text-zesto-orange"></i>
            <span>+91 98765 ZESTO</span>
          </div>
          <div class="flex items-center gap-1.5">
            <i data-lucide="mail" class="w-3.5 h-3.5 text-zesto-orange"></i>
            <span>cravings@zestonights.com</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Links and Branding -->
    <div class="flex flex-col md:flex-row items-center justify-between gap-6 pt-8 text-xs text-white/40">
      <div class="flex flex-col items-center md:items-start text-center md:text-left gap-1">
        <p class="text-sm font-bold text-zesto-orange uppercase tracking-tight" style="font-family: 'Georgia', serif;">
          ZESTO NIGHTS
        </p>
        <p class="mt-0.5 text-xs text-white/40 font-sans">
          © <?= date('Y') ?> Zesto Nights Food Tech. All rights reserved. Operating with pride in Kerala.
        </p>
      </div>

      <div class="flex items-center gap-6 font-semibold font-sans">
        <button onclick="Zesto.toast('Zesto Nights brings the warmth, smoke, and hot spices of Kerala\'s roadside Thattukadas straight to your doorstep when you need it most.', 'info')" class="hover:text-zesto-orange transition cursor-pointer bg-transparent border-none">About Us</button>
        <button onclick="Zesto.toast('Get in touch at: contact@zestonights.com', 'info')" class="hover:text-zesto-orange transition cursor-pointer bg-transparent border-none">Contact</button>
        <button class="hover:text-zesto-orange transition cursor-pointer bg-transparent border-none">Terms</button>
        <button class="hover:text-zesto-orange transition cursor-pointer bg-transparent border-none">Privacy</button>
      </div>
    </div>

    <!-- System Online Status Bar -->
    <div class="mt-10 pt-6 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between text-[10px] uppercase tracking-widest text-white/30 font-mono gap-3">
      <div>System Online • Kerala Server v2.4</div>
      <div class="flex flex-wrap gap-x-8 gap-y-2 justify-center">
        <span>Security Certified</span>
        <span>Delivery Partner Portal</span>
        <span>© Zesto Nights <?= date('Y') ?></span>
      </div>
    </div>

  </div>
</footer>

<script>
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }
</script>
</body>
</html>
