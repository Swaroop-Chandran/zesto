/**
 * Zesto — Real-Time Order Tracking & Polling JavaScript
 * Fetches order updates and transitions the vertical visual timeline and map coordinates
 */

const OrderTracking = {
    orderNumber: window.ZESTO_ORDER_NUMBER || '',
    baseUrl: window.ZESTO_BASE || '/Zesto',
    pollInterval: null,
    currentStatus: null,

    statusWorkflow: [
        'pending', 'accepted', 'preparing', 'ready_for_pickup',
        'assigned_to_delivery', 'picked_up', 'out_for_delivery', 'delivered'
    ],

    statusLabels: {
        'pending': 'Order Placed',
        'accepted': 'Accepted',
        'preparing': 'Preparing Food',
        'ready_for_pickup': 'Ready for Pickup',
        'assigned_to_delivery': 'Delivery Partner Assigned',
        'picked_up': 'Picked Up',
        'out_for_delivery': 'Out for Delivery',
        'delivered': 'Delivered Successfully'
    },

    statusDescs: {
        'pending': 'Your order has been placed successfully.',
        'accepted': 'Restaurant has accepted and verified your order.',
        'preparing': 'Our chefs are cooking your fresh hot meal.',
        'ready_for_pickup': 'Food is packaged and ready to hand over.',
        'assigned_to_delivery': 'A nearby partner has accepted your delivery.',
        'picked_up': 'Partner has picked up your food carrier bag.',
        'out_for_delivery': 'Courier is riding towards your home area.',
        'delivered': 'Delivered! Enjoy your warm fresh meal!'
    },

    // Mock map transition coordinates
    mapPositions: {
        'pending': { top: '67%', left: '76%' },              // At Restaurant
        'accepted': { top: '67%', left: '76%' },             // At Restaurant
        'preparing': { top: '67%', left: '76%' },            // At Restaurant
        'ready_for_pickup': { top: '67%', left: '76%' },     // At Restaurant
        'assigned_to_delivery': { top: '55%', left: '60%' }, // Moving to restaurant
        'picked_up': { top: '48%', left: '46%' },             // Leaving restaurant
        'out_for_delivery': { top: '35%', left: '33%' },      // Mid route
        'delivered': { top: '25%', left: '25%' }              // At home
    },

    init() {
        if (!this.orderNumber) return;
        
        console.log(`Starting real-time polling for order: ${this.orderNumber}`);
        this.fetchStatus();
        this.startPolling();

        // Call actions
        const callBtn = document.getElementById('call-driver-btn');
        const hangBtn = document.getElementById('hangup-btn');
        if (callBtn) callBtn.addEventListener('click', () => this.startCall());
        if (hangBtn) hangBtn.addEventListener('click', () => this.endCall());

        // Chat send action
        const chatForm = document.getElementById('chat-form');
        if (chatForm) {
            chatForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const input = document.getElementById('chat-input');
                if (!input || !input.value.trim()) return;
                this.addChatMessage('customer', input.value, 'Just now');
                input.value = '';
                setTimeout(() => {
                    this.addChatMessage('driver', 'Sure! I will take care of that for you.', 'Just now');
                }, 2000);
            });
        }
    },

    startPolling() {
        this.pollInterval = setInterval(() => {
            this.fetchStatus();
        }, 4000); // Poll database every 4s
    },

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    },

    async fetchStatus() {
        try {
            const res = await fetch(`${this.baseUrl}/api/orders/status.php?order=${encodeURIComponent(this.orderNumber)}`);
            const data = await res.json();
            
            if (data.success) {
                const newStatus = data.status;
                const partnerName = data.partner_name || null;
                
                if (newStatus !== this.currentStatus || partnerName) {
                    this.currentStatus = newStatus;
                    this.updateUI(newStatus, partnerName);
                }

                if (newStatus === 'delivered' || newStatus === 'cancelled') {
                    console.log('Order complete, stopping polling loop.');
                    this.stopPolling();
                }
            }
        } catch (e) {
            console.error('Polling error occurred: ', e);
        }
    },

    updateUI(status, partnerName) {
        // 1. Update text banner
        const bannerStatus = document.getElementById('banner-status');
        if (bannerStatus) bannerStatus.textContent = status.replace(/_/g, ' ');

        // 2. Update ETA & Top Status Badge
        const etaText = document.getElementById('tracking-eta');
        const statusBadge = document.getElementById('tracking-status');
        
        if (status === 'delivered') {
            if (etaText) etaText.textContent = 'Delivered! 🎉';
            if (statusBadge) {
                statusBadge.textContent = 'Completed';
                statusBadge.className = 'px-3.5 py-1.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 border border-emerald-200';
            }
        } else {
            if (etaText) {
                if (status === 'out_for_delivery') {
                    etaText.textContent = '5–10 Mins';
                } else if (status === 'picked_up' || status === 'assigned_to_delivery') {
                    etaText.textContent = '15–20 Mins';
                } else {
                    etaText.textContent = '30–40 Mins';
                }
            }
            if (statusBadge) {
                statusBadge.textContent = status.replace(/_/g, ' ');
                statusBadge.className = 'px-3.5 py-1.5 rounded-full text-[10px] font-black uppercase bg-amber-50 text-amber-700 border border-amber-200';
            }
        }

        // 3. Update Visual Timeline Steppers
        const activeIdx = this.statusWorkflow.indexOf(status);
        const steps = document.querySelectorAll('.timeline-step-row');
        
        steps.forEach((step) => {
            const idx = parseInt(step.dataset.stepIndex, 10);
            const dot = step.querySelector('.absolute.rounded-full');
            const line = step.querySelector('.absolute.w-\\[2px\\]');
            const header = step.querySelector('h4');
            
            const done = idx <= activeIdx;
            const active = idx === activeIdx;

            if (dot) {
                if (active) {
                    dot.className = 'absolute -left-8 top-1.5 w-6 h-6 rounded-full flex items-center justify-center border-4 border-white z-10 transition-all duration-500 font-bold text-[10px] bg-[#a83300] text-white shadow-md';
                    dot.innerHTML = '<div class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></div>';
                } else if (done) {
                    dot.className = 'absolute -left-8 top-1.5 w-6 h-6 rounded-full flex items-center justify-center border-4 border-white z-10 transition-all duration-500 font-bold text-[10px] bg-[#00c853] text-white';
                    dot.innerHTML = '✓';
                } else {
                    dot.className = 'absolute -left-8 top-1.5 w-6 h-6 rounded-full flex items-center justify-center border-4 border-white z-10 transition-all duration-500 font-bold text-[10px] bg-gray-100 text-gray-400';
                    dot.innerHTML = '✓';
                }
            }

            if (line) {
                if (idx < activeIdx) {
                    line.className = 'absolute -left-5 top-7 w-[2px] h-10 transition-all duration-500 bg-[#00c853]';
                } else {
                    line.className = 'absolute -left-5 top-7 w-[2px] h-10 transition-all duration-500 bg-gray-100';
                }
            }

            if (header) {
                if (active) {
                    header.className = 'font-extrabold text-sm transition-colors duration-500 text-[#a83300]';
                } else if (done) {
                    header.className = 'font-extrabold text-sm transition-colors duration-500 text-gray-800';
                } else {
                    header.className = 'font-extrabold text-sm transition-colors duration-500 text-gray-400';
                }
            }
        });

        // 4. Update Courier Card
        const agentName = document.getElementById('agent-name');
        const callAgentName = document.getElementById('call-agent-name');
        const agentRating = document.getElementById('agent-rating');
        const agentMarkerBadge = document.getElementById('agent-badge');
        
        if (partnerName) {
            if (agentName) agentName.textContent = partnerName;
            if (callAgentName) callAgentName.textContent = partnerName;
            if (agentRating) agentRating.textContent = '★ 4.9 Professional Executive';
            if (agentMarkerBadge) {
                if (status === 'out_for_delivery') {
                    agentMarkerBadge.textContent = 'Courier Out for Delivery';
                } else if (status === 'picked_up') {
                    agentMarkerBadge.textContent = 'Food Picked Up';
                } else {
                    agentMarkerBadge.textContent = 'Courier Assigned';
                }
            }
        }

        // 5. Update Map Position
        const agentMarker = document.getElementById('agent-marker');
        const agentBadgeWrap = document.getElementById('agent-badge-wrap');
        const pos = this.mapPositions[status] || this.mapPositions['pending'];
        
        if (agentMarker) {
            agentMarker.style.top = pos.top;
            agentMarker.style.left = pos.left;
            if (status === 'delivered') {
                agentMarker.innerHTML = `<div class="bg-[#00c853] text-white rounded-full p-3 shadow-xl border-2 border-white scale-110">✓</div>`;
            } else {
                agentMarker.innerHTML = `<div class="bg-[#00c853] text-white rounded-full p-3 shadow-xl border-2 border-white scale-110 animate-bounce">🏍</div>`;
            }
        }
        if (agentBadgeWrap) {
            agentBadgeWrap.style.top = `calc(${pos.top} + 56px)`;
            agentBadgeWrap.style.left = pos.left;
        }
    },

    addChatMessage(sender, text, time) {
        const container = document.getElementById('chat-messages');
        if (!container) return;

        const msgDiv = document.createElement('div');
        msgDiv.className = `max-w-[85%] rounded-2xl p-3 text-sm leading-relaxed ${
            sender === 'driver' ? 'bg-gray-50 mr-auto rounded-tl-none text-gray-700' : 'bg-[#ffdbd0]/50 ml-auto rounded-tr-none text-gray-800'
        }`;
        msgDiv.innerHTML = `<p>${text}</p><span class="text-[9px] text-gray-400 font-bold uppercase block mt-1">${time}</span>`;
        container.appendChild(msgDiv);
        container.scrollTop = container.scrollHeight;
    },

    startCall() {
        const overlay = document.getElementById('call-overlay');
        const status = document.getElementById('call-status');
        const detail = document.getElementById('call-detail');

        if (overlay) overlay.classList.remove('hidden');
        if (status) status.textContent = 'Connecting...';
        if (detail) detail.classList.add('hidden');

        setTimeout(() => {
            if (status) status.textContent = 'Connected (Secure Line)';
            if (detail) detail.classList.remove('hidden');
        }, 1500);
    },

    endCall() {
        const overlay = document.getElementById('call-overlay');
        if (overlay) overlay.classList.add('hidden');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    OrderTracking.init();
});
