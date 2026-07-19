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
        Schema::create('daily_closures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('z_number')->unique(); // Sequential daily counter (Z #1, Z #2...)
            
            // Daily Consolidated Financials
            $table->decimal('total_ttc', 12, 2);
            $table->decimal('total_ht', 12, 2);
            $table->decimal('total_tva', 12, 2);
            
            // JSON payloads containing exact tax & payment breakdowns
            $table->json('vat_breakdown'); 
            $table->json('payments_breakdown');

            // Cryptographic daily chaining
            $table->string('hash', 64)->nullable();
            $table->string('previous_hash', 64)->nullable();

            $table->timestamp('closed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_closures');
    }
};
