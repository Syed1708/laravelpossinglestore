<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_group_id')->constrained('option_groups')->cascadeOnDelete();
            $table->string('name');                           // e.g., "Bœuf", "Poulet", "Cordon Bleu", "Truffle Mayo"
            $table->decimal('extra_price', 10, 2)->default(0.00); // Extra cost beyond free allowance (e.g., +€1.50)
            $table->string('image_path')->nullable();         // Tile image displayed on touch kiosk
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};