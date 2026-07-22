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
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Adds a nullable text column for manager notes/comments
            $table->text('notes')->nullable()->after('invoice_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
