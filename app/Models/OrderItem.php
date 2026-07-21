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
    ];
    protected static function booted(): void
    {
        // 🚀 THE AUTOMATIC STOCK DEDUCTION ENGINE:
        // Whenever a synced ticket saves an item, automatically subtract ingredients from stock!
        static::created(function ($orderItem) {
            if ($orderItem->product_id) {
                // Find all recipe ingredients mapped to this product
                $recipes = \App\Models\Recipe::where('product_id', $orderItem->product_id)->get();

                foreach ($recipes as $recipe) {
                    // Deduct stock: quantity_sold * recipe_quantity
                    \App\Models\Ingredient::where('id', $recipe->ingredient_id)
                        ->decrement('stock_level', $orderItem->quantity * $recipe->quantity);
                }
            }
        });
    }
    protected $casts = [
        'unit_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
