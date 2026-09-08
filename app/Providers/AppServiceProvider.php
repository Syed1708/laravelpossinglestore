<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 🚀 Auto-clear Redis cache when admin edits products, categories, or settings
        Product::saved(fn () => Cache::forget('public_menu_v1'));
        Product::deleted(fn () => Cache::forget('public_menu_v1'));
        Category::saved(fn () => Cache::forget('public_menu_v1'));
        Category::deleted(fn () => Cache::forget('public_menu_v1'));
        Table::saved(fn () => Cache::forget('active_tables_v1'));
        Table::deleted(fn () => Cache::forget('active_tables_v1'));

                // 🚀 Site Settings & Hero Slides Auto-Bust:
        StoreSetting::saved(fn () => Cache::forget('public_site_settings_v1'));
        HeroSlide::saved(fn () => Cache::forget('public_site_settings_v1'));
        HeroSlide::deleted(fn () => Cache::forget('public_site_settings_v1'));

    }
}
