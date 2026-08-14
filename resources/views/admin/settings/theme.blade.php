@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Theme & UI Branding Settings')

@section('breadcrumb')
<a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Theme &amp; Branding</span>
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
    .theme-card-picker {
        border: 2px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .theme-card-picker.active {
        border-color: var(--primary);
        background: rgba(245, 158, 11, 0.05);
    }
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">🎨 Web Theme &amp; Brand Customization</h1>
            <p class="page-description">Configure color palettes, preset theme templates, font families, and card border radiuses for your web portal.</p>
        </div>
    </div>
</div>

<!-- SUBNAV TABS -->
<div class="settings-nav">
    <a href="{{ route('admin.settings.general') }}" class="settings-nav-item">⚙️ General &amp; Operations</a>
    <a href="{{ route('admin.settings.homepage') }}" class="settings-nav-item">🏠 Homepage Builder</a>
    <a href="{{ route('admin.settings.theme') }}" class="settings-nav-item active">🎨 Theme &amp; Branding</a>
</div>

@if(session('success'))
    <div class="badge badge-success" style="padding: 12px; font-size: 14px; width: 100%; margin-bottom: 20px; text-align: center; display: block; font-weight: bold;">
        ✅ {{ session('success') }}
    </div>
@endif

<form action="{{ route('admin.settings.update_theme') }}" method="POST">
    @csrf
    @method('PUT')

    <div style="display: grid; gap: 20px;">
        
        <!-- Preset Theme Templates -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🎭 Select Preset Theme Template</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    
                    <label class="theme-card-picker {{ $settings->theme_preset === 'amber' ? 'active' : '' }}">
                        <input type="radio" name="theme_preset" value="amber" {{ $settings->theme_preset === 'amber' ? 'checked' : '' }} style="display: none;">
                        <div style="font-weight: 800; font-size: 14px; margin-bottom: 4px;">🟡 Amber Gold (Default)</div>
                        <p style="font-size: 11px; color: var(--muted-foreground); margin: 0;">Warm dark theme with golden amber accents.</p>
                    </label>

                    <label class="theme-card-picker {{ $settings->theme_preset === 'emerald' ? 'active' : '' }}">
                        <input type="radio" name="theme_preset" value="emerald" {{ $settings->theme_preset === 'emerald' ? 'checked' : '' }} style="display: none;">
                        <div style="font-weight: 800; font-size: 14px; margin-bottom: 4px;">🟢 Emerald Fresh</div>
                        <p style="font-size: 11px; color: var(--muted-foreground); margin: 0;">Fresh green theme for organic &amp; healthy cuisine.</p>
                    </label>

                    <label class="theme-card-picker {{ $settings->theme_preset === 'purple' ? 'active' : '' }}">
                        <input type="radio" name="theme_preset" value="purple" {{ $settings->theme_preset === 'purple' ? 'checked' : '' }} style="display: none;">
                        <div style="font-weight: 800; font-size: 14px; margin-bottom: 4px;">🟣 Midnight Purple</div>
                        <p style="font-size: 11px; color: var(--muted-foreground); margin: 0;">Sleek night lounge vibe with violet accents.</p>
                    </label>

                    <label class="theme-card-picker {{ $settings->theme_preset === 'rose' ? 'active' : '' }}">
                        <input type="radio" name="theme_preset" value="rose" {{ $settings->theme_preset === 'rose' ? 'checked' : '' }} style="display: none;">
                        <div style="font-weight: 800; font-size: 14px; margin-bottom: 4px;">🔴 Crimson Rose</div>
                        <p style="font-size: 11px; color: var(--muted-foreground); margin: 0;">Bold high-energy red &amp; crimson palette.</p>
                    </label>

                </div>
            </div>
        </div>

        <!-- Custom Color & Font Controls -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🎛️ Custom Color &amp; Typography Controls</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                    
                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Primary Accent Color</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" name="primary_color" value="{{ $settings->primary_color }}" style="width: 44px; height: 38px; border: none; cursor: pointer; border-radius: 6px;">
                            <input type="text" value="{{ $settings->primary_color }}" readonly class="form-input" style="font-family: monospace;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Secondary Highlight Color</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" name="secondary_color" value="{{ $settings->secondary_color }}" style="width: 44px; height: 38px; border: none; cursor: pointer; border-radius: 6px;">
                            <input type="text" value="{{ $settings->secondary_color }}" readonly class="form-input" style="font-family: monospace;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Web Font Family</label>
                        <select name="font_family" class="form-select">
                            <option value="sans-serif" {{ $settings->font_family === 'sans-serif' ? 'selected' : '' }}>Sans-Serif (Modern Clean)</option>
                            <option value="inter" {{ $settings->font_family === 'inter' ? 'selected' : '' }}>Inter / Helvetica</option>
                            <option value="serif" {{ $settings->font_family === 'serif' ? 'selected' : '' }}>Serif (Classic Elegant)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Card Border Radius</label>
                        <select name="border_radius" class="form-select">
                            <option value="rounded-2xl" {{ $settings->border_radius === 'rounded-2xl' ? 'selected' : '' }}>Rounded (Standard - 16px)</option>
                            <option value="rounded-3xl" {{ $settings->border_radius === 'rounded-3xl' ? 'selected' : '' }}>Extra Curved (24px)</option>
                            <option value="rounded-md" {{ $settings->border_radius === 'rounded-md' ? 'selected' : '' }}>Sharp / Minimal (6px)</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="font-size: 15px; font-weight: 900; padding: 12px 28px;">
                🎨 Save Theme Settings
            </button>
        </div>

    </div>
</form>
@endsection