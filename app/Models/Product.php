<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasCrud;

    protected $fillable = [
        'category_id',
        'name',
        'description', // 🚀 ADD THIS
        'image_path',  // 🚀 ADD THIS
        'price',
        'vat_rate',
        'is_active'
    ];


    // 🚀 THE FIX: Automatically cast decimal strings to native float numbers in JSON!
    protected $casts = [
        'price' => 'float',     // Converts "8.50" to 8.50
        'vat_rate' => 'float',  // Converts "10.00" to 10.0
        'is_active' => 'boolean',
    ];
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
