<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'supplier_id', 
        'po_number', 
        'invoice_number', 
        'invoice_photo_path',
        'notes',
        'status', 
        'total_cost', 
        'received_at'
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'total_cost' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}