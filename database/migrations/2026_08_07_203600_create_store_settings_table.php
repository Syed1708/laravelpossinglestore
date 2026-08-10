<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            
            // Master Controls
            $table->boolean('is_store_open')->default(true);          // Master Manual Override
            $table->boolean('online_orders_enabled')->default(true);  // Click & Collect Toggle
            $table->boolean('reservations_enabled')->default(true);   // Table Bookings Toggle
            
            // Operating Shifts (Dynamic Times)
            $table->time('shift1_start')->default('10:00');
            $table->time('shift1_end')->default('14:30');
            $table->time('shift2_start')->default('18:30');
            $table->time('shift2_end')->default('22:30');

            // Custom Notification
            $table->string('closed_message')->default('The restaurant is currently closed.');
            
            $table->timestamps();
        });

        // Insert initial default setting record
        DB::table('store_settings')->insert([
            'is_store_open'         => true,
            'online_orders_enabled' => true,
            'reservations_enabled'  => true,
            'shift1_start'          => '10:00:00',
            'shift1_end'            => '14:30:00',
            'shift2_start'          => '18:30:00',
            'shift2_end'            => '22:30:00',
            'closed_message'        => 'Restaurant is currently closed for online orders.',
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};