@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Configure Kiosk Steps - ' . $product->name)

@section('breadcrumb')
<a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('admin.product_steps.index') }}">Product Steps</a>
<span class="breadcrumb-separator">/</span>
<span>{{ $product->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">⚙️ Kiosk Steps: {{ $product->name }}</h1>
            <p class="page-description">Configure the exact step sequence customers follow on Kiosk &amp; Web when ordering this item.</p>
        </div>
        <a href="{{ route('admin.product_steps.index') }}" class="btn btn-secondary">
            ← Back to Products List
        </a>
    </div>
</div>

@if(session('success'))
    <div class="badge badge-success" style="padding: 12px; font-size: 14px; width: 100%; margin-bottom: 20px; text-align: center; display: block; font-weight: bold;">
        ✅ {{ session('success') }}
    </div>
@endif

<div style="display: grid; grid-template-columns: 1fr; gap: 20px;">

    <!-- Form: Attach New Step -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">➕ Attach Option Group (Wizard Step)</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.product_steps.store', $product->id) }}" method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 12px; align-items: end;">
                @csrf
                
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-weight: bold;">Select Option Group Step *</label>
                    <select name="option_group_id" class="form-select" required>
                        <option value="" disabled selected>Choose step (e.g. Meats, Sauces, Crust)...</option>
                        @foreach($allOptionGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->selection_type }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-weight: bold;">Step Order # *</label>
                    <input type="number" name="step_order" class="form-input" min="1" max="20" value="{{ $product->optionGroups->count() + 1 }}" required placeholder="1, 2, 3...">
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-weight: bold;">Free Choice Limit Override</label>
                    <input type="number" name="free_choice_limit_override" class="form-input" min="0" placeholder="Optional (e.g. 1)">
                </div>

                <button type="submit" class="btn btn-primary" style="height: 42px;">
                    Attach Step
                </button>
            </form>
        </div>
    </div>

    <!-- Active Attached Steps Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📋 Active Step Sequence for {{ $product->name }}</h3>
        </div>
        @if($product->optionGroups->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Step #</th>
                        <th>Option Group Name</th>
                        <th>Selection Type</th>
                        <th>Included Choices</th>
                        <th>Options Count</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($product->optionGroups as $group)
                    <tr>
                        <td>
                            <span class="badge badge-primary" style="font-size: 14px; font-weight: 900;">
                                Step {{ $group->pivot->step_order }}
                            </span>
                        </td>
                        <td>
                            <strong style="font-size: 15px;">{{ $group->name }}</strong>
                        </td>
                        <td>
                            <span class="badge badge-secondary">{{ $group->selection_type }}</span>
                        </td>
                        <td>
                            <span class="badge badge-success">
                                {{ $group->pivot->free_choice_limit_override ?? $group->free_choice_limit }} Free Choice(s)
                            </span>
                        </td>
                        <td>{{ $group->options->count() }} Choices Available</td>
                        <td style="text-align: right;">
                            <form action="{{ route('admin.product_steps.destroy', [$product->id, $group->id]) }}" method="POST" style="display: inline;" id="detach-step-{{ $group->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="action-btn action-btn-danger" title="Remove Step" onclick="event.preventDefault(); if(confirm('Remove {{ addslashes($group->name) }} from this product\'s steps?')) document.getElementById('detach-step-{{ $group->id }}').submit();">
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
            <h3 class="empty-state-title">No Kiosk steps attached to this product</h3>
            <p class="empty-state-description">When customers tap this product, it will be added to the cart instantly in 1 tap.</p>
        </div>
        @endif
    </div>

</div>
@endsection