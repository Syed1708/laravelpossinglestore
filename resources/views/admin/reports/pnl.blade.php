@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Executive P&L Financial Report')

@section('breadcrumb')
<a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Executive P&amp;L Financials</span>
@endsection

@section('content')
<style>
    .pnl-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .pnl-card {
        background: var(--card, #1e293b);
        border: 1px solid var(--border, #334155);
        border-radius: 16px;
        padding: 20px;
    }

    .pnl-card-title {
        font-size: 11px;
        font-weight: 800;
        color: var(--muted-foreground, #94a3b8);
        text-transform: uppercase;
    }

    .pnl-card-value {
        font-size: 24px;
        font-weight: 900;
        margin-top: 6px;
    }

    .pnl-statement-table {
        width: 100%;
        border-collapse: collapse;
    }

    .pnl-statement-table td, .pnl-statement-table th {
        padding: 14px;
        border-bottom: 1px solid var(--border, #334155);
    }

    .row-highlight {
        background: rgba(245, 158, 11, 0.05);
        font-weight: 900;
    }

    .row-profit {
        background: rgba(16, 185, 129, 0.1);
        font-size: 18px;
        font-weight: 900;
    }
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">📈 Executive P&amp;L Financial Statement</h1>
            <p class="page-description">Consolidated profit and loss statement for {{ Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ Carbon\Carbon::parse($endDate)->format('M d, Y') }}.</p>
        </div>
    </div>
</div>

<!-- Date Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body">
        <form action="{{ route('admin.reports.pnl') }}" method="GET" class="filters-bar">
            <div class="filter-group">
                <label class="filter-label">From Date:</label>
                <input type="date" name="start_date" class="form-input" value="{{ $startDate }}">
            </div>
            <div class="filter-group">
                <label class="filter-label">To Date:</label>
                <input type="date" name="end_date" class="form-input" value="{{ $endDate }}">
            </div>
            <button type="submit" class="btn btn-primary">Generate P&amp;L</button>
            <a href="{{ route('admin.reports.pnl') }}" class="btn btn-ghost">This Month</a>
        </form>
    </div>
</div>

<!-- TOP STATS CARDS -->
<div class="pnl-grid">
    <div class="pnl-card">
        <div class="pnl-card-title">Gross Sales (HT)</div>
        <div class="pnl-card-value" style="color: #3b82f6;">{{ $currencySymbol }}{{ number_format($totalSalesHt, 2) }}</div>
        <span style="font-size: 11px; color: var(--muted-foreground);">{{ $orderCount }} Orders Processed</span>
    </div>

    <div class="pnl-card">
        <div class="pnl-card-title">Food &amp; Ingredient Costs</div>
        <div class="pnl-card-value" style="color: #ef4444;">-{{ $currencySymbol }}{{ number_format($foodCosts, 2) }}</div>
        <span style="font-size: 11px; color: var(--muted-foreground);">Supplier Purchases</span>
    </div>

    <div class="pnl-card">
        <div class="pnl-card-title">Food Waste Losses</div>
        <div class="pnl-card-value" style="color: #f59e0b;">-{{ $currencySymbol }}{{ number_format($wasteLoss, 2) }}</div>
        <span style="font-size: 11px; color: var(--muted-foreground);">Spoiled &amp; Kitchen Errors</span>
    </div>

    <div class="pnl-card">
        <div class="pnl-card-title">Labor &amp; Payroll Cost</div>
        <div class="pnl-card-value" style="color: #a855f7;">-{{ $currencySymbol }}{{ number_format($salaries, 2) }}</div>
        <span style="font-size: 11px; color: var(--muted-foreground);">Salaries + Employer Charges</span>
    </div>

    <div class="pnl-card" style="border-color: {{ $netProfit >= 0 ? '#10b981' : '#ef4444' }};">
        <div class="pnl-card-title">Net Operating Profit</div>
        <div class="pnl-card-value" style="color: {{ $netProfit >= 0 ? '#10b981' : '#ef4444' }};">
            {{ $currencySymbol }}{{ number_format($netProfit, 2) }}
        </div>
        <span style="font-size: 11px; font-weight: bold; color: {{ $netProfit >= 0 ? '#10b981' : '#ef4444' }};">
            Margin: {{ $profitMargin }}%
        </span>
    </div>
</div>

<!-- DETAILED P&L STATEMENT TABLE -->
<div class="card">
    <div class="card-body" style="padding: 0;">
        <table class="pnl-statement-table">
            <thead>
                <tr>
                    <th style="text-align: left;">Financial Line Item</th>
                    <th style="text-align: right;">Amount ({{ $currencySymbol }})</th>
                    <th style="text-align: right;">% of Net Revenue</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Gross Sales Revenue (Total TTC)</strong></td>
                    <td style="text-align: right;"><strong>{{ $currencySymbol }}{{ number_format($totalSalesTtc, 2) }}</strong></td>
                    <td style="text-align: right;">-</td>
                </tr>
                <tr>
                    <td style="color: var(--muted-foreground);">Less: VAT Collected (TVA)</td>
                    <td style="text-align: right; color: var(--muted-foreground);">-{{ $currencySymbol }}{{ number_format($totalVat, 2) }}</td>
                    <td style="text-align: right; color: var(--muted-foreground);">-</td>
                </tr>
                <tr class="row-highlight">
                    <td><strong>NET SALES REVENUE (HT)</strong></td>
                    <td style="text-align: right; color: #3b82f6;"><strong>{{ $currencySymbol }}{{ number_format($totalSalesHt, 2) }}</strong></td>
                    <td style="text-align: right; color: #3b82f6;"><strong>100.0%</strong></td>
                </tr>
                <tr>
                    <td>Food Costs (Supplier Deliveries)</td>
                    <td style="text-align: right; color: #ef4444;">-{{ $currencySymbol }}{{ number_format($foodCosts, 2) }}</td>
                    <td style="text-align: right; color: #ef4444;">{{ $totalSalesHt > 0 ? round(($foodCosts / $totalSalesHt) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td>Food Waste &amp; Inventory Loss</td>
                    <td style="text-align: right; color: #f59e0b;">-{{ $currencySymbol }}{{ number_format($wasteLoss, 2) }}</td>
                    <td style="text-align: right; color: #f59e0b;">{{ $totalSalesHt > 0 ? round(($wasteLoss / $totalSalesHt) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td>Labor Costs (Salaries &amp; Employer Charges)</td>
                    <td style="text-align: right; color: #a855f7;">-{{ $currencySymbol }}{{ number_format($salaries, 2) }}</td>
                    <td style="text-align: right; color: #a855f7;">{{ $totalSalesHt > 0 ? round(($salaries / $totalSalesHt) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td>Operating Overhead (Rent, Utilities, Marketing, Misc)</td>
                    <td style="text-align: right; color: var(--muted-foreground);">-{{ $currencySymbol }}{{ number_format($otherOperatingExpenses, 2) }}</td>
                    <td style="text-align: right; color: var(--muted-foreground);">{{ $totalSalesHt > 0 ? round(($otherOperatingExpenses / $totalSalesHt) * 100, 1) : 0 }}%</td>
                </tr>
                <tr class="row-profit">
                    <td><strong>NET OPERATING PROFIT / (LOSS)</strong></td>
                    <td style="text-align: right; color: {{ $netProfit >= 0 ? '#10b981' : '#ef4444' }};">
                        <strong>{{ $currencySymbol }}{{ number_format($netProfit, 2) }}</strong>
                    </td>
                    <td style="text-align: right; color: {{ $netProfit >= 0 ? '#10b981' : '#ef4444' }};">
                        <strong>{{ $profitMargin }}%</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection