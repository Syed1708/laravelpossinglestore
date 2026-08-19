<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add loyalty_points to clients table
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'loyalty_points')) {
                $table->integer('loyalty_points')->default(0)->after('email');
            }
        });

        // 2. Add discount_amount and coupon_code to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code')->nullable()->after('client_id');
            }
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0.00)->after('vat_amount');
            }
            if (!Schema::hasColumn('orders', 'points_redeemed')) {
                $table->integer('points_redeemed')->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('orders', 'points_earned')) {
                $table->integer('points_earned')->default(0)->after('points_redeemed');
            }
        });

        // 3. Create Coupons Table
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. "WELCOME10", "BURGER5"
            $table->string('type')->default('percent'); // 'percent', 'fixed'
            $table->decimal('value', 10, 2); // 10.00 for 10% or €10
            $table->decimal('min_order_amount', 10, 2)->default(0.00); // e.g. Valid on orders > €20
            $table->integer('max_uses')->nullable(); // Total times coupon can be used
            $table->integer('uses_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Create Loyalty Transactions Ledger
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('type'); // 'earned', 'redeemed', 'bonus', 'adjusted'
            $table->integer('points'); // e.g. +15 or -100
            $table->string('description');
            $table->timestamps();
        });

        // Insert initial welcome coupons
        DB::table('coupons')->insert([
            [
                'code'             => 'WELCOME10',
                'type'             => 'percent',
                'value'            => 10.00, // 10% OFF
                'min_order_amount' => 15.00,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'code'             => 'BURGER5',
                'type'             => 'fixed',
                'value'            => 5.00, // €5 OFF
                'min_order_amount' => 25.00,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('coupons');
    }
};