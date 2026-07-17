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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->decimal('price', 8, 2); // Exclude VAT or Include VAT depending on your preference. Typically Include VAT (TTC) for B2C fast food.
            $table->decimal('vat_rate', 4, 2); // e.g., 5.50, 10.00, 20.00
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
