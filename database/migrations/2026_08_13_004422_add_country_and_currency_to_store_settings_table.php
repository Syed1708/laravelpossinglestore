<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('country')->default('FR')->after('id');                     // 'FR' or 'UK'
            $table->string('currency')->default('EUR')->after('country');              // 'EUR' or 'GBP'
            $table->string('default_payroll_frequency')->default('monthly')->after('currency'); // 'monthly' or 'weekly'
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['country', 'currency', 'default_payroll_frequency']);
        });
    }
};