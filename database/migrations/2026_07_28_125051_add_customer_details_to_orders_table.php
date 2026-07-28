<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 1. Order Type (dine_in, takeaway, click_and_collect)
            if (!Schema::hasColumn('orders', 'order_type')) {
                $table->string('order_type')->default('dine_in')->nullable()->after('user_id');
            }

            // 2. Client ID (For web customers)
            if (!Schema::hasColumn('orders', 'client_id')) {
                $table->foreignId('client_id')->nullable()->after('order_type')->constrained('clients')->nullOnDelete();
            }

            // 3. Customer Name (For kitchen callouts)
            if (!Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('client_id');
            }

            // 4. Customer Phone (For loyalty / SMS)
            if (!Schema::hasColumn('orders', 'customer_phone')) {
                $table->string('customer_phone')->nullable()->after('customer_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['order_type', 'client_id', 'customer_name', 'customer_phone']);
        });
    }
};