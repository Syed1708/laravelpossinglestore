<!-- resources/views/admin/purchases/index.blade.php -->
@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Supplier Purchase Orders')

@section('breadcrumb')
<a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Purchase Orders</span>
@endsection

@section('content')
@php
    $settings = \App\Models\StoreSetting::getSettings();
    $currencySymbol = $settings->currency === 'GBP' ? '£' : '€';
@endphp

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">📦 Supplier Purchase Orders &amp; Deliveries</h1>
            <p class="page-description">Track, import, and receive raw ingredient shipments from suppliers.</p>
        </div>
        <div>
            <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary" style="font-weight: bold;">
                ➕ Create Purchase Order
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Supplier Deliveries Tracking</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        @if($orders->count() > 0)
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Invoice #</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $row)
                        <tr>
                            <td>
                                <strong style="font-size: 15px; color: var(--foreground);">PO #{{ $row->po_number }}</strong>
                            </td>
                            <td>
                                <strong>{{ $row->supplier->name ?? 'N/A' }}</strong>
                            </td>
                            <td>
                                @if($row->invoice_number)
                                    <code style="font-size: 12px; color: var(--primary);">{{ $row->invoice_number }}</code>
                                @else
                                    <span style="color: var(--muted-foreground); font-size: 12px; font-style: italic;">Pending Receipt</span>
                                @endif
                            </td>
                            <td>
                                <strong style="color: var(--primary);">{{ $currencySymbol }}{{ number_format($row->total_cost, 2) }}</strong>
                            </td>
                            <td>
                                @if($row->status === 'received')
                                    <span class="badge badge-success">✅ Received &amp; Recorded</span>
                                @elseif($row->status === 'cancelled')
                                    <span class="badge badge-danger">❌ Cancelled</span>
                                @else
                                    <span class="badge badge-warning">⏳ Pending Delivery</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-buttons" style="justify-content: flex-end; gap: 8px;">
                                    @if($row->status === 'pending')
                                        <a href="{{ route('admin.purchases.show', $row->id) }}" class="btn btn-sm btn-primary">
                                            📥 Receive Delivery
                                        </a>
                                        
                                        <!-- Delete Draft Order Form -->
                                        <form action="{{ route('admin.purchases.destroy', $row->id) }}" method="POST" id="delete-po-form-{{ $row->id }}" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-ghost" style="color: var(--danger, #ef4444); font-weight: bold;" title="Delete Draft PO" onclick="event.preventDefault(); if(confirm('Are you sure you want to delete PO #{{ $row->po_number }}?')) document.getElementById('delete-po-form-{{ $row->id }}').submit();">
                                                🗑️ Delete
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.purchases.show', $row->id) }}" class="btn btn-sm btn-secondary">
                                            👁️ View Details
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <!-- Empty State -->
        <div class="empty-state" style="padding: 40px; text-align: center;">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 48px; height: 48px; margin: 0 auto 12px; color: var(--muted-foreground);">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4" />
            </svg>
            <h3 class="empty-state-title" style="margin-bottom: 4px;">No purchase orders found</h3>
            <p class="empty-state-description" style="color: var(--muted-foreground); margin-bottom: 16px;">Create a new supplier purchase order or import a CSV invoice to get started.</p>
            <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
                ➕ Create Purchase Order
            </a>
        </div>
        @endif
    </div>
</div>
@endsection