<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HeroSlideController extends Controller
{
    /**
     * Render the Sliders Manager with Drag & Drop
     */
    public function index()
    {
        $slides = HeroSlide::orderBy('sort_order', 'asc')->get();
        return view('admin.hero_slides.index', compact('slides'));
    }

    /**
     * Store new slide via Modal
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'subtitle'   => 'nullable|string|max:500',
            'price'      => 'nullable|string|max:50',
            'badge'      => 'nullable|string|max:50',
            'image'      => 'required|image|max:3072',
            'cta_text'   => 'nullable|string|max:50',
            'cta_link'   => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('slides', 'public');
        }

        $maxOrder = HeroSlide::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 1;
        $validated['is_active']  = $request->has('is_active');

        HeroSlide::create($validated);

        return redirect()->route('admin.hero_slides.index')
            ->with('success', 'Hero slide created successfully!');
    }

    /**
     * Update slide via Modal
     */
    public function update(Request $request, HeroSlide $slide)
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'subtitle'   => 'nullable|string|max:500',
            'price'      => 'nullable|string|max:50',
            'badge'      => 'nullable|string|max:50',
            'image'      => 'nullable|image|max:3072',
            'cta_text'   => 'nullable|string|max:50',
            'cta_link'   => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('image')) {
            if ($slide->image_path) {
                Storage::disk('public')->delete($slide->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('slides', 'public');
        }

        $validated['is_active'] = $request->has('is_active');

        $slide->update($validated);

        return redirect()->route('admin.hero_slides.index')
            ->with('success', 'Hero slide updated successfully!');
    }

    /**
     * Delete slide
     */
    public function destroy(HeroSlide $slide)
    {
        if ($slide->image_path) {
            Storage::disk('public')->delete($slide->image_path);
        }
        $slide->delete();

        return redirect()->route('admin.hero_slides.index')
            ->with('success', 'Hero slide deleted successfully.');
    }

    /**
     * 🚀 Drag-and-Drop Reorder AJAX Endpoint
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order_ids'   => 'required|array',
            'order_ids.*' => 'exists:hero_slides,id',
        ]);

        foreach ($request->order_ids as $index => $id) {
            HeroSlide::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true, 'message' => 'Slide sequence updated!']);
    }
}