<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasCrud;

    protected $fillable = ['category_id', 'name', 'price', 'vat_rate', 'is_active'];

        // Ensure floats don't lose precision
    protected $casts = [
        'price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    
}