@extends('tyro-dashboard::layouts.app')

@section('title', 'Online Orders Management')

@section('content')
<div class="p-6 space-y-6" id="online-orders-app">

    <!-- HEADER BANNER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                <span>🔔 Online Orders (Click &amp; Collect)</span>
                <span id="pending-badge" class="hidden px-3 py-1 text-xs font-bold rounded-full bg-amber-500 text-white animate-pulse">
                    0 PENDING
                </span>
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Manage incoming orders, set estimated preparation time, or reject and refund directly via Stripe.
            </p>
        </div>

        <button id="toggle-audio-btn" onclick="toggleAudioMute()" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold transition">
            <span id="audio-icon">🔊</span>
            <span id="audio-status-text">Audio Alarm Active</span>
        </button>
    </div>

    <!-- AUDIO ELEMENT -->
    <audio id="order-alarm-sound" loop preload="auto">
        <source src="{{ asset('sounds/uber-alert.mp3') }}" type="audio/mpeg">
    </audio>

    <!-- ORDERS GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6" id="orders-grid">
        <div class="col-span-full py-12 text-center text-slate-400">
            Loading online orders...
        </div>
    </div>
</div>

<script>
let isMuted = false;
let pendingCount = 0;
const alarmSound = document.getElementById('order-alarm-sound');

document.addEventListener('DOMContentLoaded', () => {
    fetchOrders();
    setInterval(fetchOrders, 5000);
});

async function fetchOrders() {
    try {
        const response = await fetch('/api/online-orders');
        const orders = await response.json();

        const pendingOrders = orders.filter(o => o.preparation_status === 'not_accepted');
        pendingCount = pendingOrders.length;

        const badge = document.getElementById('pending-badge');
        if (pendingCount > 0) {
            badge.innerText = `${pendingCount} PENDING`;
            badge.classList.remove('hidden');
            playAlarm();
        } else {
            badge.classList.add('hidden');
            stopAlarm();
        }

        renderOrders(orders);
    } catch (error) {
        console.error("Error fetching online orders:", error);
    }
}

function playAlarm() {
    if (!isMuted && alarmSound.paused) {
        alarmSound.play().catch(err => {
            console.log("Audio playback blocked by browser.");
        });
    }
}

function stopAlarm() {
    alarmSound.pause();
    alarmSound.currentTime = 0;
}

function toggleAudioMute() {
    isMuted = !isMuted;
    const btnText = document.getElementById('audio-status-text');
    const btnIcon = document.getElementById('audio-icon');

    if (isMuted) {
        stopAlarm();
        btnText.innerText = 'Audio Alarm Muted';
        btnIcon.innerText = '🔇';
    } else {
        btnText.innerText = 'Audio Alarm Active';
        btnIcon.innerText = '🔊';
        if (pendingCount > 0) playAlarm();
    }
}

function renderOrders(orders) {
    const grid = document.getElementById('orders-grid');
    if (orders.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full py-16 text-center bg-white rounded-xl border border-dashed border-slate-300">
                <p class="text-slate-500 font-medium">No active online orders at the moment.</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = orders.map(order => {
        const isNotAccepted = order.preparation_status === 'not_accepted';
        
        let statusBadge = '';
        if (isNotAccepted) {
            statusBadge = `<span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 animate-pulse">⚠️ PENDING APPROVAL</span>`;
        } else if (order.preparation_status === 'accepted') {
            statusBadge = `<span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">✅ ACCEPTED (${order.estimated_prep_time} min)</span>`;
        } else if (order.preparation_status === 'preparing') {
            statusBadge = `<span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800">👨‍🍳 IN PREPARATION</span>`;
        } else {
            statusBadge = `<span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">🎉 READY</span>`;
        }

        const itemsHtml = order.items.map(item => `
            <div class="flex justify-between text-sm py-1 border-b border-slate-100 last:border-0">
                <span class="font-medium text-slate-700">${item.quantity}x ${item.product_name}</span>
                <span class="text-slate-500">€${(item.unit_price * item.quantity).toFixed(2)}</span>
            </div>
        `).join('');

        return `
            <div class="bg-white rounded-xl shadow-sm border ${isNotAccepted ? 'border-amber-400 ring-2 ring-amber-200' : 'border-slate-200'} p-5 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="font-bold text-lg text-slate-800">Ticket #${order.sequence_number || order.id}</h3>
                            <p class="text-xs text-slate-400">Customer: ${order.client ? order.client.name : 'Web Customer'}</p>
                        </div>
                        ${statusBadge}
                    </div>

                    <div class="bg-slate-50 p-3 rounded-lg mb-4 max-h-48 overflow-y-auto">
                        ${itemsHtml}
                    </div>

                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xs font-semibold text-slate-400">TOTAL (INCL. VAT)</span>
                        <span class="text-xl font-black text-slate-800">€${parseFloat(order.total_incl_vat).toFixed(2)}</span>
                    </div>
                </div>

                ${isNotAccepted ? `
                    <div class="space-y-3 pt-3 border-t border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 text-center">Select Estimated Prep Time:</p>
                        <div class="grid grid-cols-3 gap-2">
                            <button onclick="acceptOrder(${order.id}, 15)" class="py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-sm transition">⚡ 15 min</button>
                            <button onclick="acceptOrder(${order.id}, 30)" class="py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-sm transition">🕒 30 min</button>
                            <button onclick="acceptOrder(${order.id}, 45)" class="py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-sm transition">⏰ 45 min</button>
                        </div>
                        <button onclick="rejectOrder(${order.id})" class="w-full py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg font-semibold text-xs transition border border-rose-200">
                            🚫 Reject &amp; Refund via Stripe
                        </button>
                    </div>
                ` : `
                    <div class="text-center py-2 bg-slate-50 rounded-lg text-xs font-medium text-slate-500">
                        Accepted at ${new Date(order.updated_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                    </div>
                `}
            </div>
        `;
    }).join('');
}

async function acceptOrder(orderId, minutes) {
    stopAlarm();
    try {
        const response = await fetch(`/api/online-orders/${orderId}/accept`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ prep_time: minutes })
        });

        const data = await response.json();
        if (data.success) {
            fetchOrders();
        }
    } catch (error) {
        alert("Error accepting the order.");
    }
}

async function rejectOrder(orderId) {
    if (!confirm("Are you sure you want to reject this order? The customer will be immediately refunded on Stripe.")) {
        return;
    }

    stopAlarm();
    try {
        const response = await fetch(`/api/online-orders/${orderId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();
        if (data.success) {
            fetchOrders();
        }
    } catch (error) {
        alert("Error rejecting the order.");
    }
}
</script>
@endsection