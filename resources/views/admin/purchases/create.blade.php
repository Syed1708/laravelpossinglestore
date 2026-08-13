<!-- resources/views/admin/purchases/create.blade.php -->
@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Create Purchase Order')

@section('breadcrumb')
<a href="{{ route('admin.purchases.index') }}">Purchase Orders</a>
<span class="breadcrumb-separator">/</span>
<span>Create Order</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">➕ Create Purchase Order (PO)</h1>
            <p class="page-description">Generate a new draft order for a supplier or import ingredient items via CSV file.</p>
        </div>
        <div>
            <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary">
                ← Back to Orders
            </a>
        </div>
    </div>
</div>

<div class="card" style="max-width: 650px; margin: 0 auto;">
    <div class="card-body">
        <form action="{{ route('admin.purchases.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Supplier Selector -->
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" style="font-weight: bold; margin-bottom: 6px; display: block;">Supplier *</label>
                <select name="supplier_id" required class="form-select">
                    <option value="" disabled selected>Select a supplier...</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ old('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Auto-generated PO Number field -->
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" style="font-weight: bold; margin-bottom: 6px; display: block;">Purchase Order Number (PO #)</label>
                <input type="number" name="po_number" value="{{ $nextPoNumber }}" readonly class="form-input" style="background-color: var(--muted); cursor: not-allowed; font-weight: bold;">
                <p style="font-size: 11px; color: var(--muted-foreground); margin-top: 5px;">Sequential Purchase Order number generated automatically.</p>
            </div>

            <!-- CSV IMPORTER FILE INPUT -->
            <div class="form-group" style="background: var(--muted); padding: 16px; border-radius: 12px; border: 1px dashed var(--border); margin-bottom: 1.5rem;">
                <label class="form-label" style="font-weight: bold; margin-bottom: 6px; display: block;">📈 Import Items from CSV File *</label>
                <input type="file" name="import_file" accept=".csv, .txt" required class="form-input" style="background: none; border: none; padding: 0;">
                <p style="font-size: 11px; color: var(--muted-foreground); margin-top: 8px; line-height: 1.5;">
                    The file must be in <strong>.csv</strong> format.<br>
                    Required column structure: <code>Ingredient Name, Quantity Ordered, Unit Price</code>
                </p>
            </div>

            <button type="submit" class="btn btn-primary" style="font-weight: bold; padding: 12px; width: 100%;">
                Save Purchase Order
            </button>
        </form>
    </div>
</div>
@endsection