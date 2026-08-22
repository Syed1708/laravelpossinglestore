@extends(view()->exists('tyro-dashboard::layouts.admin') ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.app')

@section('title', 'Hero Carousel Sliders')

@section('breadcrumb')
<a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Hero Sliders</span>
@endsection

@section('content')
<!-- SortableJS for Drag-and-Drop Reordering -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<style>
    .slides-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    .slide-card {
        background: var(--card, #1e293b);
        border: 2px solid var(--border, #334155);
        border-radius: 16px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        cursor: grab;
        transition: all 0.2s ease;
    }
    .slide-card:active {
        cursor: grabbing;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    }
    .slide-img-preview {
        width: 100%;
        height: 140px;
        object-fit: cover;
        border-radius: 12px;
        border: 1px solid var(--border, #334155);
        background: var(--muted, #0f172a);
        margin-bottom: 12px;
    }
    .drag-handle {
        font-size: 11px;
        font-weight: 800;
        color: var(--muted-foreground, #94a3b8);
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 8px;
    }
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">🎠 Hero Carousel Sliders</h1>
            <p class="page-description">Drag cards to reorder display sequence on the homepage. Edit or add new slides via modal popups.</p>
        </div>
        <div>
            <button onclick="openCreateSlideModal()" class="btn btn-primary" style="font-weight: bold;">
                ➕ Add Hero Slide
            </button>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="badge badge-success" style="padding: 12px; font-size: 14px; width: 100%; margin-bottom: 20px; text-align: center; display: block; font-weight: bold;">
        ✅ {{ session('success') }}
    </div>
@endif

<!-- 🖐️ DRAG & DROP SLIDES CONTAINER -->
<div class="slides-grid" id="slides-sortable-container">
    @forelse($slides as $slide)
        <div class="slide-card" data-id="{{ $slide->id }}">
            <div>
                <div class="drag-handle">
                    <span>☰ Drag to Reorder</span>
                    <span style="margin-left: auto;">#{{ $slide->sort_order }}</span>
                </div>

                <!-- 🚀 LIVE IMAGE PREVIEW THUMBNAIL -->
                <div style="position: relative;">
                    <img src="{{ asset('storage/' . $slide->image_path) }}" alt="{{ $slide->title }}" class="slide-img-preview">
                    <span class="badge {{ $slide->is_active ? 'badge-success' : 'badge-danger' }}" style="position: absolute; top: 8px; right: 8px; font-size: 10px;">
                        {{ $slide->is_active ? 'Active' : 'Disabled' }}
                    </span>
                </div>

                <h3 style="font-size: 16px; font-weight: 900; margin: 4px 0; color: var(--foreground);">{{ $slide->title }}</h3>
                @if($slide->subtitle)
                    <p style="font-size: 12px; color: var(--muted-foreground); margin: 2px 0 8px 0; line-clamp: 2;">{{ $slide->subtitle }}</p>
                @endif

                <div style="display: flex; gap: 8px; font-size: 11px; font-weight: bold; margin-top: 8px;">
                    @if($slide->price)<span class="badge badge-primary">{{ $slide->price }}</span>@endif
                    @if($slide->badge)<span class="badge badge-secondary">{{ $slide->badge }}</span>@endif
                </div>
            </div>

            <div style="border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                <button type="button" onclick="openEditSlideModal({{ json_encode($slide) }})" class="btn btn-sm btn-secondary" style="font-size: 11px;">
                    ✏️ Edit Slide
                </button>

                <form action="{{ route('admin.hero_slides.destroy', $slide->id) }}" method="POST" id="delete-slide-{{ $slide->id }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-sm btn-ghost" style="color: var(--danger, #ef4444); font-weight: bold; font-size: 11px;" onclick="if(confirm('Delete slide {{ addslashes($slide->title) }}?')) document.getElementById('delete-slide-{{ $slide->id }}').submit();">
                        🗑️ Delete
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div style="grid-column: 1 / -1;" class="empty-state">
            <h3 class="empty-state-title">No hero slides found</h3>
            <p class="empty-state-description">Click the button below to add your first dynamic banner slide.</p>
            <button onclick="openCreateSlideModal()" class="btn btn-primary">➕ Add Hero Slide</button>
        </div>
    @endforelse
</div>

<!-- 💬 1. CREATE SLIDE MODAL -->
<div class="modal-overlay" id="createSlideModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">➕ Add New Hero Slide</h3>
            <button type="button" class="modal-close" onclick="closeModal('createSlideModal')">✕</button>
        </div>
        <form action="{{ route('admin.hero_slides.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body" style="display: grid; gap: 12px;">
                <div class="form-group">
                    <label class="form-label" style="font-weight: bold;">Headline Title *</label>
                    <input type="text" name="title" class="form-input" required placeholder="e.g. The Double Smash Truffle">
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-weight: bold;">Subtitle Text</label>
                    <input type="text" name="subtitle" class="form-input" placeholder="e.g. Aged Cheddar &amp; Black Truffle Mayo">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Price Badge</label>
                        <input type="text" name="price" class="form-input" placeholder="e.g. €14.90">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Badge Tag</label>
                        <input type="text" name="badge" class="form-input" placeholder="e.g. Chef Special">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-weight: bold;">Banner Image (Upload) *</label>
                    <input type="file" name="image" accept="image/*" class="form-input" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Button Text</label>
                        <input type="text" name="cta_text" class="form-input" value="Order Now">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Button Link</label>
                        <input type="text" name="cta_link" class="form-input" value="/order">
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_active" value="1" checked id="create_active" style="width: 18px; height: 18px;">
                    <label for="create_active" style="font-weight: bold; font-size: 13px; cursor: pointer;">Active Slide (Visible on Homepage)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createSlideModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Slide</button>
            </div>
        </form>
    </div>
</div>

<!-- 💬 2. EDIT SLIDE MODAL -->
<div class="modal-overlay" id="editSlideModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">✏️ Edit Hero Slide</h3>
            <button type="button" class="modal-close" onclick="closeModal('editSlideModal')">✕</button>
        </div>
        <form id="editSlideForm" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body" style="display: grid; gap: 12px;">
                <div class="form-group">
                    <label class="form-label" style="font-weight: bold;">Headline Title *</label>
                    <input type="text" name="title" id="edit_title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-weight: bold;">Subtitle Text</label>
                    <input type="text" name="subtitle" id="edit_subtitle" class="form-input">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Price Badge</label>
                        <input type="text" name="price" id="edit_price" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Badge Tag</label>
                        <input type="text" name="badge" id="edit_badge" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-weight: bold;">Replace Banner Image (Optional)</label>
                    <input type="file" name="image" accept="image/*" class="form-input">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Button Text</label>
                        <input type="text" name="cta_text" id="edit_cta_text" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: bold;">Button Link</label>
                        <input type="text" name="cta_link" id="edit_cta_link" class="form-input">
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_active" value="1" id="edit_is_active" style="width: 18px; height: 18px;">
                    <label for="edit_is_active" style="font-weight: bold; font-size: 13px; cursor: pointer;">Active Slide (Visible on Homepage)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editSlideModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Slide</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCreateSlideModal() {
    openModal('createSlideModal');
}

function openEditSlideModal(slide) {
    document.getElementById('editSlideForm').action = '/admin/hero-slides/' + slide.id + '/update';
    document.getElementById('edit_title').value = slide.title || '';
    document.getElementById('edit_subtitle').value = slide.subtitle || '';
    document.getElementById('edit_price').value = slide.price || '';
    document.getElementById('edit_badge').value = slide.badge || '';
    document.getElementById('edit_cta_text').value = slide.cta_text || 'Order Now';
    document.getElementById('edit_cta_link').value = slide.cta_link || '/order';
    document.getElementById('edit_is_active').checked = !!slide.is_active;

    openModal('editSlideModal');
}

// 🖐️ SORTABLEJS DRAG & DROP REORDER LISTENER
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('slides-sortable-container');
    if (container) {
        new Sortable(container, {
            animation: 200,
            onEnd: async function () {
                const orderIds = Array.from(container.children).map(card => card.getAttribute('data-id')).filter(Boolean);
                
                try {
                    await fetch('/admin/hero-slides/reorder', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ order_ids: orderIds })
                    });
                } catch (err) {
                    console.error('Failed to save slide order:', err);
                }
            }
        });
    }
});
</script>
@endpush
@endsection