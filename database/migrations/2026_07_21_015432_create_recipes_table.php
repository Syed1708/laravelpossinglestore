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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            
            // Relational foreign keys
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('cascade');
            
            // Quantity of ingredient consumed per product sale (e.g. 1.00 Bun, or 150.00 grams)
            $table->decimal('quantity', 10, 2); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
