@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Admin Command Center')

@section('content')
<style>
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .kpi-card {
        background: var(--card, #1e293b);
        border: 1px solid var(--border, #334155);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .kpi-title {
        font-size: 11px;
        font-weight: 800;
        color: var(--muted-foreground, #94a3b8);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .kpi-value {
        font-size: 26px;
        font-weight: 900;
        margin-top: 6px;
    }
    .quick-launch-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 24px;
        background: var(--card, #1e293b);
        border: 1px solid var(--border, #334155);
        padding: 16px;
        border-radius: 16px;
    }
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
</style>

<!-- PAGE HEADER -->
<div class="page-header" style="margin-bottom: 20px;">
    <div class="page-header-row" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 class="page-title" style="font-size: 24px; font-weight: 900; margin: 0;">👋 Welcome Back, {{ auth()->user()->name }}!</h1>
            <p class="page-description" style="color: var(--muted-foreground, #94a3b8); margin-top: 4px; font-size: 13px;">
                Here is your live command center for {{ App\Helpers\StoreHoursHelper::now()->format('l, M d, Y (H:i)') }}.
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

<!-- 🚀 1. QUICK TERMINAL LAUNCH BAR -->
<div class="quick-launch-bar">
    <a href="/pos" target="_blank" class="btn btn-primary" style="font-weight: bold;">
        ⌨️ Open Web POS Terminal
    </a>
    <a href="{{ route('admin.orders.online') }}" class="btn btn-secondary">
        🔔 Online Orders Dispatcher
        @if($pendingOnlineOrders->count() > 0)
            <span class="badge badge-warning" style="margin-left: 6px;">{{ $pendingOnlineOrders->count() }} PENDING</span>
        @endif
    </a>
    <a href="{{ route('admin.reservations.floor_plan') }}" class="btn btn-secondary">
        🗺️ Table Floor Plan &amp; Hostess
    </a>
    <a href="{{ route('admin.kds.chef') }}" target="_blank" class="btn btn-ghost">
        👨‍🍳 Chef KDS
    </a>
    <a href="{{ route('admin.kds.packer') }}" target="_blank" class="btn btn-ghost">
        📦 Packer KDS
    </a>
</div>

<!-- 🚀 2. TOP KPI CARDS -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-title">Today's Sales Revenue</div>
        <div class="kpi-value" style="color: #3b82f6;">{{ $currencySymbol }}{{ number_format($todayRevenue, 2) }}</div>
        <span style="font-size: 11px; color: var(--muted-foreground);">Gross Sales (TTC)</span>
    </div>

    <div class="kpi-card">
        <div class="kpi-title">Today's Total Orders</div>
        <div class="kpi-value" style="color: #f59e0b;">{{ $todayOrderCount }}</div>
        <span style="font-size: 11px; color: var(--muted-foreground);">
            🛍️ {{ $onlineCount }} Online | 📦 {{ $takeawayCount }} Takeaway | 🍽️ {{ $dineInCount }} Dine-In
        </span>
    </div>

    <div class="kpi-card">
        <div class="kpi-title">This Month Net Profit</div>
        <div class="kpi-value" style="color: {{ $monthNetProfit >= 0 ? '#10b981' : '#ef4444' }};">
            {{ $currencySymbol }}{{ number_format($monthNetProfit, 2) }}
        </div>
        <a href="{{ route('admin.reports.pnl') }}" style="font-size: 11px; color: var(--primary, #3b82f6); font-weight: bold; text-decoration: none;">View P&amp;L Financials →</a>
    </div>

    <div class="kpi-card" style="border-color: {{ $lowStockIngredients->count() > 0 ? '#ef4444' : 'var(--border)' }};">
        <div class="kpi-title">Low Stock Warnings</div>
        <div class="kpi-value" style="color: {{ $lowStockIngredients->count() > 0 ? '#ef4444' : '#10b981' }};">
            {{ $lowStockIngredients->count() }}
        </div>
        <span style="font-size: 11px; color: var(--muted-foreground);">Ingredients Below Alert Level</span>
    </div>
</div>

<!-- 🚀 3. INTERACTIVE VISUAL CHARTS SECTION -->
<div class="charts-grid">
    
    <!-- CHART 1: 7-DAY REVENUE TRAJECTORY -->
    <div class="card" style="background: var(--card, #1e293b); border: 1px solid var(--border, #334155); border-radius: 16px; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: var(--foreground, #f8fafc);">📈 7-Day Revenue Trend</h3>
                <span style="font-size: 11px; color: var(--muted-foreground, #94a3b8);">Daily Gross Sales (TTC)</span>
            </div>
            <span style="font-size: 12px; font-weight: 700; color: #3b82f6; background: rgba(59, 130, 246, 0.1); padding: 4px 10px; border-radius: 8px;">
                Past Week
            </span>
        </div>
        <div style="position: relative; height: 260px; width: 100%;">
            <canvas id="revenueTrendChart"></canvas>
        </div>
    </div>

    <!-- CHART 2: CHANNEL DISTRIBUTION (DONUT) -->
    <div class="card" style="background: var(--card, #1e293b); border: 1px solid var(--border, #334155); border-radius: 16px; padding: 20px;">
        <div style="margin-bottom: 16px;">
            <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: var(--foreground, #f8fafc);">🍩 Sales Channels Today</h3>
            <span style="font-size: 11px; color: var(--muted-foreground, #94a3b8);">Dine-In vs Takeaway vs Online</span>
        </div>
        <div style="position: relative; height: 220px; width: 100%; display: flex; justify-content: center; align-items: center;">
            <canvas id="channelsDonutChart"></canvas>
        </div>
        <div style="display: flex; justify-content: space-around; font-size: 11px; font-weight: 700; margin-top: 10px; text-align: center;">
            <span style="color: #f59e0b;">🍽️ {{ $dineInCount }} Dine-In</span>
            <span style="color: #10b981;">📦 {{ $takeawayCount }} Takeaway</span>
            <span style="color: #8b5cf6;">🛍️ {{ $onlineCount }} Online</span>
        </div>
    </div>

</div>

<!-- 🚀 4. TWO-COLUMN LIVE OPERATIONS QUEUE -->
<div class="ops-grid">

    <!-- LEFT: LIVE QUEUES -->
    <div class="space-y-4" style="display: grid; gap: 20px;">
        
        <!-- Pending Online Orders Needing Validation -->
        <div class="card" style="background: var(--card, #1e293b); border: 1px solid var(--border, #334155); border-radius: 16px; padding: 20px;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 class="card-title" style="font-size: 15px; font-weight: 800; margin: 0;">🔔 Pending Online Orders</h3>
                <a href="{{ route('admin.orders.online') }}" class="btn btn-sm btn-ghost" style="font-size: 11px; text-decoration: none;">View Dispatcher →</a>
            </div>
            @if($pendingOnlineOrders->count() > 0)
            <div class="table-container" style="overflow-x: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border, #334155); color: var(--muted-foreground);">
                            <th style="padding: 10px 8px;">Ticket #</th>
                            <th style="padding: 10px 8px;">Customer</th>
                            <th style="padding: 10px 8px;">Items</th>
                            <th style="padding: 10px 8px;">Total</th>
                            <th style="padding: 10px 8px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingOnlineOrders as $order)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 10px 8px;"><strong style="color: #f59e0b;">#{{ $order->sequence_number ?? $order->id }}</strong></td>
                            <td style="padding: 10px 8px;">{{ $order->customer_name ?? ($order->client->name ?? 'Web Customer') }}</td>
                            <td style="padding: 10px 8px;">{{ $order->items->count() }} Items</td>
                            <td style="padding: 10px 8px;"><strong>{{ $currencySymbol }}{{ number_format($order->total_incl_vat, 2) }}</strong></td>
                            <td style="padding: 10px 8px; text-align: right;">
                                <a href="{{ route('admin.orders.online') }}" class="btn btn-sm btn-primary" style="font-size: 11px; padding: 4px 10px;">Accept / Reject</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="padding: 20px; text-align: center; color: var(--muted-foreground, #94a3b8); font-size: 13px;">
                ✓ All online orders have been processed!
            </div>
            @endif
        </div>

        <!-- Today's Table Reservations -->
        <div class="card" style="background: var(--card, #1e293b); border: 1px solid var(--border, #334155); border-radius: 16px; padding: 20px;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 class="card-title" style="font-size: 15px; font-weight: 800; margin: 0;">📅 Today's Table Reservations</h3>
                <a href="{{ route('admin.reservations.floor_plan') }}" class="btn btn-sm btn-ghost" style="font-size: 11px; text-decoration: none;">View Floor Plan →</a>
            </div>
            @if($todayReservations->count() > 0)
            <div class="table-container" style="overflow-x: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border, #334155); color: var(--muted-foreground);">
                            <th style="padding: 10px 8px;">Time</th>
                            <th style="padding: 10px 8px;">Customer</th>
                            <th style="padding: 10px 8px;">Guests</th>
                            <th style="padding: 10px 8px;">Table</th>
                            <th style="padding: 10px 8px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($todayReservations as $res)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 10px 8px;"><strong>{{ Carbon\Carbon::parse($res->reservation_time)->format('H:i') }}</strong></td>
                            <td style="padding: 10px 8px;">{{ $res->customer_name }}</td>
                            <td style="padding: 10px 8px;">{{ $res->guest_count }} Guests</td>
                            <td style="padding: 10px 8px;">{{ $res->table->table_number ?? 'Unassigned' }}</td>
                            <td style="padding: 10px 8px;">
                                <span style="font-size: 11px; padding: 3px 8px; border-radius: 6px; font-weight: 700; background: {{ $res->status === 'seated' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(245, 158, 11, 0.2)' }}; color: {{ $res->status === 'seated' ? '#ef4444' : '#f59e0b' }};">
                                    {{ strtoupper($res->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="padding: 20px; text-align: center; color: var(--muted-foreground, #94a3b8); font-size: 13px;">
                No table bookings scheduled for today yet.
            </div>
            @endif
        </div>

    </div>

    <!-- RIGHT: CHANNEL BREAKDOWN & STOCK ALERTS -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        
        <!-- Low Stock Ingredients Alert Card -->
        <div class="card" style="background: var(--card, #1e293b); border: 1px solid var(--border, #334155); border-radius: 16px; padding: 20px;">
            <div class="card-header" style="margin-bottom: 12px;">
                <h3 class="card-title" style="font-size: 15px; font-weight: 800; margin: 0;">🚨 Low Stock Ingredients</h3>
            </div>
            <div>
                @if($lowStockIngredients->count() > 0)
                    <div style="display: grid; gap: 10px;">
                        @foreach($lowStockIngredients as $ing)
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px;">
                                <div>
                                    <strong style="font-size: 13px; color: var(--foreground, #f8fafc);">{{ $ing->name }}</strong>
                                    <span style="display: block; font-size: 11px; color: var(--muted-foreground, #94a3b8);">
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
        <div class="card" style="background: var(--card, #1e293b); border: 1px solid var(--border, #334155); border-radius: 16px; padding: 20px;">
            <div class="card-header" style="margin-bottom: 12px;">
                <h3 class="card-title" style="font-size: 15px; font-weight: 800; margin: 0;">⚙️ Quick Admin Actions</h3>
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

<!-- 🚀 CHART.JS SCRIPT INITIALIZATION -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currency = '{{ $currencySymbol }}';

    // 1. 📈 7-Day Revenue Trend Chart
    const trendCtx = document.getElementById('revenueTrendChart').getContext('2d');
    
    // Create soft gradient fill under the line
    const trendGradient = trendCtx.createLinearGradient(0, 0, 0, 260);
    trendGradient.addColorStop(0, 'rgba(59, 130, 246, 0.35)');
    trendGradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($trendLabels) !!},
            datasets: [{
                label: 'Sales Revenue',
                data: {!! json_encode($trendData) !!},
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
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#94a3b8', font: { size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 11 },
                        callback: function (value) { return currency + value; }
                    }
                }
            }
        }
    });

    // 2. 🍩 Sales Channel Donut Chart
    const channelsCtx = document.getElementById('channelsDonutChart').getContext('2d');
    const channelData = {!! json_encode($channelData) !!};
    const hasData = channelData.some(val => val > 0);

    new Chart(channelsCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($channelLabels) !!},
            datasets: [{
                data: hasData ? channelData : [1, 1, 1],
                backgroundColor: hasData ? ['#f59e0b', '#10b981', '#8b5cf6'] : ['#334155', '#475569', '#64748b'],
                borderWidth: 2,
                borderColor: 'var(--card, #1e293b)',
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