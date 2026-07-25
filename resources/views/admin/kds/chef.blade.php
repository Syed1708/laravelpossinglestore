<!-- resources/views/admin/kds/chef.blade.php -->
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
            align-items: center;
        }

        .kds-ticket-num {
            font-size: 22px;
            font-weight: 900;
        }

        .kds-timer {
            font-size: 16px;
            font-weight: bold;
            color: var(--warning);
            font-family: monospace;
        }

        .kds-card-body {
            padding: 20px 15px;
            flex: 1;
            overflow-y: auto;
        }

        .kds-item-row {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 15px;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            user-select: none;
        }

        /* 🚀 THE UI FIX: Text color turns slightly muted/grey when checked, with NO line-through! */
        .kds-item-row-done {
            color: var(--text-muted);
            opacity: 0.6;
        }

        .kds-item-qty {
            background-color: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 18px;
        }

        .kds-item-row-done .kds-item-qty {
            background-color: var(--border);
        }

        .kds-item-name {
            flex: 1;
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
        }

        .kds-btn-ready {
            background-color: var(--success);
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
            <span
                style="height: 10px; width: 10px; background-color: var(--success); border-radius: 50%; display: inline-block;"></span>
            Connexion active (WebSockets)
        </div>
    </div>

    <div class="kds-grid" id="kds-workspace">
        <!-- Cards injected here dynamically -->
    </div>

    <script>
        // Enable Pusher console logging
        Pusher.logToConsole = true;

        const workspace = document.getElementById('kds-workspace');
        const wsStatus = document.getElementById('ws-status');
        let activeOrdersList = [];

        // 🚀 1. THE AUDIO ENGINE: Load a clean, loud, high-speed kitchen bell chime from a public CDN
        // (You can replace this URL with your own local file like '/audio/bell.mp3' later)
        // const alertSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2568/2568-84.wav');

        // // 🚀 2. THE BYPASS: Tap anywhere on the screen once when opening to unlock browser audio permissions
        // document.body.addEventListener('click', function() {
        //     alertSound.play().then(() => {
        //         alertSound.pause();
        //         alertSound.currentTime = 0;
        //         console.log('[Audio] Kitchen alerts successfully unlocked and ready!');
        //     }).catch(e => console.log('[Audio] Unlock failed:', e.message));
        // }, {
        //     once: true
        // }); // Runs exactly once on the first tap!


            // 🚀 1. THE NATIVE AUDIO SYNTHESIZER:
        // Generates a clean, professional dual-beep ("Ping-Ping!") directly in the soundcard
        let audioCtx = null;

        function playKitchenAlert() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!audioCtx) {
                    audioCtx = new AudioContext();
                }

                // Play Beep 1: High Pitch (A5), short duration, instantly
                playSyntheticBeep(880.00, 0.08, 0);
                
                // Play Beep 2: Even Higher (C6), slightly longer, after 100ms
                playSyntheticBeep(1046.50, 0.12, 0.10);

            } catch (error) {
                console.log('[Audio Synth] Blocked or not supported:', error.message);
            }
        }

        function playSyntheticBeep(frequency, duration, delay) {
            if (!audioCtx) return;

            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();

            oscillator.type = 'sine'; // Clean electronic wave
            oscillator.frequency.value = frequency;

            // Fades out volume smoothly to prevent harsh clicks
            gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime + delay); // 15% volume
            gainNode.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + delay + duration);

            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);

            oscillator.start(audioCtx.currentTime + delay);
            oscillator.stop(audioCtx.currentTime + delay + duration);
        }

        // 🚀 2. THE BYPASS: Click once anywhere on the screen to wake up the Audio Context
        document.body.addEventListener('click', function() {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            audioCtx = new AudioContext();
            audioCtx.resume().then(() => {
                console.log('[Audio Synth] Engine unlocked and ready!');
                playKitchenAlert(); // Play a test beep to confirm!
            });
        }, { once: true }); // Only runs once


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
                    body: JSON.stringify({
                        status: newStatus
                    })
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

                const itemsHtml = order.items.map(item => `
                    <div class="kds-item-row ${item.item_status === 'done' ? 'kds-item-row-done' : ''}" onclick="toggleItemCheckbox(${item.id})">
                        <span class="kds-item-qty">${item.quantity}</span>
                        <!-- 🚀 THE CHECKBOX UI: Renders ✅ for completed, ⬜ for pending -->
                        <span class="kds-item-name">
                            ${item.item_status === 'done' ? '✅' : '⬜'} ${item.product_name}
                        </span>
                    </div>
                `).join('');

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
                                style="${!allItemsDone ? 'background-color: var(--border); cursor: not-allowed; opacity: 0.5;' : ''}"
                                ${!allItemsDone ? 'disabled' : ''}
                                onclick="updateOrderStatus(${order.id}, 'ready')">
                            ✔️ Prêt (Ready)
                        </button>
                    `;
                }

                return `
                    <div class="kds-card" id="card-${order.id}">
                        <div class="kds-card-header">
                            <span class="kds-ticket-num">TICKET #${order.sequence_number}</span>
                            <span class="kds-timer kds-timer-clock" data-completed-at="${order.completed_at}">00:00:00</span>
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

        // THE WEBSOCKET ENGINE (Pusher Client)
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

            // // 🚀 3. PLAY SOUND: Only play the loud kitchen bell for new incoming synchronized orders!
            // if (data.message === 'new_orders_synced') {
            //     alertSound.play().catch(e => {
            //         console.log(
            //             '[Audio] Playback blocked by browser. Please tap the screen once to unlock.');
            //     });
            // }


             // 🚀 3. PLAY SYNTHETIC ALERT: Trigger the dual-beep on new orders!
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
