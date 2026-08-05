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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'estimated_prep_time')) {
                $table->integer('estimated_prep_time')->nullable()->after('preparation_status'); // e.g. 15, 30, 45 minutes
            }
            if (!Schema::hasColumn('orders', 'estimated_ready_at')) {
                $table->timestamp('estimated_ready_at')->nullable()->after('estimated_prep_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['estimated_prep_time', 'estimated_ready_at']);
        });
    }
};
