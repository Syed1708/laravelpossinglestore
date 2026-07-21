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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the ingredient (e.g. Buns, Cheddar)

            // Decimals support partial/fractional amounts (e.g., 250.50 grams)
            $table->decimal('stock_level', 10, 2)->default(0.00); // Current stock in house
            $table->decimal('alert_level', 10, 2)->default(10.00); // Minimum stock before warning
            $table->string('unit')->default('unit'); // unit, g, kg, cl, l
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
