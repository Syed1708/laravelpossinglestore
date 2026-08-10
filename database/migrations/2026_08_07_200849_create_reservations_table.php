<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            
            // Relational Foreign Keys
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete(); // Linked POS Dine-In Order

            // Customer Contact Info
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->integer('guest_count')->default(2);

            // Booking Date & Time
            $table->date('reservation_date');
            $table->time('reservation_time');

            // Statuses: 'pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show'
            $table->string('status')->default('confirmed');

            // Source: 'phone', 'online', 'walk_in'
            $table->string('source')->default('phone');

            $table->text('special_notes')->nullable(); // e.g. "High chair needed", "Birthday"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};