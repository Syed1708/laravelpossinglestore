<?php

namespace App\Http\Controllers\Api\v1\client;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Models\HeroSlide;
use App\Helpers\StoreHoursHelper;

class SiteSettingsApiController extends Controller
{
    /**
     * ⚡ Fast Real-Time Store Status Check (For Cart & Reservation Protection)
     */
    public function storeStatus()
    {
        $settings = StoreSetting::getSettings();

        return response()->json([
            'is_open'               => StoreHoursHelper::isOpen(),              // Open right now at this minute
            'is_store_open'         => (bool) $settings->is_store_open,         // Master Admin Toggle
            'online_orders_enabled' => StoreHoursHelper::canAcceptOnlineOrders(),
            'reservations_enabled'  => StoreHoursHelper::canAcceptReservations(), // Master ON + Reservations ON
            'schedule'              => StoreHoursHelper::getScheduleText(),
            'closed_message'        => StoreHoursHelper::getClosedMessage(),
        ]);
    }

    /**
     * 🎨 Full Branding, Hero Sliders, Section Toggles, & Theme Settings
     */
    public function siteSettings()
    {
        $settings = StoreSetting::getSettings();

        // 🚀 Fetch all active Hero Slides ordered by sort_order
        $slides = HeroSlide::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($slide) {
                return [
                    'id'        => $slide->id,
                    'title'     => $slide->title,
                    'subtitle'  => $slide->subtitle,
                    'price'     => $slide->price,
                    'badge'     => $slide->badge,
                    'image_url' => asset('storage/' . $slide->image_path),
                    'cta_text'  => $slide->cta_text,
                    'cta_link'  => $slide->cta_link,
                ];
            });

        return response()->json([
            ...$settings->toArray(),
            'logo_url'    => $settings->logo_path ? asset('storage/' . $settings->logo_path) : null,
            'favicon_url' => $settings->favicon_path ? asset('storage/' . $settings->favicon_path) : null,
            'hero_slides' => $slides, // 🚀 Dynamic 1 to N slides array!
        ]);
    }
}