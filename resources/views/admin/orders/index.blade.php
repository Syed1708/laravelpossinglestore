
@extends('tyro-dashboard::layouts.admin')
@section('title', 'Orders Archive')

@section('breadcrumb')
<a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Orders Archive</span>
@endsection


@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Orders Archive</h1>
            <p class="page-description">View, search, and filter all historical sales records across all channels.</p>
        </div>
        <a href="{{ route('admin.orders.online') }}" class="btn btn-primary" target="_blank">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; margin-right: 6px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            Live Online Orders Dispatcher
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('admin.orders.index') }}" method="GET">
            <div class="filters-bar">
                <!-- Search Box -->
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="search" class="form-input" placeholder="Search ticket #, customer name..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <!-- Order Type Filter Dropdown -->
                <div class="filter-group">
                    <label class="filter-label">Order Type:</label>
                    <select name="order_type" class="form-select" style="min-width: 180px;">
                        <option value="">All Channels</option>
                        <option value="click_and_collect" {{ ($filters['order_type'] ?? '') === 'click_and_collect' ? 'selected' : '' }}>🛍️ Online (Click &amp; Collect)</option>
                        <option value="takeaway" {{ ($filters['order_type'] ?? '') === 'takeaway' ? 'selected' : '' }}>📦 POS Takeaway</option>
                        <option value="dine_in" {{ ($filters['order_type'] ?? '') === 'dine_in' ? 'selected' : '' }}>🍽️ POS Dine-In</option>
                    </select>
                </div>

                <!-- Status Filter Dropdown -->
                <div class="filter-group">
                    <label class="filter-label">Status:</label>
                    <select name="status" class="form-select" style="min-width: 150px;">
                        <option value="">All Statuses</option>
                        <option value="not_accepted" {{ ($filters['status'] ?? '') === 'not_accepted' ? 'selected' : '' }}>Pending Acceptance</option>
                        <option value="accepted" {{ ($filters['status'] ?? '') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="preparing" {{ ($filters['status'] ?? '') === 'preparing' ? 'selected' : '' }}>In Kitchen</option>
                        <option value="ready" {{ ($filters['status'] ?? '') === 'ready' ? 'selected' : '' }}>Ready</option>
                        <option value="delivered" {{ ($filters['status'] ?? '') === 'delivered' ? 'selected' : '' }}>Served / Completed</option>
                        <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled / Refunded</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">Filter</button>
                @if(!empty($filters['search']) || !empty($filters['order_type']) || !empty($filters['status']))
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    @if($orders->count())
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Ticket #</th>
                    <th>Customer</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th>Total TTC</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td>
                        <strong style="font-size: 15px;">#{{ $order->sequence_number ?? $order->id }}</strong>
                    </td>
                    <td>
                        <div class="user-cell-info">
                            <div class="user-cell-name">{{ $order->customer_name ?? ($order->client->name ?? 'Walk-in Customer') }}</div>
                            @if($order->customer_phone)
                                <div class="user-cell-email" style="font-size: 11px;">{{ $order->customer_phone }}</div>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($order->order_type === 'click_and_collect' || $order->order_type === 'online')
                            <span class="badge badge-primary">🛍️ Online</span>
                        @elseif($order->order_type === 'takeaway')
                            <span class="badge badge-secondary">📦 Takeaway</span>
                        @else
                            <span class="badge badge-info">🍽️ Dine-In</span>
                        @endif
                    </td>
                    <td>
                        @if($order->preparation_status === 'not_accepted')
                            <span class="badge badge-warning">⚠️ Pending</span>
                        @elseif($order->preparation_status === 'preparing')
                            <span class="badge badge-primary">👨‍🍳 In Kitchen</span>
                        @elseif($order->preparation_status === 'ready')
                            <span class="badge badge-success">🎉 Ready</span>
                        @elseif($order->preparation_status === 'delivered' || $order->status === 'completed')
                            <span class="badge badge-success">✅ Served</span>
                        @else
                            <span class="badge badge-danger">❌ Cancelled</span>
                        @endif
                    </td>
                    <td>
                        <strong style="color: var(--primary);">€{{ number_format($order->total_incl_vat, 2) }}</strong>
                    </td>
                    <td>{{ $order->created_at ? $order->created_at->format('M d, Y H:i') : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
    <div class="pagination">
        {{ $orders->links() }}
    </div>
    @endif

    @else
    <div class="empty-state">
        <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
        </svg>
        <h3 class="empty-state-title">No orders found</h3>
        <p class="empty-state-description">Try adjusting your filters or search term.</p>
    </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput && searchInput.value) {
            searchInput.focus();
            searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
        }
    });
</script>
@endpush
@endsection