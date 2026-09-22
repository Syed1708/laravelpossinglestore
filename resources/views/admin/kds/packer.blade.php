<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>📦 Packer &amp; Expediter Screen (KDS)</title>
    <!-- Pusher JS -->
    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
    <style>
        :root[data-theme="dark"] {
            --bg-color: #0b0f19;
            --card-bg: #182234;
            --card-header-bg: rgba(0, 0, 0, 0.25);
            --text-main: #f9fafb;
            --text-muted: #94a3b8;
            --border: #2e3e56;
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --destructive: #ef4444;
            --notes-bg: rgba(245, 158, 11, 0.18);
            --notes-border: rgba(245, 158, 11, 0.7);
            --notes-text: #fef08a;
        }

        :root[data-theme="light"] {
            --bg-color: #f1f5f9;
            --card-bg: #ffffff;
            --card-header-bg: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #cbd5e1;
            --primary: #2563eb;
            --success: #059669;
            --warning: #d97706;
            --destructive: #dc2626;
            --notes-bg: #fef3c7;
            --notes-border: #f59e0b;
            --notes-text: #92400e;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 14px;
            box-sizing: border-box;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            user-select: none;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .kds-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border);
            padding-bottom: 12px;
            margin-bottom: 12px;
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
        }

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
            background: rgba(100, 116, 139, 0.1);
        }

        .filter-btn.active {
            background: var(--primary);
            color: #ffffff !important;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
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
            width: 150px;
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
            background: rgba(16, 185, 129, 0.12);
            padding: 6px 10px;
            border-radius: 20px;
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
        }

        .kds-main-layout {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 310px;
            gap: 16px;
            overflow: hidden;
            transition: grid-template-columns 0.25s ease;
        }

        .kds-main-layout.sidebar-collapsed {
            grid-template-columns: 1fr 0px;
        }
        .kds-main-layout.sidebar-collapsed .kds-sidebar {
            display: none;
        }

        .kds-grid {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            align-items: flex-start;
            padding-bottom: 12px;
            scroll-behavior: smooth;
        }

        .kds-grid::-webkit-scrollbar { height: 10px; }
        .kds-grid::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 5px;
        }

        .kds-card {
            width: 330px;
            min-width: 330px;
            background-color: var(--card-bg);
            border: 2px solid var(--border);
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 8px 18px -4px rgba(0, 0, 0, 0.15);
            max-height: 100%;
        }

        .kds-card.urgent {
            border-color: var(--destructive);
            box-shadow: 0 0 16px rgba(239, 68, 68, 0.35);
        }

        .kds-card.ready-to-pack {
            border-color: var(--success);
            box-shadow: 0 0 16px rgba(16, 185, 129, 0.25);
        }

        .kds-card-header {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            background: var(--card-header-bg);
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

        .kds-badge {
            font-size: 10px;
            font-weight: 900;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }
        .badge-takeaway { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.3); }
        .badge-dinein { background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); }

        .kds-timer {
            font-size: 13px;
            font-weight: 800;
            font-family: monospace;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .timer-normal { color: var(--success); }
        .timer-warning { color: var(--warning); background: rgba(245, 158, 11, 0.12); }
        .timer-urgent { color: var(--destructive); background: rgba(239, 68, 68, 0.15); font-weight: 900; }

        .kitchen-status-banner {
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }

        .status-pending { background-color: rgba(71, 85, 105, 0.25); color: var(--text-muted); }
        .status-preparing { background-color: rgba(245, 158, 11, 0.15); color: var(--warning); }
        .status-ready { background-color: rgba(16, 185, 129, 0.2); color: var(--success); font-weight: 900; }
        .status-no-kitchen { background-color: rgba(59, 130, 246, 0.15); color: var(--primary); }

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
            opacity: 0.45;
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

        .kds-item-notes {
            background-color: var(--notes-bg);
            border: 1px solid var(--notes-border);
            color: var(--notes-text);
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
            background: var(--card-header-bg);
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
            transition: all 0.2s ease;
        }
        .kds-btn:active { transform: scale(0.98); }
        .kds-btn-complete { background-color: var(--success); color: #111827; }

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

        .kds-stat-label { font-size: 10px; font-weight: 800; color: var(--text-muted); }
        .kds-stat-val { font-size: 20px; font-weight: 900; margin-top: 2px; }

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
            background: var(--primary);
            color: #ffffff;
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

    <div class="kds-header">
        <div class="kds-title-group">
            <h1 class="kds-title">📦 PACKER &amp; EXPEDITER SCREEN</h1>
            <div class="filter-tabs">
                <button class="filter-btn active" data-filter="all" onclick="setPackerFilter('all')">
                    All <span id="badge-count-all" style="font-weight: 900;">0</span>
                </button>
                <button class="filter-btn" data-filter="ready" onclick="setPackerFilter('ready')">
                    ✔️ Kitchen Ready <span id="badge-count-ready" style="font-weight: 900; color: var(--success);">0</span>
                </button>
                <button class="filter-btn" data-filter="kitchen" onclick="setPackerFilter('kitchen')">
                    🔥 In Kitchen <span id="badge-count-kitchen" style="font-weight: 900; color: var(--warning);">0</span>
                </button>
                <button class="filter-btn" data-filter="direct" onclick="setPackerFilter('direct')">
                    🥤 Direct / Drinks <span id="badge-count-direct" style="font-weight: 900;">0</span>
                </button>
                <button class="filter-btn" data-filter="urgent" onclick="setPackerFilter('urgent')">
                    🚨 Urgent <span id="badge-count-urgent" style="font-weight: 900; color: var(--destructive);">0</span>
                </button>
            </div>
        </div>

        <div class="header-actions">
            <input type="text" id="packer-search" class="kds-search-input" placeholder="🔍 Search Ticket / Name..." oninput="handleSearch(this.value)">
            <button class="btn-action" id="theme-btn" onclick="toggleKdsTheme()">🌙 Dark</button>
            <button class="btn-action" onclick="toggleSidebar()">📊 Sidebar</button>
            <button class="btn-action" id="audio-btn" onclick="unlockAudio()">🔔 Audio</button>
            <button class="btn-action" onclick="toggleFullScreen()">⛶ Screen</button>
            <div class="kds-status" id="ws-status">
                <span id="ws-dot" style="height: 9px; width: 9px; background-color: var(--success); border-radius: 50%; display: inline-block;"></span>
                <span id="ws-text">Connected</span>
            </div>
        </div>
    </div>

    <div class="kds-main-layout" id="kds-layout">
        <div class="kds-grid" id="kds-workspace"></div>

        <div class="kds-sidebar">
            <div>
                <div class="sidebar-section-title">
                    <span>📊 Counter &amp; Packing Stats</span>
                    <span id="sidebar-time" style="color: var(--primary);">00:00:00</span>
                </div>
                <div class="stat-grid">
                    <div class="kds-stat-box">
                        <span class="kds-stat-label">TO PACK</span>
                        <span class="kds-stat-val" id="stat-active" style="color: var(--primary);">0</span>
                    </div>
                    <div class="kds-stat-box">
                        <span class="kds-stat-label">✔️ KITCHEN READY</span>
                        <span class="kds-stat-val" id="stat-ready" style="color: var(--success);">0</span>
                    </div>
                    <div class="kds-stat-box">
                        <span class="kds-stat-label">🔥 IN KITCHEN</span>
                        <span class="kds-stat-val" id="stat-kitchen" style="color: var(--warning);">0</span>
                    </div>
                    <div class="kds-stat-box">
                        <span class="kds-stat-label">🚨 URGENT (>15m)</span>
                        <span class="kds-stat-val" id="stat-urgent" style="color: var(--destructive);">0</span>
                    </div>
                </div>
            </div>

            <div style="flex: 1; display: flex; flex-direction: column;">
                <div class="sidebar-section-title">
                    <span>🎒 Total Items to Bag &amp; Pack</span>
                    <span style="font-size: 11px; color: var(--primary);">Active Batch</span>
                </div>
                <div class="batch-items-container" id="batch-items-list">
                    <div style="text-align: center; color: var(--text-muted); font-size: 12px; padding: 20px 0;">
                        No items waiting to be packed.
                    </div>
                </div>
            </div>

            <div>
                <button onclick="fetchPackerOrders()" class="btn-action" style="width: 100%; padding: 10px; justify-content: center; font-weight: 800;">
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
        const themeBtn = document.getElementById('theme-btn');
        const layoutEl = document.getElementById('kds-layout');

        let activeOrdersList = [];
        let currentFilter = 'all';
        let currentSearchQuery = '';
        let audioCtx = null;
        let isAudioUnlocked = false;

        function initTheme() {
            const savedTheme = localStorage.getItem('kds_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            themeBtn.innerHTML = savedTheme === 'dark' ? '🌙 Dark' : '☀️ Light';
        }
        function toggleKdsTheme() {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('kds_theme', next);
            themeBtn.innerHTML = next === 'dark' ? '🌙 Dark' : '☀️ Light';
        }
        function toggleSidebar() { layoutEl.classList.toggle('sidebar-collapsed'); }
        initTheme();

        function unlockAudio() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!audioCtx) audioCtx = new AudioContext();
                audioCtx.resume().then(() => {
                    isAudioUnlocked = true;
                    audioBadge.innerHTML = '🔊 Sound On';
                    audioBadge.style.color = 'var(--success)';
                    playKitchenAlert();
                });
            } catch (e) {}
        }
        document.body.addEventListener('click', () => { if (!isAudioUnlocked) unlockAudio(); }, { once: true });

        function playKitchenAlert() {
            if (!audioCtx) return;
            try { playTone(880.00, 0.08, 0); playTone(1046.50, 0.14, 0.10); } catch (err) {}
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
            if (!document.fullscreenElement) document.documentElement.requestFullscreen().catch(() => {});
            else document.exitFullscreen().catch(() => {});
        }

        // --- FETCH PACKER ORDERS ---
        async function fetchPackerOrders() {
            try {
                const response = await fetch("{{ route('admin.kds.orders.packer') }}");
                if (!response.ok) return;
                const orders = await response.json();
                activeOrdersList = orders;
                updateSidebarAndBadges();
                renderKdsWorkspace();
            } catch (error) {
                console.error('[KDS Packer] Failed to fetch orders:', error);
            }
        }

        function setPackerFilter(filterType) {
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

        // --- OPTIMISTIC ITEM TOGGLE & INSTANT COMPLETE BUTTON UNLOCK ---
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

            const order = activeOrdersList.find(o => o.id === orderId);
            if (order) {
                const item = order.items.find(i => i.id === itemId);
                if (item) item.item_status = (item.item_status === 'done') ? 'pending' : 'done';
                updateSidebarAndBadges();
                // 🚀 INSTANT BUTTON UNLOCK (NO REFRESH NEEDED)
                checkCompletionButton(orderId);
            }

            try {
                await fetch(`/api/kds/items/${itemId}/toggle`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });
            } catch (error) {
                fetchPackerOrders();
            }
        }

        // 🚀 DYNAMIC COMPLETE BUTTON UNLOCK CHECKER
        function checkCompletionButton(orderId) {
            const order = activeOrdersList.find(o => o.id === orderId);
            if (!order) return;
            const card = document.getElementById(`card-${orderId}`);
            if (!card) return;

            const rows = card.querySelectorAll('.kds-item-row');
            const allItemsDone = Array.from(rows).every(r => r.classList.contains('kds-item-row-done'));
            const isKitchenReady = !order.has_kitchen_items || order.preparation_status === 'ready';
            const canComplete = isKitchenReady && allItemsDone;

            const btn = document.getElementById(`btn-complete-${orderId}`);
            if (btn) {
                btn.disabled = !canComplete;
                btn.style.backgroundColor = canComplete ? 'var(--success)' : 'var(--border)';
                btn.style.color = canComplete ? '#111827' : 'var(--text-muted)';
                btn.style.cursor = canComplete ? 'pointer' : 'not-allowed';
                btn.style.opacity = canComplete ? '1' : '0.5';
            }
        }

        async function completeOrder(orderId) {
            try {
                await fetch(`/api/kds/orders/${orderId}/status`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ status: 'delivered' })
                });
                fetchPackerOrders();
            } catch (error) {
                console.error('[KDS Packer] Order completion failed:', error);
            }
        }

        // --- RENDER WORKSPACE ---
        function renderKdsWorkspace() {
            const currentScroll = workspace.scrollLeft;

            const filteredOrders = activeOrdersList.filter(order => {
                const isKitchenReady = !order.has_kitchen_items || order.preparation_status === 'ready';
                const isDirectOnly = !order.has_kitchen_items;
                const isCooking = order.has_kitchen_items && order.preparation_status !== 'ready';
                const diffMs = getElapsedMilliseconds(order.completed_at || order.created_at);
                const isUrgent = Math.floor(diffMs / 60000) >= 15;

                if (currentFilter === 'ready' && !isKitchenReady) return false;
                if (currentFilter === 'kitchen' && !isCooking) return false;
                if (currentFilter === 'direct' && !isDirectOnly) return false;
                if (currentFilter === 'urgent' && !isUrgent) return false;

                if (currentSearchQuery) {
                    const ticketNum = String(order.sequence_number || order.id);
                    const custName = (order.customer_name || (order.client ? order.client.name : '')).toLowerCase();
                    if (!ticketNum.includes(currentSearchQuery) && !custName.includes(currentSearchQuery)) return false;
                }
                return true;
            });

            if (filteredOrders.length === 0) {
                workspace.innerHTML = `
                    <div class="empty-kds">
                        <div style="font-size: 64px; margin-bottom: 12px;">🎒</div>
                        <h2 style="font-size: 20px; font-weight: 800; margin: 0 0 6px 0;">No orders to pack!</h2>
                        <p style="margin: 0; font-size: 13px; color: var(--text-muted);">${activeOrdersList.length > 0 ? 'Try changing your filter above.' : 'All customer orders have been served.'}</p>
                    </div>
                `;
                return;
            }

            workspace.innerHTML = filteredOrders.map(order => {
                const allItemsDone = order.items.every(item => item.item_status === 'done');
                const orderType = (order.order_type || '').toLowerCase();
                const isOnline = orderType === 'click_and_collect' || orderType === 'online';
                const isTakeaway = orderType === 'takeaway' || isOnline;
                const customerName = order.customer_name || (order.client ? order.client.name : 'Counter');
                const isKitchenReady = !order.has_kitchen_items || order.preparation_status === 'ready';

                let statusClass = 'status-pending';
                let statusLabel = '⏳ Waiting for Kitchen';

                if (!order.has_kitchen_items) {
                    statusClass = 'status-no-kitchen';
                    statusLabel = '🥤 Direct Items Only (Drinks/Sides)';
                } else if (order.preparation_status === 'preparing') {
                    statusClass = 'status-preparing';
                    statusLabel = '🔥 Kitchen: In Preparation';
                } else if (order.preparation_status === 'ready') {
                    statusClass = 'status-ready';
                    statusLabel = '✔️ KITCHEN READY • BAG & SERVE';
                }

                const itemsHtml = order.items.map(item => {
                    const rawName = item.product_name || 'Item';
                    const cleanName = rawName.includes('[') ? rawName.substring(0, rawName.indexOf('[')).trim() : rawName;

                    let formattedNotes = null;
                    if (item.notes) {
                        if (Array.isArray(item.notes)) formattedNotes = item.notes.join(' | ');
                        else if (typeof item.notes === 'string') {
                            try { formattedNotes = JSON.parse(item.notes).join(' | '); } catch(e) { formattedNotes = item.notes; }
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

                const canComplete = isKitchenReady && allItemsDone;

                return `
                    <div class="kds-card ${isKitchenReady ? 'ready-to-pack' : ''}" id="card-${order.id}">
                        <div>
                            <div class="kds-card-header">
                                <div>
                                    <div class="kds-ticket-num">#${order.sequence_number || order.id}</div>
                                    <div class="kds-customer-name">👤 ${customerName}</div>
                                </div>
                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                    <span class="kds-badge ${isTakeaway ? 'badge-takeaway' : 'badge-dinein'}">
                                        ${isOnline ? '🛍️ ONLINE' : (isTakeaway ? '📦 TAKEAWAY' : '🍽️ DINE-IN')}
                                    </span>
                                    <span class="kds-timer kds-timer-clock" data-completed-at="${order.completed_at || order.created_at}">00:00</span>
                                </div>
                            </div>
                            
                            <div class="kitchen-status-banner ${statusClass}">
                                ${statusLabel}
                            </div>

                            <div class="kds-card-body">
                                ${itemsHtml}
                            </div>
                        </div>

                        <div class="kds-card-footer">
                            <button class="kds-btn kds-btn-complete" 
                                    id="btn-complete-${order.id}"
                                    style="${!canComplete ? 'background-color: var(--border); opacity: 0.5; color: var(--text-muted); cursor: not-allowed;' : ''}"
                                    ${!canComplete ? 'disabled' : ''}
                                    onclick="completeOrder(${order.id})">
                                🎁 COMPLETE ORDER (Served)
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            workspace.scrollLeft = currentScroll;
            updateAllClocks();
        }

        // --- SIDEBAR METRICS ---
        function updateSidebarAndBadges() {
            let readyCount = 0;
            let kitchenCount = 0;
            let directCount = 0;
            let urgentCount = 0;
            const batchCounts = {};

            activeOrdersList.forEach(order => {
                const isKitchenReady = !order.has_kitchen_items || order.preparation_status === 'ready';
                const isDirectOnly = !order.has_kitchen_items;
                const isCooking = order.has_kitchen_items && order.preparation_status !== 'ready';
                const diffMs = getElapsedMilliseconds(order.completed_at || order.created_at);
                const isUrgent = Math.floor(diffMs / 60000) >= 15;

                if (isKitchenReady) readyCount++;
                if (isCooking) kitchenCount++;
                if (isDirectOnly) directCount++;
                if (isUrgent) urgentCount++;

                order.items.forEach(item => {
                    if (item.item_status !== 'done') {
                        const rawName = item.product_name || 'Item';
                        const cleanName = rawName.includes('[') ? rawName.substring(0, rawName.indexOf('[')).trim() : rawName;
                        batchCounts[cleanName] = (batchCounts[cleanName] || 0) + (parseInt(item.quantity) || 1);
                    }
                });
            });

            document.getElementById('badge-count-all').innerText = activeOrdersList.length;
            document.getElementById('badge-count-ready').innerText = readyCount;
            document.getElementById('badge-count-kitchen').innerText = kitchenCount;
            document.getElementById('badge-count-direct').innerText = directCount;
            document.getElementById('badge-count-urgent').innerText = urgentCount;

            document.getElementById('stat-active').innerText = activeOrdersList.length;
            document.getElementById('stat-ready').innerText = readyCount;
            document.getElementById('stat-kitchen').innerText = kitchenCount;
            document.getElementById('stat-urgent').innerText = urgentCount;

            const batchContainer = document.getElementById('batch-items-list');
            const items = Object.entries(batchCounts);

            if (items.length === 0) {
                batchContainer.innerHTML = `<div style="text-align: center; color: var(--text-muted); font-size: 12px; padding: 20px 0;">All items bagged and ready!</div>`;
            } else {
                batchContainer.innerHTML = items
                    .sort((a, b) => b[1] - a[1])
                    .map(([name, qty]) => `
                        <div class="batch-item-row">
                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;">${name}</span>
                            <span class="batch-item-qty">${qty}x</span>
                        </div>
                    `).join('');
            }
        }

        // --- SAFE ISO-8601 TIMERS ---
        function getElapsedMilliseconds(dateStr) {
            if (!dateStr) return 0;
            let s = String(dateStr).trim();
            if (s.length === 19 && s.indexOf(' ') === 10) s = s.replace(' ', 'T') + 'Z';
            else if (!s.endsWith('Z') && !s.includes('+')) s += 'Z';
            const parsed = new Date(s);
            const time = isNaN(parsed.getTime()) ? new Date(dateStr).getTime() : parsed.getTime();
            return Math.max(0, new Date().getTime() - time);
        }

        function updateAllClocks() {
            document.getElementById('sidebar-time').innerText = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            const clocks = document.querySelectorAll('.kds-timer-clock');
            clocks.forEach(clock => {
                const dateStr = clock.getAttribute('data-completed-at');
                const diffMs = getElapsedMilliseconds(dateStr);

                const totalMins = Math.floor(diffMs / 60000);
                const diffSecs = Math.floor((diffMs % 60000) / 1000);
                const pad = (n) => n.toString().padStart(2, '0');

                clock.textContent = `⏱️ ${pad(totalMins)}:${pad(diffSecs)}`;

                const card = clock.closest('.kds-card');
                if (!card) return;

                if (totalMins >= 15) {
                    card.classList.add('urgent');
                    clock.className = 'kds-timer kds-timer-clock timer-urgent';
                } else if (totalMins >= 10) {
                    card.classList.add('delayed');
                    clock.className = 'kds-timer kds-timer-clock timer-warning';
                } else {
                    card.classList.remove('urgent');
                    clock.className = 'kds-timer kds-timer-clock timer-normal';
                }
            });
        }

        // --- REVERB WEBSOCKET LISTENER + BACKGROUND AUTO-POLL FALLBACK ---
        try {
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

            function handleLiveEvent(data) {
                if (data && data.message === 'new_orders_synced') playKitchenAlert();
                fetchPackerOrders();
            }

            channel.bind('order-event', handleLiveEvent);
            channel.bind('.order-event', handleLiveEvent);

            pusher.connection.bind('state_change', function(states) {
                if (states.current === 'connected') {
                    wsDot.style.backgroundColor = 'var(--success)';
                    wsText.textContent = 'Connected';
                } else {
                    wsDot.style.backgroundColor = 'var(--destructive)';
                    wsText.textContent = 'Live Reconnecting';
                }
            });
        } catch (e) {
            console.warn('[KDS WebSocket] Reverb error, relying on auto-poll:', e);
        }

        // 🚀 IMMEDIATE INVOCATION
        fetchPackerOrders();
        setInterval(updateAllClocks, 1000);

        // 🚀 REAL-TIME AUTO-POLL FALLBACK (EVERY 6 SECONDS)
        setInterval(fetchPackerOrders, 6000);
    </script>
</body>

</html>