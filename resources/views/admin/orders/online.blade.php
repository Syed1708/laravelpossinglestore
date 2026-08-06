<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔔 Online Orders Management</title>
    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --warning: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --border: #334155;
            --primary: #3b82f6;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .title {
            font-size: 24px;
            font-weight: 900;
        }

        .alert-banner {
            background-color: rgba(245, 158, 11, 0.2);
            border: 2px solid var(--warning);
            color: var(--warning);
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: bold;
            display: none;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            animation: pulseBanner 1.5s infinite;
        }

        @keyframes pulseBanner {

            0%,
            100% {
                border-color: rgba(245, 158, 11, 0.5);
            }

            50% {
                border-color: rgba(245, 158, 11, 1);
            }
        }

        /* 🚀 MAIN TWO-COLUMN LAYOUT */
        .main-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
        }

        @media (max-width: 1024px) {
            .main-layout {
                grid-template-columns: 1fr;
            }
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .order-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-unaccepted {
            border: 2px solid var(--warning);
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.2);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .ticket-num {
            font-size: 20px;
            font-weight: 900;
        }

        .badge-unaccepted {
            background-color: var(--warning);
            color: #0f172a;
            font-size: 10px;
            font-weight: 900;
            padding: 4px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .badge-accepted {
            background-color: rgba(16, 185, 129, 0.2);
            color: var(--success);
            font-size: 10px;
            font-weight: 900;
            padding: 4px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .items-list {
            margin: 15px 0;
            font-size: 14px;
        }

        .time-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 10px;
        }

        .btn-time {
            background-color: var(--border);
            color: var(--text-main);
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-time:hover {
            background-color: var(--warning);
            color: #0f172a;
        }

        .btn-reject {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid var(--danger);
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.2s;
        }

        .btn-reject:hover {
            background-color: var(--danger);
            color: #ffffff;
        }

        /* 🚀 RIGHT SIDEBAR STATS PANEL */
        .stats-sidebar {
            background-color: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .stats-title {
            font-size: 16px;
            font-weight: 800;
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .stat-box {
            background-color: var(--bg-color);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: bold;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 900;
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1 class="title">🔔 ONLINE ORDERS (Click &amp; Collect)</h1>
        <div id="ws-status" style="color: var(--success); font-weight: bold;">● Reverb WebSockets Connected</div>
    </div>

    <!-- Alarm Banner -->
    <div class="alert-banner" id="alarm-banner">
        <span>🔔 New Online Order Needing Acceptance!</span>
        <button onclick="stopAlarm()"
            style="background: var(--warning); border: none; padding: 6px 12px; border-radius: 6px; font-weight: bold; cursor: pointer;">
            Mute Alarm
        </button>
    </div>

    <!-- MAIN TWO-COLUMN CONTAINER -->
    <div class="main-layout">

        <!-- LEFT: Active Orders Cards Grid -->
        <div class="orders-grid" id="orders-container">
            <!-- Rendered dynamically -->
        </div>

        <!-- RIGHT: Live Statistics Sidebar -->
        <!-- RIGHT: Live Statistics Sidebar -->
        <div class="stats-sidebar">
            <div class="stats-title">📊 Today's Online Stats</div>

            <div class="stat-box">
                <span class="stat-label">TOTAL TODAY</span>
                <span class="stat-value" id="stat-total" style="color: var(--primary);">0</span>
            </div>

            <div class="stat-box">
                <span class="stat-label">⚠️ PENDING</span>
                <span class="stat-value" id="stat-pending" style="color: var(--warning);">0</span>
            </div>

            <div class="stat-box">
                <span class="stat-label">👨‍🍳 IN KITCHEN</span>
                <span class="stat-value" id="stat-kitchen" style="color: #a855f7;">0</span>
            </div>

            <div class="stat-box">
                <span class="stat-label">🎉 READY</span>
                <span class="stat-value" id="stat-ready" style="color: var(--success);">0</span>
            </div>

            <!-- 🚀 NEW: COMPLETED ORDERS COUNTER -->
            <div class="stat-box">
                <span class="stat-label">✅ COMPLETED</span>
                <span class="stat-value" id="stat-completed" style="color: #38bdf8;">0</span>
            </div>

            <div class="stat-box" style="border-color: var(--warning); background-color: rgba(245, 158, 11, 0.05);">
                <span class="stat-label" style="color: var(--warning);">TODAY'S REVENUE</span>
                <span class="stat-value" id="stat-revenue" style="color: var(--warning);">€0.00</span>
            </div>
        </div>

    </div>

    <script>
        let alarmInterval = null;
        let audioCtx = null;

        function playRepeatingAlarm() {
            if (alarmInterval) return;

            alarmInterval = setInterval(() => {
                playSyntheticChime();
            }, 2500);

            const banner = document.getElementById('alarm-banner');
            if (banner) banner.style.display = 'flex';
        }

        function stopAlarm() {
            if (alarmInterval) {
                clearInterval(alarmInterval);
                alarmInterval = null;
            }
            const banner = document.getElementById('alarm-banner');
            if (banner) banner.style.display = 'none';
        }

        function playSyntheticChime() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!audioCtx) audioCtx = new AudioContext();

                playTone(880.00, 0.1, 0); // Note A5
                playTone(1174.66, 0.2, 0.12); // Note D6
            } catch (e) {
                console.log('Audio context blocked:', e.message);
            }
        }

        function playTone(freq, duration, delay) {
            if (!audioCtx) return;
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.2, audioCtx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + delay + duration);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(audioCtx.currentTime + delay);
            osc.stop(audioCtx.currentTime + delay + duration);
        }

        document.body.addEventListener('click', () => {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            audioCtx = new AudioContext();
            audioCtx.resume();
        }, {
            once: true
        });

        // 🚀 1. Fetch Online Orders API
        async function fetchOrders() {
            try {
                const response = await fetch('/admin/api/online-orders');
                const data = await response.json();

                // Handle both new { orders, stats } response and legacy array response
                const orders = Array.isArray(data) ? data : (data.orders || []);
                const stats = data.stats || null;

                const pendingOrders = orders.filter(o => o.preparation_status === 'not_accepted');
                const pendingCount = pendingOrders.length;

                if (pendingCount > 0) {
                    playRepeatingAlarm();
                } else {
                    stopAlarm();
                }

                if (stats) {
                    updateStatsSidebar(stats);
                }

                renderOrders(orders);
            } catch (error) {
                console.error("Error fetching online orders:", error);
            }
        }

        // 🚀 2. Update Sidebar Statistics Function

        function updateStatsSidebar(stats) {
            if (!stats) return;

            document.getElementById('stat-total').innerText = stats.total ?? 0;
            document.getElementById('stat-pending').innerText = stats.pending ?? 0;
            document.getElementById('stat-kitchen').innerText = stats.kitchen ?? 0;
            document.getElementById('stat-ready').innerText = stats.ready ?? 0;
            document.getElementById('stat-completed').innerText = stats.completed ?? 0;

            // 🚀 Safe float formatting
            const revenue = parseFloat(stats.revenue || 0);
            document.getElementById('stat-revenue').innerText = `€${revenue.toFixed(2)}`;
                console.log("Updated stats:", revenue);
        }

        // Accept Order
        async function acceptOrder(orderId, minutes) {
            stopAlarm();
            try {
                const response = await fetch(`/admin/api/online-orders/${orderId}/accept`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        prep_time: minutes
                    })
                });

                const data = await response.json();
                if (data.success) {
                    fetchOrders();
                }
            } catch (error) {
                alert("Error accepting the order.");
            }
        }

        // Reject Order
        async function rejectOrder(orderId) {
            if (!confirm(
                    "Are you sure you want to reject this order? The customer will be immediately refunded on Stripe."
                )) {
                return;
            }

            stopAlarm();
            try {
                const response = await fetch(`/admin/api/online-orders/${orderId}/reject`, {
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

        // Render Cards
        function renderOrders(orders) {
            const container = document.getElementById('orders-container');

            if (orders.length === 0) {
                container.innerHTML = '<p style="color: var(--text-muted);">No active online orders at the moment.</p>';
                return;
            }

            container.innerHTML = orders.map(order => {
                const isUnaccepted = order.preparation_status === 'not_accepted';

                const itemsHtml = order.items.map(item => {
                    const price = item.subtotal || (item.unit_price * item.quantity);
                    return `
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>${item.quantity}x ${item.product_name}</span>
                            <span>€${Number(price).toFixed(2)}</span>
                        </div>
                    `;
                }).join('');

                let actionsHtml = '';
                if (isUnaccepted) {
                    actionsHtml = `
                        <div style="margin-top: 10px;">
                            <span style="font-size: 11px; color: var(--text-muted); font-weight: bold;">ESTIMATED PREP TIME:</span>
                            <div class="time-buttons">
                                <button class="btn-time" onclick="acceptOrder(${order.id}, 15)">15 min</button>
                                <button class="btn-time" onclick="acceptOrder(${order.id}, 30)">30 min</button>
                                <button class="btn-time" onclick="acceptOrder(${order.id}, 45)">45 min</button>
                            </div>
                            <button class="btn-reject" onclick="rejectOrder(${order.id})">❌ Reject &amp; Refund via Stripe</button>
                        </div>
                    `;
                } else {
                    // 🚀 TIMEZONE PARSING FIX
                    const readyTime = order.estimated_ready_at ? new Date(order.estimated_ready_at)
                        .toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : 'Soon';
                    actionsHtml = `
                        <div style="margin-top: 10px; font-size: 12px; color: var(--success); font-weight: bold;">
                            ✓ Accepted • Estimated Ready at ${readyTime} (${order.estimated_prep_time || 0} min)
                        </div>
                    `;
                }

                return `
                    <div class="order-card ${isUnaccepted ? 'card-unaccepted' : ''}">
                        <div>
                            <div class="card-header">
                                <div>
                                    <div class="ticket-num">TICKET #${order.sequence_number || order.id}</div>
                                    <div style="font-size: 12px; color: var(--warning); font-weight: bold;">
                                        Customer: ${order.customer_name || order.client?.name || 'Web Customer'}
                                    </div>
                                </div>
                                <span class="${isUnaccepted ? 'badge-unaccepted' : 'badge-accepted'}">
                                    ${isUnaccepted ? 'PENDING 🔔' : order.preparation_status.toUpperCase()}
                                </span>
                            </div>

                            <div class="items-list">
                                ${itemsHtml}
                            </div>
                            <div style="border-top: 1px solid var(--border); padding-top: 8px; font-weight: bold; font-size: 16px; text-align: right; color: var(--warning);">
                                Total: €${Number(order.total_incl_vat).toFixed(2)}
                            </div>
                        </div>

                        ${actionsHtml}
                    </div>
                `;
            }).join('');
        }

        // WebSockets Listener
        const pusherKey = '{{ env('REVERB_APP_KEY') }}';
        if (pusherKey) {
            const pusher = new Pusher(pusherKey, {
                cluster: '{{ env('REVERB_APP_CLUSTER', 'mt1') }}',
                wsHost: '{{ env('REVERB_HOST', '127.0.0.1') }}',
                wsPort: {{ env('REVERB_PORT', 8080) }},
                forceTLS: false,
                enabledTransports: ['ws', 'wss']
            });

            const channel = pusher.subscribe('kds-channel');
            channel.bind('new_orders_synced', function(data) {
                fetchOrders();
            });
            channel.bind('order-event', function(data) {
                fetchOrders();
            });
        }

        fetchOrders();
    </script>
</body>

</html>
