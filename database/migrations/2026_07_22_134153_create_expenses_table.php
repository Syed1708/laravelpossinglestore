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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            
            // Categories: food_cost, rent, electricity, water, salaries, marketing, other
            $table->string('category'); 
            
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('bank_transfer'); // cash, card, bank_transfer
            
            // Photo upload for bills/receipts
            $table->string('receipt_photo_path')->nullable(); 

            // Automated Link to Purchase Orders for Food Cost audits
            $table->foreignId('purchase_order_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('set null');

            $table->timestamp('due_date')->nullable();
            $table->timestamp('paid_at')->nullable(); // Nullable means "unpaid/pending"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
