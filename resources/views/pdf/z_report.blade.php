<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Z-Report #{{ $closure->z_number }}</title>
    <style>
        body { font-family: monospace; font-size: 11px; line-height: 1.4; color: #000; margin: 0; padding: 10px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .large { font-size: 16px; font-weight: bold; }
        .divider { border-bottom: 1px dashed #000; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; clear: both; }
        .left { float: left; }
        .right { float: right; text-align: right; }
        .clear { clear: both; }
        .hash { font-size: 8px; word-break: break-all; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="center">
        <div class="large">BURGER PALACE</div>
        <div>12 Rue Sainte-Catherine, 33000 Bordeaux</div>
        <div>SIRET: 892 143 567 00012</div>
        <div class="divider"></div>
        <div class="bold">DAILY Z-REPORT #{{ $closure->z_number }}</div>
        <div>{{ $closure->closed_at->format('d/m/Y H:i:s') }}</div>
        <div>FIRMWARE: POS-v1.0 (NF525)</div>
    </div>

    <div class="divider"></div>

    <div class="row">
        <span class="left">NET SALES (HT):</span>
        <span class="right bold">€{{ number_format($closure->total_ht, 2) }}</span>
    </div>
    <div class="clear"></div>

    <div class="row">
        <span class="left">TOTAL TVA (TAX):</span>
        <span class="right bold">€{{ number_format($closure->total_tva, 2) }}</span>
    </div>
    <div class="clear"></div>

    <div class="divider"></div>

    <div class="row" style="font-size: 14px;">
        <span class="left bold">TOTAL GROSS (TTC):</span>
        <span class="right bold">€{{ number_format($closure->total_ttc, 2) }}</span>
    </div>
    <div class="clear"></div>

    <div class="divider"></div>

    <div class="bold center">PAYMENTS BREAKDOWN</div>
    @foreach($closure->payments_breakdown ?? [] as $method => $amount)
        <div class="row">
            <span class="left" style="text-transform: capitalize;">{{ $method }}:</span>
            <span class="right">€{{ number_format((float) $amount, 2) }}</span>
        </div>
        <div class="clear"></div>
    @endforeach

    <div class="divider"></div>

    <div class="bold center">TVA BREAKDOWN</div>
    @foreach($closure->vat_breakdown ?? [] as $rate => $data)
        <div class="row">
            <span class="left">TVA {{ $rate }}% (TTC: €{{ number_format($data['ttc'] ?? 0, 2) }}):</span>
            <span class="right">€{{ number_format($data['vat'] ?? 0, 2) }}</span>
        </div>
        <div class="clear"></div>
    @endforeach

    <div class="divider"></div>

    <div class="center">
        <div class="bold">CRYPTOGRAPHIC INTEGRITY</div>
        <div class="hash">PREV HASH: {{ substr($closure->previous_hash, 0, 32) }}...</div>
        <div class="hash">SIGNATURE: {{ $closure->hash }}</div>
        <div class="divider"></div>
        <div>END OF Z-CLOSURE TICKET</div>
    </div>
</body>
</html>