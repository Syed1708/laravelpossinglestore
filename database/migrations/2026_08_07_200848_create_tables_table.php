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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('table_number')->unique(); // e.g. "T-01", "T-02", "TERRACE-1", "VIP-1"
            $table->integer('capacity')->default(4);   // Number of seats
            $table->string('zone')->default('indoor'); // 'indoor', 'terrace', 'bar', 'vip'
            $table->boolean('is_active')->default(true); // Active for reservations
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};