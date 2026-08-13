<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'ingredient_id', 'quantity'];

    protected $casts = [
        'quantity' => 'float',
    ];

    /**
     * Get the finished product this recipe belongs to.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the raw ingredient required for this recipe.
     */
    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * 🚀 Calculate theoretical cost for this ingredient line item
     */
    public function getLineCostAttribute(): float
    {
        if (!$this->ingredient) {
            return 0.0;
        }

        // Uses ingredient cost per base unit (g, ml, unit)
        $costPerUnit = (float) ($this->ingredient->cost_per_unit ?? 0.0);
        return round($this->quantity * $costPerUnit, 4);
    }
}