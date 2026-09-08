<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('counters', function (Blueprint $table): void {
            $table->string('key', 50)->primary();
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();
        });

        // 🚀 SEED CONTINUITY: Migrate existing max sequence_number so the chain never resets
        $currentMax = (int) (DB::table('orders')->max('sequence_number') ?? 0);

        DB::table('counters')->insert([
            'key'        => 'order_sequence',
            'value'      => $currentMax,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};