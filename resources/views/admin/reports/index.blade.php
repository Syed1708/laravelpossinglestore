<!-- resources/views/admin/reports/index.blade.php -->
@extends('tyro-dashboard::layouts.app')

@section('title', 'Rapports de Vente')

@section('breadcrumb')
<span>Rapports (PDF)</span>
@endsection

@push('styles')
<!-- 🚀 CUSTOM STYLE OVERRIDES: Forces raw inputs to perfectly match Tyro's theme -->
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
        height: 38px; /* Standard Tyro input height */
        box-sizing: border-box;
    }

    .pos-filter-form .form-control:focus {
        border-color: var(--primary, #3182ce);
        outline: 0;
        box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.15);
    }

    /* Custom SVG Dropdown Arrow to replace browser defaults */
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

    /* Standard height alignment for the filter button */
    .pos-filter-form .btn {
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-bottom: 0;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">📅 Rapports d'Activité & TVA</h1>
            <p class="page-description">Filtrez et exportez vos données de vente par période.</p>
        </div>
    </div>
</div>

<!-- 1. DATE FILTER & REPORT SELECTION FORM CARD -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-body" style="padding: 1.25rem;">
        <form action="{{ route('admin.reports.index') }}" method="GET" class="pos-filter-form" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; width: 100%;">
            
            <!-- Start Date -->
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block; color: var(--foreground);">Date de Début</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
            </div>

            <!-- End Date -->
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block; color: var(--foreground);">Date de Fin</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
            </div>

            <!-- Select Report Type Dropdown -->
            <div class="form-group" style="flex: 1.5; min-width: 220px;">
                <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block; color: var(--foreground);">Type de Rapport (PDF)</label>
                <select name="report_type" id="report_type" class="form-control">
                    <option value="p_and_l">📈 Compte de Résultat (P&L global)</option>
                    <option value="sales">📊 Rapport de Ventes & TVA (Sales)</option>
                    <option value="purchases">📦 Approvisionnements (Purchases)</option>
                    <option value="expenses">💸 Dépenses de Fonctionnement (Expenses)</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; flex: 0 0 auto;">
                <button type="submit" class="btn btn-primary" style="padding: 0 1.5rem; margin: 0;">
                    🔍 Filtrer
                </button>
                
                <a href="#" onclick="triggerPdfDownload(event)" class="btn" style="background-color: var(--success); border-color: var(--success); color: white; padding: 0 1.5rem; margin: 0; gap: 8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Télécharger PDF
                </a>
            </div>
        </form>
    </div>
</div>

<!-- 2. TOTALS STATS GRID -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-icon stat-icon-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Ventes Totales (TTC)</div>
            <div class="stat-value" style="color: var(--primary);">{{ number_format($totals->total_ttc, 2, ',', ' ') }} €</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(100, 116, 139, 0.1); color: rgb(100, 116, 139);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 00-2 2z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Chiffre d'Affaires (HT)</div>
            <div class="stat-value" style="color: rgb(100, 116, 139);">{{ number_format($totals->total_ht, 2, ',', ' ') }} €</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">TVA Total Collectée</div>
            <div class="stat-value" style="color: var(--success);">{{ number_format($totals->total_tva, 2, ',', ' ') }} €</div>
        </div>
    </div>
</div>

<!-- 3. SPLIT ROW: Payment Methods & VAT Breakdown -->
<div class="grid-2">
    <!-- Card Left: Payments -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">💳 Modes de Règlement</h3>
        </div>
        <div class="card-body">
            @if($payments->isEmpty())
                <p style="color: var(--muted-foreground); text-align: center; padding: 1.5rem 0;">Aucune transaction réglée pour cette période.</p>
            @else
                @foreach($payments as $payment)
                    <div style="display: flex; justify-content: space-between; align-items: center; background: var(--muted); padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 0.75rem;">
                        <span style="font-weight: 600; text-transform: capitalize; color: var(--foreground);">
                            @if($payment->method === 'cash') 💵 Espèces @elseif($payment->method === 'card') 💳 Carte @else 🎟️ Ticket Resto @endif
                        </span>
                        <strong style="font-size: 1.1rem; color: var(--foreground);">{{ number_format($payment->total, 2, ',', ' ') }} €</strong>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Card Right: VAT Bracket Breakdown -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">⚖️ Répartition de la TVA</h3>
        </div>
        <div class="card-body">
            @if($vatBreakdown->isEmpty())
                <p style="color: var(--muted-foreground); text-align: center; padding: 1.5rem 0;">Aucun produit vendu pour cette période.</p>
            @else
                @foreach($vatBreakdown as $bracket)
                    <div style="display: flex; justify-content: space-between; align-items: center; border-left: 4px solid var(--success); background: var(--muted); padding: 0.75rem 1rem; border-radius: 0 8px 8px 0; margin-bottom: 0.75rem; padding-left: 1.25rem;">
                        <div>
                            <strong style="font-size: 1rem; display: block; color: var(--foreground);">Taux {{ number_format($bracket->vat_rate, 1, ',', ' ') }}%</strong>
                            <span style="font-size: 0.75rem; color: var(--muted-foreground);">Chiffre TTC: {{ number_format($bracket->total_ttc, 2, ',', ' ') }} €</span>
                        </div>
                        <strong style="color: var(--success); font-size: 1.1rem;">+{{ number_format($bracket->collected_vat, 2, ',', ' ') }} €</strong>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

<!-- JavaScript to handle dynamic PDF parameters -->
<script>
function triggerPdfDownload(event) {
    event.preventDefault();
    const reportType = document.getElementById('report_type').value;
    const url = "{{ route('admin.reports.pdf') }}?start_date={{ $startDate }}&end_date={{ $endDate }}&report_type=" + reportType;
    window.location.href = url;
}
</script>
@endsection