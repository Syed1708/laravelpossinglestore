<!-- resources/views/admin/menu_engineering/index.blade.php -->
@extends('tyro-dashboard::layouts.app')

@section('title', 'Marge & Rentabilité')

@section('breadcrumb')
<span>Marge & Rentabilité</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">⚖️ Ingénierie de Menu & Coût de Revient</h1>
            <p class="page-description">Analyse en temps réel de votre coût matière, marge brute, et seuils de rentabilité par produit.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Analyse Financière des Produits Finis</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom du Produit</th>
                        <th>Catégorie</th>
                        <th>Prix (TTC)</th>
                        <th>Prix (HT)</th>
                        <th>Coût Matière</th>
                        <th>Marge Brute (€)</th>
                        <th style="text-align: right;">Statut Marge (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <!-- Product Name -->
                            <td style="font-weight: bold; color: var(--foreground);">{{ $product['name'] }}</td>
                            
                            <!-- Category -->
                            <td style="color: var(--muted-foreground);">{{ $product['category'] }}</td>
                            
                            <!-- Price TTC -->
                            <td>{{ number_format($product['price_ttc'], 2, ',', ' ') }} €</td>
                            
                            <!-- Price HT -->
                            <td style="color: var(--muted-foreground);">{{ number_format($product['price_ht'], 2, ',', ' ') }} €</td>
                            
                            <!-- Food Cost -->
                            <td style="color: #e53e3e; font-weight: 500;">
                                {{ number_format($product['food_cost'], 2, ',', ' ') }} €
                            </td>
                            
                            <!-- Margin in Euros -->
                            <td style="font-weight: bold; color: var(--foreground);">
                                {{ number_format($product['margin_euros'], 2, ',', ' ') }} €
                            </td>
                            
                            <!-- 🚀 DYNAMIC STATS BADGES (French Restaurant Standards) -->
                            <td style="text-align: right;">
                                @if($product['margin_percentage'] >= 70)
                                    <!-- Green Badge: Excellent Margin -->
                                    <span class="badge badge-success" style="padding: 5px 12px; font-weight: bold;">
                                        Excellente ({{ number_format($product['margin_percentage'], 1, ',', ' ') }}%)
                                    </span>
                                @elseif($product['margin_percentage'] >= 60 && $product['margin_percentage'] < 70)
                                    <!-- Blue Badge: Correct Margin -->
                                    <span class="badge badge-primary" style="padding: 5px 12px; font-weight: bold;">
                                        Correcte ({{ number_format($product['margin_percentage'], 1, ',', ' ') }}%)
                                    </span>
                                @else
                                    <!-- Red Badge: LOW MARGIN WARNING ALERT -->
                                    <span class="badge badge-danger" style="padding: 5px 12px; font-weight: bold;">
                                        ⚠️ Basse ({{ number_format($product['margin_percentage'], 1, ',', ' ') }}%)
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection