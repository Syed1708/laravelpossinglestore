<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    

    protected $fillable = ['product_id', 'ingredient_id', 'quantity'];

    protected $casts = [
        'quantity' => 'decimal:2',
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
}