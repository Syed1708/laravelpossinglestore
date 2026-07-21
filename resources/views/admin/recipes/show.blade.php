<!-- resources/views/admin/recipes/show.blade.php -->
@extends('tyro-dashboard::layouts.app')

@section('title', 'Recette: ' . $product->name)

@section('breadcrumb')
<a href="{{ route('admin.recipes.index') }}">Fiches Recettes</a>
<span style="margin: 0 8px;">/</span>
<span>{{ $product->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">🍔 Recette de : {{ $product->name }}</h1>
            <p class="page-description">Catégorie : {{ $product->category->name ?? 'N/A' }} | Prix Public : {{ number_format($product->price, 2, ',', ' ') }} €</p>
        </div>
        <div>
            <a href="{{ route('admin.recipes.index') }}" class="btn btn-ghost">
                ⬅️ Retour à la liste
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="badge badge-success" style="padding: 10px; font-size: 14px; width: 100%; margin-bottom: 20px; display: block; text-align: center;">
        {{ session('success') }}
    </div>
@endif

<!-- SPLIT ROW: Active Recipe List vs Add Ingredient Form -->
<div class="grid-2">
    
    <!-- Left Card: Current Recipe Ingredients -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📂 Composition de la Recette ({{ $recipes->count() }} Ingrédients)</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            @if($recipes->isEmpty())
                <div style="padding: 30px; text-align: center; color: var(--muted-foreground);">
                    <p>Cette recette est actuellement vide.</p>
                    <p style="font-size: 12px; margin-top: 5px;">Ajoutez des ingrédients à l'aide du formulaire sur la droite.</p>
                </div>
            @else
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ingrédient</th>
                                <th>Quantité Requise</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recipes as $row)
                                <tr>
                                    <td style="font-weight: bold; color: var(--foreground);">{{ $row->ingredient->name }}</td>
                                    <td>
                                        <span class="badge badge-primary">
                                            {{ number_format($row->quantity, 2, ',', ' ') }} {{ $row->ingredient->unit }}
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <!-- Compliant inline deletion form -->
                                        <form action="{{ route('admin.recipes.destroy', [$product->id, $row->id]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Retirer cet ingrédient de la recette ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-ghost" style="color: var(--destructive);">
                                                ❌ Retirer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Right Card: Add New Ingredient Quickly -->
    <div class="card" style="height: fit-content;">
        <div class="card-header">
            <h3 class="card-title">➕ Ajouter un ingrédient</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.recipes.store', $product->id) }}" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                @csrf

                <!-- Ingredient Selector -->
                <div class="form-group">
                    <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block;">Ingrédient Brut</label>
                    <select name="ingredient_id" required class="form-control">
                        <option value="">Sélectionnez un ingrédient...</option>
                        @foreach($ingredients as $ing)
                            <option value="{{ $ing->id }}">{{ $ing->name }} (en {{ $ing->unit }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Quantity Required -->
                <div class="form-group">
                    <label class="form-label" style="font-weight: bold; margin-bottom: 5px; display: block;">Quantité Consommée par vente</label>
                    <input type="number" name="quantity" step="0.01" min="0.01" placeholder="Ex: 1.00 ou 150.00" required class="form-control">
                </div>

                <button type="submit" class="btn btn-primary" style="font-weight: bold; padding: 10px;">
                    Enregistrer l'Ingrédient
                </button>
            </form>
        </div>
    </div>

</div>
@endsection