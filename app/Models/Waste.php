<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Waste extends Model
{
    protected $fillable = [
        'ingredient_id',
        'product_id',
        'quantity_wasted',
        'unit',
        'cost_per_unit',
        'total_loss_amount',
        'reason',
        'logged_by_user_id',
        'expense_id',
        'notes',
    ];

    protected $casts = [
        'quantity_wasted'   => 'float',
        'cost_per_unit'     => 'float',
        'total_loss_amount' => 'float',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}