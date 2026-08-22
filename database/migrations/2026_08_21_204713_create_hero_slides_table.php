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
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title');                          // e.g. "The Double Smash Truffle"
            $table->string('subtitle')->nullable();            // e.g. "Aged French Cheddar & Truffle Mayo"
            $table->string('price')->nullable();               // e.g. "€14.90"
            $table->string('badge')->nullable();               // e.g. "Chef Special", "Best Seller"
            $table->string('image_path');                     // Uploaded slide image
            $table->string('cta_text')->default('Order Now');
            $table->string('cta_link')->default('/order');
            $table->integer('sort_order')->default(1);         // Slide sequence order (1, 2, 3...)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 🚀 Insert default slides
        DB::table('hero_slides')->insert([
            [
                'title'       => 'The Double Smash Truffle',
                'subtitle'    => 'Aged French Cheddar, Black Truffle Mayo & Crispy Shallots',
                'price'       => '€14.90',
                'badge'       => 'Chef Special',
                'image_path'  => 'slides/slide1.jpg',
                'cta_text'    => 'Order Now',
                'cta_link'    => '/order',
                'sort_order'  => 1,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Bordeaux Bacon Supreme',
                'subtitle'    => '100% Beef, Smoked Pork Belly, House BBQ Sauce & Brioche',
                'price'       => '€13.50',
                'badge'       => 'Best Seller',
                'image_path'  => 'slides/slide2.jpg',
                'cta_text'    => 'Order Now',
                'cta_link'    => '/order',
                'sort_order'  => 2,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};