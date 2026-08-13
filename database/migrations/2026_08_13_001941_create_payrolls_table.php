<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('hours_worked', 8, 2)->nullable(); // For hourly staff
            
            // Amounts
            $table->decimal('net_pay', 10, 2)->default(0.00);          // Amount paid to staff (€/£)
            $table->decimal('employer_charges', 10, 2)->default(0.00); // Cotisations Patronales (FR) / Employer NI (UK)
            $table->decimal('total_employer_cost', 10, 2)->default(0.00); // net_pay + employer_charges (True P&L Expense)
            
            // Statuses: 'pending', 'paid', 'cancelled'
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete(); // Auto-generated Expense
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};