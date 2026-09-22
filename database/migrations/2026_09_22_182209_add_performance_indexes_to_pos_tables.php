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
        // 1. Orders table performance indexes
        Schema::table('orders', function (Blueprint $table) {
            // Speed up KDS chef and packer queries
            $table->index('preparation_status', 'idx_orders_prep_status');

            // Speed up Z-Report closures (unclosed orders lookup)
            $table->index('daily_closure_id', 'idx_orders_daily_closure_id');

            // Speed up Stripe idempotency and verification
            $table->index('payment_intent_id', 'idx_orders_payment_intent_id');

            // Speed up ticket audit and sequence lookups
            $table->index('sequence_number', 'idx_orders_sequence_number');

            // Composite index for Dashboard, Online dispatcher, and P&L date filtering
            $table->index(['created_at', 'status'], 'idx_orders_created_status');
        });

        // 2. Reservations table performance indexes
        Schema::table('reservations', function (Blueprint $table) {
            // Composite index for rapid 90-minute conflict window checking
            $table->index(
                ['reservation_date', 'reservation_time', 'status'],
                'idx_reservations_conflict_window'
            );

            // Table assignment and hostess floor-plan lookup
            $table->index(['reservation_date', 'table_id'], 'idx_reservations_date_table');
        });

        // 3. Order Items table performance indexes
        Schema::table('order_items', function (Blueprint $table) {
            // Speed up recipe COGS, product sales analysis, and menu engineering
            $table->index(['order_id', 'product_id'], 'idx_order_items_order_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_prep_status');
            $table->dropIndex('idx_orders_daily_closure_id');
            $table->dropIndex('idx_orders_payment_intent_id');
            $table->dropIndex('idx_orders_sequence_number');
            $table->dropIndex('idx_orders_created_status');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('idx_reservations_conflict_window');
            $table->dropIndex('idx_reservations_date_table');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_order_product');
        });
    }
};