@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Product Kiosk Steps Manager')

@section('breadcrumb')
<a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Product Kiosk Steps</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">⚙️ Product Kiosk Steps Manager</h1>
            <p class="page-description">Assign and configure step-by-step wizard option groups for each product in your catalog.</p>
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
                    <th>Price</th>
                    <th>Configured Kiosk Steps</th>
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
                        @if($product->optionGroups->count() > 0)
                            <span class="badge badge-success">
                                {{ $product->optionGroups->count() }} Steps Configured
                            </span>
                        @else
                            <span class="badge badge-secondary">
                                0 Steps (1-Tap Direct Purchase)
                            </span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('admin.product_steps.show', $product->id) }}" class="btn btn-primary" style="font-size: 12px; padding: 6px 12px;">
                            ⚙️ Manage Kiosk Steps
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection