<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasinHayder\TyroDashboard\Concerns\HasCrud; // 🚀 Import Tyro's CRUD Trait

class Ingredient extends Model
{
    use HasCrud; // 🚀 Enable Tyro's Automatic Admin panel

    protected $fillable = ['name', 'primary_supplier_id', 'stock_level', 'alert_level', 'unit'];

    protected $casts = [
        'stock_level' => 'decimal:2',
        'alert_level' => 'decimal:2',
    ];
    /**
     * 🚀 RELATIONSHIP: Get the primary supplier of this ingredient.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'primary_supplier_id');
    }
}
