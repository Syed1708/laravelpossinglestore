@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'General & Operations Settings')

@section('breadcrumb')
    <a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>General Settings</span>
@endsection

@section('content')
    <style>
        .settings-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
        }

        .settings-nav-item {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 13px;
            text-decoration: none;
            color: var(--muted-foreground);
        }

        .settings-nav-item.active {
            background: var(--primary);
            color: var(--primary-foreground, #0f172a);
        }

        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: var(--muted);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 12px;
        }
    </style>

    <div class="page-header">
        <div class="page-header-row">
            <div>
                <h1 class="page-title">⚙️ General &amp; Operations Settings</h1>
                <p class="page-description">Configure country region, operating shifts, and master status toggles.</p>
            </div>
        </div>
    </div>

    <!-- SUBNAV TABS -->
    <div class="settings-nav">
        <a href="{{ route('admin.settings.general') }}" class="settings-nav-item active">⚙️ General &amp; Operations</a>
        <a href="{{ route('admin.settings.homepage') }}" class="settings-nav-item">🏠 Homepage Builder</a>
        <a href="{{ route('admin.settings.theme') }}" class="settings-nav-item">🎨 Theme &amp; Branding</a>
    </div>

    @if (session('success'))
        <div class="badge badge-success"
            style="padding: 12px; font-size: 14px; width: 100%; margin-bottom: 20px; text-align: center; display: block; font-weight: bold;">
            ✅ {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.update_general') }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; gap: 20px;">

            <!-- Regional Setup -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🏢 Region &amp; Store Setup</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold;">Country Region *</label>
                            <select name="country" class="form-select" required>
                                <option value="FR" {{ $settings->country === 'FR' ? 'selected' : '' }}>🇫🇷 France (FR)
                                </option>
                                <option value="UK" {{ $settings->country === 'UK' ? 'selected' : '' }}>🇬🇧 United
                                    Kingdom (UK)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold;">Primary Currency *</label>
                            <select name="currency" class="form-select" required>
                                <option value="EUR" {{ $settings->currency === 'EUR' ? 'selected' : '' }}>💶 Euro (€)
                                </option>
                                <option value="GBP" {{ $settings->currency === 'GBP' ? 'selected' : '' }}>💷 British
                                    Pound (£)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold;">Default Payroll Cadence *</label>
                            <select name="default_payroll_frequency" class="form-select" required>
                                <option value="monthly"
                                    {{ $settings->default_payroll_frequency === 'monthly' ? 'selected' : '' }}>Monthly
                                    (Standard FR / UK)</option>
                                <option value="weekly"
                                    {{ $settings->default_payroll_frequency === 'weekly' ? 'selected' : '' }}>Weekly (UK
                                    Hospitality)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Master Toggles & Operating Shifts -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🟢 Master Toggles &amp; Operating Shifts</h3>
                </div>
                <div class="card-body">
                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; margin-bottom: 20px;">
                        <div class="toggle-row">
                            <div>
                                <strong style="display: block; font-size: 13px;">Master Store Status</strong>
                                <span style="font-size: 11px; color: var(--muted-foreground);">Uncheck to force store
                                    closed</span>
                            </div>
                            <input type="checkbox" name="is_store_open" value="1"
                                {{ $settings->is_store_open ? 'checked' : '' }}
                                style="width: 20px; height: 20px; cursor: pointer;">
                        </div>

                        <div class="toggle-row">
                            <div>
                                <strong style="display: block; font-size: 13px;">Online Click &amp; Collect</strong>
                                <span style="font-size: 11px; color: var(--muted-foreground);">Allow web customer
                                    orders</span>
                            </div>
                            <input type="checkbox" name="online_orders_enabled" value="1"
                                {{ $settings->online_orders_enabled ? 'checked' : '' }}
                                style="width: 20px; height: 20px; cursor: pointer;">
                        </div>

                        <div class="toggle-row">
                            <div>
                                <strong style="display: block; font-size: 13px;">Table Reservations</strong>
                                <span style="font-size: 11px; color: var(--muted-foreground);">Allow web table
                                    bookings</span>
                            </div>
                            <input type="checkbox" name="reservations_enabled" value="1"
                                {{ $settings->reservations_enabled ? 'checked' : '' }}
                                style="width: 20px; height: 20px; cursor: pointer;">
                        </div>
                    </div>

                    <!-- Operating Shift Hours (HH:MM Time Pickers) -->
                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px;">

                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold;">Shift 1 Start (HH:MM) *</label>
                            <input type="time" name="shift1_start" class="form-input"
                                value="{{ \Carbon\Carbon::parse($settings->shift1_start)->format('H:i') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold;">Shift 1 End (HH:MM) *</label>
                            <input type="time" name="shift1_end" class="form-input"
                                value="{{ \Carbon\Carbon::parse($settings->shift1_end)->format('H:i') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold;">Shift 2 Start (HH:MM) *</label>
                            <input type="time" name="shift2_start" class="form-input"
                                value="{{ \Carbon\Carbon::parse($settings->shift2_start)->format('H:i') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold;">Shift 2 End (HH:MM) *</label>
                            <input type="time" name="shift2_end" class="form-input"
                                value="{{ \Carbon\Carbon::parse($settings->shift2_end)->format('H:i') }}" required>
                        </div>

                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Closure Message Shown to Customers</label>
                        <input type="text" name="closed_message" class="form-input"
                            value="{{ $settings->closed_message }}">
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary"
                    style="font-size: 15px; font-weight: 900; padding: 12px 28px;">
                    💾 Save General Settings
                </button>
            </div>

        </div>
    </form>
@endsection
