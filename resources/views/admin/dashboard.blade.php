@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Admin Command Center')

@section('content')
<style>
    /* THEME-AWARE STYLES (Light & Dark Mode Adaptive) */
    .dashboard-container {
        color: var(--foreground, #0f172a);
    }
    
    .theme-card {
        background: var(--card, #ffffff);
        color: var(--card-foreground, var(--foreground, #0f172a));
        border: 1px solid var(--border, #e2e8f0);
        border-radius: 16px;
        transition: background 0.2s ease, border-color 0.2s ease;
    }

    /* FILTER BAR */
    .filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 16px;
        border-radius: 14px;
        margin-bottom: 20px;
        gap: 12px;
        flex-wrap: wrap;
    }
    .period-pills {
        display: flex;
        gap: 6px;
        background: var(--muted, rgba(100, 116, 139, 0.08));
        padding: 4px;
        border-radius: 10px;
        border: 1px solid var(--border, #e2e8f0);
    }
    .period-pill {
        color: var(--muted-foreground, #64748b);
        text-decoration: none;
        font-size: 12px;
        font-weight: 800;
        padding: 6px 14px;
        border-radius: 8px;
        transition: all 0.15s ease;
    }
    .period-pill:hover {
        color: var(--foreground, #0f172a);
        background: rgba(100, 116, 139, 0.1);
    }
    .period-pill.active {
        background: var(--primary, #3b82f6);
        color: #ffffff !important;
    }
    .custom-date-form {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .date-input {
        background: var(--background, #ffffff);
        border: 1px solid var(--border, #cbd5e1);
        color: var(--foreground, #0f172a);
        font-size: 12px;
        font-weight: 700;
        padding: 6px 10px;
        border-radius: 8px;
        outline: none;
    }

    /* KPI CARDS */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .kpi-card {
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .kpi-title {
        font-size: 11px;
        font-weight: 800;
        color: var(--muted-foreground, #64748b);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .kpi-value {
        font-size: 26px;
        font-weight: 900;
        margin-top: 6px;
    }

    /* QUICK LAUNCH BAR */
    .quick-launch-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 24px;
        padding: 16px;
        border-radius: 16px;
    }

    /* CHARTS */
    .charts-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }
    .ops-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 20px;
    }
    @media (max-width: 1024px) {
        .charts-grid, .ops-grid { grid-template-columns: 1fr; }
    }

    .table-row-border {
        border-bottom: 1px solid var(--border, #e2e8f0);
    }
</style>

<div class="dashboard-container">

    <!-- PAGE HEADER -->
    <div class="page-header" style="margin-bottom: 16px;">
        <div class="page-header-row" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 class="page-title" style="font-size: 24px; font-weight: 900; margin: 0; color: var(--foreground, #0f172a);">
                    👋 Welcome Back, {{ auth()->user()->name }}!
                </h1>
                <p class="page-description" style="color: var(--muted-foreground, #64748b); margin-top: 4px; font-size: 13px;">
                    Live operations and sales metrics for <strong style="color: var(--primary, #3b82f6);">{{ $periodLabel }}</strong>.
                </p>
            </div>
            <div>
                @if(App\Helpers\StoreHoursHelper::isOpen())
                    <span class="badge badge-success" style="font-size: 13px; padding: 8px 16px; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 20px; font-weight: 700;">
                        🟢 Store Open (Shift Active)
                    </span>
                @else
                    <span class="badge badge-danger" style="font-size: 13px; padding: 8px 16px; background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 20px; font-weight: 700;">
                        🔴 Store Closed
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- 🚀 PERIOD FILTER SWITCHER BAR -->
    <div class="theme-card filter-bar">
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <span style="font-size: 12px; font-weight: 800; color: var(--muted-foreground, #64748b);">PERIOD:</span>
            <div class="period-pills">
                <a href="{{ route('tyro-dashboard.index', ['period' => 'month']) }}" class="period-pill {{ $period === 'month' ? 'active' : '' }}">
                    📊 This Month (Default)
                </a>
                <a href="{{ route('tyro-dashboard.index', ['period' => 'week']) }}" class="period-pill {{ $period === 'week' ? 'active' : '' }}">
                    📅 This Week
                </a>
                <a href="{{ route('tyro-dashboard.index', ['period' => 'today']) }}" class="period-pill {{ $period === 'today' ? 'active' : '' }}">
                    ☀️ Today
                </a>
            </div>
        </div>

        <!-- CUSTOM DATE RANGE FILTER -->
        <form action="{{ route('tyro-dashboard.index') }}" method="GET" class="custom-date-form">
            <input type="hidden" name="period" value="custom">
            <input type="date" name="start_date" class="date-input" value="{{ $customStart ?? '' }}" required>
            <span style="color: var(--muted-foreground); font-size: 12px;">to</span>
            <input type="date" name="end_date" class="date-input" value="{{ $customEnd ?? '' }}" required>
            <button type="submit" class="btn btn-sm btn-primary" style="padding: 6px 12px; font-size: 12px; font-weight: 800;">
                Apply
            </button>
        </form>
    </div>

    <!-- 🚀 QUICK TERMINAL LAUNCH BAR -->
    <div class="theme-card quick-launch-bar">
        <a href="/pos" target="_blank" class="btn btn-primary" style="font-weight: bold; text-decoration: none;">
            ⌨️ Open Web POS Terminal
        </a>
        <a href="{{ route('admin.orders.online') }}" class="btn btn-secondary" style="text-decoration: none;">
            🔔 Online Orders Dispatcher
            @if($pendingOnlineOrders->count() > 0)
                <span class="badge badge-warning" style="margin-left: 6px; background: #f59e0b; color: #111827; padding: 2px 6px; border-radius: 4px; font-weight: 900;">
                    {{ $pendingOnlineOrders->count() }} PENDING
                </span>
            @endif
        </a>
        <a href="{{ route('admin.reservations.floor_plan') }}" class="btn btn-secondary" style="text-decoration: none;">
            🗺️ Table Floor Plan &amp; Hostess
        </a>
        <a href="{{ route('admin.kds.chef') }}" target="_blank" class="btn btn-ghost" style="text-decoration: none;">
            👨‍🍳 Chef KDS
        </a>
        <a href="{{ route('admin.kds.packer') }}" target="_blank" class="btn btn-ghost" style="text-decoration: none;">
            📦 Packer KDS
        </a>
    </div>

    <!-- 🚀 TOP KPI CARDS -->
    <div class="kpi-grid">
        <div class="theme-card kpi-card">
            <div class="kpi-title">Sales Revenue ({{ strtoupper($period) }})</div>
            <div class="kpi-value" style="color: #3b82f6;">{{ $currencySymbol }}{{ number_format($revenue, 2) }}</div>
            <span style="font-size: 11px; color: var(--muted-foreground);">Gross Sales (TTC)</span>
        </div>

        <div class="theme-card kpi-card">
            <div class="kpi-title">Total Orders ({{ strtoupper($period) }})</div>
            <div class="kpi-value" style="color: #f59e0b;">{{ $orderCount }}</div>
            <span style="font-size: 11px; color: var(--muted-foreground);">
                🛍️ {{ $onlineCount }} Online | 📦 {{ $takeawayCount }} Takeaway | 🍽️ {{ $dineInCount }} Dine-In
            </span>
        </div>

        <div class="theme-card kpi-card">
            <div class="kpi-title">Net Operating Profit ({{ strtoupper($period) }})</div>
            <div class="kpi-value" style="color: {{ $netProfit >= 0 ? '#10b981' : '#ef4444' }};">
                {{ $currencySymbol }}{{ number_format($netProfit, 2) }}
            </div>
            <a href="{{ route('admin.reports.pnl') }}" style="font-size: 11px; color: #3b82f6; font-weight: bold; text-decoration: none;">
                View Detailed P&amp;L →
            </a>
        </div>

        <div class="theme-card kpi-card" style="border-color: {{ $lowStockIngredients->count() > 0 ? '#ef4444' : 'var(--border)' }};">
            <div class="kpi-title">Low Stock Warnings</div>
            <div class="kpi-value" style="color: {{ $lowStockIngredients->count() > 0 ? '#ef4444' : '#10b981' }};">
                {{ $lowStockIngredients->count() }}
            </div>
            <span style="font-size: 11px; color: var(--muted-foreground);">Ingredients Below Alert Threshold</span>
        </div>
    </div>

    <!-- 🚀 INTERACTIVE CHARTS SECTION -->
    <div class="charts-grid">
        
        <!-- CHART 1: REVENUE TREND -->
        <div class="theme-card" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: var(--foreground, #0f172a);">
                        📈 Revenue Trend ({{ $periodLabel }})
                    </h3>
                    <span style="font-size: 11px; color: var(--muted-foreground, #64748b);">Gross Sales Trajectory</span>
                </div>
                <span style="font-size: 12px; font-weight: 700; color: #3b82f6; background: rgba(59, 130, 246, 0.1); padding: 4px 10px; border-radius: 8px;">
                    {{ strtoupper($period) }}
                </span>
            </div>
            <div style="position: relative; height: 260px; width: 100%;">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>

        <!-- CHART 2: CHANNEL DISTRIBUTION (DONUT) -->
        <div class="theme-card" style="padding: 20px;">
            <div style="margin-bottom: 16px;">
                <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: var(--foreground, #0f172a);">🍩 Sales Channels</h3>
                <span style="font-size: 11px; color: var(--muted-foreground, #64748b);">Dine-In vs Takeaway vs Online</span>
            </div>
            <div style="position: relative; height: 210px; width: 100%; display: flex; justify-content: center; align-items: center;">
                <canvas id="channelsDonutChart"></canvas>
            </div>
            <div style="display: flex; justify-content: space-around; font-size: 11px; font-weight: 700; margin-top: 10px; text-align: center;">
                <span style="color: #f59e0b;">🍽️ {{ $dineInCount }} Dine-In</span>
                <span style="color: #10b981;">📦 {{ $takeawayCount }} Takeaway</span>
                <span style="color: #8b5cf6;">🛍️ {{ $onlineCount }} Online</span>
            </div>
        </div>

    </div>

    <!-- 🚀 OPERATIONS QUEUE & LOW STOCK -->
    <div class="ops-grid">

        <!-- LEFT: LIVE QUEUES -->
        <div style="display: grid; gap: 20px;">
            
            <!-- Pending Online Orders -->
            <div class="theme-card" style="padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                    <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: var(--foreground, #0f172a);">
                        🔔 Pending Online Orders (Requires Acceptance)
                    </h3>
                    <a href="{{ route('admin.orders.online') }}" class="btn btn-sm btn-ghost" style="font-size: 11px; text-decoration: none;">View Dispatcher →</a>
                </div>
                @if($pendingOnlineOrders->count() > 0)
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr class="table-row-border" style="text-align: left; color: var(--muted-foreground);">
                                <th style="padding: 10px 8px;">Ticket #</th>
                                <th style="padding: 10px 8px;">Customer</th>
                                <th style="padding: 10px 8px;">Items</th>
                                <th style="padding: 10px 8px;">Total</th>
                                <th style="padding: 10px 8px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingOnlineOrders as $order)
                            <tr class="table-row-border">
                                <td style="padding: 10px 8px;"><strong style="color: #f59e0b;">#{{ $order->sequence_number ?? $order->id }}</strong></td>
                                <td style="padding: 10px 8px;">{{ $order->customer_name ?? ($order->client->name ?? 'Web Customer') }}</td>
                                <td style="padding: 10px 8px;">{{ $order->items->count() }} Items</td>
                                <td style="padding: 10px 8px;"><strong>{{ $currencySymbol }}{{ number_format($order->total_incl_vat, 2) }}</strong></td>
                                <td style="padding: 10px 8px; text-align: right;">
                                    <a href="{{ route('admin.orders.online') }}" class="btn btn-sm btn-primary" style="font-size: 11px; padding: 4px 10px; text-decoration: none;">Accept / Reject</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div style="padding: 20px; text-align: center; color: var(--muted-foreground); font-size: 13px;">
                    ✓ All online orders have been processed!
                </div>
                @endif
            </div>

            <!-- Table Reservations -->
            <div class="theme-card" style="padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                    <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: var(--foreground, #0f172a);">
                        📅 Table Reservations
                    </h3>
                    <a href="{{ route('admin.reservations.floor_plan') }}" class="btn btn-sm btn-ghost" style="font-size: 11px; text-decoration: none;">View Floor Plan →</a>
                </div>
                @if($todayReservations->count() > 0)
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr class="table-row-border" style="text-align: left; color: var(--muted-foreground);">
                                <th style="padding: 10px 8px;">Time</th>
                                <th style="padding: 10px 8px;">Customer</th>
                                <th style="padding: 10px 8px;">Guests</th>
                                <th style="padding: 10px 8px;">Table</th>
                                <th style="padding: 10px 8px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todayReservations as $res)
                            <tr class="table-row-border">
                                <td style="padding: 10px 8px;"><strong>{{ Carbon\Carbon::parse($res->reservation_time)->format('H:i') }}</strong></td>
                                <td style="padding: 10px 8px;">{{ $res->customer_name }}</td>
                                <td style="padding: 10px 8px;">{{ $res->guest_count }} Guests</td>
                                <td style="padding: 10px 8px;">{{ $res->table->table_number ?? 'Unassigned' }}</td>
                                <td style="padding: 10px 8px;">
                                    <span style="font-size: 11px; padding: 3px 8px; border-radius: 6px; font-weight: 700; background: {{ $res->status === 'seated' ? 'rgba(239, 68, 68, 0.15)' : 'rgba(245, 158, 11, 0.15)' }}; color: {{ $res->status === 'seated' ? '#ef4444' : '#f59e0b' }};">
                                        {{ strtoupper($res->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div style="padding: 20px; text-align: center; color: var(--muted-foreground); font-size: 13px;">
                    No table bookings scheduled for this date.
                </div>
                @endif
            </div>

        </div>

        <!-- RIGHT: LOW STOCK & QUICK LINKS -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- Low Stock Ingredients Alert Card -->
            <div class="theme-card" style="padding: 20px;">
                <div style="margin-bottom: 12px;">
                    <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: var(--foreground, #0f172a);">
                        🚨 Low Stock Ingredients
                    </h3>
                </div>
                <div>
                    @if($lowStockIngredients->count() > 0)
                        <div style="display: grid; gap: 10px;">
                            @foreach($lowStockIngredients as $ing)
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 8px;">
                                    <div>
                                        <strong style="font-size: 13px; color: var(--foreground, #0f172a);">{{ $ing->name }}</strong>
                                        <span style="display: block; font-size: 11px; color: var(--muted-foreground, #64748b);">
                                            Threshold: {{ App\Helpers\UnitConverter::formatForDisplay($ing->alert_level, $ing->unit) }}
                                        </span>
                                    </div>
                                    <span style="font-weight: 900; color: #ef4444; font-size: 13px;">
                                        {{ App\Helpers\UnitConverter::formatForDisplay($ing->stock_level, $ing->unit) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary" style="width: 100%; margin-top: 14px; text-align: center; font-size: 12px; text-decoration: none; display: block; box-sizing: border-box;">
                            ➕ Order Stock from Supplier
                        </a>
                    @else
                        <div style="text-align: center; color: #10b981; font-weight: bold; padding: 10px 0; font-size: 13px;">
                            ✅ All ingredient stocks are above alert thresholds!
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Settings & Admin Actions -->
            <div class="theme-card" style="padding: 20px;">
                <div style="margin-bottom: 12px;">
                    <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: var(--foreground, #0f172a);">
                        ⚙️ Quick Admin Actions
                    </h3>
                </div>
                <div style="display: grid; gap: 8px;">
                    <a href="{{ route('admin.settings.general') }}" class="btn btn-ghost" style="justify-content: flex-start; text-decoration: none; font-size: 12px;">
                        ⚙️ General &amp; Store Operating Hours
                    </a>
                    <a href="{{ route('admin.settings.homepage') }}" class="btn btn-ghost" style="justify-content: flex-start; text-decoration: none; font-size: 12px;">
                        🏠 Homepage Banner &amp; Content
                    </a>
                    <a href="{{ route('admin.settings.theme') }}" class="btn btn-ghost" style="justify-content: flex-start; text-decoration: none; font-size: 12px;">
                        🎨 Web Theme &amp; Branding Colors
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- 🚀 CHART.JS SCRIPT (Theme-Adaptive for Light and Dark Modes) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currency = '{{ $currencySymbol }}';

    // 🎨 Dynamic Theme Detection (Checks Tyro's dark class or system dark preference)
    const isDark = document.documentElement.classList.contains('dark') || 
                   document.body.classList.contains('dark') || 
                   window.matchMedia('(prefers-color-scheme: dark)').matches;

    const tickColor = isDark ? '#94a3b8' : '#64748b';
    const gridLineColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)';

    // 1. 📈 Dynamic Revenue Trend Chart
    const trendCtx = document.getElementById('revenueTrendChart').getContext('2d');
    const trendGradient = trendCtx.createLinearGradient(0, 0, 0, 260);
    trendGradient.addColorStop(0, 'rgba(59, 130, 246, 0.35)');
    trendGradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($trendLabels ?? []) !!},
            datasets: [{
                label: 'Sales Revenue',
                data: {!! json_encode($trendData ?? []) !!},
                borderColor: '#3b82f6',
                borderWidth: 3,
                backgroundColor: trendGradient,
                fill: true,
                tension: 0.38,
                pointBackgroundColor: '#3b82f6',
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return ' Sales: ' + currency + Number(ctx.parsed.y).toFixed(2);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: gridLineColor },
                    ticks: { color: tickColor, font: { size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridLineColor },
                    ticks: {
                        color: tickColor,
                        font: { size: 11 },
                        callback: function (value) { return currency + value; }
                    }
                }
            }
        }
    });

    // 2. 🍩 Sales Channel Donut Chart
    const channelsCtx = document.getElementById('channelsDonutChart').getContext('2d');
    const channelData = {!! json_encode($channelData ?? [0, 0, 0]) !!};
    const hasData = channelData.some(val => val > 0);

    new Chart(channelsCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($channelLabels ?? ['Dine-In', 'Takeaway', 'Online']) !!},
            datasets: [{
                data: hasData ? channelData : [1, 1, 1],
                backgroundColor: hasData ? ['#f59e0b', '#10b981', '#8b5cf6'] : ['#94a3b8', '#cbd5e1', '#e2e8f0'],
                borderWidth: 2,
                borderColor: isDark ? '#1e293b' : '#ffffff',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: hasData,
                    callbacks: {
                        label: function (ctx) {
                            return ' ' + ctx.label + ': ' + ctx.parsed + ' Orders';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection