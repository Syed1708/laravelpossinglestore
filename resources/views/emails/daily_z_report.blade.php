<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #0f172a; color: #f8fafc; padding: 20px; margin: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #1e293b; border-radius: 16px; border: 1px solid #334155; padding: 30px; }
        .header { text-align: center; border-bottom: 2px solid #334155; padding-bottom: 20px; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: 900; color: #f59e0b; margin: 0; }
        .subtitle { font-size: 13px; color: #94a3b8; margin-top: 5px; }
        .badge { display: inline-block; background-color: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid #10b981; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .grid { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px; }
        .grid span { color: #94a3b8; }
        .grid strong { color: #f8fafc; }
        .total-box { background-color: #090d16; border: 1px solid #f59e0b; border-radius: 12px; padding: 15px; text-align: center; margin: 20px 0; }
        .total-box span { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 700; display: block; }
        .total-box font { font-size: 28px; font-weight: 900; color: #f59e0b; }
        .section-title { font-size: 13px; font-weight: 800; text-transform: uppercase; color: #f59e0b; border-bottom: 1px solid #334155; padding-bottom: 6px; margin-top: 20px; margin-bottom: 10px; }
        .hash-box { background-color: #090d16; border: 1px solid #334155; border-radius: 8px; padding: 10px; font-family: monospace; font-size: 10px; color: #94a3b8; word-break: break-all; margin-top: 15px; }
        .footer { text-align: center; font-size: 11px; color: #64748b; margin-top: 25px; border-top: 1px solid #334155; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="badge">NF525 Certified Shift Closing</span>
            <h1 class="title" style="margin-top: 10px;">Burger Palace Bordeaux</h1>
            <p class="subtitle">Daily Z-Report #{{ $closure->z_number }} &bull; {{ $closure->closed_at->format('d/m/Y H:i') }}</p>
        </div>

        <div class="total-box">
            <span>Total Gross Revenue (TTC)</span>
            <font>€{{ number_format($closure->total_ttc, 2) }}</font>
        </div>

        <div class="section-title">Consolidated Financials</div>
        <div class="grid">
            <span>Net Sales Revenue (HT):</span>
            <strong>€{{ number_format($closure->total_ht, 2) }}</strong>
        </div>
        <div class="grid">
            <span>Total Value Added Tax (TVA):</span>
            <strong>€{{ number_format($closure->total_tva, 2) }}</strong>
        </div>

        <div class="section-title">Payments Breakdown</div>
        @foreach($closure->payments_breakdown ?? [] as $method => $amount)
            <div class="grid">
                <span style="text-transform: capitalize;">• Payment Method ({{ $method }}):</span>
                <strong>€{{ number_format((float) $amount, 2) }}</strong>
            </div>
        @endforeach

        <div class="section-title">VAT (TVA) Brackets Breakdown</div>
        @foreach($closure->vat_breakdown ?? [] as $rate => $data)
            <div class="grid">
                <span>• Bracket {{ $rate }}%:</span>
                <strong>€{{ number_format($data['ttc'] ?? 0, 2) }} (TVA: €{{ number_format($data['vat'] ?? 0, 2) }})</strong>
            </div>
        @endforeach

        <div class="hash-box">
            <strong>NF525 SHA-256 Cryptographic Hash:</strong><br>
            {{ $closure->hash }}
        </div>

        <div class="footer">
            <p>Generated automatically by Burger Palace POS Core.<br>A complete PDF copy is attached to this email for your accounting records.</p>
        </div>
    </div>
</body>
</html>