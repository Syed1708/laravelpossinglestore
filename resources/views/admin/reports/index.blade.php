@extends('tyro-dashboard::layouts.app')

@section('title', 'Sales & Financial Reports')

@section('breadcrumb')
<span>Reports (PDF &amp; Analytics)</span>
@endsection

@push('styles')
<style>
    .pos-filter-form .form-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 0;
    }
    
    .pos-filter-form .form-control {
        display: block;
        width: 100%;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: var(--foreground, #1a202c);
        background-color: var(--background, #fff);
        border: 1px solid var(--border, #e2e8f0);
        border-radius: 6px;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        height: 38px;
        box-sizing: border-box;
    }

    .pos-filter-form .form-control:focus {
        border-color: var(--primary, #3182ce);
        outline: 0;
        box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.15);
    }

    .pos-filter-form select.form-control {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.75rem center;
        background-repeat: no-repeat;
        background-size: 1.2em 1.2em;
        padding-right: 2.5rem;
    }

    .pos-filter-form .btn {
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-bottom: 0;
    }

    @media (max-width: 768px) {
        .reports-grid-2 {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 1.5rem;">
    <div>
        <h1 class="page-title">📅 Sales &amp; Financial Reports</h1>
        <p class="page-description">Filter, analyze, and export sales, tax, and accounting reports by date range.</p>
    </div>

    <!-- 🚀 NF525 Daily Z-Closure Trigger (Handled by DailyClosureController::closeDay) -->
    @if($isAdmin)
    <form action="{{ route('admin.closures.close') }}" method="POST" onsubmit="return confirm('⚠️ ATTENTION: Are you sure you want to close the day?\n\nThis will permanently freeze and cryptographically seal all open orders into an NF525 Z-Report, and email a PDF copy to management.');">
        @csrf
        <button type="submit" class="btn" style="background: #dc2626; color: white; border: none; padding: 0.5rem 1.25rem; font-weight: 700; border-radius: 6px; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Seal Daily Z-Report
        </button>
    </form>
    @endif
</div>

<!-- 1. DATE FILTER & REPORT SELECTION CARD -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-body" style="padding: 1.25rem;">
        <form action="{{ route('admin.reports.index') }}" method="GET" class="pos-filter-form" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; width: 100%;">
            
            <!-- Start Date -->
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block; color: var(--foreground);">Start Date</label>
                <input type="date" name="start_date" id="filter_start_date" value="{{ $startDate }}" class="form-control">
            </div>

            <!-- End Date -->
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block; color: var(--foreground);">End Date</label>
                <input type="date" name="end_date" id="filter_end_date" value="{{ $endDate }}" class="form-control">
            </div>

            <!-- Select Report Type Dropdown (State-preserved) -->
            <div class="form-group" style="flex: 1.5; min-width: 220px;">
                <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block; color: var(--foreground);">Report Export Type (PDF)</label>
                <select name="report_type" id="report_type" class="form-control">
                    <option value="p_and_l" {{ request('report_type') === 'p_and_l' ? 'selected' : '' }}>📈 Profit &amp; Loss Statement (P&amp;L)</option>
                    <option value="sales" {{ request('report_type') === 'sales' ? 'selected' : '' }}>📊 Sales &amp; VAT Tax Ledger</option>
                    <option value="purchases" {{ request('report_type') === 'purchases' ? 'selected' : '' }}>📦 Supplier Purchases &amp; Deliveries</option>
                    <option value="expenses" {{ request('report_type') === 'expenses' ? 'selected' : '' }}>💸 Operating Expenses Ledger</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; flex: 0 0 auto;">
                <button type="submit" class="btn btn-primary" style="padding: 0 1.5rem; margin: 0;">
                    🔍 Filter
                </button>
                
                <button type="button" onclick="triggerPdfDownload(event)" class="btn" style="background-color: var(--success, #10b981); border: 1px solid var(--success, #10b981); color: white; padding: 0 1.5rem; margin: 0; gap: 8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download PDF
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 2. TOTALS STATS GRID -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <!-- Gross Sales TTC -->
    <div class="stat-card">
        <div class="stat-icon stat-icon-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Gross Revenue (TTC)</div>
            <div class="stat-value" style="color: var(--primary);">{{ $currencySymbol }}{{ number_format($totals->total_ttc ?? 0, 2) }}</div>
        </div>
    </div>

    <!-- Net Sales HT -->
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(100, 116, 139, 0.1); color: rgb(100, 116, 139);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 00-2 2z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Net Turnover (HT)</div>
            <div class="stat-value" style="color: rgb(100, 116, 139);">{{ $currencySymbol }}{{ number_format($totals->total_ht ?? 0, 2) }}</div>
        </div>
    </div>

    <!-- Total Collected VAT -->
    <div class="stat-card">
        <div class="stat-icon stat-icon-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Collected VAT (TVA)</div>
            <div class="stat-value" style="color: var(--success, #10b981);">{{ $currencySymbol }}{{ number_format($totals->total_tva ?? 0, 2) }}</div>
        </div>
    </div>
</div>

<!-- 3. SPLIT ROW 1: Payment Methods & VAT Breakdown -->
<div class="grid-2 reports-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 2rem;">
    <!-- Card Left: Payment Methods -->
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid var(--border); padding: 1rem 1.25rem;">
            <h3 class="card-title" style="margin: 0; font-size: 1.1rem; font-weight: bold;">💳 Payment Methods Breakdown</h3>
        </div>
        <div class="card-body" style="padding: 1.25rem;">
            @if($payments->isEmpty())
                <p style="color: var(--muted-foreground); text-align: center; padding: 1.5rem 0;">No completed transactions for this period.</p>
            @else
                @foreach($payments as $payment)
                    @php
                        $methodLabel = match($payment->method) {
                            'cash'            => '💵 Cash (Till)',
                            'card'            => '💳 Card (Terminal)',
                            'stripe_checkout' => '🌐 Stripe (Online)',
                            'card_terminal'   => '📱 Card (Kiosk Terminal)',
                            'split'           => '⚖️ Split Payment',
                            'bank_transfer'   => '🏦 Bank Transfer',
                            default           => '🎟️ ' . ucfirst($payment->method),
                        };
                    @endphp
                    <div style="display: flex; justify-content: space-between; align-items: center; background: var(--muted); padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 0.75rem;">
                        <span style="font-weight: 600; color: var(--foreground);">{{ $methodLabel }}</span>
                        <strong style="font-size: 1.05rem; color: var(--foreground);">{{ $currencySymbol }}{{ number_format($payment->total, 2) }}</strong>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Card Right: VAT Bracket Breakdown -->
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid var(--border); padding: 1rem 1.25rem;">
            <h3 class="card-title" style="margin: 0; font-size: 1.1rem; font-weight: bold;">⚖️ French VAT (TVA) Tax Breakdown</h3>
        </div>
        <div class="card-body" style="padding: 1.25rem;">
            @if($vatBreakdown->isEmpty())
                <p style="color: var(--muted-foreground); text-align: center; padding: 1.5rem 0;">No product sales recorded for this period.</p>
            @else
                @foreach($vatBreakdown as $bracket)
                    <div style="display: flex; justify-content: space-between; align-items: center; border-left: 4px solid var(--success, #10b981); background: var(--muted); padding: 0.75rem 1rem; border-radius: 0 8px 8px 0; margin-bottom: 0.75rem; padding-left: 1.25rem;">
                        <div>
                            <strong style="font-size: 1rem; display: block; color: var(--foreground);">VAT Rate {{ number_format($bracket->vat_rate, 1) }}%</strong>
                            <span style="font-size: 0.75rem; color: var(--muted-foreground);">Gross (TTC): {{ $currencySymbol }}{{ number_format($bracket->total_ttc, 2) }}</span>
                        </div>
                        <strong style="color: var(--success, #10b981); font-size: 1.05rem;">+{{ $currencySymbol }}{{ number_format($bracket->collected_vat, 2) }}</strong>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

<!-- 4. SPLIT ROW 2: Top Selling Products & Purchases / Expenses -->
<div class="grid-2 reports-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 2rem;">
    <!-- Top 15 Best Selling Products -->
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid var(--border); padding: 1rem 1.25rem;">
            <h3 class="card-title" style="margin: 0; font-size: 1.1rem; font-weight: bold;">🏆 Top Selling Items (Volume &amp; Turnover)</h3>
        </div>
        <div class="card-body" style="padding: 1.25rem; max-height: 380px; overflow-y: auto;">
            @if($topProducts->isEmpty())
                <p style="color: var(--muted-foreground); text-align: center; padding: 1.5rem 0;">No sales data available.</p>
            @else
                @foreach($topProducts as $idx => $prod)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0; border-bottom: 1px solid var(--border);">
                        <div>
                            <span style="font-weight: bold; color: var(--foreground);">#{{ $idx + 1 }} {{ $prod->product_name }}</span>
                            <span style="display: block; font-size: 0.75rem; color: var(--muted-foreground);">Qty Sold: {{ $prod->qty_sold }} units</span>
                        </div>
                        <strong style="color: var(--primary);">{{ $currencySymbol }}{{ number_format($prod->total_ttc, 2) }}</strong>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Operating Costs & Purchases Summary -->
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid var(--border); padding: 1rem 1.25rem;">
            <h3 class="card-title" style="margin: 0; font-size: 1.1rem; font-weight: bold;">📦 Cost Overview (Deliveries &amp; Overheads)</h3>
        </div>
        <div class="card-body" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; background: var(--muted); padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <div>
                    <span style="font-weight: bold; display: block; color: var(--foreground);">📦 Received Supplier Purchases</span>
                    <span style="font-size: 0.75rem; color: var(--muted-foreground);">{{ $purchasesList->count() }} Delivery Invoices</span>
                </div>
                <strong style="color: #ef4444; font-size: 1.05rem;">-{{ $currencySymbol }}{{ number_format($totalPurchasesCost, 2) }}</strong>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; background: var(--muted); padding: 0.75rem 1rem; border-radius: 8px;">
                <div>
                    <span style="font-weight: bold; display: block; color: var(--foreground);">💸 Operating &amp; Staff Expenses</span>
                    <span style="font-size: 0.75rem; color: var(--muted-foreground);">{{ $expensesList->count() }} Recorded Expenses</span>
                </div>
                <strong style="color: #ef4444; font-size: 1.05rem;">-{{ $currencySymbol }}{{ number_format($totalExpensesCost, 2) }}</strong>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic PDF Export JS -->
<script>
function triggerPdfDownload(event) {
    event.preventDefault();
    const reportType = document.getElementById('report_type').value;
    const startDate  = document.getElementById('filter_start_date').value;
    const endDate    = document.getElementById('filter_end_date').value;

    const url = "{{ route('admin.reports.pdf') }}?start_date=" + encodeURIComponent(startDate) + "&end_date=" + encodeURIComponent(endDate) + "&report_type=" + encodeURIComponent(reportType);
    window.location.href = url;
}
</script>
@endsection