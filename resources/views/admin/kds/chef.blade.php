<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>👨‍🍳 Écran Chef (KDS)</title>
    <!-- Include Pusher JS -->
    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
    <style>
        :root {
            --bg-color: #111827;
            --card-bg: #1f2937;
            --text-main: #f9fafb;
            --text-muted: #9ca3af;
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --border: #374151;
            --destructive: #ef4444;
            --purple: #8b5cf6;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .kds-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .kds-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 1px;
        }

        .kds-status {
            font-size: 14px;
            font-weight: bold;
            color: var(--success);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kds-grid {
            flex: 1;
            display: flex;
            gap: 20px;
            overflow-x: auto;
            align-items: flex-start;
            padding-bottom: 15px;
        }

        .kds-card {
            width: 320px;
            min-width: 320px;
            background-color: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            max-height: 100%;
        }

        .kds-card-header {
            padding: 15px;
            border-bottom: 2px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .kds-ticket-num {
            font-size: 22px;
            font-weight: 900;
        }

        /* 🚀 Customer Name Display */
        .kds-customer-name {
            font-size: 13px;
            font-weight: 800;
            color: var(--warning);
            margin-top: 4px;
        }

        /* 🚀 ORDER TYPE BADGES */
        .kds-badge-takeaway {
            background-color: rgba(139, 92, 246, 0.2);
            color: #c4b5fd;
            border: 1px solid rgba(139, 92, 246, 0.5);
            font-size: 10px;
            font-weight: 900;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kds-badge-dinein {
            background-color: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.5);
            font-size: 10px;
            font-weight: 900;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kds-timer {
            font-size: 14px;
            font-weight: bold;
            color: var(--warning);
            font-family: monospace;
        }

        .kds-card-body {
            padding: 15px;
            flex: 1;
            overflow-y: auto;
        }

        /* 🚀 ITEM CONTAINER & BRIGHT YELLOW KITCHEN NOTES BOX */
        .kds-item-container {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px dashed var(--border);
        }

        .kds-item-row {
            display: flex;
            gap: 10px;
            align-items: center;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            user-select: none;
        }

        .kds-item-row-done {
            color: var(--text-muted);
            opacity: 0.5;
        }

        .kds-item-qty {
            background-color: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 16px;
        }

        .kds-item-row-done .kds-item-qty {
            background-color: var(--border);
        }

        .kds-item-name {
            flex: 1;
        }

        /* 🚀 BRIGHT YELLOW HIGHLIGHT BOX FOR SANS/EXTRAS */
        .kds-item-notes {
            background-color: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.7);
            color: #fef08a;
            font-size: 12px;
            font-weight: 900;
            padding: 6px 10px;
            border-radius: 8px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.5px;
            animation: pulseNotes 2s infinite;
        }

        @keyframes pulseNotes {
            0%, 100% { border-color: rgba(245, 158, 11, 0.5); }
            50% { border-color: rgba(245, 158, 11, 1); }
        }

        .kds-card-footer {
            padding: 15px;
            border-top: 1px solid var(--border);
        }

        .kds-btn {
            width: 100%;
            padding: 15px;
            border-radius: 8px;
            border: none;
            font-size: 18px;
            font-weight: bold;
            color: white;
            cursor: pointer;
            transition: opacity 0.15s ease;
        }

        .kds-btn:hover {
            opacity: 0.9;
        }

        .kds-btn-start {
            background-color: var(--warning);
            color: #111827;
            font-weight: 900;
        }

        .kds-btn-ready {
            background-color: var(--success);
            color: #111827;
            font-weight: 900;
        }

        .empty-kds {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <div class="kds-header">
        <h1 class="kds-title">👨‍🍳 ÉCRAN CHEF CUISINE (KDS)</h1>
        <div class="kds-status" id="ws-status">
            <span style="height: 10px; width: 10px; background-color: var(--success); border-radius: 50%; display: inline-block;"></span>
            Connexion active (WebSockets)
        </div>
    </div>

    <div class="kds-grid" id="kds-workspace">
        <!-- Cards injected here dynamically -->
    </div>

    <script>
        Pusher.logToConsole = true;

        const workspace = document.getElementById('kds-workspace');
        const wsStatus = document.getElementById('ws-status');
        let activeOrdersList = [];

        let audioCtx = null;

        function playKitchenAlert() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!audioCtx) {
                    audioCtx = new AudioContext();
                }
                playSyntheticBeep(880.00, 0.08, 0);
                playSyntheticBeep(1046.50, 0.12, 0.10);
            } catch (error) {
                console.log('[Audio Synth] Error:', error.message);
            }
        }

        function playSyntheticBeep(frequency, duration, delay) {
            if (!audioCtx) return;
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;

            gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime + delay);
            gainNode.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + delay + duration);

            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);

            oscillator.start(audioCtx.currentTime + delay);
            oscillator.stop(audioCtx.currentTime + delay + duration);
        }

        document.body.addEventListener('click', function() {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            audioCtx = new AudioContext();
            audioCtx.resume().then(() => {
                console.log('[Audio Synth] Engine unlocked!');
                playKitchenAlert();
            });
        }, { once: true });

        async function fetchChefOrders() {
            try {
                const response = await fetch("{{ route('admin.kds.orders.chef') }}");
                const orders = await response.json();
                activeOrdersList = orders;
                renderKdsWorkspace();
            } catch (error) {
                console.error('[KDS Chef] Failed to fetch active orders:', error);
            }
        }

        async function toggleItemCheckbox(itemId) {
            try {
                await fetch(`/api/kds/items/${itemId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
            } catch (error) {
                console.error('[KDS Chef] Item toggle failed:', error);
            }
        }

        async function updateOrderStatus(orderId, newStatus) {
            try {
                await fetch(`/api/kds/orders/${orderId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ status: newStatus })
                });
            } catch (error) {
                console.error('[KDS Chef] Status update failed:', error);
            }
        }

        function renderKdsWorkspace() {
            if (activeOrdersList.length === 0) {
                workspace.innerHTML = `
                    <div class="empty-kds">
                        <div class="empty-icon">🍔</div>
                        <h2>Aucune commande en cuisine !</h2>
                        <p>Détendez-vous ou nettoyez vos plans de travail.</p>
                    </div>
                `;
                return;
            }

            workspace.innerHTML = activeOrdersList.map(order => {
                const allItemsDone = order.items.every(item => item.item_status === 'done');
                const isTakeaway = (order.order_type || order.orderType) === 'takeaway';
                const customerName = order.customer_name || order.customerName || null;

                const itemsHtml = order.items.map(item => {
                    const fullName = item.product_name || 'Article';

                    const hasBracketNotes = fullName.includes('[') && fullName.includes(']');
                    const baseName = hasBracketNotes ? fullName.substring(0, fullName.indexOf('[')).trim() : fullName;
                    const extractedNotes = hasBracketNotes 
                        ? fullName.substring(fullName.indexOf('[') + 1, fullName.lastIndexOf(']'))
                        : (item.notes ? (Array.isArray(item.notes) ? item.notes.join(', ') : item.notes) : null);

                    return `
                        <div class="kds-item-container">
                            <div class="kds-item-row ${item.item_status === 'done' ? 'kds-item-row-done' : ''}" onclick="toggleItemCheckbox(${item.id})">
                                <span class="kds-item-qty">${item.quantity}</span>
                                <span class="kds-item-name">
                                    ${item.item_status === 'done' ? '✅' : '⬜'} ${baseName}
                                </span>
                            </div>

                            ${extractedNotes ? `
                                <div class="kds-item-notes">
                                    ⚠️ <span>${extractedNotes}</span>
                                </div>
                            ` : ''}
                        </div>
                    `;
                }).join('');

                let footerButton = '';
                if (order.preparation_status === 'pending') {
                    footerButton = `
                        <button class="kds-btn kds-btn-start" onclick="updateOrderStatus(${order.id}, 'preparing')">
                            🔥 Commencer la Préparation
                        </button>
                    `;
                } else if (order.preparation_status === 'preparing') {
                    footerButton = `
                        <button class="kds-btn kds-btn-ready" 
                                style="${!allItemsDone ? 'background-color: var(--border); cursor: not-allowed; opacity: 0.5; color: var(--text-muted);' : ''}"
                                ${!allItemsDone ? 'disabled' : ''}
                                onclick="updateOrderStatus(${order.id}, 'ready')">
                            ✔️ Prêt (Ready)
                        </button>
                    `;
                }

                return `
                    <div class="kds-card" id="card-${order.id}">
                        <div class="kds-card-header">
                            <div>
                                <span class="kds-ticket-num">TICKET #${order.sequence_number || order.id}</span>
                                ${customerName ? `<div class="kds-customer-name">Client: ${customerName}</div>` : ''}
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 6px;">
                                <span class="${isTakeaway ? 'kds-badge-takeaway' : 'kds-badge-dinein'}">
                                    ${isTakeaway ? '🛍️ À EMPORTER' : '🍽️ SUR PLACE'}
                                </span>
                                <span class="kds-timer kds-timer-clock" data-completed-at="${order.completed_at}">00:00:00</span>
                            </div>
                        </div>
                        <div class="kds-card-body">
                            ${itemsHtml}
                        </div>
                        <div class="kds-card-footer">
                            ${footerButton}
                        </div>
                    </div>
                `;
            }).join('');

            updateAllClocks();
        }

        function updateAllClocks() {
            const clocks = document.querySelectorAll('.kds-timer-clock');
            clocks.forEach(clock => {
                const completedAtStr = clock.getAttribute('data-completed-at');
                if (!completedAtStr) return;

                const completedTime = new Date(completedAtStr);
                const diffMs = new Date() - completedTime;

                if (diffMs < 0) return;

                const diffHrs = Math.floor(diffMs / 3600000);
                const diffMins = Math.floor((diffMs % 3600000) / 60000);
                const diffSecs = Math.floor((diffMs % 60000) / 1000);

                const pad = (num) => num.toString().padStart(2, '0');
                clock.textContent = `⏱️ ${pad(diffHrs)}:${pad(diffMins)}:${pad(diffSecs)}`;
            });
        }

        const pusher = new Pusher('{{ env('REVERB_APP_KEY') }}', {
            cluster: '{{ env('REVERB_APP_CLUSTER') }}',
            wsHost: '10.178.169.244',
            wsPort: 8080,
            forceTLS: false,
            disableStats: true,
            enabledTransports: ['ws', 'wss']
        });

        const channel = pusher.subscribe('kds-channel');

        channel.bind('order-event', function(data) {
            console.log('[WebSocket] Live event received:', data.message);

            if (data.message === 'new_orders_synced') {
                playKitchenAlert();
            }

            fetchChefOrders();
        });

        pusher.connection.bind('state_change', function(states) {
            if (states.current === 'connected') {
                wsStatus.style.color = 'var(--success)';
            } else {
                wsStatus.style.color = 'var(--destructive)';
            }
        });

        fetchChefOrders();
        setInterval(updateAllClocks, 1000);
    </script>

</body>

</html>