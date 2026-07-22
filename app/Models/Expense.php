<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasinHayder\TyroDashboard\Concerns\HasCrud; // Import HasCrud

class Expense extends Model
{
    use HasCrud; // Enable Tyro's Automatic Admin panel

    protected $fillable = [
        'category', 'description', 'amount', 'payment_method', 
        'receipt_photo_path', 'purchase_order_id', 'due_date', 'paid_at'
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}