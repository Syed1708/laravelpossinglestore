<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();

            // 1. Regional & Payroll Defaults
            $table->string('country')->default('FR');                     // 'FR' or 'UK'
            $table->string('currency')->default('EUR');                    // 'EUR' or 'GBP'
            $table->string('default_payroll_frequency')->default('monthly'); // 'monthly' or 'weekly'

            // 2. Master Controls & Toggles
            $table->boolean('is_store_open')->default(true);          // Master Manual Override
            $table->boolean('online_orders_enabled')->default(true);  // Click & Collect Toggle
            $table->boolean('reservations_enabled')->default(true);   // Table Bookings Toggle

            // 🚀 3. Operating Shift Hours (Format: HH:MM - No Seconds)
            $table->time('shift1_start')->default('10:00');
            $table->time('shift1_end')->default('14:30');
            $table->time('shift2_start')->default('18:30');
            $table->time('shift2_end')->default('22:30');
            $table->string('closed_message')->default('Restaurant is currently closed for online orders.');

             // 4. Branding Logos
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();

            // 5. Homepage Content & Announcement Banner
            $table->string('hero_title')->default('Burger Palace Bordeaux');
            $table->text('hero_subtitle')->nullable();
            $table->string('promo_banner_text')->nullable();
            $table->boolean('promo_active')->default(false);

            // 6. Section Visibility Toggles (ON / OFF)
            $table->boolean('show_how_it_works')->default(true);
            $table->boolean('show_featured')->default(true);
            $table->boolean('show_why_choose_us')->default(true);
            $table->boolean('show_newsletter')->default(true);
            $table->boolean('show_faq')->default(true);
            $table->boolean('show_contact')->default(true);

            // 7. Dynamic Section JSON Content
            $table->string('how_it_works_title')->default('3 Easy Steps to Order');
            $table->string('how_it_works_subtitle')->default('Fast, simple, and delicious gourmet dining.');
            $table->json('how_it_works_steps')->nullable();

            $table->string('why_choose_us_title')->default('Why Burger Palace?');
            $table->string('why_choose_us_subtitle')->default('Uncompromising quality in every single bite.');
            $table->json('why_choose_us_items')->nullable();

            $table->string('faq_title')->default('Frequently Asked Questions');
            $table->string('faq_subtitle')->default('Got questions? We have answers.');
            $table->json('faq_items')->nullable();

            // 8. About & Contact Information
            $table->string('about_title')->default('About Burger Palace');
            $table->text('about_text')->nullable();
            $table->string('contact_email')->default('contact@burgerpalace.fr');
            $table->string('contact_phone')->default('+33 5 56 00 00 00');
            $table->string('contact_address')->default('12 Rue Sainte-Catherine, 33000 Bordeaux');
            $table->text('google_maps_iframe')->nullable();
            $table->json('reviews')->nullable();

            // 9. Theme & UI Customization
            $table->string('theme_preset')->default('amber');
            $table->string('primary_color')->default('#f59e0b');
            $table->string('secondary_color')->default('#10b981');
            $table->string('font_family')->default('sans-serif');
            $table->string('border_radius')->default('rounded-2xl');

            $table->timestamps();
        });

        // 🚀 Insert default settings row (#1)
        DB::table('store_settings')->insert([
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
            'promo_banner_text'         => '🔥 Get 10% OFF on all Click & Collect orders tonight!',
            'promo_active'              => true,
            'show_how_it_works'         => true,
            'show_featured'             => true,
            'show_why_choose_us'        => true,
            'show_newsletter'           => true,
            'show_faq'                  => true,
            'show_contact'              => true,
            'how_it_works_title'        => '3 Easy Steps to Order',
            'how_it_works_subtitle'     => 'Fast, simple, and delicious gourmet dining.',
            'how_it_works_steps'        => json_encode([
                ['step' => 1, 'title' => 'Explore & Customize', 'description' => 'Browse our menu, select sizes, sauces, and extra toppings.'],
                ['step' => 2, 'title' => 'Fast Checkout', 'description' => 'Pay securely via Stripe or choose Pay at Counter.'],
                ['step' => 3, 'title' => 'Express Pickup', 'description' => 'Collect your hot meal at the counter or sit at your table.']
            ]),
            'why_choose_us_title'       => 'Why Burger Palace?',
            'why_choose_us_subtitle'    => 'Uncompromising quality in every single bite.',
            'why_choose_us_items'       => json_encode([
                ['icon' => '🥩', 'title' => '100% Fresh Local Beef', 'description' => 'Locally sourced French beef ground daily in our kitchen.'],
                ['icon' => '🍞', 'title' => 'Artisanal Brioche Buns', 'description' => 'Baked fresh every morning by local Bordeaux bakeries.'],
                ['icon' => '⚡', 'title' => 'Express Preparation', 'description' => 'Real-time kitchen display tracking ensures hot, fresh food.']
            ]),
            'faq_title'                 => 'Frequently Asked Questions',
            'faq_subtitle'              => 'Got questions? We have answers.',
            'faq_items'                 => json_encode([
                ['question' => 'What are your Click & Collect pickup hours?', 'answer' => 'Click & Collect is available during shift hours: 10:00 - 14:30 and 18:30 - 22:30.'],
                ['question' => 'Are your meats Halal certified?', 'answer' => 'Yes! All our meat options are 100% Halal certified.'],
                ['question' => 'How do table reservations work?', 'answer' => 'You can reserve a table online 24/7. Once confirmed, your table will be held for 15 minutes.']
            ]),
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
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};