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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('po_number')->unique(); // Sequential PO number
            $table->string('invoice_number')->nullable(); // Supplier Invoice/Facture Number
            $table->string('invoice_photo_path')->nullable(); // Photo of the paper bill

            // Statuses: pending (ordered), received (delivered), cancelled
            $table->string('status')->default('pending');

            $table->decimal('total_cost', 12, 2)->default(0.00);
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
