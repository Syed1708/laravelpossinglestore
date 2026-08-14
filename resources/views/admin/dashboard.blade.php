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
    .ops-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 20px;
    }
    @media (max-width: 1024px) {
        .ops-grid { grid-template-columns: 1fr; }
    }
</style>


<!-- PAGE HEADER -->
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">👋 Welcome Back, {{ auth()->user()->name }}!</h1>
            <p class="page-description">Here is your live command center for {{ Carbon\Carbon::now('Europe/Paris')->format('l, M d, Y') }}.</p>
        </div>
        <div>
            @if(App\Helpers\StoreHoursHelper::isOpen())
                <span class="badge badge-success" style="font-size: 13px; padding: 8px 16px;">🟢 Store Open (Shift Active)</span>
            @else
                <span class="badge badge-danger" style="font-size: 13px; padding: 8px 16px;">🔴 Store Closed</span>
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
        <a href="{{ route('admin.reports.pnl') }}" style="font-size: 11px; color: var(--primary); font-weight: bold; text-decoration: none;">View P&amp;L Financials →</a>
    </div>

    <div class="kpi-card" style="border-color: {{ $lowStockIngredients->count() > 0 ? '#ef4444' : 'var(--border)' }};">
        <div class="kpi-title">Low Stock Warnings</div>
        <div class="kpi-value" style="color: {{ $lowStockIngredients->count() > 0 ? '#ef4444' : '#10b981' }};">
            {{ $lowStockIngredients->count() }}
        </div>
        <span style="font-size: 11px; color: var(--muted-foreground);">Ingredients Below Alert Level</span>
    </div>
</div>

<!-- 🚀 3. TWO-COLUMN LIVE OPERATIONS QUEUE -->
<div class="ops-grid">

    <!-- LEFT: LIVE QUEUES -->
    <div class="space-y-4" style="display: grid; gap: 20px;">
        
        <!-- Pending Online Orders Needing Validation -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">🔔 Pending Online Orders (Requires Acceptance)</h3>
                <a href="{{ route('admin.orders.online') }}" class="btn btn-sm btn-ghost" style="font-size: 11px;">View Dispatcher →</a>
            </div>
            @if($pendingOnlineOrders->count() > 0)
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingOnlineOrders as $order)
                        <tr>
                            <td><strong style="color: var(--warning);">#{{ $order->sequence_number || $order->id }}</strong></td>
                            <td>{{ $order->customer_name ?? ($order->client->name ?? 'Web Customer') }}</td>
                            <td>{{ $order->items->count() }} Items</td>
                            <td><strong>{{ $currencySymbol }}{{ number_format($order->total_incl_vat, 2) }}</strong></td>
                            <td style="text-align: right;">
                                <a href="{{ route('admin.orders.online') }}" class="btn btn-sm btn-primary">Accept / Reject</a>
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

        <!-- Today's Table Reservations -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">📅 Today's Table Reservations</h3>
                <a href="{{ route('admin.reservations.floor_plan') }}" class="btn btn-sm btn-ghost" style="font-size: 11px;">View Floor Plan →</a>
            </div>
            @if($todayReservations->count() > 0)
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Guests</th>
                            <th>Table</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($todayReservations as $res)
                        <tr>
                            <td><strong>{{ Carbon\Carbon::parse($res->reservation_time)->format('H:i') }}</strong></td>
                            <td>{{ $res->customer_name }}</td>
                            <td>{{ $res->guest_count }} Guests</td>
                            <td>{{ $res->table->table_number ?? 'Unassigned' }}</td>
                            <td>
                                <span class="badge {{ $res->status === 'seated' ? 'badge-danger' : 'badge-warning' }}">
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
                No table bookings scheduled for today yet.
            </div>
            @endif
        </div>

    </div>

    <!-- RIGHT: CHANNEL BREAKDOWN & STOCK ALERTS -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        
        <!-- Low Stock Ingredients Alert Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🚨 Low Stock Ingredients</h3>
            </div>
            <div class="card-body">
                @if($lowStockIngredients->count() > 0)
                    <div style="display: grid; gap: 10px;">
                        @foreach($lowStockIngredients as $ing)
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px;">
                                <div>
                                    <strong style="font-size: 13px; color: var(--foreground);">{{ $ing->name }}</strong>
                                    <span style="display: block; font-size: 11px; color: var(--muted-foreground);">
                                        Threshold: {{ App\Helpers\UnitConverter::formatForDisplay($ing->alert_level, $ing->unit) }}
                                    </span>
                                </div>
                                <span style="font-weight: 900; color: #ef4444; font-size: 13px;">
                                    {{ App\Helpers\UnitConverter::formatForDisplay($ing->stock_level, $ing->unit) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary" style="width: 100%; margin-top: 14px; text-align: center; font-size: 12px;">
                        ➕ Order Stock from Supplier
                    </a>
                @else
                    <div style="text-align: center; color: var(--success); font-weight: bold; padding: 10px 0; font-size: 13px;">
                        ✅ All ingredient stocks are above alert thresholds!
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Settings & Admin Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">⚙️ Quick Admin Actions</h3>
            </div>
            <div class="card-body" style="display: grid; gap: 10px;">
                <a href="{{ route('admin.settings.general') }}" class="btn btn-ghost" style="justify-content: flex-start;">
                    ⚙️ General &amp; Store Operating Hours
                </a>
                <a href="{{ route('admin.settings.homepage') }}" class="btn btn-ghost" style="justify-content: flex-start;">
                    🏠 Homepage Banner &amp; Content
                </a>
                <a href="{{ route('admin.settings.theme') }}" class="btn btn-ghost" style="justify-content: flex-start;">
                    🎨 Web Theme &amp; Branding Colors
                </a>
            </div>
        </div>

    </div>

</div>
@endsection