<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    // 1. General & Operations Settings Page
    public function general()
    {
        $settings = StoreSetting::getSettings();
        return view('admin.settings.general', compact('settings'));
    }

    public function updateGeneral(Request $request)
    {
        $settings = StoreSetting::getSettings();

        $validated = $request->validate([
            'country'                   => 'required|string|in:FR,UK',
            'currency'                  => 'required|string|in:EUR,GBP',
            'default_payroll_frequency' => 'required|string|in:monthly,weekly',
            'shift1_start'              => 'required|string',
            'shift1_end'                => 'required|string',
            'shift2_start'              => 'required|string',
            'shift2_end'                => 'required|string',
            'closed_message'            => 'nullable|string|max:255',
        ]);

        $validated['is_store_open']         = $request->has('is_store_open');
        $validated['online_orders_enabled'] = $request->has('online_orders_enabled');
        $validated['reservations_enabled']  = $request->has('reservations_enabled');

        $settings->update($validated);

        return redirect()->route('admin.settings.general')
            ->with('success', 'General store and operations settings updated successfully!');
    }

    // 2. Homepage Builder Settings Page
    public function homepage()
    {
        $settings = StoreSetting::getSettings();
        return view('admin.settings.homepage', compact('settings'));
    }

    public function updateHomepage(Request $request)
    {
        $settings = StoreSetting::getSettings();

        $validated = $request->validate([
            'hero_title'          => 'required|string|max:255',
            'hero_subtitle'       => 'nullable|string|max:1000',
            'promo_banner_text'   => 'nullable|string|max:255',
            'about_title'         => 'nullable|string|max:255',
            'about_text'          => 'nullable|string|max:2000',
            'contact_email'       => 'nullable|email|max:255',
            'contact_phone'       => 'nullable|string|max:50',
            'contact_address'     => 'nullable|string|max:255',
            'google_maps_iframe'  => 'nullable|string',
            'logo'                => 'nullable|image|max:2048',
            'favicon'             => 'nullable|image|max:1024',
        ]);

        $validated['promo_active'] = $request->has('promo_active');

        // Handle Logo Upload
        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('branding', 'public');
        }

        // Handle Favicon Upload
        if ($request->hasFile('favicon')) {
            $validated['favicon_path'] = $request->file('favicon')->store('branding', 'public');
        }

        $settings->update($validated);

        return redirect()->route('admin.settings.homepage')
            ->with('success', 'Homepage content and branding updated successfully!');
    }

    // 3. Theme & UI Customization Settings Page
    public function theme()
    {
        $settings = StoreSetting::getSettings();
        return view('admin.settings.theme', compact('settings'));
    }

    public function updateTheme(Request $request)
    {
        $settings = StoreSetting::getSettings();

        $validated = $request->validate([
            'theme_preset'    => 'required|string|in:amber,emerald,purple,rose',
            'primary_color'   => 'required|string|max:50',
            'secondary_color' => 'required|string|max:50',
            'font_family'     => 'required|string|max:50',
            'border_radius'   => 'required|string|max:50',
        ]);

        $settings->update($validated);

        return redirect()->route('admin.settings.theme')
            ->with('success', 'Theme and UI styling updated successfully!');
    }
}