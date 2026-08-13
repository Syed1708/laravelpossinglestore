<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('expense_category_id')->nullable()->after('id')->constrained('expense_categories')->nullOnDelete();
            $table->string('reference_type')->nullable()->after('payment_method'); // 'supplier_purchase', 'payroll', 'waste'
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type'); // ID of invoice/payroll
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->dropColumn(['expense_category_id', 'reference_type', 'reference_id']);
        });
    }
};