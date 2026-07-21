<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasinHayder\TyroDashboard\Concerns\HasCrud; // Import HasCrud

class Supplier extends Model
{
    use HasCrud; // Enable Tyro's Automatic Admin panel

    protected $fillable = ['name', 'address', 'contact_name', 'email', 'phone'];

    /**
     * Get all ingredients provided primarily by this supplier.
     */
    public function ingredients()
    {
        return $this->hasMany(Ingredient::class, 'primary_supplier_id');
    }
}