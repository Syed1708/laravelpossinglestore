<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
    {
        // 1. Add overall preparation status to the orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'preparation_status')) {
                $table->string('preparation_status')->default('pending')->after('status'); // pending, preparing, ready, delivered
            }
        });

        // 2. Add individual item checkbox status to the order_items table
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'item_status')) {
                $table->string('item_status')->default('pending')->after('subtotal'); // pending, done
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('preparation_status');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('item_status');
        });
    }
};
