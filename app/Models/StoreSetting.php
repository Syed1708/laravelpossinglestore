<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'country',
        'currency',
        'default_payroll_frequency',
        'is_store_open',
        'online_orders_enabled',
        'reservations_enabled',
        'shift1_start',
        'shift1_end',
        'shift2_start',
        'shift2_end',
        'closed_message',
        'logo_path',
        'favicon_path',
        'hero_title',
        'hero_subtitle',
        'promo_banner_text',
        'promo_active',
        'show_how_it_works',
        'show_featured',
        'show_why_choose_us',
        'show_newsletter',
        'show_faq',
        'show_contact',
        'how_it_works_title',
        'how_it_works_subtitle',
        'how_it_works_steps',
        'why_choose_us_title',
        'why_choose_us_subtitle',
        'why_choose_us_items',
        'faq_title',
        'faq_subtitle',
        'faq_items',
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
        'show_how_it_works'     => 'boolean',
        'show_featured'         => 'boolean',
        'show_why_choose_us'    => 'boolean',
        'show_newsletter'       => 'boolean',
        'show_faq'              => 'boolean',
        'show_contact'          => 'boolean',
        'how_it_works_steps'    => 'array',
        'why_choose_us_items'   => 'array',
        'faq_items'             => 'array',
        'reviews'               => 'array',
    ];

    /**
     * 🚀 Singleton Helper: Always returns row #1
     */
    /**
     * 🚀 Guaranteed Singleton: Returns row #1 or creates it with safe default values
     */
    public static function getSettings(): self
    {
        return static::find(1) ?? static::first() ?? static::create([
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
            'promo_banner_text'         => '🔥 10% OFF on all Click & Collect orders tonight!',
            'promo_active'              => true,
            'primary_color'             => '#f59e0b',
            'secondary_color'           => '#10b981',
            'font_family'               => 'sans-serif',
            'show_how_it_works'         => true,
            'show_featured'             => true,
            'show_why_choose_us'        => true,
            'show_newsletter'           => true,
            'show_faq'                  => true,
            'show_contact'              => true,
        ]);
    }
}