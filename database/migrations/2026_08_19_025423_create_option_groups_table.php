<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');                            // e.g., "Cheese Sauce", "Choice of Meats", "Pizza Crust", "Extra Toppings"
            $table->string('selection_type')->default('single_select'); // 'single_select' or 'multi_select'
            $table->boolean('is_required')->default(false);    // Forces customer to choose before proceeding
            $table->integer('min_selections')->default(0);
            $table->integer('max_selections')->default(1);
            $table->integer('free_choice_limit')->default(0);   // Default free allowance (e.g., 1 free meat)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_groups');
    }
};