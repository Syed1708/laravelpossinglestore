<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wastes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->nullable()->constrained('ingredients')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            
            $table->decimal('quantity_wasted', 10, 2);
            $table->string('unit')->default('kg'); // 'g', 'kg', 'l', 'cl', 'unit'
            $table->decimal('cost_per_unit', 10, 2)->default(0.00);
            $table->decimal('total_loss_amount', 10, 2)->default(0.00); // Financial Loss (€)
            
            // Reasons: 'spoiled_expired', 'kitchen_error', 'damaged', 'customer_return'
            $table->string('reason')->default('spoiled_expired');
            $table->foreignId('logged_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete(); // Auto-generated Expense
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wastes');
    }
};