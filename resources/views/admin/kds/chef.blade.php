<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>👨‍🍳 Chef Kitchen Command Screen (KDS)</title>
    <!-- Include Pusher JS -->
    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: #182234;
            --card-bg-light: #1f2d42;
            --text-main: #f9fafb;
            --text-muted: #94a3b8;
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --border: #2e3e56;
            --destructive: #ef4444;
            --purple: #8b5cf6;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 16px;
            box-sizing: border-box;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            user-select: none;
        }

        /* HEADER */
        .kds-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border);
            padding-bottom: 12px;
            margin-bottom: 14px;
            flex-shrink: 0;
            gap: 12px;
            flex-wrap: wrap;
        }

        .kds-title-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .kds-title {
            font-size: 20px;
            font-weight: 900;
            margin: 0;
            letter-spacing: 0.5px;
        }

        /* FILTER TABS IN HEADER */
        .filter-tabs {
            display: flex;
            gap: 6px;
            background: var(--card-bg);
            padding: 4px;
            border-radius: 10px;
            border: 1px solid var(--border);
        }

        .filter-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 800;
            padding: 6px 12px;
            border-radius: 7px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-btn:hover {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.05);
        }

        .filter-btn.active {
            background: var(--primary);
            color: #ffffff;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .kds-search-input {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            outline: none;
            width: 140px;
        }

        .kds-search-input:focus {
            border-color: var(--primary);
        }

        .btn-action {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            font-size: 12px;
        }

        .kds-status {
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 185, 129, 0.1);
            padding: 6px 10px;
            border-radius: 20px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        /* 🚀 MAIN TWO-COLUMN LAYOUT */
        .kds-main-layout {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 310px;
            gap: 16px;
            overflow: hidden;
        }

        @media (max-width: 1100px) {
            .kds-main-layout {
                grid-template-columns: 1fr;
            }
            .kds-sidebar {
                display: none;
            }
        }

        /* LEFT: HORIZONTAL SCROLL TICKETS WORKSPACE */
        .kds-grid {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            align-items: flex-start;
            padding-bottom: 12px;
            scroll-behavior: smooth;
        }

        .kds-grid::-webkit-scrollbar {
            height: 10px;
        }
        .kds-grid::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 5px;
        }

        /* TICKET CARD */
        .kds-card {
            width: 320px;
            min-width: 320px;
            background-color: var(--card-bg);
            border: 2px solid var(--border);
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.5);
            max-height: 100%;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .kds-card.urgent {
            border-color: var(--destructive);
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.3);
            animation: pulseUrgent 2s infinite ease-in-out;
        }

        .kds-card.delayed {
            border-color: var(--warning);
        }

        @keyframes pulseUrgent {
            0%, 100% { border-color: rgba(239, 68, 68, 0.7); }
            50% { border-color: rgba(239, 68, 68, 1); }
        }

        .kds-card-header {
            padding: 12px 14px;
            border-bottom: 2px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            background: rgba(0, 0, 0, 0.15);
            border-radius: 12px 12px 0 0;
        }

        .kds-ticket-num {
            font-size: 20px;
            font-weight: 900;
        }

        .kds-customer-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--warning);
            margin-top: 2px;
            max-width: 170px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .kds-badge-takeaway {
            background-color: rgba(139, 92, 246, 0.2);
            color: #c4b5fd;
            border: 1px solid rgba(139, 92, 246, 0.5);
            font-size: 10px;
            font-weight: 900;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
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
        }

        .kds-timer {
            font-size: 13px;
            font-weight: 800;
            font-family: monospace;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .timer-normal { color: var(--success); }
        .timer-warning { color: var(--warning); background: rgba(245, 158, 11, 0.1); }
        .timer-urgent { color: var(--destructive); background: rgba(239, 68, 68, 0.15); font-weight: 900; }

        .kds-card-body {
            padding: 12px 14px;
            flex: 1;
            overflow-y: auto;
        }

        .kds-item-container {
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px dashed var(--border);
        }

        .kds-item-row {
            display: flex;
            gap: 10px;
            align-items: center;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            touch-action: manipulation;
            padding: 3px 0;
        }

        .kds-item-row-done {
            color: var(--text-muted);
            text-decoration: line-through;
            opacity: 0.5;
        }

        .kds-item-qty {
            background-color: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 900;
            min-width: 18px;
            text-align: center;
        }

        .kds-item-row-done .kds-item-qty {
            background-color: var(--border);
            color: var(--text-muted);
        }

        .kds-item-name {
            flex: 1;
            line-height: 1.3;
        }

        .kds-item-notes {
            background-color: rgba(245, 158, 11, 0.18);
            border: 1px solid rgba(245, 158, 11, 0.7);
            color: #fef08a;
            font-size: 12px;
            font-weight: 800;
            padding: 5px 8px;
            border-radius: 7px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .kds-card-footer {
            padding: 12px 14px;
            border-top: 2px solid var(--border);
            background: rgba(0, 0, 0, 0.1);
            border-radius: 0 0 12px 12px;
        }

        .kds-btn {
            width: 100%;
            padding: 13px;
            border-radius: 9px;
            border: none;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
            transition: transform 0.1s ease;
        }

        .kds-btn:active { transform: scale(0.98); }
        .kds-btn-start { background-color: var(--warning); color: #111827; }
        .kds-btn-ready { background-color: var(--success); color: #111827; }

        /* 🚀 RIGHT SIDEBAR OPERATIONS PANEL */
        .kds-sidebar {
            background-color: var(--card-bg);
            border: 2px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            overflow-y: auto;
            max-height: 100%;
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.4);
        }

        .sidebar-section-title {
            font-size: 12px;
            font-weight: 900;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .kds-stat-box {
            background-color: var(--bg-color);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
        }

        .kds-stat-label {
            font-size: 11px;
            font-weight: 800;
            color: var(--text-muted);
        }

        .kds-stat-val {
            font-size: 22px;
            font-weight: 900;
            margin-top: 2px;
        }

        /* 🍳 LIVE GRILL PREP BATCH COUNTER */
        .batch-items-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
            max-height: 280px;
            overflow-y: auto;
        }

        .batch-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-color);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 700;
        }

        .batch-item-qty {
            background: #f59e0b;
            color: #111827;
            font-weight: 900;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 13px;
        }

        .empty-kds {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: var(--text-muted);
        }
    </style>
</head>

<body>

    <!-- 1. HEADER WITH TABS, SEARCH, AND AUDIO TEST -->
    <div class="kds-header">
        <div class="kds-title-group">
            <h1 class="kds-title">👨‍🍳 KITCHEN SCREEN</h1>
            <!-- FILTER TABS -->
            <div class="filter-tabs">
                <button class="filter-btn active" data-filter="all" onclick="setKdsFilter('all')">
                    All <span id="badge-count-all" style="font-weight: 900;">0</span>
                </button>
                <button class="filter-btn" data-filter="pending" onclick="setKdsFilter('pending')">
                    🔥 Needs Start <span id="badge-count-pending" style="font-weight: 900;">0</span>
                </button>
                <button class="filter-btn" data-filter="preparing" onclick="setKdsFilter('preparing')">
                    🍳 Cooking <span id="badge-count-cooking" style="font-weight: 900;">0</span>
                </button>
                <button class="filter-btn" data-filter="urgent" onclick="setKdsFilter('urgent')">
                    🚨 Urgent <span id="badge-count-urgent" style="font-weight: 900; color: #ef4444;">0</span>
                </button>
            </div>
        </div>

        <div class="header-actions">
            <!-- TICKET / CUSTOMER SEARCH -->
            <input type="text" id="kds-search" class="kds-search-input" placeholder="🔍 Search Ticket / Name..." oninput="handleSearch(this.value)">

            <button class="btn-action" id="audio-btn" onclick="unlockAudio()">🔔 Audio Test</button>
            <button class="btn-action" onclick="toggleFullScreen()">⛶ Fullscreen</button>
            <div class="kds-status" id="ws-status">
                <span id="ws-dot" style="height: 9px; width: 9px; background-color: var(--success); border-radius: 50%; display: inline-block;"></span>
                <span id="ws-text">Connected</span>
            </div>
        </div>
    </div>

    <!-- 2. TWO-COLUMN LAYOUT -->
    <div class="kds-main-layout">
        
        <!-- LEFT: HORIZONTAL TICKETS SCROLLER -->
        <div class="kds-grid" id="kds-workspace"></div>

        <!-- RIGHT: LIVE OPERATIONS SIDEBAR -->
        <div class="kds-sidebar">
            <!-- SECTION A: LIVE METRICS -->
            <div>
                <div class="sidebar-section-title">
                    <span>📊 Kitchen Queue Stats</span>
                    <span id="sidebar-time" style="color: var(--primary);">00:00</span>
                </div>
                <div class="stat-grid">
                    <div class="kds-stat-box">
                        <span class="kds-stat-label">ACTIVE TICKETS</span>
                        <span class="kds-stat-val" id="stat-active" style="color: var(--primary);">0</span>
                    </div>
                    <div class="kds-stat-box">
                        <span class="kds-stat-label">🚨 URGENT (>15m)</span>
                        <span class="kds-stat-val" id="stat-urgent" style="color: var(--destructive);">0</span>
                    </div>
                    <div class="kds-stat-box">
                        <span class="kds-stat-label">🍽️ DINE-IN</span>
                        <span class="kds-stat-val" id="stat-dinein" style="color: #93c5fd;">0</span>
                    </div>
                    <div class="kds-stat-box">
                        <span class="kds-stat-label">🛍️ TAKEAWAY</span>
                        <span class="kds-stat-val" id="stat-takeaway" style="color: #c4b5fd;">0</span>
                    </div>
                </div>
            </div>

            <!-- SECTION B: 🍳 LIVE GRILL PREP BATCH COUNTER -->
            <div style="flex: 1; display: flex; flex-direction: column;">
                <div class="sidebar-section-title">
                    <span>🍳 Total Items Needed on Grill</span>
                    <span style="font-size: 11px; color: var(--warning);">Active Batch</span>
                </div>
                <div class="batch-items-container" id="batch-items-list">
                    <div style="text-align: center; color: var(--text-muted); font-size: 12px; padding: 20px 0;">
                        No pending items to cook.
                    </div>
                </div>
            </div>

            <!-- SECTION C: QUICK ACTIONS -->
            <div>
                <button onclick="fetchChefOrders()" class="btn-action" style="width: 100%; padding: 10px; background: rgba(59, 130, 246, 0.15); border-color: rgba(59, 130, 246, 0.4); color: #93c5fd; font-weight: 800;">
                    🔄 Refresh Orders
                </button>
            </div>
        </div>

    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const workspace = document.getElementById('kds-workspace');
        const wsDot = document.getElementById('ws-dot');
        const wsText = document.getElementById('ws-text');
        const audioBadge = document.getElementById('audio-btn');

        let activeOrdersList = [];
        let currentFilter = 'all'; // 'all', 'pending', 'preparing', 'urgent'
        let currentSearchQuery = '';
        let audioCtx = null;
        let isAudioUnlocked = false;

        // --- 1. AUDIO SYNTH ENGINE ---
        function unlockAudio() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!audioCtx) audioCtx = new AudioContext();
                audioCtx.resume().then(() => {
                    isAudioUnlocked = true;
                    audioBadge.innerHTML = '🔊 Sound Active';
                    audioBadge.style.color = 'var(--success)';
                    audioBadge.style.borderColor = 'rgba(16, 185, 129, 0.4)';
                    playKitchenAlert();
                });
            } catch (e) {}
        }

        document.body.addEventListener('click', () => {
            if (!isAudioUnlocked) unlockAudio();
        }, { once: true });

        function playKitchenAlert() {
            if (!audioCtx) return;
            try {
                playTone(880.00, 0.08, 0);
                playTone(1046.50, 0.14, 0.10);
            } catch (err) {}
        }

        function playTone(freq, dur, delay) {
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.2, audioCtx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + delay + dur);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(audioCtx.currentTime + delay);
            osc.stop(audioCtx.currentTime + delay + dur);
        }

        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        }

        // --- 2. FETCH ACTIVE ORDERS ---
        async function fetchChefOrders() {
            try {
                const response = await fetch("{{ route('admin.kds.orders.chef') }}");
                if (!response.ok) return;
                const orders = await response.json();
                activeOrdersList = orders;
                updateSidebarAndBadges();
                renderKdsWorkspace();
            } catch (error) {
                console.error('[KDS Chef] Failed to fetch orders:', error);
            }
        }

        // --- 3. FILTER & SEARCH HANDLERS ---
        function setKdsFilter(filterType) {
            currentFilter = filterType;
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-filter') === filterType);
            });
            renderKdsWorkspace();
        }

        function handleSearch(query) {
            currentSearchQuery = query.toLowerCase().trim();
            renderKdsWorkspace();
        }

        // --- 4. OPTIMISTIC ITEM TOGGLE ---
        async function toggleItemCheckbox(itemId, orderId) {
            const row = document.getElementById(`item-row-${itemId}`);
            if (row) {
                const wasDone = row.classList.contains('kds-item-row-done');
                row.classList.toggle('kds-item-row-done');
                const nameEl = row.querySelector('.kds-item-name');
                if (nameEl) {
                    nameEl.innerHTML = nameEl.innerHTML.replace(wasDone ? '✅' : '⬜', wasDone ? '⬜' : '✅');
                }
            }

            try {
                await fetch(`/api/kds/items/${itemId}/toggle`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });
                // Update local batch list
                const order = activeOrdersList.find(o => o.id === orderId);
                if (order) {
                    const item = order.items.find(i => i.id === itemId);
                    if (item) item.item_status = (item.item_status === 'done') ? 'pending' : 'done';
                    updateSidebarAndBadges();
                }
            } catch (error) {
                fetchChefOrders();
            }
        }

        async function updateOrderStatus(orderId, newStatus) {
            try {
                await fetch(`/api/kds/orders/${orderId}/status`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ status: newStatus })
                });
                fetchChefOrders();
            } catch (error) {
                console.error('[KDS Chef] Status update failed:', error);
            }
        }

        // --- 5. RENDER TICKETS & SIDEBAR ---
        function renderKdsWorkspace() {
            const currentScroll = workspace.scrollLeft;

            // Apply active filters & search
            const filteredOrders = activeOrdersList.filter(order => {
                const status = order.preparation_status || '';
                const diffMs = new Date() - new Date(order.completed_at || order.created_at);
                const isUrgent = Math.floor(diffMs / 60000) >= 15;

                // Tab Filter
                if (currentFilter === 'pending' && status !== 'pending' && status !== 'accepted') return false;
                if (currentFilter === 'preparing' && status !== 'preparing') return false;
                if (currentFilter === 'urgent' && !isUrgent) return false;

                // Search Filter
                if (currentSearchQuery) {
                    const ticketNum = String(order.sequence_number || order.id);
                    const custName = (order.customer_name || (order.client ? order.client.name : '')).toLowerCase();
                    if (!ticketNum.includes(currentSearchQuery) && !custName.includes(currentSearchQuery)) {
                        return false;
                    }
                }

                return true;
            });

            if (filteredOrders.length === 0) {
                workspace.innerHTML = `
                    <div class="empty-kds">
                        <div style="font-size: 60px; margin-bottom: 12px;">🍔</div>
                        <h2 style="font-size: 20px; font-weight: 800; margin: 0 0 6px 0;">No matching kitchen tickets!</h2>
                        <p style="margin: 0; font-size: 13px;">${activeOrdersList.length > 0 ? 'Try changing your filter settings.' : 'Kitchen line is clear.'}</p>
                    </div>
                `;
                return;
            }

            workspace.innerHTML = filteredOrders.map(order => {
                const allItemsDone = order.items.every(item => item.item_status === 'done');
                const orderType = (order.order_type || '').toLowerCase();
                const isOnline = orderType === 'click_and_collect' || orderType === 'online';
                const isTakeaway = orderType === 'takeaway' || isOnline;
                const customerName = order.customer_name || (order.client ? order.client.name : 'Web Customer');

                const itemsHtml = order.items.map(item => {
                    const rawName = item.product_name || 'Item';
                    const cleanName = rawName.includes('[') ? rawName.substring(0, rawName.indexOf('[')).trim() : rawName;

                    let formattedNotes = null;
                    if (item.notes) {
                        if (Array.isArray(item.notes)) {
                            formattedNotes = item.notes.join(' | ');
                        } else if (typeof item.notes === 'string' && item.notes.trim() !== '') {
                            try {
                                const parsed = JSON.parse(item.notes);
                                formattedNotes = Array.isArray(parsed) ? parsed.join(' | ') : item.notes;
                            } catch (e) {
                                formattedNotes = item.notes;
                            }
                        }
                    } else if (rawName.includes('[') && rawName.includes(']')) {
                        formattedNotes = rawName.substring(rawName.indexOf('[') + 1, rawName.lastIndexOf(']'));
                    }

                    const isDone = item.item_status === 'done';

                    return `
                        <div class="kds-item-container">
                            <div class="kds-item-row ${isDone ? 'kds-item-row-done' : ''}" 
                                 id="item-row-${item.id}"
                                 onclick="toggleItemCheckbox(${item.id}, ${order.id})">
                                <span class="kds-item-qty">${item.quantity}</span>
                                <span class="kds-item-name">${isDone ? '✅' : '⬜'} ${cleanName}</span>
                            </div>
                            ${formattedNotes ? `<div class="kds-item-notes">⚠️ ${formattedNotes}</div>` : ''}
                        </div>
                    `;
                }).join('');

                let footerBtn = '';
                if (order.preparation_status === 'pending' || order.preparation_status === 'accepted') {
                    footerBtn = `<button class="kds-btn kds-btn-start" onclick="updateOrderStatus(${order.id}, 'preparing')">🔥 Start Cooking</button>`;
                } else if (order.preparation_status === 'preparing') {
                    footerBtn = `
                        <button class="kds-btn kds-btn-ready" 
                                style="${!allItemsDone ? 'background-color: var(--border); opacity: 0.5; color: var(--text-muted); cursor: not-allowed;' : ''}"
                                ${!allItemsDone ? 'disabled' : ''}
                                onclick="updateOrderStatus(${order.id}, 'ready')">
                            ✔️ Mark Ready
                        </button>`;
                }

                return `
                    <div class="kds-card" id="card-${order.id}">
                        <div class="kds-card-header">
                            <div>
                                <div class="kds-ticket-num">#${order.sequence_number || order.id}</div>
                                <div class="kds-customer-name">👤 ${customerName}</div>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                <span class="${isTakeaway ? 'kds-badge-takeaway' : 'kds-badge-dinein'}">
                                    ${isOnline ? '🛍️ ONLINE' : (isTakeaway ? '📦 TAKEAWAY' : '🍽️ DINE-IN')}
                                </span>
                                <span class="kds-timer kds-timer-clock" data-completed-at="${order.completed_at || order.created_at}">00:00</span>
                            </div>
                        </div>
                        <div class="kds-card-body">${itemsHtml}</div>
                        <div class="kds-card-footer">${footerBtn}</div>
                    </div>
                `;
            }).join('');

            workspace.scrollLeft = currentScroll;
            updateAllClocks();
        }

        // --- 6. UPDATE SIDEBAR METRICS & GRILL BATCH COUNTERS ---
        function updateSidebarAndBadges() {
            let pendingCount = 0;
            let cookingCount = 0;
            let urgentCount = 0;
            let dineInCount = 0;
            let takeawayCount = 0;
            const batchCounts = {};

            activeOrdersList.forEach(order => {
                const status = order.preparation_status || '';
                const diffMs = new Date() - new Date(order.completed_at || order.created_at);
                const isUrgent = Math.floor(diffMs / 60000) >= 15;

                if (status === 'pending' || status === 'accepted') pendingCount++;
                if (status === 'preparing') cookingCount++;
                if (isUrgent) urgentCount++;

                const orderType = (order.order_type || '').toLowerCase();
                if (orderType === 'dine_in') dineInCount++;
                else takeawayCount++;

                // Aggregate items still needed to be cooked
                order.items.forEach(item => {
                    if (item.item_status !== 'done') {
                        const rawName = item.product_name || 'Item';
                        const cleanName = rawName.includes('[') ? rawName.substring(0, rawName.indexOf('[')).trim() : rawName;
                        batchCounts[cleanName] = (batchCounts[cleanName] || 0) + (parseInt(item.quantity) || 1);
                    }
                });
            });

            // Update badge counts in header tabs
            document.getElementById('badge-count-all').innerText = activeOrdersList.length;
            document.getElementById('badge-count-pending').innerText = pendingCount;
            document.getElementById('badge-count-cooking').innerText = cookingCount;
            document.getElementById('badge-count-urgent').innerText = urgentCount;

            // Update sidebar boxes
            document.getElementById('stat-active').innerText = activeOrdersList.length;
            document.getElementById('stat-urgent').innerText = urgentCount;
            document.getElementById('stat-dinein').innerText = dineInCount;
            document.getElementById('stat-takeaway').innerText = takeawayCount;

            // Update 🍳 Grill Batch List
            const batchContainer = document.getElementById('batch-items-list');
            const items = Object.entries(batchCounts);

            if (items.length === 0) {
                batchContainer.innerHTML = `<div style="text-align: center; color: var(--text-muted); font-size: 12px; padding: 20px 0;">All items cooked!</div>`;
            } else {
                batchContainer.innerHTML = items
                    .sort((a, b) => b[1] - a[1]) // highest quantity first
                    .map(([name, qty]) => `
                        <div class="batch-item-row">
                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;">${name}</span>
                            <span class="batch-item-qty">${qty}x</span>
                        </div>
                    `).join('');
            }
        }

        // --- 7. TICKET TIMERS ---
        function updateAllClocks() {
            document.getElementById('sidebar-time').innerText = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            const clocks = document.querySelectorAll('.kds-timer-clock');
            clocks.forEach(clock => {
                const dateStr = clock.getAttribute('data-completed-at');
                if (!dateStr) return;

                const diffMs = new Date() - new Date(dateStr);
                if (diffMs < 0) { clock.textContent = '⏱️ 00:00'; return; }

                const totalMins = Math.floor(diffMs / 60000);
                const diffSecs = Math.floor((diffMs % 60000) / 1000);
                const pad = (n) => n.toString().padStart(2, '0');

                clock.textContent = `⏱️ ${pad(totalMins)}:${pad(diffSecs)}`;

                const card = clock.closest('.kds-card');
                if (!card) return;

                if (totalMins >= 15) {
                    card.classList.add('urgent');
                    card.classList.remove('delayed');
                    clock.className = 'kds-timer kds-timer-clock timer-urgent';
                } else if (totalMins >= 10) {
                    card.classList.add('delayed');
                    card.classList.remove('urgent');
                    clock.className = 'kds-timer kds-timer-clock timer-warning';
                } else {
                    card.classList.remove('urgent', 'delayed');
                    clock.className = 'kds-timer kds-timer-clock timer-normal';
                }
            });
        }

        // --- 8. REVERB WEBSOCKET LISTENER ---
        const reverbKey = "{{ config('broadcasting.connections.reverb.key') ?? env('REVERB_APP_KEY') }}";
        const reverbHost = "{{ config('broadcasting.connections.reverb.options.host') ?? env('REVERB_HOST', '127.0.0.1') }}";
        const reverbPort = {{ config('broadcasting.connections.reverb.options.port') ?? env('REVERB_PORT', 8080) }};
        const reverbScheme = "{{ config('broadcasting.connections.reverb.options.scheme') ?? env('REVERB_SCHEME', 'http') }}";

        const pusher = new Pusher(reverbKey, {
            wsHost: reverbHost,
            wsPort: reverbPort,
            wssPort: reverbPort,
            forceTLS: reverbScheme === 'https',
            disableStats: true,
            enabledTransports: ['ws', 'wss']
        });

        const channel = pusher.subscribe('kds-channel');

        channel.bind('order-event', function(data) {
            if (data.message === 'new_orders_synced') playKitchenAlert();
            fetchChefOrders();
        });

        pusher.connection.bind('state_change', function(states) {
            if (states.current === 'connected') {
                wsDot.style.backgroundColor = 'var(--success)';
                wsText.textContent = 'Connected';
            } else {
                wsDot.style.backgroundColor = 'var(--destructive)';
                wsText.textContent = 'Reconnecting...';
            }
        });

        fetchChefOrders();
        setInterval(updateAllClocks, 1000);
    </script>
</body>

</html>