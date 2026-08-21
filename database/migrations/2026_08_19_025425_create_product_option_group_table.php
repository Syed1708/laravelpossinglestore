<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('option_group_id')->constrained('option_groups')->cascadeOnDelete();
            
            // 🚀 CONTROLS STEP SEQUENCE: Step 1, Step 2, Step 3, Step 4...
            $table->integer('step_order')->default(1);
            
            // 🚀 PER-SIZE FREE ALLOWANCE OVERRIDE:
            // e.g. Same "Meats" group, but Tacos M gets 1 free meat, Tacos L gets 2, Tacos XL gets 3!
            $table->integer('free_choice_limit_override')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_group');
    }
};