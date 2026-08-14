@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Homepage Builder Settings')

@section('breadcrumb')
<a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Homepage Builder</span>
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
    }
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">🏠 Homepage Builder &amp; Contact Info</h1>
            <p class="page-description">Customize website logo, hero headers, promo announcement banners, and contact info.</p>
        </div>
    </div>
</div>

<!-- SUBNAV TABS -->
<div class="settings-nav">
    <a href="{{ route('admin.settings.general') }}" class="settings-nav-item">⚙️ General &amp; Operations</a>
    <a href="{{ route('admin.settings.homepage') }}" class="settings-nav-item active">🏠 Homepage Builder</a>
    <a href="{{ route('admin.settings.theme') }}" class="settings-nav-item">🎨 Theme &amp; Branding</a>
</div>

@if(session('success'))
    <div class="badge badge-success" style="padding: 12px; font-size: 14px; width: 100%; margin-bottom: 20px; text-align: center; display: block; font-weight: bold;">
        ✅ {{ session('success') }}
    </div>
@endif

<form action="{{ route('admin.settings.update_homepage') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div style="display: grid; gap: 20px;">
        
        <!-- Branding Logo & Favicon -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🖼️ Branding Logo &amp; Favicon</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Website Logo (PNG/WebP)</label>
                        <input type="file" name="logo" accept="image/*" class="form-input">
                        @if($settings->logo_path)
                            <div style="margin-top: 10px;">
                                <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="Logo" style="height: 40px; object-fit: contain; background: var(--muted); padding: 4px; border-radius: 6px;">
                            </div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Favicon Icon (.ico/PNG)</label>
                        <input type="file" name="favicon" accept="image/*" class="form-input">
                        @if($settings->favicon_path)
                            <div style="margin-top: 10px;">
                                <img src="{{ asset('storage/' . $settings->favicon_path) }}" alt="Favicon" style="width: 24px; height: 24px;">
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Hero Banner Content -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">✨ Hero Header &amp; Announcement Banner</h3>
            </div>
            <div class="card-body" style="display: grid; gap: 16px;">
                <div class="form-group">
                    <label class="form-label" style="font-weight: bold;">Hero Headline Title *</label>
                    <input type="text" name="hero_title" class="form-input" value="{{ $settings->hero_title }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: bold;">Hero Subtitle / Description</label>
                    <textarea name="hero_subtitle" class="form-textarea" rows="2">{{ $settings->hero_subtitle }}</textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: center;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" style="font-weight: bold;">Top Promo Announcement Banner</label>
                        <input type="text" name="promo_banner_text" class="form-input" value="{{ $settings->promo_banner_text }}" placeholder="e.g. 🔥 Get 10% OFF on all Click &amp; Collect orders tonight!">
                    </div>

                    <div class="toggle-row" style="margin-top: 22px;">
                        <span style="font-size: 12px; font-weight: bold; margin-right: 8px;">Show Promo</span>
                        <input type="checkbox" name="promo_active" value="1" {{ $settings->promo_active ? 'checked' : '' }} style="width: 18px; height: 18px; cursor: pointer;">
                    </div>
                </div>
            </div>
        </div>

        <!-- About Us & Contact Details -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📍 About Section &amp; Contact Details</h3>
            </div>
            <div class="card-body" style="display: grid; gap: 16px;">
                <div class="form-group">
                    <label class="form-label" style="font-weight: bold;">About Us Section Title</label>
                    <input type="text" name="about_title" class="form-input" value="{{ $settings->about_title }}">
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: bold;">About Us Story Text</label>
                    <textarea name="about_text" class="form-textarea" rows="3">{{ $settings->about_text }}</textarea>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Contact Email</label>
                        <input type="email" name="contact_email" class="form-input" value="{{ $settings->contact_email }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-input" value="{{ $settings->contact_phone }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Physical Store Address</label>
                        <input type="text" name="contact_address" class="form-input" value="{{ $settings->contact_address }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: bold;">Google Maps Embed Iframe URL</label>
                    <textarea name="google_maps_iframe" class="form-textarea" rows="2" placeholder="e.g. https://www.google.com/maps/embed?pb=...">{{ $settings->google_maps_iframe }}</textarea>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="font-size: 15px; font-weight: 900; padding: 12px 28px;">
                💾 Save Homepage Content
            </button>
        </div>

    </div>
</form>
@endsection