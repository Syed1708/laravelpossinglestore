@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Recipe Builder - ' . $product->name)

@section('breadcrumb')
<a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('admin.recipes.index') }}">Recipes</a>
<span class="breadcrumb-separator">/</span>
<span>{{ $product->name }}</span>
@endsection

@section('content')
<style>
    .recipe-container {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 20px;
    }
    @media (max-width: 1024px) {
        .recipe-container { grid-template-columns: 1fr; }
    }
    .cost-card {
        background: var(--card, #1e293b);
        border: 1px solid var(--border, #334155);
        border-radius: 16px;
        padding: 20px;
    }
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">⚙️ Recipe Builder: {{ $product->name }}</h1>
            <p class="page-description">Configure ingredients deducted when this dish is sold on POS or Web, and monitor Food Cost %.</p>
        </div>
        <a href="{{ route('admin.recipes.index') }}" class="btn btn-secondary">
            ← Back to Products
        </a>
    </div>
</div>

<div class="recipe-container">

    <!-- LEFT: INGREDIENTS LIST & ADD FORM -->
    <div class="space-y-4">
        
        <!-- Add Ingredient Form Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">➕ Add Ingredient to Recipe</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.recipes.store', $product->id) }}" method="POST" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 12px; align-items: end;">
                    @csrf
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Select Ingredient</label>
                        <select name="ingredient_id" class="form-select" required>
                            <option value="" disabled selected>Choose ingredient...</option>
                            @foreach($ingredients as $ing)
                                <option value="{{ $ing->id }}">
                                    {{ $ing->name }} (Base Unit: {{ $ing->unit }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Quantity in Base Unit</label>
                        <input type="number" name="quantity" step="0.0001" min="0.0001" class="form-input" placeholder="e.g. 120 (g/ml/unit)" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="height: 42px;">
                        Add
                    </button>
                </form>
            </div>
        </div>

        <!-- Recipe Items Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📋 Recipe Composition ({{ $recipes->count() }} Items)</h3>
            </div>
            @if($recipes->count())
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ingredient Name</th>
                            <th>Quantity Per Dish</th>
                            <th>Base Storage Unit</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recipes as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->ingredient->name ?? 'Unknown Ingredient' }}</strong>
                            </td>
                            <td>
                                <span class="badge badge-primary" style="font-size: 13px;">
                                    {{ number_format($item->quantity, 2) }}
                                </span>
                            </td>
                            <td>
                                <code>{{ $item->ingredient->unit ?? 'unit' }}</code>
                            </td>
                            <td style="text-align: right;">
                                <form action="{{ route('admin.recipes.destroy', [$product->id, $item->id]) }}" method="POST" style="display: inline;" id="delete-recipe-{{ $item->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-danger" title="Remove" onclick="event.preventDefault(); showDanger('Remove Ingredient', 'Are you sure you want to remove {{ addslashes($item->ingredient->name ?? 'this ingredient') }} from the recipe?').then(confirmed => { if(confirmed) document.getElementById('delete-recipe-{{ $item->id }}').submit(); })">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="empty-state">
                <h3 class="empty-state-title">No ingredients configured yet</h3>
                <p class="empty-state-description">Add raw ingredients above to enable automatic stock reduction on sales.</p>
            </div>
            @endif
        </div>

    </div>

    <!-- RIGHT: MARGIN & FOOD COST CALCULATOR SIDEBAR -->
    <div class="space-y-4">
        <div class="cost-card">
            <h3 style="margin-top: 0; font-size: 16px; font-weight: 800; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                📊 Food Cost &amp; Profit Margin
            </h3>

            <div style="margin-top: 15px; space-y: 12px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: var(--muted-foreground); font-size: 13px;">Selling Price (TTC):</span>
                    <strong style="font-size: 15px;">{{ $currencySymbol }}{{ number_format($product->price, 2) }}</strong>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: var(--muted-foreground); font-size: 13px;">Selling Price (HT):</span>
                    <strong style="font-size: 15px; color: var(--primary);">{{ $currencySymbol }}{{ number_format($sellingPriceHt, 2) }}</strong>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: var(--muted-foreground); font-size: 13px;">Theoretical Food Cost:</span>
                    <strong style="font-size: 15px; color: #ef4444;">-{{ $currencySymbol }}{{ number_format($totalFoodCost, 2) }}</strong>
                </div>

                <div style="border-top: 2px dashed var(--border); padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 800; font-size: 14px;">Gross Profit Margin:</span>
                    <span style="font-size: 18px; font-weight: 900; color: {{ $profitMargin >= 0 ? '#10b981' : '#ef4444' }};">
                        {{ $currencySymbol }}{{ number_format($profitMargin, 2) }}
                    </span>
                </div>

                <div style="margin-top: 12px; background: var(--bg-color); padding: 10px; border-radius: 8px; text-align: center;">
                    <span style="font-size: 11px; color: var(--muted-foreground); display: block;">Food Cost % (Target: 28% - 32%)</span>
                    <span style="font-size: 20px; font-weight: 900; color: {{ $foodCostPercentage <= 32 ? '#10b981' : '#f59e0b' }};">
                        {{ $foodCostPercentage }}%
                    </span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection