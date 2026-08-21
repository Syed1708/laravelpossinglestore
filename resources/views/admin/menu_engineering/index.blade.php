@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'AI Menu Engineering Matrix')

@section('breadcrumb')
<a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Menu Engineering</span>
@endsection

@section('content')
<style>
    .matrix-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }
    @media (max-width: 1024px) {
        .matrix-grid { grid-template-columns: 1fr; }
    }
    .quadrant-card {
        background: var(--card, #1e293b);
        border: 2px solid var(--border, #334155);
        border-radius: 16px;
        padding: 20px;
    }
    .quadrant-star { border-color: #f59e0b; background: rgba(245, 158, 11, 0.05); }
    .quadrant-plowhorse { border-color: #3b82f6; background: rgba(59, 130, 246, 0.05); }
    .quadrant-puzzle { border-color: #a855f7; background: rgba(168, 85, 247, 0.05); }
    .quadrant-dog { border-color: #ef4444; background: rgba(239, 68, 68, 0.05); }
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">🤖 AI Menu Engineering Matrix</h1>
            <p class="page-description">Automated BCG Profitability vs. Popularity Analysis based on last 30 days sales data.</p>
        </div>
    </div>
</div>

<!-- BCG 4-QUADRANT MATRIX GRID -->
<div class="matrix-grid">

    <!-- 🌟 STARS -->
    <div class="quadrant-card quadrant-star">
        <h3 style="margin-top: 0; color: #f59e0b; font-weight: 900; font-size: 18px;">
            🌟 STARS (High Profit + High Popularity)
        </h3>
        <p style="font-size: 11px; color: var(--muted-foreground); margin-bottom: 12px;">
            These items generate high profit margins and sell in large volumes. <strong>Strategy: Keep &amp; Promote!</strong>
        </p>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Dish</th>
                        <th>Sold</th>
                        <th>Unit Margin</th>
                        <th>Total Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stars as $item)
                        <tr>
                            <td><strong>{{ $item['name'] }}</strong></td>
                            <td><span class="badge badge-warning">{{ $item['quantity_sold'] }}</span></td>
                            <td>{{ $currencySymbol }}{{ number_format($item['unit_margin'], 2) }}</td>
                            <td><strong style="color: #f59e0b;">{{ $currencySymbol }}{{ number_format($item['total_margin'], 2) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align: center; color: var(--muted-foreground);">No items in this category yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 🐴 PLOWHORSES -->
    <div class="quadrant-card quadrant-plowhorse">
        <h3 style="margin-top: 0; color: #3b82f6; font-weight: 900; font-size: 18px;">
            🐴 PLOWHORSES (Low Profit + High Popularity)
        </h3>
        <p style="font-size: 11px; color: var(--muted-foreground); margin-bottom: 12px;">
            High sales volume but low profit margins. <strong>Strategy: Increase price slightly or optimize recipe cost!</strong>
        </p>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Dish</th>
                        <th>Sold</th>
                        <th>Unit Margin</th>
                        <th>Total Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plowhorses as $item)
                        <tr>
                            <td><strong>{{ $item['name'] }}</strong></td>
                            <td><span class="badge badge-primary">{{ $item['quantity_sold'] }}</span></td>
                            <td>{{ $currencySymbol }}{{ number_format($item['unit_margin'], 2) }}</td>
                            <td><strong style="color: #3b82f6;">{{ $currencySymbol }}{{ number_format($item['total_margin'], 2) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align: center; color: var(--muted-foreground);">No items in this category yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 🧩 PUZZLES -->
    <div class="quadrant-card quadrant-puzzle">
        <h3 style="margin-top: 0; color: #a855f7; font-weight: 900; font-size: 18px;">
            🧩 PUZZLES (High Profit + Low Popularity)
        </h3>
        <p style="font-size: 11px; color: var(--muted-foreground); margin-bottom: 12px;">
            High profit margin but low sales volume. <strong>Strategy: Improve menu placement, photos, or staff upsells!</strong>
        </p>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Dish</th>
                        <th>Sold</th>
                        <th>Unit Margin</th>
                        <th>Total Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($puzzles as $item)
                        <tr>
                            <td><strong>{{ $item['name'] }}</strong></td>
                            <td><span class="badge badge-secondary">{{ $item['quantity_sold'] }}</span></td>
                            <td>{{ $currencySymbol }}{{ number_format($item['unit_margin'], 2) }}</td>
                            <td><strong style="color: #a855f7;">{{ $currencySymbol }}{{ number_format($item['total_margin'], 2) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align: center; color: var(--muted-foreground);">No items in this category yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 🐕 DOGS -->
    <div class="quadrant-card quadrant-dog">
        <h3 style="margin-top: 0; color: #ef4444; font-weight: 900; font-size: 18px;">
            🐕 DOGS (Low Profit + Low Popularity)
        </h3>
        <p style="font-size: 11px; color: var(--muted-foreground); margin-bottom: 12px;">
            Low profit margin and low sales volume. <strong>Strategy: Consider replacing or removing from menu!</strong>
        </p>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Dish</th>
                        <th>Sold</th>
                        <th>Unit Margin</th>
                        <th>Total Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dogs as $item)
                        <tr>
                            <td><strong>{{ $item['name'] }}</strong></td>
                            <td><span class="badge badge-danger">{{ $item['quantity_sold'] }}</span></td>
                            <td>{{ $currencySymbol }}{{ number_format($item['unit_margin'], 2) }}</td>
                            <td><strong style="color: #ef4444;">{{ $currencySymbol }}{{ number_format($item['total_margin'], 2) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align: center; color: var(--muted-foreground);">No items in this category yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection