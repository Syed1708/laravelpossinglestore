<!-- resources/views/admin/recipes/index.blade.php -->
@extends('tyro-dashboard::layouts.app')

@section('title', 'Gestion des Fiches Recettes')

@section('breadcrumb')
<span>Fiches Recettes</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">⚙️ Fiches Techniques & Recettes</h1>
            <p class="page-description">Associez vos produits finis à leurs ingrédients bruts pour automatiser vos stocks.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Sélectionnez un Produit</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom du Produit</th>
                        <th>Catégorie</th>
                        <th>Prix Public (TTC)</th>
                        <th style="text-align: right;">Configuration</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td style="font-weight: bold; color: var(--foreground);">{{ $product->name }}</td>
                            <td style="color: var(--muted-foreground);">{{ $product->category->name ?? 'N/A' }}</td>
                            <td style="font-weight: 600;">{{ number_format($product->price, 2, ',', ' ') }} €</td>
                            <td style="text-align: right;">
                                <a href="{{ route('admin.recipes.show', $product->id) }}" class="btn btn-sm btn-primary">
                                    ⚙️ Gérer la Recette
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection