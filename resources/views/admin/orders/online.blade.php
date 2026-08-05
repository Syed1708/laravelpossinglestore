<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🔔Online Orders Management</title>
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
            0%, 100% { border-color: rgba(245, 158, 11, 0.5); }
            50% { border-color: rgba(245, 158, 11, 1); }
        }

        .orders-grid {
            display: grid;
            grid-template-cols: repeat(auto-fill, minmax(340px, 1fr));
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
        }

        .btn-time:hover {
            background-color: var(--warning);
            color: #0f172a;
        }

        .btn-reject {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--destructive);
            border: 1px solid var(--destructive);
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 8px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1 class="title">🔔 ONLINE ORDERS (Click & Collect)</h1>
        <div id="ws-status" style="color: var(--success); font-bold: true;">● Reverb WebSockets Connecté</div>
    </div>

    <!-- Repeating Audio Ring Alarm Banner -->
    <div class="alert-banner" id="alarm-banner">
        <span>🔔 New Order Online to Validate !</span>
        <button onclick="stopAlarm()" style="background: var(--warning); border: none; padding: 6px 12px; border-radius: 6px; font-weight: bold; cursor: pointer;">
            Couper le Son
        </button>
    </div>

    <div class="orders-grid" id="orders-container">
        <!-- Rendered dynamically -->
    </div>

    <script>
        let unacceptedOrders = [];
        let alarmInterval = null;
        let audioCtx = null;

        // 🚀 UBEREATS REPEATING CHIME ALARM
        function playRepeatingAlarm() {
            if (alarmInterval) return;

            alarmInterval = setInterval(() => {
                playSyntheticChime();
            }, 2500);

            document.getElementById('alarm-banner').style.display = 'flex';
        }

        function stopAlarm() {
            if (alarmInterval) {
                clearInterval(alarmInterval);
                alarmInterval = null;
            }
            document.getElementById('alarm-banner').style.display = 'none';
        }

        function playSyntheticChime() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!audioCtx) audioCtx = new AudioContext();

                playTone(880.00, 0.1, 0);   // Note A5
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
        }, { once: true });

        async function fetchOnlineOrders() {
            try {
                const res = await fetch('/api/online-orders');
                const orders = await res.json();
                renderOrders(orders);
            } catch (e) {
                console.error('Failed to fetch online orders:', e);
            }
        }

        function renderOrders(orders) {
            const container = document.getElementById('orders-container');
            const hasUnaccepted = orders.some(o => o.preparation_status === 'not_accepted');

            if (hasUnaccepted) {
                playRepeatingAlarm();
            } else {
                stopAlarm();
            }

            if (orders.length === 0) {
                container.innerHTML = '<p style="color: var(--text-muted);">Aucune commande en ligne active.</p>';
                return;
            }

            container.innerHTML = orders.map(order => {
                const isUnaccepted = order.preparation_status === 'not_accepted';

                const itemsHtml = order.items.map(item => `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>${item.quantity}x ${item.product_name}</span>
                        <span>€${Number(item.subtotal).toFixed(2)}</span>
                    </div>
                `).join('');

                let actionsHtml = '';
                if (isUnaccepted) {
                    actionsHtml = `
                        <div style="margin-top: 10px;">
                            <span style="font-size: 11px; color: var(--text-muted); font-bold: true;">DÉLAI DE PRÉPARATION :</span>
                            <div class="time-buttons">
                                <button class="btn-time" onclick="acceptOrder(${order.id}, 15)">15 min</button>
                                <button class="btn-time" onclick="acceptOrder(${order.id}, 30)">30 min</button>
                                <button class="btn-time" onclick="acceptOrder(${order.id}, 45)">45 min</button>
                            </div>
                            <button class="btn-reject" onclick="rejectOrder(${order.id})">❌ Refuser la commande</button>
                        </div>
                    `;
                } else {
                    actionsHtml = `
                        <div style="margin-top: 10px; font-size: 12px; color: var(--success); font-weight: bold;">
                            ✓ Acceptée • Prête à ${new Date(order.estimated_ready_at).toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'})} (${order.estimated_prep_time} min)
                        </div>
                    `;
                }

                return `
                    <div class="order-card ${isUnaccepted ? 'card-unaccepted' : ''}">
                        <div>
                            <div class="card-header">
                                <div>
                                    <div class="ticket-num">TICKET #${order.sequence_number || order.id}</div>
                                    <div style="font-size: 12px; color: var(--warning); font-weight: bold;">Client: ${order.customer_name || order.client?.name || 'Client Web'}</div>
                                </div>
                                <span class="${isUnaccepted ? 'badge-unaccepted' : 'badge-accepted'}">
                                    ${isUnaccepted ? 'À VALIDER 🔔' : order.preparation_status}
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

        async function acceptOrder(orderId, prepTimeMins) {
            try {
                await fetch(`/api/online-orders/${orderId}/accept`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prep_time: prepTimeMins })
                });
                fetchOnlineOrders();
            } catch (e) {
                console.error(e);
            }
        }

        async function rejectOrder(orderId) {
            if (!confirm('Refuser cette commande en ligne ?')) return;
            try {
                await fetch(`/api/online-orders/${orderId}/reject`, { method: 'POST' });
                fetchOnlineOrders();
            } catch (e) {
                console.error(e);
            }
        }

        // WebSockets Reverb/Pusher Listener
        const pusher = new Pusher('{{ env('REVERB_APP_KEY') }}', {
            cluster: '{{ env('REVERB_APP_CLUSTER') }}',
            wsHost: '127.0.0.1',
            wsPort: 8080,
            forceTLS: false,
            enabledTransports: ['ws', 'wss']
        });

        const channel = pusher.subscribe('kds-channel');
        channel.bind('order-event', function(data) {
            fetchOnlineOrders();
        });

        fetchOnlineOrders();
    </script>
</body>
</html>