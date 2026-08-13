<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Optional link to POS User
            
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('job_title'); // 'Head Chef', 'Line Cook', 'Cashier', 'Waiter', 'Manager'
            
            // Contract & Hours (France 35h/39h/42h CDI vs UK Contract)
            $table->string('contract_type')->default('cdi'); // 'cdi', 'cdd', 'extra', 'apprenti', 'full_time', 'zero_hours'
            $table->decimal('contract_hours', 8, 2)->default(35.00); // 🚀 35.00, 39.00, 42.00 hours/week!
            
            $table->string('pay_type')->default('monthly_salary'); // 'monthly_salary', 'hourly'
            $table->decimal('base_rate', 10, 2)->default(0.00);   // Base salary or hourly wage
            
            $table->string('iban_bank_details')->nullable();
            $table->date('hire_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};