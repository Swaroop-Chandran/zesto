/**
 * Zesto — Order Tracking JavaScript
 * Simulates real-time delivery progress (ported from React OrderTracking component)
 */

const TrackingState = {
  simStep: 2,
  agentMinutesAway: 8,
  simulationRunning: false,
  timer: null,
  agentPos: { top: '48%', left: '46%' },
  chatMessages: [
    { sender: 'driver', text: 'Hi! I have just picked up your fresh hot order. Heading towards you now!', time: '12:20 PM' }
  ],

  init() {
    this.renderChat();
    this.updateTimeline();
    this.updateAgentBadge();

    // Bind UI buttons
    const runBtn    = document.getElementById('sim-run-btn');
    const resetBtn  = document.getElementById('sim-reset-btn');
    const callBtn   = document.getElementById('call-driver-btn');
    const hangBtn   = document.getElementById('hangup-btn');
    const chatForm  = document.getElementById('chat-form');

    if (runBtn)   runBtn.addEventListener('click',   () => this.toggleSim());
    if (resetBtn) resetBtn.addEventListener('click', () => this.reset());
    if (callBtn)  callBtn.addEventListener('click',  () => this.startCall());
    if (hangBtn)  hangBtn.addEventListener('click',  () => this.endCall());
    if (chatForm) chatForm.addEventListener('submit', (e) => this.sendMessage(e));
  },

  toggleSim() {
    if (this.simStep === 3) { this.reset(); return; }
    this.simulationRunning = !this.simulationRunning;
    const btn = document.getElementById('sim-run-btn');
    if (btn) {
      btn.innerHTML = this.simulationRunning
        ? `<svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Sim Running...`
        : `▶ Run Simulation`;
    }
    if (this.simulationRunning) this.startTimer();
    else { clearInterval(this.timer); this.timer = null; }
  },

  startTimer() {
    this.timer = setInterval(() => {
      if (this.simStep === 2) {
        if (this.agentMinutesAway > 2) {
          this.agentMinutesAway -= 2;
          const moved = 8 - this.agentMinutesAway;
          this.agentPos = {
            top:  `${48 - moved * 2}%`,
            left: `${46 - moved * 2}%`
          };
          this.addChatMsg('driver', `Traffic is light! I'm now just ${this.agentMinutesAway} minutes away. See you soon!`, '12:35 PM');
        } else {
          this.simStep = 3;
          this.agentMinutesAway = 0;
          this.agentPos = { top: '25%', left: '25%' };
          this.addChatMsg('driver', "I've arrived at the entrance. Walking up with your warm food!", '12:44 PM');
          clearInterval(this.timer);
          this.simulationRunning = false;
          const btn = document.getElementById('sim-run-btn');
          if (btn) btn.innerHTML = '↺ Restart Simulation';
        }
      }
      this.updateAgentMarker();
      this.updateAgentBadge();
      this.updateTimeline();
    }, 4000);
  },

  reset() {
    clearInterval(this.timer);
    this.simStep = 2;
    this.agentMinutesAway = 8;
    this.agentPos = { top: '48%', left: '46%' };
    this.simulationRunning = false;
    this.chatMessages = [
      { sender: 'driver', text: 'Hi! I have just picked up your fresh hot order. Heading towards you now!', time: '12:20 PM' }
    ];
    const btn = document.getElementById('sim-run-btn');
    if (btn) btn.innerHTML = '▶ Run Simulation';
    this.renderChat();
    this.updateTimeline();
    this.updateAgentMarker();
    this.updateAgentBadge();
  },

  updateAgentMarker() {
    const marker = document.getElementById('agent-marker');
    if (marker) {
      marker.style.top  = this.agentPos.top;
      marker.style.left = this.agentPos.left;
      if (this.simStep === 3) {
        marker.innerHTML = `<div class="bg-[#00c853] text-white rounded-full p-3 shadow-xl border-2 border-white scale-110">✓</div>`;
      } else {
        marker.innerHTML = `<div class="bg-[#00c853] text-white rounded-full p-3 shadow-xl border-2 border-white scale-110 animate-bounce">🏍</div>`;
      }
    }
  },

  updateAgentBadge() {
    const badge = document.getElementById('agent-badge');
    if (badge) {
      badge.textContent = this.simStep === 3 ? 'Arrived!' : `${this.agentMinutesAway} mins away`;
    }
  },

  updateTimeline() {
    const eta     = document.getElementById('tracking-eta');
    const statusBadge = document.getElementById('tracking-status');
    if (eta) eta.textContent = this.simStep === 3 ? 'Delivered!' : '12:45 PM';
    if (statusBadge) {
      statusBadge.textContent  = this.simStep === 3 ? 'Delivered' : 'On Track';
      statusBadge.className = this.simStep === 3
        ? 'px-3 py-1 rounded-full text-xs font-extrabold bg-[#00c853]/10 text-[#00c853]'
        : 'px-3 py-1 rounded-full text-xs font-extrabold bg-[#ffdbd0] text-[#a83300]';
    }

    // Step 3 marker
    const step3Label = document.getElementById('step-out-for-delivery');
    if (step3Label) {
      step3Label.textContent = this.simStep === 2
        ? `Delivery Agent is ${this.agentMinutesAway} mins away`
        : 'Agent delivered warm package';
    }

    // Step 4 color
    const step4Dot = document.getElementById('step4-dot');
    if (step4Dot) {
      step4Dot.className = `absolute -left-8 top-1.5 w-6 h-6 rounded-full flex items-center justify-center z-10 border-4 border-white ${this.simStep === 3 ? 'bg-[#00c853]' : 'bg-gray-200'}`;
    }

    const step4Label = document.getElementById('step4-label');
    if (step4Label) {
      step4Label.className = `font-bold text-sm ${this.simStep === 3 ? 'text-[#00c853]' : 'text-gray-400'}`;
    }
  },

  addChatMsg(sender, text, time) {
    this.chatMessages.push({ sender, text, time });
    this.renderChat();
  },

  renderChat() {
    const container = document.getElementById('chat-messages');
    if (!container) return;
    container.innerHTML = this.chatMessages.map(msg => `
      <div class="max-w-[85%] rounded-2xl p-3 text-sm leading-relaxed ${msg.sender === 'driver'
        ? 'bg-[#f5f3f3]/60 mr-auto rounded-tl-none'
        : 'bg-[#ffdbd0]/60 ml-auto rounded-tr-none'} text-gray-800">
        <p class="leading-relaxed">${msg.text}</p>
        <span class="text-[9px] text-gray-400 font-bold uppercase block mt-1">${msg.time}</span>
      </div>
    `).join('');
    container.scrollTop = container.scrollHeight;
  },

  sendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('chat-input');
    if (!input || !input.value.trim()) return;
    this.addChatMsg('customer', input.value, 'Just now');
    input.value = '';
    setTimeout(() => {
      this.addChatMsg('driver', 'Received that! Thanks for the tip, will follow instructions.', 'Just now');
    }, 1500);
  },

  startCall() {
    const overlay = document.getElementById('call-overlay');
    const status  = document.getElementById('call-status');
    if (overlay) overlay.classList.remove('hidden');
    if (status)  status.textContent = 'Calling...';
    setTimeout(() => {
      if (status) status.textContent = 'Connected (Secure Line)';
      const detail = document.getElementById('call-detail');
      if (detail) { detail.textContent = 'Connected speaking line active'; detail.classList.remove('hidden'); }
    }, 2000);
  },

  endCall() {
    const overlay = document.getElementById('call-overlay');
    if (overlay) overlay.classList.add('hidden');
  }
};

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('tracking-eta')) {
    TrackingState.init();
  }
});
