<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasCrud; // Enable Tyro's Automatic Admin panel

    protected $fillable = [
        'expense_category_id', // 🚀 Foreign key linking to ExpenseCategory
        'category',            // Legacy string category fallback
        'description',
        'amount',
        'payment_method',
        'receipt_photo_path',
        'purchase_order_id',
        'reference_type',      // 🚀 Auto-link source: 'purchase_order', 'payroll', 'waste'
        'reference_id',        // 🚀 ID of linked purchase order, payroll, or waste record
        'due_date',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'paid_at'  => 'datetime',
        'amount'   => 'float',
    ];

    /**
     * Relationship to Dynamic Expense Category
     */
    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * Relationship to Purchase Order
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Relationship to Linked Payroll Entry
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'reference_id');
    }

    /**
     * Relationship to Linked Food Waste Record
     */
    public function waste(): BelongsTo
    {
        return $this->belongsTo(Waste::class, 'reference_id');
    }
}