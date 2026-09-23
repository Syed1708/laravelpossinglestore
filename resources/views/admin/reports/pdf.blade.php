<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $settings->hero_title ?? 'Burger Palace' }} — {{ ucfirst($reportType) }} Report</title>
    <style>
        @page {
            margin: 28px 32px 35px 32px;
        }

        body {
            font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
            color: #1e293b;
            line-height: 1.45;
            font-size: 11px;
            background: #ffffff;
        }

        /* 2-COLUMN HEADER TABLE */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
            border: none;
        }

        .brand-logo {
            max-height: 48px;
            max-width: 180px;
            margin-bottom: 6px;
        }

        .brand-crest {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 14px;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 4px;
            display: inline-block;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .brand-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0 0 4px 0;
        }

        .store-meta {
            font-size: 10px;
            color: #64748b;
            line-height: 1.4;
        }

        .report-badge {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 4px;
            display: inline-block;
            letter-spacing: 0.8px;
        }

        .report-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 5px;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .period-text {
            font-size: 10px;
            color: #475569;
            margin-top: 2px;
        }

        /* SECTION HEADERS */
        .section-header {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background-color: #f1f5f9;
            border-left: 3px solid #3b82f6;
            padding: 5px 8px;
            margin-top: 16px;
            margin-bottom: 8px;
        }

        /* DATA TABLES */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        table.data-table th, 
        table.data-table td {
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }

        table.data-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            border-bottom: 1.5px solid #cbd5e1;
        }

        table.data-table tr:nth-child(even) td {
            background-color: #fcfdfe;
        }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-bold { font-weight: bold; }
        
        .color-green { color: #059669; }
        .color-red { color: #dc2626; }
        .color-blue { color: #2563eb; }
        .color-muted { color: #64748b; }

        /* EXECUTIVE TOTALS CARD */
        .summary-card {
            width: 100%;
            border-collapse: collapse;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-top: 14px;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .summary-card td {
            padding: 10px 14px;
            vertical-align: middle;
            border: none;
        }

        .summary-label {
            font-size: 11px;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-val {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            color: #2563eb;
        }

        .badge {
            font-size: 9px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
            background: #e2e8f0;
            color: #334155;
            display: inline-block;
        }

        .empty-text {
            color: #94a3b8;
            text-align: center;
            padding: 16px 0;
            font-style: italic;
        }

        /* COMPLIANCE FOOTER */
        .footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    @php
        // 🚀 1. DIRECT STORAGE FILE LOADER (Bypasses symlinks and web permissions)
        $logoBase64 = null;
        if (!empty($settings->logo_path)) {
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            if ($disk->exists($settings->logo_path)) {
                $realPath = $disk->path($settings->logo_path);
                $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
                $mime = match($ext) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    default => 'image/png'
                };
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($realPath));
            }
        }

        // 🚀 2. Dynamic Country Tax Title
        $taxTitle = match(strtoupper($settings->country ?? 'FR')) {
            'UK', 'GB' => 'UK VAT Tax Ledger',
            'BD', 'BANGLADESH' => 'Bangladesh VAT & Tax Ledger',
            default => 'French VAT (TVA) Tax Ledger',
        };

        // 🚀 3. Report Name Label
        $reportTypeLabel = match($reportType) {
            'sales'     => 'Sales & Tax Activity Ledger',
            'purchases' => 'Supplier Purchases & Deliveries (COGS)',
            'expenses'  => 'Operating Expenses Ledger (OPEX)',
            default     => 'Executive Profit & Loss Statement (P&L)',
        };
    @endphp

    <!-- ==========================================
         CORPORATE LETTERHEAD HEADER
         ========================================== -->
    <table class="header-table">
        <tr>
            <!-- Left: Logo & Store Contact Details -->
            <td style="width: 55%;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="brand-logo" alt="Logo"><br>
                @else
                    <div class="brand-crest">{{ $settings->hero_title ?? 'BURGER PALACE' }}</div><br>
                @endif
                <div class="store-meta">
                    @if(!empty($settings->contact_address))
                        Address: {{ $settings->contact_address }}<br>
                    @endif
                    @if(!empty($settings->contact_phone))
                        Tel: {{ $settings->contact_phone }}
                    @endif
                    @if(!empty($settings->contact_phone) && !empty($settings->contact_email))
                        &nbsp;|&nbsp;
                    @endif
                    @if(!empty($settings->contact_email))
                        Email: {{ $settings->contact_email }}
                    @endif
                </div>
            </td>

            <!-- Right: Report Title, Reference & Date Range -->
            <td style="width: 45%; text-align: right;">
                <span class="report-badge">Official Financial Report</span>
                <div class="report-title">{{ $reportTypeLabel }}</div>
                <div class="period-text">
                    Period: <strong>{{ $startDate }}</strong> to <strong>{{ $endDate }}</strong>
                </div>
                <div class="period-text" style="color: #94a3b8; font-size: 9px; margin-top: 4px;">
                    Generated: {{ App\Helpers\StoreHoursHelper::now()->format('d/m/Y H:i') }} ({{ App\Models\StoreSetting::timezone() }})
                </div>
            </td>
        </tr>
    </table>

    <!-- ==========================================
         1. SALES & VAT REPORT
         ========================================== -->
    @if($reportType === 'sales')
        <div class="section-header">Sales Activity Summary</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Completed Customer Tickets</td>
                    <td class="text-right font-bold">{{ number_format($totals->total_orders ?? 0) }} Orders</td>
                </tr>
                <tr>
                    <td>Gross Revenue (Incl. VAT / TTC)</td>
                    <td class="text-right font-bold color-blue">{{ $currencySymbol }}{{ number_format($totals->total_ttc ?? 0, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td>Net Revenue (Excl. VAT / HT)</td>
                    <td class="text-right font-bold">{{ $currencySymbol }}{{ number_format($totals->total_ht ?? 0, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td>Total VAT / Tax Collected</td>
                    <td class="text-right font-bold color-green">+{{ $currencySymbol }}{{ number_format($totals->total_tva ?? 0, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-header">{{ $taxTitle }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tax Bracket</th>
                    <th class="text-right">Gross Total (TTC)</th>
                    <th class="text-right">Tax Collected</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vatBreakdown as $bracket)
                    <tr>
                        <td><strong>Rate {{ number_format($bracket->vat_rate, 1) }}%</strong></td>
                        <td class="text-right">{{ $currencySymbol }}{{ number_format($bracket->total_ttc, 2, '.', ',') }}</td>
                        <td class="text-right font-bold color-green">+{{ $currencySymbol }}{{ number_format($bracket->collected_vat, 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="empty-text">No product sales recorded for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-header">Payment Methods Breakdown</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th class="text-right">Total Settled</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    @php
                        $methodLabel = match($payment->method) {
                            'cash'            => 'Cash (Till)',
                            'card'            => 'Card (POS Terminal)',
                            'stripe_checkout' => 'Stripe (Online / Web)',
                            'card_terminal'   => 'Card (Kiosk Terminal)',
                            'split'           => 'Split Tender',
                            'bank_transfer'   => 'Bank Transfer',
                            default           => ucfirst(str_replace('_', ' ', $payment->method)),
                        };
                    @endphp
                    <tr>
                        <td><strong>{{ $methodLabel }}</strong></td>
                        <td class="text-right font-bold">{{ $currencySymbol }}{{ number_format($payment->total, 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="empty-text">No payment transactions recorded for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-header">Top Selling Items (Volume &amp; Turnover)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>Product Name</th>
                    <th class="text-right">Quantity Sold</th>
                    <th class="text-right">Gross Total (TTC)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topProducts as $idx => $item)
                    <tr>
                        <td class="color-muted">#{{ $idx + 1 }}</td>
                        <td><strong>{{ $item->product_name }}</strong></td>
                        <td class="text-right">{{ number_format($item->qty_sold) }} units</td>
                        <td class="text-right font-bold">{{ $currencySymbol }}{{ number_format($item->total_ttc, 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-text">No product sales recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="summary-card">
            <tr>
                <td class="summary-label">Total Gross Sales (TTC):</td>
                <td class="summary-val">{{ $currencySymbol }}{{ number_format($totals->total_ttc ?? 0, 2, '.', ',') }}</td>
            </tr>
        </table>

    <!-- ==========================================
         2. PURCHASES & INVENTORY DELIVERIES (COGS)
         ========================================== -->
    @elseif($reportType === 'purchases')
        <div class="section-header">Received Supplier Deliveries (COGS Audit)</div>
        @if($purchasesList->isEmpty())
            <div class="empty-text">No supplier delivery invoices recorded for this period.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Delivery Date</th>
                        <th>PO #</th>
                        <th>Supplier</th>
                        <th>Invoice #</th>
                        <th class="text-right">Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchasesList as $po)
                        <tr>
                            <td>{{ $po->received_at ? $po->received_at->format('d/m/Y') : '—' }}</td>
                            <td><strong>PO #{{ $po->po_number }}</strong></td>
                            <td>{{ $po->supplier->name ?? 'N/A' }}</td>
                            <td><span class="badge">{{ $po->invoice_number }}</span></td>
                            <td class="text-right font-bold color-red">-{{ $currencySymbol }}{{ number_format($po->total_cost, 2, '.', ',') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <table class="summary-card">
            <tr>
                <td class="summary-label">Total Inventory &amp; Raw Material Cost (COGS):</td>
                <td class="summary-val color-red">-{{ $currencySymbol }}{{ number_format($totalPurchasesCost, 2, '.', ',') }}</td>
            </tr>
        </table>

    <!-- ==========================================
         3. OPERATING EXPENSES (OPEX)
         ========================================== -->
    @elseif($reportType === 'expenses')
        <div class="section-header">Operating Expenses Ledger (OPEX)</div>
        @if($expensesList->isEmpty())
            <div class="empty-text">No operating expenses recorded for this period.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Payment Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Method</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expensesList as $exp)
                        <tr>
                            <td>{{ ($exp->paid_at ?? $exp->created_at)->format('d/m/Y') }}</td>
                            <td><span class="badge">{{ $exp->expenseCategory->name ?? ucfirst($exp->category) }}</span></td>
                            <td>{{ $exp->description }}</td>
                            <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $exp->payment_method ?? 'N/A') }}</td>
                            <td class="text-right font-bold color-red">-{{ $currencySymbol }}{{ number_format($exp->amount, 2, '.', ',') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <table class="summary-card">
            <tr>
                <td class="summary-label">Total Operating Expenses (OPEX):</td>
                <td class="summary-val color-red">-{{ $currencySymbol }}{{ number_format($totalExpensesCost, 2, '.', ',') }}</td>
            </tr>
        </table>

    <!-- ==========================================
         4. EXECUTIVE P&L STATEMENT (DEFAULT)
         ========================================== -->
    @else
        <div class="section-header">Operating Revenue Summary</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Component</th>
                    <th class="text-right">Net (Excl. Tax)</th>
                    <th class="text-right">Tax (VAT)</th>
                    <th class="text-right">Gross (Incl. Tax)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Gross Operating Sales Revenue [A]</strong></td>
                    <td class="text-right">{{ $currencySymbol }}{{ number_format($totals->total_ht ?? 0, 2, '.', ',') }}</td>
                    <td class="text-right">{{ $currencySymbol }}{{ number_format($totals->total_tva ?? 0, 2, '.', ',') }}</td>
                    <td class="text-right font-bold color-blue">{{ $currencySymbol }}{{ number_format($totals->total_ttc ?? 0, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-header">Income Statement Summary (Net Basis)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Line Item</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Net Sales Revenue (Excl. Tax) [A]:</strong></td>
                    <td class="text-right font-bold">{{ $currencySymbol }}{{ number_format($totals->total_ht ?? 0, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td><strong class="color-red">Cost of Goods Sold (COGS - Deliveries) [B]:</strong></td>
                    <td class="text-right font-bold color-red">-{{ $currencySymbol }}{{ number_format($totalPurchasesCost, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td><strong class="color-red">Operating Overheads &amp; Salaries (OPEX) [C]:</strong></td>
                    <td class="text-right font-bold color-red">-{{ $currencySymbol }}{{ number_format($totalExpensesCost, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>

        @php
            $netProfit = ($totals->total_ht ?? 0) - ($totalPurchasesCost + $totalExpensesCost);
            $marginPct = ($totals->total_ht ?? 0) > 0 ? round(($netProfit / $totals->total_ht) * 100, 1) : 0;
            $isPositive = $netProfit >= 0;
        @endphp

        <table class="summary-card" style="border-left: 5px solid {{ $isPositive ? '#059669' : '#dc2626' }};">
            <tr>
                <td>
                    <div class="summary-label">Estimated Net Operating Profit (A - B - C):</div>
                    <div style="font-size: 10px; color: #64748b; margin-top: 2px;">
                        Net Margin: <strong>{{ $marginPct }}%</strong>
                    </div>
                </td>
                <td class="summary-val" style="color: {{ $isPositive ? '#059669' : '#dc2626' }};">
                    {{ $isPositive ? '+' : '-' }}{{ $currencySymbol }}{{ number_format(abs($netProfit), 2, '.', ',') }}
                </td>
            </tr>
        </table>
    @endif

    <!-- ==========================================
         LEGAL & FISCAL COMPLIANCE FOOTER
         ========================================== -->
    <div class="footer">
        Document generated on {{ App\Helpers\StoreHoursHelper::now()->format('d/m/Y H:i:s') }} ({{ App\Models\StoreSetting::timezone() }}) by {{ $settings->hero_title ?? 'Burger Palace' }} POS.<br>
        Certified compliant in accordance with fiscal integrity standards (Art. 286-I-3° bis of the CGI / NF525). Page 1 of 1
    </div>

</body>
</html>