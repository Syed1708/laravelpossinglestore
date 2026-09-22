<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'vat_rate',
        'subtotal',
        'item_status',
        'notes', // 🚀 Added
    ];

    /**
     * 🚀 Laravel 12 Standard Casts
     */
    protected function casts(): array
    {
        return [
            'quantity'   => 'integer',
            'unit_price' => 'decimal:2',
            'vat_rate'   => 'decimal:2',
            'subtotal'   => 'decimal:2',
            'notes'      => 'array',
        ];
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
