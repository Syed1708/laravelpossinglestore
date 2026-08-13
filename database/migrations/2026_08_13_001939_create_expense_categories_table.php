<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');              // e.g. "Food Cost", "Salaries & Personnel", "Waste Loss"
            $table->string('code')->unique();     // e.g. "food_cost", "salaries", "waste_loss", "rent"
            $table->boolean('is_system')->default(false); // Protects auto-system categories from deletion
            $table->timestamps();
        });

        // Insert default system categories
        DB::table('expense_categories')->insert([
            ['name' => 'Food Cost (Auto)', 'code' => 'food_cost', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Salaries & Personnel', 'code' => 'salaries', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Food Waste Loss (Auto)', 'code' => 'waste_loss', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rent & Lease', 'code' => 'rent', 'is_system' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Electricity & Gas', 'code' => 'electricity', 'is_system' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Water Public Service', 'code' => 'water', 'is_system' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Marketing & Ads', 'code' => 'marketing', 'is_system' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Other Miscellaneous', 'code' => 'other', 'is_system' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};