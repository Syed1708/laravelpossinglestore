@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Recipe Costing & Builder')

@section('breadcrumb')
<a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Recipes &amp; Costing</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">📖 Recipe Costing &amp; Composition</h1>
            <p class="page-description">Manage raw ingredient compositions, stock reduction rules, and food cost margins for menu products.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Selling Price (TTC)</th>
                    <th>Recipe Composition</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr>
                    <td>
                        <strong style="font-size: 15px;">{{ $product->name }}</strong>
                    </td>
                    <td>
                        <span class="badge badge-secondary">{{ $product->category->name ?? 'Uncategorized' }}</span>
                    </td>
                    <td>
                        <strong style="color: var(--primary);">{{ $currencySymbol }}{{ number_format($product->price, 2) }}</strong>
                    </td>
                    <td>
                        <span class="badge {{ $product->recipes->count() > 0 ? 'badge-success' : 'badge-warning' }}">
                            {{ $product->recipes->count() }} Ingredients Configured
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('admin.recipes.show', $product->id) }}" class="btn btn-primary" style="font-size: 12px; padding: 6px 12px;">
                            ⚙️ Manage Recipe &amp; Costing
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection