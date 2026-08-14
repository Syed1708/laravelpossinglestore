<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'country',
        'currency',
        'default_payroll_frequency',
        'logo_path',
        'favicon_path',
        'is_store_open',
        'online_orders_enabled',
        'reservations_enabled',
        'shift1_start',
        'shift1_end',
        'shift2_start',
        'shift2_end',
        'closed_message',
        'hero_title',
        'hero_subtitle',
        'promo_banner_text',
        'promo_active',
        'about_title',
        'about_text',
        'contact_email',
        'contact_phone',
        'contact_address',
        'google_maps_iframe',
        'reviews',
        'theme_preset',
        'primary_color',
        'secondary_color',
        'font_family',
        'border_radius',
    ];

    protected $casts = [
        'is_store_open'         => 'boolean',
        'online_orders_enabled' => 'boolean',
        'reservations_enabled'  => 'boolean',
        'promo_active'          => 'boolean',
        'reviews'               => 'array', // Automatically decodes JSON reviews
    ];

    /**
     * 🚀 SINGLETON SETTING: Always fetches row ID #1 (edited in Tyro Dashboard)
     */
    public static function getSettings(): self
    {
        $setting = static::find(1) ?? static::first();

        if (!$setting) {
            $setting = static::create([
                'id'                        => 1,
                'country'                   => 'FR',
                'currency'                  => 'EUR',
                'default_payroll_frequency' => 'monthly',
                'is_store_open'             => true,
                'online_orders_enabled'     => true,
                'reservations_enabled'      => true,
                'shift1_start'              => '10:00',
                'shift1_end'                => '14:30',
                'shift2_start'              => '18:30',
                'shift2_end'                => '22:30',
                'closed_message'            => 'Restaurant is currently closed for online ordering.',
                'hero_title'                => 'Burger Palace Bordeaux',
                'hero_subtitle'             => 'Executive Gourmet Burgers prepared fresh with local ingredients.',
                'about_title'               => 'Gourmet Passion in Bordeaux',
                'about_text'                => 'Founded in 2026, Burger Palace brings gourmet artisanal burgers to the heart of Bordeaux.',
                'contact_email'             => 'contact@burgerpalace.fr',
                'contact_phone'             => '+33 5 56 00 00 00',
                'contact_address'           => '12 Rue Sainte-Catherine, 33000 Bordeaux',
                'theme_preset'              => 'amber',
                'primary_color'             => '#f59e0b',
                'secondary_color'           => '#10b981',
                'font_family'               => 'sans-serif',
                'border_radius'             => 'rounded-2xl',
            ]);
        }

        return $setting;
    }
}