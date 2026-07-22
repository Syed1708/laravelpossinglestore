<!-- resources/views/admin/dashboard.blade.php -->
@extends('tyro-dashboard::layouts.app')

@section('title', 'SaaS Live Analytics')

@section('breadcrumb')
<span>Dashboard Financier</span>
@endsection

@push('styles')
<!-- 🚀 CUSTOM STYLE OVERRIDES: Forces raw inputs to perfectly match Tyro's theme -->
<style>
    .pos-filter-form .form-group {
        display: flex;
        flex-direction: column;
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

    /* 🚀 Custom SVG Dropdown Arrow to replace browser defaults */
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
    .pos-filter-form .btn-primary {
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<!-- Include Chart.js via secure CDN for our native-style chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Header Row (Clôture Z button completely removed) -->
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">📊 Live Sales & P&L Analytics</h1>
            <p class="page-description">Synthèse financière et marges opérationnelles de votre restaurant.</p>
        </div>
    </div>
</div>

<!-- 🚀 THE FILTER BAR (Isolated CSS: No Class Mismatches) -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-body" style="padding: 1.25rem;">
        <form action="{{ route('tyro-dashboard.index') }}" method="GET">
            <div style="display: flex; flex-direction: row; gap: 15px; align-items: flex-end; justify-content: flex-start; flex-wrap: wrap; width: 100%;">
                
                <!-- Period Dropdown Container (No form-group class to prevent margin conflicts) -->
                <div style="flex: 1 1 180px; min-width: 150px; display: flex; flex-direction: column; margin-bottom: 0;">
                    <label style="font-size: 0.875rem; font-weight: bold; margin-bottom: 6px; display: block; color: var(--foreground, #1a202c);">Période</label>
                    <select name="filter_type" id="filter_type" class="form-control" onchange="toggleDateInputs()" style="height: 38px; border: 1px solid var(--border, #cbd5e0); border-radius: 6px; padding: 0.5rem 0.75rem; background-color: var(--background, #fff); color: var(--foreground, #1a202c);">
                        <option value="today" @selected($filterType === 'today')>Aujourd'hui</option>
                        <option value="month" @selected($filterType === 'month')>Ce Mois (Mensuel)</option>
                        <option value="custom" @selected($filterType === 'custom')>Période personnalisée...</option>
                    </select>
                </div>

                <!-- Custom Start Date (Hidden by default, shown as flex when active) -->
                <div id="custom-start-box" style="display: {{ $filterType === 'custom' ? 'flex' : 'none' }}; flex: 1 1 180px; min-width: 150px; flex-direction: column; margin-bottom: 0;">
                    <label style="font-size: 0.875rem; font-weight: bold; margin-bottom: 6px; display: block; color: var(--foreground, #1a202c);">Date de Début</label>
                    <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" class="form-control" style="height: 38px; border: 1px solid var(--border, #cbd5e0); border-radius: 6px; padding: 0.5rem 0.75rem; background-color: var(--background, #fff); color: var(--foreground, #1a202c);">
                </div>

                <!-- Custom End Date (Hidden by default) -->
                <div id="custom-end-box" style="display: {{ $filterType === 'custom' ? 'flex' : 'none' }}; flex: 1 1 180px; min-width: 150px; flex-direction: column; margin-bottom: 0;">
                    <label style="font-size: 0.875rem; font-weight: bold; margin-bottom: 6px; display: block; color: var(--foreground, #1a202c);">Date de Fin</label>
                    <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" class="form-control" style="height: 38px; border: 1px solid var(--border, #cbd5e0); border-radius: 6px; padding: 0.5rem 0.75rem; background-color: var(--background, #fff); color: var(--foreground, #1a202c);">
                </div>

                <!-- Filter Button Container (Forced to the same baseline with no margin gaps) -->
                <div style="flex: 0 0 auto; margin-bottom: 0; display: flex; align-items: flex-end; height: 38px;">
                    <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; margin: 0;">
                        🔍 Filtrer
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- 2. TOP CARDS: Core P&L Financials -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <!-- Card 1: Revenue (HT) -->
    <div class="stat-card">
        <div class="stat-icon stat-icon-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Chiffre d'Affaires (HT)</div>
            <div class="stat-value" style="color: var(--primary);">{{ number_format($revenue->total_ht, 2, ',', ' ') }} €</div>
        </div>
    </div>

    <!-- Card 2: COGS (Food Cost) -->
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: rgb(239, 68, 68);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 00-2 2z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Coût Matières (Food Cost)</div>
            <div class="stat-value" style="color: rgb(239, 68, 68);">{{ number_format($foodCost, 2, ',', ' ') }} €</div>
        </div>
    </div>

    <!-- Card 3: Net Profit (HT) -->
    <div class="stat-card">
        <div class="stat-icon stat-icon-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="stat-label">Bénéfice Net Estimé</div>
            <div class="stat-value" style="color: var(--success);">{{ number_format($netProfit, 2, ',', ' ') }} €</div>
        </div>
    </div>
</div>

<!-- 3. SPLIT ROW: P&L Statement & Interactive Chart -->
<div class="grid-2" style="margin-bottom: 2rem;">
    <!-- Card Left: P&L Accounting Ledger Statement -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">⚖️ Compte de Résultat Périodique (P&L)</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                    <span style="color: var(--muted-foreground);">Ventes Brutes (TTC) :</span>
                    <strong style="color: var(--foreground);">{{ number_format($revenue->total_ttc, 2, ',', ' ') }} €</strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                    <span style="color: var(--muted-foreground);">Ventes Nettes (HT) (A) :</span>
                    <strong style="color: var(--foreground);">{{ number_format($revenue->total_ht, 2, ',', ' ') }} €</strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 8px; color: rgb(239, 68, 68);">
                    <span>Coût des Matières (COGS) (B) :</span>
                    <strong>-{{ number_format($foodCost, 2, ',', ' ') }} €</strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 8px; color: rgb(100, 116, 139);">
                    <span>Frais de Fonctionnement (OPEX) (C) :</span>
                    <strong>-{{ number_format($operatingCost, 2, ',', ' ') }} €</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding-top: 10px; font-size: 1.25rem;">
                    <strong style="color: var(--foreground);">Bénéfice Net (A - B - C) :</strong>
                    <strong style="color: {{ $netProfit >= 0 ? 'var(--success)' : 'var(--destructive)' }};">
                        {{ number_format($netProfit, 2, ',', ' ') }} €
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Card: Native-Looking ChartJS Pie Chart -->
    <div class="card" style="height: fit-content; display: flex; flex-direction: column;">
        <div class="card-header">
            <h3 class="card-title">📊 Répartition Financière</h3>
        </div>
        <div class="card-body" style="display: flex; justify-content: center; align-items: center; height: 250px;">
            <canvas id="plChart" style="max-height: 220px; max-width: 100%;"></canvas>
        </div>
    </div>
</div>

<!-- 4. Recent Synced Transactions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📄 Ventes Synchronisées sur la Période</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        @if($recentOrders->isEmpty())
            <p style="color: var(--muted-foreground); text-align: center; padding: 1.5rem 0;">Aucune transaction enregistrée pour cette période.</p>
        @else
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Date / Heure</th>
                            <th>Montant (TTC)</th>
                            <th style="text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                        <tr>
                            <td style="font-weight: bold;">
                                @if($order->total_incl_vat < 0)
                                    <span class="badge badge-danger">Avoir #{{ $order->sequence_number }}</span>
                                @else
                                    <span style="color: var(--foreground);">Ticket #{{ $order->sequence_number }}</span>
                                @endif
                            </td>
                            <td>{{ $order->completed_at->format('d/m/Y H:i') }}</td>
                            <td style="font-weight: bold; {{ $order->total_incl_vat < 0 ? 'color: var(--destructive);' : 'color: var(--foreground);' }}">
                                {{ number_format($order->total_incl_vat, 2, ',', ' ') }} €
                            </td>
                            <td style="text-align: right;">
                                <span class="badge badge-success">Synchronisé ☁️</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<!-- Chart JS & Toggle Logic -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('plChart').getContext('2d');
    
    const primaryColor = '#3182ce'; // Blue (Net Profit)
    const dangerColor = '#e53e3e';  // Red (Food Cost)
    const mutedColor = '#718096';   // Grey (OPEX)
    
    const profit = {{ $netProfit > 0 ? $netProfit : 0 }};
    const foodCost = {{ $foodCost }};
    const opex = {{ $operatingCost }};

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Bénéfice Net (€)', 'Coût Matières (Food Cost)', 'Fonctionnement (OPEX)'],
            datasets: [{
                data: [profit, foodCost, opex],
                backgroundColor: [primaryColor, dangerColor, mutedColor],
                borderWidth: 1,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#4a5568',
                        boxWidth: 12,
                        font: {
                            family: "'Inter', sans-serif",
                            size: 11
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });
});


function toggleDateInputs() {
    const filterType = document.getElementById('filter_type').value;
    const startBox = document.getElementById('custom-start-box');
    const endBox = document.getElementById('custom-end-box');

    if (filterType === 'custom') {
        startBox.style.setProperty('display', 'flex', 'important'); // 🚀 Shows as flex
        endBox.style.setProperty('display', 'flex', 'important');   // 🚀 Shows as flex
    } else {
        startBox.style.setProperty('display', 'none', 'important');
        endBox.style.setProperty('display', 'none', 'important');
    }
}
</script>
@endsection