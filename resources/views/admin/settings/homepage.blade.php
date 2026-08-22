<!-- resources/views/admin/settings/homepage.blade.php -->
@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Homepage Builder Settings')

@section('breadcrumb')
    <a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Homepage Builder</span>
@endsection

@section('content')
    <!-- SortableJS for Drag-and-Drop Reordering -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

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

        .sortable-item-box {
            background: var(--muted);
            border: 1px solid var(--border);
            padding: 14px;
            border-radius: 12px;
            cursor: grab;
        }

        .sortable-item-box:active {
            cursor: grabbing;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
    </style>

    <div class="page-header">
        <div class="page-header-row">
            <div>
                <h1 class="page-title">🏠 Homepage Builder &amp; Section Toggles</h1>
                <p class="page-description">Customize website logo, hero sliders, section visibility, FAQs, and contact
                    details.</p>
            </div>
        </div>
    </div>

    <div class="settings-nav">
        <a href="{{ route('admin.settings.general') }}" class="settings-nav-item">⚙️ General &amp; Operations</a>
        <a href="{{ route('admin.settings.homepage') }}" class="settings-nav-item active">🏠 Homepage Builder</a>
        <a href="{{ route('admin.settings.theme') }}" class="settings-nav-item">🎨 Theme &amp; Branding</a>
    </div>

    @if (session('success'))
        <div class="badge badge-success"
            style="padding: 12px; font-size: 14px; width: 100%; margin-bottom: 20px; text-align: center; display: block; font-weight: bold;">
            ✅ {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.update_homepage') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div style="display: grid; gap: 20px;">

            <!-- SECTION VISIBILITY TOGGLES -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">👁️ Homepage Section Visibility Switches</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                        <div class="toggle-row">
                            <span style="font-size: 13px; font-weight: bold;">1. How It Works Section</span>
                            <input type="checkbox" name="show_how_it_works" value="1"
                                {{ $settings->show_how_it_works ?? true ? 'checked' : '' }}
                                style="width: 18px; height: 18px;">
                        </div>
                        <div class="toggle-row">
                            <span style="font-size: 13px; font-weight: bold;">2. Bestseller Favorites Grid</span>
                            <input type="checkbox" name="show_featured" value="1"
                                {{ $settings->show_featured ?? true ? 'checked' : '' }}
                                style="width: 18px; height: 18px;">
                        </div>
                        <div class="toggle-row">
                            <span style="font-size: 13px; font-weight: bold;">3. Why Choose Us Section</span>
                            <input type="checkbox" name="show_why_choose_us" value="1"
                                {{ $settings->show_why_choose_us ?? true ? 'checked' : '' }}
                                style="width: 18px; height: 18px;">
                        </div>
                        <div class="toggle-row">
                            <span style="font-size: 13px; font-weight: bold;">4. About Us Section</span>
                            <input type="checkbox" name="show_about" value="1"
                                {{ $settings->show_about ?? true ? 'checked' : '' }} style="width: 18px; height: 18px;">
                        </div>
                        <div class="toggle-row">
                            <span style="font-size: 13px; font-weight: bold;">5. Promo Banner Section</span>
                            <input type="checkbox" name="show_newsletter" value="1"
                                {{ $settings->show_newsletter ?? true ? 'checked' : '' }}
                                style="width: 18px; height: 18px;">
                        </div>
                        <div class="toggle-row">
                            <span style="font-size: 13px; font-weight: bold;">6. FAQ Accordion Section</span>
                            <input type="checkbox" name="show_faq" value="1"
                                {{ $settings->show_faq ?? true ? 'checked' : '' }} style="width: 18px; height: 18px;">
                        </div>
                        <div class="toggle-row">
                            <span style="font-size: 13px; font-weight: bold;">7. Contact &amp; Maps Section</span>
                            <input type="checkbox" name="show_contact" value="1"
                                {{ $settings->show_contact ?? true ? 'checked' : '' }}
                                style="width: 18px; height: 18px;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- HERO TITLE INPUT FIELD -->
            <div class="form-group">
                <label class="form-label" style="font-weight: bold;">Default Website Title / Brand Name</label>
                <input type="text" name="hero_title" class="form-input" value="{{ $settings->hero_title }}"
                    placeholder="e.g. Burger Palace Bordeaux">
            </div>

            <!-- BRANDING LOGO & FAVICON -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🖼️ Branding Logo &amp; Favicon</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold;">Website Logo (PNG/WebP)</label>
                            <input type="file" name="logo" accept="image/*" class="form-input">
                            @if ($settings->logo_path)
                                <div style="margin-top: 8px;">
                                    <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="Logo"
                                        style="height: 40px; object-fit: contain; background: var(--card-bg, #1e293b); padding: 4px; border-radius: 6px; border: 1px solid var(--border);">
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold;">Favicon Icon (.ico/PNG/SVG)</label>
                            <input type="file" name="favicon" accept="image/*" class="form-input">
                            @if ($settings->favicon_path)
                                <div style="margin-top: 8px;">
                                    <img src="{{ asset('storage/' . $settings->favicon_path) }}" alt="Favicon"
                                        style="width: 24px; height: 24px;">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 💡 HOW IT WORKS DYNAMIC BUILDER (DRAG & DROP) -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="card-title">💡 "How It Works" Steps Builder</h3>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addHowItWorksStep()">➕ Add
                        Step</button>
                </div>
                <div class="card-body" style="display: grid; gap: 16px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" style="font-weight: bold;">Section Title</label>
                            <input type="text" name="how_it_works_title" class="form-input"
                                value="{{ $settings->how_it_works_title }}">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" style="font-weight: bold;">Section Subtitle</label>
                            <input type="text" name="how_it_works_subtitle" class="form-input"
                                value="{{ $settings->how_it_works_subtitle }}">
                        </div>
                    </div>

                    @php $hwSteps = is_array($settings->how_it_works_steps) ? $settings->how_it_works_steps : []; @endphp

                    <div id="hw-steps-container" style="display: grid; gap: 12px;">
                        @foreach ($hwSteps as $idx => $step)
                            <div class="sortable-item-box">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <span class="drag-handle"
                                        style="font-size: 11px; font-weight: bold; color: var(--primary);">☰ Drag to
                                        Reorder Step</span>
                                    <button type="button" onclick="this.closest('.sortable-item-box').remove()"
                                        class="btn btn-sm btn-ghost"
                                        style="color: var(--danger, #ef4444); font-size: 11px;">🗑️ Remove</button>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 8px;">
                                    <input type="text" name="hw_step_title[]" class="form-input"
                                        placeholder="Step Title" value="{{ $step['title'] ?? '' }}" required>
                                    <input type="text" name="hw_step_desc[]" class="form-input"
                                        placeholder="Step Description" value="{{ $step['description'] ?? '' }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- ⭐ WHY CHOOSE US DYNAMIC BUILDER (DRAG & DROP) -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="card-title">⭐ "Why Choose Us" Highlights Builder</h3>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addWhyChooseUsItem()">➕ Add
                        Highlight</button>
                </div>
                <div class="card-body" style="display: grid; gap: 16px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" style="font-weight: bold;">Section Title</label>
                            <input type="text" name="why_choose_us_title" class="form-input"
                                value="{{ $settings->why_choose_us_title }}">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" style="font-weight: bold;">Section Subtitle</label>
                            <input type="text" name="why_choose_us_subtitle" class="form-input"
                                value="{{ $settings->why_choose_us_subtitle }}">
                        </div>
                    </div>

                    @php $wcuItems = is_array($settings->why_choose_us_items) ? $settings->why_choose_us_items : []; @endphp

                    <div id="wcu-items-container" style="display: grid; gap: 12px;">
                        @foreach ($wcuItems as $idx => $item)
                            <div class="sortable-item-box">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <span class="drag-handle"
                                        style="font-size: 11px; font-weight: bold; color: var(--primary);">☰ Drag to
                                        Reorder Highlight</span>
                                    <button type="button" onclick="this.closest('.sortable-item-box').remove()"
                                        class="btn btn-sm btn-ghost"
                                        style="color: var(--danger, #ef4444); font-size: 11px;">🗑️ Remove</button>
                                </div>
                                <div style="display: grid; grid-template-columns: 80px 1fr 2fr; gap: 8px;">
                                    <input type="text" name="wcu_item_icon[]" class="form-input" placeholder="Icon"
                                        value="{{ $item['icon'] ?? '✨' }}">
                                    <input type="text" name="wcu_item_title[]" class="form-input" placeholder="Title"
                                        value="{{ $item['title'] ?? '' }}" required>
                                    <input type="text" name="wcu_item_desc[]" class="form-input"
                                        placeholder="Description" value="{{ $item['description'] ?? '' }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- ❓ FAQ DYNAMIC BUILDER (DRAG & DROP) -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="card-title">❓ FAQ Questions &amp; Answers Builder</h3>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addFaqItem()">➕ Add Question</button>
                </div>
                <div class="card-body" style="display: grid; gap: 16px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" style="font-weight: bold;">Section Title</label>
                            <input type="text" name="faq_title" class="form-input"
                                value="{{ $settings->faq_title }}">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" style="font-weight: bold;">Section Subtitle</label>
                            <input type="text" name="faq_subtitle" class="form-input"
                                value="{{ $settings->faq_subtitle }}">
                        </div>
                    </div>

                    @php $faqItems = is_array($settings->faq_items) ? $settings->faq_items : []; @endphp

                    <div id="faq-items-container" style="display: grid; gap: 12px;">
                        @foreach ($faqItems as $idx => $faq)
                            <div class="sortable-item-box">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <span class="drag-handle"
                                        style="font-size: 11px; font-weight: bold; color: var(--primary);">☰ Drag to
                                        Reorder Q&amp;A</span>
                                    <button type="button" onclick="this.closest('.sortable-item-box').remove()"
                                        class="btn btn-sm btn-ghost"
                                        style="color: var(--danger, #ef4444); font-size: 11px;">🗑️ Remove</button>
                                </div>
                                <div style="display: grid; gap: 6px;">
                                    <input type="text" name="faq_question[]" class="form-input"
                                        placeholder="Question" value="{{ $faq['question'] ?? '' }}" required>
                                    <textarea name="faq_answer[]" class="form-textarea" rows="2" placeholder="Answer" required>{{ $faq['answer'] ?? '' }}</textarea>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 📖 ABOUT SECTION & CONTACT DETAILS -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📍 About Section &amp; Contact Information</h3>
                </div>
                <div class="card-body" style="display: grid; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">About Us Headline Title</label>
                        <input type="text" name="about_title" class="form-input"
                            value="{{ $settings->about_title }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">About Us Story Text</label>
                        <textarea name="about_text" class="form-textarea" rows="3">{{ $settings->about_text }}</textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold;">Contact Email</label>
                            <input type="email" name="contact_email" class="form-input"
                                value="{{ $settings->contact_email }}">
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold;">Contact Phone</label>
                            <input type="text" name="contact_phone" class="form-input"
                                value="{{ $settings->contact_phone }}">
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold;">Physical Store Address</label>
                            <input type="text" name="contact_address" class="form-input"
                                value="{{ $settings->contact_address }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Google Maps Embed Iframe URL</label>
                        <textarea name="google_maps_iframe" class="form-textarea" rows="2">{{ $settings->google_maps_iframe }}</textarea>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary"
                    style="font-size: 15px; font-weight: 900; padding: 12px 28px;">
                    💾 Save Homepage Content &amp; Sections
                </button>
            </div>

        </div>
    </form>

    @push('scripts')
        <script>
            // 🚀 DYNAMIC ITEM ADDERS & SORTABLEJS REORDERING
            function addHowItWorksStep() {
                const container = document.getElementById('hw-steps-container');
                const div = document.createElement('div');
                div.className = 'sortable-item-box';
                div.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span class="drag-handle" style="font-size: 11px; font-weight: bold; color: var(--primary);">☰ Drag to Reorder Step</span>
            <button type="button" onclick="this.closest('.sortable-item-box').remove()" class="btn btn-sm btn-ghost" style="color: var(--danger, #ef4444); font-size: 11px;">🗑️ Remove</button>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 8px;">
            <input type="text" name="hw_step_title[]" class="form-input" placeholder="Step Title" required>
            <input type="text" name="hw_step_desc[]" class="form-input" placeholder="Step Description">
        </div>
    `;
                container.appendChild(div);
            }

            function addWhyChooseUsItem() {
                const container = document.getElementById('wcu-items-container');
                const div = document.createElement('div');
                div.className = 'sortable-item-box';
                div.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span class="drag-handle" style="font-size: 11px; font-weight: bold; color: var(--primary);">☰ Drag to Reorder Highlight</span>
            <button type="button" onclick="this.closest('.sortable-item-box').remove()" class="btn btn-sm btn-ghost" style="color: var(--danger, #ef4444); font-size: 11px;">🗑️ Remove</button>
        </div>
        <div style="display: grid; grid-template-columns: 80px 1fr 2fr; gap: 8px;">
            <input type="text" name="wcu_item_icon[]" class="form-input" placeholder="Icon" value="✨">
            <input type="text" name="wcu_item_title[]" class="form-input" placeholder="Title" required>
            <input type="text" name="wcu_item_desc[]" class="form-input" placeholder="Description">
        </div>
    `;
                container.appendChild(div);
            }

            function addFaqItem() {
                const container = document.getElementById('faq-items-container');
                const div = document.createElement('div');
                div.className = 'sortable-item-box';
                div.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span class="drag-handle" style="font-size: 11px; font-weight: bold; color: var(--primary);">☰ Drag to Reorder Q&amp;A</span>
            <button type="button" onclick="this.closest('.sortable-item-box').remove()" class="btn btn-sm btn-ghost" style="color: var(--danger, #ef4444); font-size: 11px;">🗑️ Remove</button>
        </div>
        <div style="display: grid; gap: 6px;">
            <input type="text" name="faq_question[]" class="form-input" placeholder="Question" required>
            <textarea name="faq_answer[]" class="form-textarea" rows="2" placeholder="Answer" required></textarea>
        </div>
    `;
                container.appendChild(div);
            }

            // 🖐️ INITIALIZE SORTABLEJS ON ALL 3 SECTION CONTAINERS
            document.addEventListener('DOMContentLoaded', function() {
                ['hw-steps-container', 'wcu-items-container', 'faq-items-container'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        new Sortable(el, {
                            animation: 150,
                            handle: '.drag-handle',
                        });
                    }
                });
            });
        </script>
    @endpush
@endsection
